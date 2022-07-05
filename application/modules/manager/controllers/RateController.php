<?php

class Manager_RateController extends Zend_Controller_Action
{

    protected $_user_auth;

    public function init()
    {
        $storage = new Zend_Auth_Storage_Session();
        $data = $storage->read();

        if (!$data) {
            $this->_redirect('auth/login');
        }

        $this->_user_auth = $data;

        if ($this->_user_auth->type !== 'admin') {
            throw new Exception('You are not allowed to access this module.');
        }


        $this->view->user_auth = $this->_user_auth;
    }

    public function indexAction()
    {
        // action body
        $RateMap = new Messerve_Model_Mapper_Rate();

        $rates = $RateMap->fetchList('1', 'id DESC');

        $this->view->rates = $rates;

        $ClientRateMap = new Messerve_Model_Mapper_RateClient();

        $client_rates = $ClientRateMap->fetchList('1', 'id DESC');

        $this->view->client_rates = $client_rates;

    }

    public function editAction()
    {
        // action body
        $form = new Messerve_Form_EditRate();

        $id = $this->_request->getParam('id');

        $Rate = new Messerve_Model_Rate();

        if ($this->_request->isPost()) { // Save submit
            $postvars = $this->_request->getPost();

            if ($form->isValid($postvars)) {

                if (!$form->getValue('id') > 0) {
                    $form->removeElement('id');
                }

                $Rate->setOptions($form->getValues())
                    ->save();

                $this->_redirect('/manager/rate/');

            }
        } elseif ($id > 0) { // Get requested rate
            $Rate->find($id);
            $form->populate($Rate->toArray());
        }
        $this->view->form = $form;
    }

    public function editclientAction()
    {
        // action body
        $form = new Messerve_Form_EditRateClient();

        $id = $this->_request->getParam('id');

        $Rate = new Messerve_Model_RateClient();

        if ($this->_request->isPost()) { // Save submit
            $postvars = $this->_request->getPost();

            if ($form->isValid($postvars)) {

                if (!$form->getValue('id') > 0) {
                    $form->removeElement('id');
                }

                $Rate->setOptions($form->getValues())
                    ->save();

                $this->_redirect('/manager/rate/');

            }
        } elseif ($id > 0) { // Get requested rate
            $Rate->find($id);
            $form->populate($Rate->toArray());
        }
        $this->view->form = $form;
    }

    public function calendarAction()
    {
        // action body
        $CalendarMap = new Messerve_Model_Mapper_Calendar();

        $calendars = $CalendarMap->fetchList('1', 'name ASC');

        $this->view->calendars = $calendars;

    }

    public function calendareditAction()
    {
        // action body
        $form = new Messerve_Form_EditCalendar();

        $id = (int)$this->_request->getParam('id');

        $entryform = new Messerve_Form_EditCalendarEntry();

        $entryform->populate(array('calendar_id' => $id));

        $Calendar = new Messerve_Model_Calendar();

        $Calendar->find($id);

        $form->populate($Calendar->toArray());

        $CalendarEntryMap = new Messerve_Model_Mapper_CalendarEntry();
        // $calendar_entries = $CalendarEntryMap->fetchList("(year = '0000' OR year = '".date('Y')."') AND calendar_id = " . $id, 'date ASC');
        $calendar_entries = $CalendarEntryMap->fetchList("calendar_id = " . $id, array('year DESC', 'date DESC'));

        $this->view->calendar_entries = $calendar_entries;

        if ($this->_request->isPost()) { // Save submit
            $postvars = $this->_request->getPost();


            if ($this->_request->getParam('calendar_id') > 0) { // adding a calendar entry
                if ($entryform->isValid($postvars)) {

                    if (!$entryform->getValue('id') > 0) { // TODO: clean up
                        $entryform->removeElement('id');
                    }

                    $date = $entryform->getValue('date');

                    $date_array = explode('-', $date);

                    $CalendarEntry = new Messerve_Model_CalendarEntry();

                    $CalendarEntry->setOptions($entryform->getValues());

                    if (count($date_array) > 2) {
                        $CalendarEntry
                            ->setYear($date_array[0])
                            ->setDate($date_array[1] . '-' . $date_array[2]);
                    } else {
                        $CalendarEntry
                            ->setYear('0000')
                            ->setDate($date_array[0] . '-' . $date_array[1]);
                    }

                    // preprint($CalendarEntry->toArray(),1);

                    $CalendarEntry->save();

                    $this->_redirect('/manager/rate/calendaredit/id/' . $id);
                }
            } else { // Editing calendar
                if ($form->isValid($postvars)) {

                    if (!$form->getValue('id') > 0) {
                        $form->removeElement('id');
                    }


                    $Calendar->setOptions($form->getValues())
                        ->save();

                    $this->_redirect('/manager/rate/calendar');
                }
            }

        } elseif ($id > 0) { // Get requested rate

        }

        $this->view->id = $id;

        $this->view->form = $form;

        // $entryform->setAction('/manager/rate/addcalendarentry');
        $this->view->entryform = $entryform;
    }

    public function addcalendarentryAction()
    {
        // action body
    }

    public function deletecalendarentryAction()
    {
        // action body
    }


    public function clientRateTemplateAction()
    {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $rate_columns = [
            'id' => 0, 'client_name' => '', 'name' => '',
            'code' => '',
            'reg' => 0, 'reg_ot' => 0, 'reg_nd' => 0, 'reg_nd_ot' => 0, 'spec' => 0, 'spec_ot' => 0,
            'spec_nd' => 0, 'spec_nd_ot' => 0, 'legal' => 0, 'legal_ot' => 0, 'legal_nd' => 0,
            'legal_nd_ot' => 0, 'legal_unattend' => 0

        ];

        $group_rates = [];


        header('Content-Type: application/csv');
        header('Content-Disposition: attachment; filename="Messerve_Client_Rate_Template.csv";');

        $f = fopen('php://output', 'rb');

        fputcsv($f, ['DO NOT EDIT ID', 'DO NOT EDIT CLIENT NAME', 'DO NOT EDIT NAME', 'DO NOT EDIT CODE'], ',');
        fputcsv($f, array_keys($rate_columns), ',');

        foreach (Messerve_Model_Eloquent_Group::orderBy('client_id')->orderBy('name')->get() as $group) {
            $this_group_rate = $rate_columns;
            $this_group_rate['id'] = $group->id;
            $this_group_rate['code'] = $group->code;
            $this_group_rate['name'] = $group->name;
            $this_group_rate['client_name'] = $group->client->name;

            fputcsv($f, $this_group_rate, ',');
        }

    }

    private function insertNewClientRate(array $row, string $prefix)
    {

        list($id, $client_name, $name, $code, $reg, $reg_ot, $reg_nd, $reg_nd_ot, $spec,
            $spec_ot, $spec_nd, $spec_nd_ot, $legal, $legal_ot, $legal_nd, $legal_nd_ot, $legal_unattend,
            ) = $row;

        if (!((int)$reg > 0)) {
            return false;
        }

        $rate_name = str_replace(' ', '', sprintf("%s-%d-%s-%s", $prefix, $id, $client_name, $name));

        // Create new rate
        return Messerve_Model_Eloquent_ClientRate::create([
            'name' => $rate_name,
            'reg' => $reg,
            'reg_ot' => $reg_ot,
            'reg_nd' => $reg_nd,
            'reg_nd_ot' => $reg_nd_ot,
            'spec' => $spec,
            'spec_ot' => $spec_ot,
            'spec_nd' => $spec_nd,
            'spec_nd_ot' => $spec_nd_ot,
            'legal' => $legal,
            'legal_ot' => $legal_ot,
            'legal_nd' => $legal_nd,
            'legal_nd_ot' => $legal_nd_ot,
            'legal_unattend' => $legal_unattend,
        ]);
    }

    /**
     * @throws Zend_File_Transfer_Exception
     */
    public function clientRateImportAction()
    {
        if (!$this->_request->isPost()) {
            return $this->clientRateImportForm();
            // throw new \RuntimeException("Action requires POST.");
        }

        $this->_helper->_layout->setLayout('iframe');
        $this->_helper->viewRenderer->setRender('client-rate-import-output');

        $upload = new Zend_File_Transfer_Adapter_Http();

        $upload->addValidator('Count', false, array('min' => 1, 'max' => 1))
            ->addValidator('Extension', false, 'csv')
            ->addValidator('Size', false, array('max' => '1024kb'))
            ->setDestination('/tmp');

        if (!$upload->isValid()) {
            throw new \RuntimeException('Bad upload data: ' . implode(',', $upload->getMessages()));
        }

        $upload->receive();

        $file = new SplFileObject($upload->getFileName());

        $file->setFlags(SplFileObject::READ_CSV);

        $prefix = date('Y-m-d_') . str_random(8);

        $client_rates = [];

        foreach ($file as $row) {
            if (!((int)$row[0] > 0)) {
                continue;
            }

            $client_rate = $this->insertNewClientRate($row, $prefix);

            if (!$client_rate) {
                continue;
            }

            // Update groups to use client rate
            try {
                $group = Messerve_Model_Eloquent_Group::find($client_rate->group_id);

                if (!$group) {
                    $client_rates[] = (object)[
                        'name' => $client_rate->name,
                        'error' => 'Did not find group with id ' . $client_rate->group_id
                    ];

                } else {
                    $group->rate_client_id = $client_rate->id;
                    $group->save();
                    $client_rates[] = (object)[
                        'name' => $client_rate->name,
                        'error' => ''
                    ];

                }

            } catch (\Exception $exception) {
                $client_rates[] = (object)[
                    'name' => $client_rate->name,
                    'error' => 'Failed assigning rate to group; error follows: ' . $exception->getMessage()
                ];

            }
        }

        $this->view->client_rates = $client_rates;
    }

    protected function clientRateImportForm()
    {
    }
}











