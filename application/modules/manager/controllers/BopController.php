<?php
class Manager_BopController extends Zend_Controller_Action
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
        if($this->_user_auth->type != 'admin') {
            throw new Exception('You are not allowed to access this module.');
        }

    }
    
    public function indexAction() {
    	
    	if($this->_request->isPost()) {
    		preprint($_POST);
    		
    		$BOP = new Messerve_Model_Bop();
    		$BOP->setOptions($this->_request->getParams());
    		// preprint($BOP->toArray());
    		$BOP->save();
    		$this->_redirect('manager/bop');
    	}
    	
    	$BopMap = new Messerve_Model_Mapper_Bop();
    	
    	$bop = $BopMap->fetchList('1');
    	
    	$bop_forms = array();
    	
    	foreach($bop as $bvalue) {
    		$form = new Messerve_Form_EditBop();
    		$form->populate($bvalue->toArray());
    		
    		$bop_forms[] = array(
    					'name'=>$bvalue->getName()
    					, 'id'=>$bvalue->getId()
    					, 'form'=>$form	
    				);
    	}
    	
    	$this->view->bop_forms = $bop_forms;
    	// preprint($bop_forms);
    }
}

