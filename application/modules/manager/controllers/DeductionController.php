<?php

class Manager_DeductionController extends Zend_Controller_Action
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
    	$form = new Messerve_Form_EditDeduction();
    	$this->view->form = $form;
    	
    	$employee_id = (int) $this->_request->getParam('employee_id');
    	$group_id = (int) $this->_request->getParam('group_id');
    	
    	if(!$employee_id > 0) {
            throw new Exception('Invalid employee ID');
    	}
    	
    	$Employee = new Messerve_Model_Employee();
    	
    	$Employee->find($employee_id);
    	
    	if(!$Employee->getId() > 0) {
    		throw new Exception('Invalid employee record');
    	}
    	
    	$this->view->employee = $Employee;
    	
    	$client_id = $this->_request->getParam('client_id');
    	
    	$Client = new Messerve_Model_Client();
    	$Client->find($client_id);
    	
    	$this->view->client = $Client;
    	
    	
    	$DeductionSchedMap = new Messerve_Model_Mapper_DeductionSchedule();
    	
    	$deductions = $DeductionSchedMap->fetchList('employee_id = ' . $employee_id, 'id');
    	
    	$DeductAttendMap = new Messerve_Model_Mapper_DeductionAttendance();
    	
    	$deducted_amounts = array();
    	
    	foreach ($deductions as $dvalue) {
    		$select = $DeductAttendMap->getDbTable()->select();
    		
    		$select
	    		->from('deduction_attendance',array('mysum'=>'SUM(amount)'))
	    		->where('deduction_schedule_id = ?', $dvalue->getId());
    		
    		$deducted_amounts[$dvalue->getId()] = $DeductAttendMap->getDbTable()->fetchRow($select)->mysum;
    	}
    	
    	$this->view->deducted_amounts = $deducted_amounts;
    	$this->view->deductions = $deductions;
    	
    	 
    	if($this->_request->isPost()) { // Save submit
    		
    		$postvars = $this->_request->getPost();
    		
    		if($form->isValid($postvars)) {
    			/*
    			if($postvars['cutoff'] == '3') { // TODO:  FIX!
    				$DeductionSched = new Messerve_Model_DeductionSchedule();
    				$DeductionSched
	    				->setEmployeeId($employee_id)
	    				->setOptions($postvars)
	    				->setAmount($postvars['amount']/2)
	    				->setCutoff('1')
	    				->save()
    				;
    				
    				$DeductionSched = new Messerve_Model_DeductionSchedule();
    				$DeductionSched
	    				->setEmployeeId($employee_id)
	    				->setOptions($postvars)
	    				->setCutoff('2')
	    				->setAmount($postvars['amount']/2)
	    				->save()
    				;
    				
    			} else {
    				$DeductionSched = new Messerve_Model_DeductionSchedule();
    				$DeductionSched
	    				->setEmployeeId($employee_id)
	    				->setOptions($postvars)
	    				->save()
    				;
    				
    			}
    			*/
    			$DeductionSched = new Messerve_Model_DeductionSchedule();
    			$DeductionSched
	    			->setEmployeeId($employee_id)
	    			->setOptions($postvars)
                    ->setUserId($this->_user_auth->id)
                    ->setNotes('Encoded by ' . $this->_user_auth->username)
                    //->setDateAdded()
	    			->save()
	    			;

                $redirect = $this->_request->getParam('redirect');

                if($redirect != '') {
                    $this->_redirect($redirect);
                } else {
                    $this->_redirect('manager/deduction/index/client_id/' . $client_id .'/employee_id/' . $employee_id);
                }
    		}
    		
    	}
	}

    public function editAction()
    {
    	$deduction_id = $this->_request->getParam('id');
    	
        if(!$deduction_id > 0) {
    		die('Invalid deduction id');	
    	}
    	
    	$DeductionSched = new Messerve_Model_DeductionSchedule();
    	
    	$DeductionSched->find($deduction_id);
    	
    	if(!$DeductionSched->getId() > 0) {
    		die('Invalid employee record');
    	}
    	
    	$Employee = new Messerve_Model_Employee();
    	 
    	$Employee->find($DeductionSched->getEmployeeId());
    	 
    	if(!$Employee->getId() > 0) {
    		die('Invalid employee record');
    	}
    	
    	$form = new Messerve_Form_EditDeduction();
    	
    	$legend = $form->getDisplayGroup('deductions');
    	$legend->setOptions(array('legend'=>'Edit deduction'));
    	
    	$cut_off_options = array(1,2,3);
    	
    	foreach ($cut_off_options as $ckey=>$cvalue) {
    		if($cvalue == $DeductionSched->getCutoff()) unset($cut_off_options[$ckey]);
    	}
    	
    	$cutoff = $form->getElement('cutoff');
    	$cutoff->setOptions(array('disable'=>$cut_off_options));
    	
    	
    	$form->populate($DeductionSched->toArray());
    	
    	$this->view->form = $form;
    	
    	// action body
    	if($this->_request->isPost()) { // Save submit
    	
    		$postvars = $this->_request->getPost();
    	
    		if($form->isValid($postvars)) {
    			$DeductionSched
    			// ->setEmployeeId($employee_id)
    			->setOptions($postvars)
    			->save();
    			 
    			$this->_redirect('manager/deduction/index/group_id/' 
    					. $Employee->getGroupId() .'/employee_id/' . $Employee->getId());
    		}
    	
    	}        
    }
}