<?php

class Manager_GroupController extends Zend_Controller_Action
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
        $where = '1';

        $client_id = (int)$this->_request->getParam('client_id');

        if ($client_id > 0) {
            $where = "client_id = {$client_id}";
        }

        $GroupMap = new Messerve_Model_Mapper_Group();

        $groups = $GroupMap->fetchList($where, 'name ASC');

        $this->view->groups = $groups;


        $Client = new Messerve_Model_Mapper_Client();

        $clients = array();

        foreach ($Client->fetchList('1', 'name ASC') as $cvalue) {
            $clients[$cvalue->getId()] = $cvalue->getName();
        }

    }

    public function editAction()
    {
        // action body
        $form = new Messerve_Form_EditGroup();

        // Calendar options
        $CalendarMap = new Messerve_Model_Mapper_Calendar();

        $calendars = $CalendarMap->fetchList('1', 'name ASC');

        $calendar_array = array();

        foreach ($calendars as $cvalue) {
            $calendar_array[$cvalue->getId()] = $cvalue->getName();
        }

        $calendar_tickboxes = $form->getElement('calendars');

        $calendar_tickboxes->setMultiOptions($calendar_array);


        // Client combobox
        $client_select = $form->getElement('client_id');

        $Client = new Messerve_Model_Mapper_Client();

        $client_options = array();

        foreach ($Client->fetchList('1', 'name ASC') as $cvalue) {
            $client_options[$cvalue->getId()] = $cvalue->getName();
        }

        $client_select->setMultiOptions($client_options);


        // Default rate
        $client_rate_select = $form->getElement('rate_client_id');

        $client_rate_array = $this->_getClientRates();

        $client_rate_select->setMultiOptions(array('0' => '') + $client_rate_array);

        $rate_select = $form->getElement('rate_id');

        $rate_array = $this->_getRates();

        $rate_select->setMultiOptions(array('0' => '') + $rate_array);


        // Regions

        $region_array = [
            ""=>"",
            "NCR" => "NCR",
            "CAR" => "CAR",
            "REGION I" => "REGION I",
            "REGION II" => "REGION II",
            "REGION III" => "REGION III",
            "REGION IV-A" => "REGION IV-A",
            "REGION IV-B" => "REGION IV-B",
            "REGION IX" => "REGION IX",
            "REGION V" => "REGION V",
            "REGION VI" => "REGION VI",
            "REGION VII" => "REGION VII",
            "REGION VIII" => "REGION VIII",
            "REGION X" => "REGION X",
            "REGION XI" => "REGION XI",
            "REGION XII" => "REGION XII"
        ];

        $region_select = $form->getElement('region');

        $region_select->setMultiOptions($region_array);

        $Group = new Messerve_Model_Group();

        $id = $this->_request->getParam('id');

        if ($this->_request->isPost()) { // Save submit
            $postvars = $this->_request->getPost();


            if ($form->isValid($postvars)) {
                if (!$form->getValue('id') > 0) {
                    $form->removeElement('id');
                }

                $Group->setOptions($form->getValues())
                    // ->setCalendars(json_encode($postvars['calendars']))
                    ->setSearch($postvars['name'] . ' ' . $client_options[$postvars['client_id']]);

                if (isset($postvars['calendars'])) {
                    $Group->setCalendars(json_encode($postvars['calendars']));
                }

                if (!$form->getValue('id') > 0) {
                    $Group->save();
                } else {
                    $Group->save(true);
                }

                $this->_redirect('/manager/client/edit/id/' . $Group->getClientId());

            } else {
                die('INVALID');
            }
        } elseif ($id > 0) { // Get requested group
            $Group->find($id);

            $form->populate($Group->toArray());

            $form->setDefaults(array('calendars' => json_decode($Group->getCalendars())));

        } else { // Add new group, try to set default client
            $client_id = (int)$this->_request->getParam('client_id');
            $form->populate(array('client_id' => $client_id));
        }

        $this->view->form = $form;
    }

    public function employeesAction()
    {
        // action body
        $id = (int)$this->_request->getParam('id');
        $Group = new Messerve_Model_Group();
        $Group->find($id);

        // if(!$Group->getId() > 0) die('Invalid group.');

        $this->view->group = $Group;

        $Client = new Messerve_Model_Client();
        $Client->find($Group->getClientId());
        $this->view->client = $Client;

        $EmployeeMap = new Messerve_Model_Mapper_Employee();

        $employees = $EmployeeMap->fetchList("group_id = {$id}", array('lastname ASC', 'firstname ASC'));
        $this->view->employees = $employees;

        $this->view->rates = $this->_getRates();
    }

    public function editemployeeAction()
    {
        // action body
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

    protected function _getClientRates()
    {
        $RateMap = new Messerve_Model_Mapper_RateClient();

        $rate_options = $RateMap->fetchList('1', 'name ASC');

        $rate_array = array(// 	'0'=>'N/A'
        );

        foreach ($rate_options as $rovalue) {
            $rate_array[$rovalue->getId()] = $rovalue->getName();
        }

        return $rate_array;
    }

    protected function _getGroups()
    {
        $ClientsMap = new Messerve_Model_Mapper_Client();
        $clients = array();

        foreach ($ClientsMap->fetchList('1', 'name ASC') as $cvalue) {
            $clients[$cvalue->getId()] = $cvalue->getName();
        }

        $GroupsMap = new Messerve_Model_Mapper_Group();

        $group_options = $GroupsMap->fetchList('1', array('client_id ASC', 'name ASC'));

        $group_array = array();

        foreach ($group_options as $govalue) {
            $group_array[$govalue->getId()] = $clients[$govalue->getClientId()] . " - " . $govalue->getName();
        }

        return $group_array;
    }

    public function employeeRateScheduleAction()
    {
        // preprint($this->_user_auth);
        $form = new Messerve_Form_EditEmployeeRateSchedule();
        $groups = $form->getElement("group_id");
        $groups->setOptions(array("multiOptions" => $this->_getGroups()));

        $rates = $form->getElement("rate_id");
        $rates->setOptions(array("multiOptions" => $this->_getRates()));

        if ($this->getRequest()->isPost()) {
            $post_data = $this->_request->getPost();

            if ($form->isValid($post_data)) {
                $Schedule = new Messerve_Model_EmployeeRateSchedule();
                $Schedule->setOptions($post_data)->save();

                $Log = new Messerve_Model_Log();

                $user = $this->_user_auth;

                $data = json_encode($post_data);
                $message = <<<EOF
{$user->real_name} saved a new group rate schedule.
EOF;

                $Log
                    ->setChangeType("rate")
                    ->setUserId($user->id)
                    ->setUserName($user->username)
                    ->setDatetime(date("Y-m-d H:i"))
                    ->setDescription($message)
                    ->setData($data)
                    ->save();

            }
        }
        $this->view->form = $form;
    }

    public function ratescheduleAction()
    {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout->disableLayout();

        /*
        $GroupMap = new Messerve_Model_Mapper_Group();

        $groups = $GroupMap->fetchListToArray("rate_client_id = 2");

        foreach($groups as $gvalue) {
            preprint($gvalue);
            $ClientRateSchedule = new Messerve_Model_ClientRateSchedule();
            $ClientRateSchedule
                ->setDateActive("2014-04-04")
                ->setGroupId($gvalue['id'])
                ->setClientRateId(27)
                ->setNotes("Inserted by " . $this->_user_auth->username . " on " . date("Y-m-d H:i"))
                ->save()
            ;

            preprint($ClientRateSchedule->toArray());
        }

        $groups = $GroupMap->fetchListToArray("rate_id = 6");

        foreach($groups as $gvalue) {


            $RateSchedule = new Messerve_Model_EmployeeRateSchedule();
            $RateSchedule
                ->setDateActive("2014-04-04")
                ->setGroupId($gvalue['id'])
                ->setRateId(18)
                ->setNotes("Inserted by " . $this->_user_auth->username . " on " . date("Y-m-d H:i"))
                ->save()
            ;

            preprint($RateSchedule->toArray());

        }
        */

    }
}







