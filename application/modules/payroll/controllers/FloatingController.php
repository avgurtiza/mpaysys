<?php

use Messerve_Model_Eloquent_FloatingAttendance as Floating;

class Payroll_FloatingController extends Zend_Controller_Action
{

    protected $_user_auth, $_config;

    public function init()
    {
        $storage = new Zend_Auth_Storage_Session();
        $data = $storage->read();

        if (!$data) {
            $this->_redirect('auth/login');
        }

        $this->_user_auth = $data;

        $this->view->user_auth = $this->_user_auth;

        if ($this->_user_auth->type != 'admin' && $this->_user_auth->type != 'accounting') {
            throw new Exception('You are not allowed to access this module.');
        }

        $this->_config = Zend_Registry::get('config');

    }

    public function indexAction() {

        foreach (Floating::where('floating_status', 0)->get() as $ot) {
            preprint($ot->attendance->employee->toArray());
        }
    }
}