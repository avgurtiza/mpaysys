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


        $groups = array();

        $GroupMap = new Messerve_Model_Mapper_Group();

        foreach($GroupMap->fetchList("client_id <> 7", array("client_id", "name")) as $gvalue) {
        // foreach($GroupMap->fetchList("rate_id = 18", array("client_id", "name")) as $gvalue) {

            $groups[$clients[$gvalue->getClientId()]][$gvalue->getId()] = ucwords(strtolower($gvalue->getName()));
        }

        $this->view->groups = $groups;

        $last_month = strtotime("last month");

        $date_today = date('Ymd');

        if($date_today >= date('Ym01') && $date_today <= date('Ym15')) {
            $pay_period = date('Y-m-16_31', $last_month);
            $date_start = date('Y-m-16', $last_month);
            $date_end = date('Y-m-d', strtotime("last day of last month"));

        } elseif(
            $date_today > date('Ymd',strtotime("last day of this month"))
            && $date_today > date('Ym15',strtotime("next month"))
        ) {
            $pay_period = date('Y-m-01_15');
            $date_start = date('Y-m-01');
            $date_end = date('Y-m-15');
        } else {
            $pay_period = date('Y-m-16_31');
            $date_start = date('Y-m-16');
            $date_end = date('Y-m-d', strtotime("last day of this month"));
        }


        $pay_period = date('Y-m-01_15');
        $date_start = date('Y-m-01');
        $date_end = date('Y-m-15');

        // die('OI');
        echo "xx $date_today : $pay_period / $date_start - $date_end <br />";

        $this->view->pay_period = $pay_period;
        $this->view->date_start = $date_start;
        $this->view->date_end = $date_end;
    }

}