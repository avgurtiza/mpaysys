<?php

class Default_UserController extends Zend_Controller_Action
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

        if($this->_user_auth->type != 'admin') {
            throw new Exception('You are not allowed to access this module.');
        }
    }

    public function indexAction()
    {
        // action body
    }

    public function gridAction()
    {
        // action body
    	$User = new Messerve_Model_User();
    	$this->view->grid_title = 'User';
    	$this->view->entries = $User->getMapper()->fetchList('1',array('status','CAST(type AS CHAR) ASC','real_name'));
    }

    public function editAction()
    {
        // action body
    	$this->_helper->layout->setLayout('iframe');
    	$request = $this->getRequest();
    	$success = 'false';
    	
    	$User = new Messerve_Model_User();
    	$User->find($request->getParam('id'));
    	
    	if(!$User->getId() > 0 ) die('Invalid parameters');
    	
    	$form = new Messerve_Form_User();
    	$form->removeElement('password_salt');
    	
    	$password = $form->getElement('password');
    	$password->setRequired(false)->removeValidator('notEmpty')->setDescription('Leave blank to keep old password');
    	
    	$form->populate($User->toArray());
    	
    	if ($this->getRequest()->isPost()) {
    		if ($form->isValid($request->getPost())) {
    			
    			$User = new Messerve_Model_User();
    			$User->setOptions($form->getValues());
    			
    			if($form->getValue('password') != '') {
	    			$salt = md5(time());
	    			$password = md5($form->getValue('password').$salt);
	    			
					$User->setPasswordSalt($salt)
	    				->setPassword($password);
    			}
    			    			
    			$User->save();
    			$success = 'true';
    		}
    	}
    	
    	$this->view->success = $success;    	
    	$this->view->form = $form;
    }

    public function addAction()
    {
        // action body
    	$this->_helper->layout->setLayout('iframe');
    	$request = $this->getRequest();
    	$success = 'false';
    	
    	$form = new Messerve_Form_User();
    	
    	$form->removeElement('id');
    	$form->removeElement('password_salt');
    	
    	if ($this->getRequest()->isPost()) {
    		if ($form->isValid($request->getPost())) {
    			$salt = md5(time());
    			$password = md5($form->getValue('password').$salt);
    			
    			$User = new Messerve_Model_User();
    			$User->setOptions($form->getValues())
    			    ->setPasswordSalt($salt)
    				->setPassword($password);
    			
    			$User->save();
    			
    			$success = 'true';
    		}
    	}
    	
    	$this->view->success = $success;
    	$this->view->form = $form;        
    }
    
    public function getdataAction()
    {
    	// action body
    	$request = $this->getRequest();
    	$success = 'false';
    
    	$User = new Messerve_Model_User();
    	$User->find($request->getParam('id'));
    
    	if(!$User->getId() > 0 ) die('Invalid parameters');
    
    	if ($this->getRequest()->isPost()) {
    		$this->view->data = $User;
    		$this->_helper->layout()->disableLayout();
    		$this->renderScript('user/partials/gridrow.phtml');
    	}
    
    }    

}







