<?php

class Payroll_AutoController extends Zend_Controller_Action
{

    protected $_user_auth, $_config;

    public function init() {
        $storage = new Zend_Auth_Storage_Session();
        $data = $storage->read();

        if(!$data){
            $this->_redirect('auth/login');
        }

        $this->_user_auth = $data;

        $this->view->user_auth = $this->_user_auth;

        if($this->_user_auth->type != 'admin' && $this->_user_auth->type != 'accounting') {
            throw new Exception('You are not allowed to access this module.');
        }

        $this->_config = Zend_Registry::get('config');

    }

    public function indexAction() {
        $ClientMap = new Messerve_Model_Mapper_Client();

        $clients = array();

        foreach($ClientMap->fetchAll() as $cvalue) {
            $clients[$cvalue->getId()] = $cvalue->getName();
        }


        $groups = [];

        $GroupMap = new Messerve_Model_Mapper_Group();

        $groups_list = $GroupMap->fetchList("id > 0", array('client_id ASC', 'name ASC'));

        foreach ($groups_list as $gvalue) {
            $Employee = new Messerve_Model_DbTable_Employee();

            $employee_count = $Employee->countByQuery('group_id = ' . $gvalue->getId());
            if ($employee_count > 0) {
               /* $groups_array[$gvalue->getId()] = $clients[$gvalue->getClientId()] . ' ' . $gvalue->getName()
                    . ' (' . $employee_count . ')';*/

                $groups[$clients[$gvalue->getClientId()]][$gvalue->getId()] = $gvalue->getName()
                    . ' (' . $employee_count . ')';

            }
        }


        // asort($groups);

        foreach($GroupMap->fetchList("client_id <> 7", array("client_id", "name")) as $gvalue) {
        // foreach($GroupMap->fetchList("rate_id = 18", array("client_id", "name")) as $gvalue) {

            // $groups[$clients[$gvalue->getClientId()]][$gvalue->getId()] = ucwords(strtolower($gvalue->getName()));
        }

        $this->view->groups = $groups;

        $last_month = strtotime("last month");

        $date_today = date('Ymd');


        $pay_period = date('Y-m-16_t', strtotime('-1 month'));
        $date_start = date('Y-m-16', strtotime('-1 month'));
        $date_end = date('Y-m-t', strtotime('-1 month'));

        if(date('d') > 15) {
            $pay_period = date('Y-m-01_15');
            $date_start = date('Y-m-01');
            $date_end = date('Y-m-15');
        }


        $this->view->pay_period = $pay_period;
        $this->view->date_start = $date_start;
        $this->view->date_end = $date_end;
    }

}