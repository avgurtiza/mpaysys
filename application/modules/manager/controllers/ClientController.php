<?php

class Manager_ClientController extends Zend_Controller_Action
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

        if (!in_array($this->_user_auth->type, ['admin', 'bop'])) {
            throw new Exception('You are not allowed to access this module.');
        }

    }

    public function indexAction()
    {
        // action body
        $ClientMap = new Messerve_Model_Mapper_Client();

        $clients = $ClientMap->fetchList('is_active = 1', 'name ASC');

        $this->view->clients = $clients;
    }

    public function editAction()
    {
        // action body
        $id = (int) $this->_request->getParam('id');

        $form = new Messerve_Form_EditClient();

        $Client = new Messerve_Model_Client();

        if ($id > 0) {

            $Client->find($id);
            if (!$Client->getId() > 0) {
                die('Invalid client ID.');
            }

            $form->removeElement('cancel');
        }

        $this->view->client = $Client;


        if ($this->_request->isPost()) { // Save submit
            $postvars = $this->_request->getPost();

            if ($form->isValid($postvars)) {

                if (!$form->getValue('id') > 0) {
                    $form->removeElement('id');
                }

                $Client->setOptions($form->getValues())
                    ->save();

                // $this->_redirect('/manager/client/edit/client_id/'. $Group->getClientId());

            }
        }

        $form->populate($Client->toArray());

        $this->view->form = $form;

        $GroupMap = new Messerve_Model_Mapper_Group();
        $groups = $GroupMap->fetchList("client_id = {$id}", 'name ASC');
        $this->view->groups = $groups;

        $groups_array = array();

        foreach ($groups as $value) {
            $groups_array[$value->getId()] = array("id" => $value->getId(), "name" => $value->getName());
        }

        $groups_map = $groups_array;

        $this->view->groups_map = $groups_map;

        $employees = $this->_employee_datagrid($groups);

        $this->view->employees = $employees;
    }

    protected function _employee_datagrid($groups)
    {

        if (!count($groups) > 0) {
            return false;
        }

        foreach ($groups as $value) {
            $groups_array[] = $value->getId();
        }

        $groups_in = implode(',', $groups_array);

        $Employees = new Messerve_Model_Mapper_Employee();

        $employees = $Employees->fetchList("group_id IN ({$groups_in})", array("lastname ASC", "firstname ASC"));

        return $employees;
    }
}

