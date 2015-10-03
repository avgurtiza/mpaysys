<?php

class Dataentry_IndexController extends Zend_Controller_Action
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

    public function startAction() {
        // $this->_helper->_layout->setLayout('encoder');
    }

    public function ridersAction() { // AJAX
        $this->_helper->layout()->disableLayout();

        $keywords = '%' . $this->_request->getParam('keywords') . '%';

        $EmployeeMap = new Messerve_Model_Mapper_Employee();

        $search = $EmployeeMap->getDbTable()->getAdapter()->quoteInto("(firstname LIKE ? OR lastname LIKE ? OR employee_number LIKE ?) AND group_id > 0", $keywords);

        $riders = $EmployeeMap->fetchList($search, array('lastname ASC','firstname ASC'));

        $this->view->riders = $riders;

        $GroupMap = new Messerve_Model_Mapper_Group();

        $groups = array();

        foreach($GroupMap->fetchAll() as $group) {
            $groups[$group->getId()] = $group->getName();
        }

        $this->view->groups = $groups;

    }

    public function riderAction() {
        // $this->_helper->_layout->setLayout('encoder');
        $rider_id = (int) $this->_request->getParam('id');

        $Employee = new Messerve_Model_Employee();
        $Employee->find($rider_id);

        if(!$Employee->getId() > 0) {
            throw new Exception('Rider not found or ID is invalid.');
        }

        $this->view->rider = $Employee;

        $form = new Messerve_Form_EditDeduction();
        $form->populate(array('employee_id'=>$Employee->getId()));
        $form->setAction('/manager/deduction/index');

        $redirect = $form->createElement('hidden', 'redirect', array('value'=>'/dataentry/index/rider/id/' . $rider_id));
        $redirect->removeDecorator('DtDd')->removeDecorator('label');
        $form->addElement($redirect);

        $employee_id = $form->createElement('hidden', 'employee_id', array('value'=>$rider_id));
        $employee_id->removeDecorator('DtDd')->removeDecorator('label');
        $form->addElement($employee_id);

        // $form->getDisplayGroup('deductions')->setLegend('Add Scheduled deductions');

        $this->view->form = $form;

        $this->_fetch_deductions($Employee->getId());

    }

    protected function _fetch_deductions($employee_id) {
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
    }

}

