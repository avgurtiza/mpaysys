<?php

class Manager_EmployeeController extends Zend_Controller_Action
{

    protected $_user_auth;

    public function init()
    {
        /* Initialize action controller here */
        $storage = new Zend_Auth_Storage_Session();
        $data = $storage->read();

        if (!$data) {
            $this->_redirect('auth/login');
        }

        $this->_user_auth = $data;

        $this->view->user_auth = $this->_user_auth;
        /* Initialize action controller here */
    }

    public function indexAction()
    {
        // action body
    }

    public function editAction()
    {
        // action body
        $group_id = (int)$this->_request->getParam('group_id');
        $client_id = (int)$this->_request->getParam('client_id');
        $GroupMap = new Messerve_Model_Mapper_Group();

        $groups = $GroupMap->fetchList("1", 'name ASC');

        $Client = new Messerve_Model_Mapper_Client();

        $clients = array();

        foreach ($Client->fetchList('1', 'name ASC') as $cvalue) {
            $clients[$cvalue->getId()] = $cvalue->getName();
        }

        $groups_array = array();

        foreach ($groups as $gvalue) {
            if (!isset($clients[$gvalue->getClientId()])) $clients[$gvalue->getClientId()] = '';
            $groups_array[$gvalue->getId()] = $clients[$gvalue->getClientId()] . ': ' . $gvalue->getName();
        }

        asort($groups_array);


        $BOPMap = new Messerve_Model_Mapper_Bop();

        foreach ($BOPMap->fetchList('1') as $bopvalue) {
            $bop_array[$bopvalue->getId()] = $bopvalue->getName();
        }


        if($this->_user_auth->type == 'bop') {
            $form = new Messerve_Form_EditRiderBOP();
        } else {
            $form = new Messerve_Form_EditEmployee();

            $group_select = $form->getElement('group_id');

            $groups_array = array('0' => 'Unemployed') + $groups_array;

            $this->view->groups = $groups_array;

            $group_select->setMultiOptions($groups_array);
        }



        $bop_select = $form->getElement('bop_id');

        $bop_array = array('0' => '') + $bop_array;

        $this->view->bops = $bop_array;

        $bop_select->setMultiOptions($bop_array);

        $Employee = new Messerve_Model_Employee();

        if ($this->_request->isPost()) { // Save submit
            $postvars = $this->_request->getPost();


            if(!$postvars['bop_currentbalance'] || !($postvars['bop_currentbalance'] > 0)) {
                $postvars['bop_currentbalance'] = 0;
            }


            if ($form->isValid($postvars)) {

                if (!($form->getValue('id') > 0)) {
                    $form->removeElement('id');
                }

                $Db = $Employee->getMapper()->getDbTable();

                $data = $form->getValues();

                $now = \Carbon\Carbon::now()->toDateTimeString();

                $data['updated_at'] = $now;

                if ($form->getValue('id') == '') { // New record
                    $data['created_at'] = $now;
                    $data['employee_number'] = 9000000 + rand(1111, 9999999); // TODO:  search for last id and add a number
                    $new_id = $Db->insert($data);

                    $Employee->find($new_id);
                    $Employee->setEmployeeNumber(1000000 + $new_id)->save();
                } else {
                    $Employee->setOptions($data)->save();
                }

                $this->_redirect('/manager/client/edit/id/' . $client_id);

            }
        }

        $employee_id = (int)$this->_request->getParam('id');

        $bop_data = array();

        if ($employee_id > 0) {
            $Employee->find($employee_id);

            if ($Employee->getId() > 0) {
                $bop_data = $this->_get_bop_data($employee_id);

                $bop_sum = 0;
                foreach ($bop_data as $bvalue) {
                    if ($bvalue["bop_id"] == $Employee->getBopId()) {
                        $bop_sum += $bvalue["motorcycle_deduction"];
                    }

                }
                $form->populate($Employee->toArray());
                $form->populate(array("bop_currentbalance" => number_format($Employee->getBopStartingbalance() - $bop_sum, 2, '.', '')));
                $this->view->employee = $Employee;
            }
        } elseif ($group_id > 0) {
            $form->populate(array('group_id' => $group_id));
        }

        $this->view->form = $form;
        $this->view->bop_data = $bop_data;


        // $client_id = $this->_request->getParam('client_id');

        // $Client = new Messerve_Model_Client();
        // $Client->find($client_id);

        $this->view->client = (new Messerve_Model_Client())->find($client_id);

    }

    protected function _getRates()
    {
        $RateMap = new Messerve_Model_Mapper_Rate();

        $rate_options = $RateMap->fetchList('1', 'name ASC');

        $rate_array = array(// 	'0'=>'N/A'
        );

        foreach ($rate_options as $rovalue) {
            $rate_array[$rovalue->getId()] = $rovalue->getName();
        }

        return $rate_array;
    }

    protected function _get_bop_data($employee_id /*, $bop_id */)
    {

        $db = new Messerve_Model_Mapper_BopAttendance();
        $select = $db->getSelect();
        $select->setIntegrityCheck(false);
        // $select->columns(array("motodeduct"=>"SUM(motorcycle_deduction)","*"));


        $select->columns(array("date" => "attendance.datetime_start", "bop_attendance.*"));
        $select->joinInner("attendance", "attendance.id = bop_attendance.attendance_id", array());
        $select->where("attendance.employee_id = $employee_id");
        // $select->where("bop_id = $bop_id");
        $select->order("datetime_start ASC");

        $result = $db->getDbTable()->fetchAll($select);

        $return = array();
        foreach ($result as $value) {
            $return[] = $value->toArray();
        }

        return $return;

        /*
            SELECT  SUM(BOPATT.motorcycle_deduction)
            -- BOP.name,
            -- EMP.firstname, EMP.lastname,
            -- BOPATT.motorcycle_deduction


            FROM `bop_attendance` BOPATT
            INNER JOIN `attendance` ATT ON ATT.id = BOPATT.attendance_id

            -- INNER JOIN `bop` BOP ON BOP.id = BOPATT.bop_id
            -- INNER JOIN `employee` EMP on EMP.id = ATT.employee_id

            WHERE ATT.employee_id = 161
            AND BOPATT.bop_id = 2
         */
    }

}




