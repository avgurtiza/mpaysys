<?php

class Manager_EmployerController extends Zend_Controller_Action
{

	protected $_user_auth;
	
	public function init()
	{
		/* Initialize action controller here */
		$storage = new Zend_Auth_Storage_Session();
		$data = $storage->read();
		 
		if(!$data){
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
        $form = new Messerve_Form_EditClient();
    }

}



