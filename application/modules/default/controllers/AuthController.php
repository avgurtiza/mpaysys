<?php

class Default_AuthController extends Zend_Controller_Action
{
	protected $_user_auth;
	
	public function init()
	{
		/* Initialize action controller here */
		$storage = new Zend_Auth_Storage_Session();
		$data = $storage->read();
		
		$this->_user_auth = $data;
		
		$this->view->user_auth = $this->_user_auth;
	}

	public function indexAction()
	{
		$storage = new Zend_Auth_Storage_Session();
		$data = $storage->read();

		if(!$data){
			$this->_redirect('auth/login');
		}

		$this->view->username = $data->username;
	}

	public function loginAction()
	{
		$StaffDB = new Messerve_Model_DbTable_User();
		$form = new Messerve_Form_Login();

		$this->view->form = $form;

		if($this->getRequest()->isPost()){
			if($form->isValid($_POST)){
				$data = $form->getValues();

				$auth = Zend_Auth::getInstance();

				$authAdapter = new Zend_Auth_Adapter_DbTable($StaffDB->getAdapter(),'user');

				$authAdapter->setIdentityColumn('username')
					->setCredentialColumn('password');



				$authAdapter->setIdentity($data['username'])
					->setCredential($data['password'])
					->setCredentialTreatment("MD5(CONCAT(?,password_salt)) AND status = 'active'");

				// ->setCredentialTreatment("MD5(CONCAT(?,password_salt) AND status LIKE 'active')");

				$result = $auth->authenticate($authAdapter);

				if($result->isValid()){
					$storage = new Zend_Auth_Storage_Session();
                    $auth_object = $authAdapter->getResultRowObject();
					$storage->write($auth_object);

                    // preprint($authAdapter->getResultRowObject(),1);

                    if($auth_object->type == 'encoder') { // TODO: Make flexible!
                        $this->_redirect('dataentry/index/start');
                    } else {
                        $this->_redirect('dataentry/attendance');
                    }

				} else {
					$this->view->errorMessage = "Invalid username or password. Please try again.";
				}
			}
		}
	}

	public function logoutAction()
	{
		$storage = new Zend_Auth_Storage_Session();
		$storage->clear();
		$this->_redirect('auth/login');
	}

	public function changepassAction() {
		$form = new Messerve_Form_EditPassword();
		$message = '';
		$user_id = $form->getElement('id');
		
		$user_id->setValue($this->_user_auth->id);

		if($this->getRequest()->isPost()) {
			if($form->isValid($_POST)) {
				// preprint($form->getValues(),1);
				
				$User = new Messerve_Model_User();
				
				$User->setOptions($form->getValues());
				 
				if($form->getValue('password') != '') {
					$salt = md5(time());
					$password = md5($form->getValue('password').$salt);
				
					$User->setPasswordSalt($salt)
						->setPassword($password);
				}
				
				$User->save();
				
				$message = 'Password change successful';
			}
		}
		
		$this->view->message = $message;
		$this->view->form = $form;
		
	}
}



