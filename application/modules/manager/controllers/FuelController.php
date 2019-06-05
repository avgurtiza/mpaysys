<?php

class Manager_FuelController extends Zend_Controller_Action
{

    protected $_user_auth,
        $gascard_type,
        $gascard_field;

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

        if ($this->_user_auth->type !== 'admin') {
            throw new Exception('You are not allowed to access this module.');
        }
    }

    public function indexAction()
    {
        // action body
    }

    public function caltexAction()
    {
        $this->gascard_type = 'caltex';
        ini_set('memory_limit', '1G');

        define('C_LOC_NUM', 0);
        define('C_GASCARD_NO', 7);
        define('C_STATEMENT_DATE', 6);
        define('C_INVOICE_DATE', 4);
        define('C_INVOICE_TIME', 5);
        define('C_PRODUCT_QUANTITY', 18);
        define('C_INVOICE_NUMBER', 3);
        define('C_STATION_NAME', 1);
        define('C_PRODUCT', 17);
        define('C_FUEL_NET', 21);
        define('C_VAT', 22);

        if ($this->_request->isPost()) {
            set_time_limit(0);
            $upload = new Zend_File_Transfer_Adapter_Http();

            $upload->setDestination('/tmp');


            if (!$upload->receive()) {
                throw new Exception($upload->getMessages());
            }

            $filename = $upload->getFilename();

            $file = new SplFileObject($filename);
            $file->setFlags(SplFileObject::READ_CSV);

            $saved = [];
            $orphans = [];
            $gascard_no_user = [];
            $gascard_employee = [];

            $row_count = 0;

            foreach ($file as $row) {

                $row_count++;
                array_map('trim', $row);

                if (!isset($row[C_GASCARD_NO]) || $row[C_GASCARD_NO] == '') {
                    echo "No gas card number, skipping...<br/>";
                    continue;
                }


                if (!isset($row[C_LOC_NUM]) || !is_numeric($row[C_LOC_NUM])) {
                    echo "No location number, skipping...<br/>";
                    continue;
                }


                if (
                    isset($row[C_GASCARD_NO]) && $row[C_GASCARD_NO] != ''
                    && isset($row[C_INVOICE_NUMBER]) && $row[C_INVOICE_NUMBER] != ''
                    && isset($row[C_INVOICE_DATE]) && $row[C_INVOICE_DATE] != ''
                ) {

                    echo "Gas card number {$row[C_GASCARD_NO]}<br/>";

                    $invoice_date = false;

                    $split_time = explode(':', $row[C_INVOICE_TIME]);

                    $invoice_time = '';

                    if (!$split_time[0]) {
                        $invoice_time .= '00:';
                    } else {
                        $invoice_time .= $split_time[0] . ':';
                    }


                    if (!$split_time[1]) {
                        $invoice_time .= '00:';
                    } else {
                        $invoice_time .= $split_time[1] . ':';
                    }


                    if (!$split_time[2]) {
                        $invoice_time .= '00';
                    } else {
                        $invoice_time .= $split_time[2];
                    }


                    $full_date = $row[C_INVOICE_DATE] . ' ' . $invoice_time;


                    try {
                        $invoice_date = \Carbon\Carbon::createFromFormat('m/d/Y H:i:s', $full_date)->toDateTimeString();
                    } catch (Exception $exception) {
                        echo "Invalid date m/d/Y H:i -- " . $full_date . "...";
                    }


                    if (is_numeric($row[C_GASCARD_NO]) && Carbon\Carbon::parse($invoice_date)->year > \Carbon\Carbon::now()->year) {
                        throw new Exception('HALT.  Invoice date is in the future! At line ' . $row_count . ' -- ' . $full_date . Carbon\Carbon::parse($invoice_date)->year);
                    }


                    if (!$invoice_date && is_numeric($row[C_GASCARD_NO])) {
                        throw new Exception('Your invoice date is invalid! Expecting MM/DD/YYYY HH:MM:SS but got: ' . $invoice_date . $full_date . ' at line ' . $row_count . ' -- ' . $row[C_INVOICE_DATE]);
                    }

                    $statement_date = false;

                    try {
                        $statement_date = \Carbon\Carbon::createFromFormat('m/d/Y', $row[C_STATEMENT_DATE])->toDateString();
                    } catch (Exception $exception) {
                        echo "Invalid statement date m/d/Y -- " . $row[C_STATEMENT_DATE] . "...";
                    }

                    if (!$statement_date) {
                        echo "No valid statement date found; skipping. <br>";
                        continue;
                    }

                    $data = [
                        // 'gascard' => $row[C_GASCARD_NO]
                        // , 'raw_invoice_date' => $invoice_date
                        // 'statement_date' => $statement_date
                        'invoice_date' => $invoice_date,
                        'product_quantity' => $row[C_PRODUCT_QUANTITY],
                        'invoice_number' => $row[C_INVOICE_NUMBER],
                        'station_name' => $row[C_STATION_NAME],
                        'product' => $row[C_PRODUCT],
                        'fuel_cost' => $row[C_FUEL_NET] + $row[C_VAT],
                        'gascard_type' => $this->gascard_type
                    ];

                    if (in_array($row[C_GASCARD_NO], $gascard_no_user)) {
                        $orphans[] = $data;
                        continue;
                    }

                    if (isset($gascard_employee[$row[C_GASCARD_NO]])) {
                        $Employee = $gascard_employee[$row[C_GASCARD_NO]];
                    } else {
                        $Employee = Messerve_Model_Eloquent_Employee::where('gascard2', $row[C_GASCARD_NO])->first();

                        if ($Employee) {
                            $gascard_employee[$row[C_GASCARD_NO]] = $Employee;
                        }
                    }

                    if ($Employee && $Employee->id > 0) {
                        $data['employee_id'] = $Employee->id;

                        $Fuel = $this->getFuelPurchase($invoice_date, $row[C_INVOICE_NUMBER], $Employee->id, $this->gascard_type);

                        if ($Fuel->getId() > 0) {
                            echo "Skipped existing fuel record: ";
                            preprint($Fuel->toArray());
                            continue;
                        }

                        Messerve_Model_Eloquent_Fuelpurchase::create($data);
                        /*
                        $Fuel
                            ->setOptions($data)
                            ->setEmployeeId($Employee->id)
                            ->save();
                        */
                        $data['employee'] = $Employee->firstname . ' ' . $Employee->lastname . ' ' . $Employee->employee_number;
                        $saved[] = $data;
                    } else {
                        $gascard_no_user[] = $row[C_GASCARD_NO];
                        $orphans[] = $data;
                    }

                }

            }

            $this->view->saved = $saved;
            $this->view->orphans = $orphans;

            echo "<h1>SAVED : " . count($saved) . "</h1>";

        }
    }


    public function petronAction()
    {
        $this->gascard_type = 'petron';
        $this->gascard_field = 'gascard';
        return $this->importAction();
    }

    public function oilEmpireAction()
    {
        $this->gascard_type = 'oilempire';
        $this->gascard_field = 'gascard3';
        return $this->importAction();
    }

    public function importAction()
    {
        ini_set('memory_limit', '1G');

        define('P_GASCARD_NO', 5);
        define('P_STATEMENT_DATE', 2);
        define('P_INVOICE_DATE', 10);
        define('P_PRODUCT_QUANTITY', 15);
        define('P_INVOICE_NUMBER', 13);
        define('P_STATION_NAME', 11);
        define('P_PRODUCT', 14);
        define('P_FUEL_COST', 18);

        if ($this->_request->isPost()) {
            set_time_limit(0);
            $upload = new Zend_File_Transfer_Adapter_Http();

            $upload->setDestination('/tmp');


            if (!$upload->receive()) {
                $messages = $upload->getMessages();
                die(implode("\n", $messages));
            } else {
                $filename = $upload->getFilename();

                // echo $filename;

                $file = new SplFileObject($filename);
                $file->setFlags(SplFileObject::READ_CSV);

                $saved = array();
                $orphans = array();
                $gascard_no_user = array();
                $gascard_employee = array();

                $i = 0;


                foreach ($file as $row) {
                    array_map('trim', $row);

                    if (!isset($row[P_GASCARD_NO]) || !is_numeric($row[P_GASCARD_NO])) {
                        continue;
                    }

                    if (isset($row[P_GASCARD_NO]) && $row[P_GASCARD_NO] != '' && isset($row[P_INVOICE_NUMBER]) && $row[P_INVOICE_NUMBER] != '') {
                        // if(trim($row[7]) == '') continue;
                        // $invoice_date = date('Y-m-d h:i:s', strtotime($row[12]));
                        $raw_invoice_date = str_replace('/', '-', $row[P_INVOICE_DATE]);
                        $temp_invoice_date = DateTime::createFromFormat('Y-m-d', $raw_invoice_date);

                        // if(!$temp_invoice_date) $temp_invoice_date = DateTime::createFromFormat('d-m-Y', $raw_invoice_date);
                        if (!$temp_invoice_date) $temp_invoice_date = DateTime::createFromFormat('m-d-Y H:i', $raw_invoice_date);
                        if (!$temp_invoice_date) $temp_invoice_date = DateTime::createFromFormat('m-d-y H:i A', $raw_invoice_date);

                        if (!$temp_invoice_date) {
                            die('INVALID INVOICE DATE ' . $raw_invoice_date);
                        }

                        $invoice_date = $temp_invoice_date->format('Y-m-d');

                        $raw_statement_date = str_replace('/', '-', $row[P_STATEMENT_DATE]);
                        $raw_statement_date = str_replace("'", '', $raw_statement_date);
                        $temp_statement_date = DateTime::createFromFormat('Y-m-d', $raw_statement_date);

                        // if(!$temp_statement_date) $temp_statement_date = DateTime::createFromFormat('d-m-Y', $raw_statement_date);
                        if (!$temp_statement_date) $temp_statement_date = DateTime::createFromFormat('m-d-Y', $raw_statement_date);

                        if (!$temp_statement_date) {
                            die('INVALID STATEMENT DATE ' . $raw_statement_date);
                        }

                        $statement_date = $temp_statement_date->format('Y-m-d');

                        $data = array(
                            //    'gascard' => $row[P_GASCARD_NO]
                            // , 'raw_invoice_date' => $raw_invoice_date
                            // , 'statement_date' => $statement_date
                            'invoice_date' => $invoice_date
                        , 'product_quantity' => $row[P_PRODUCT_QUANTITY]
                        , 'invoice_number' => $row[P_INVOICE_NUMBER]
                        , 'station_name' => $row[P_STATION_NAME]
                        , 'product' => $row[P_PRODUCT]
                        , 'fuel_cost' => $row[P_FUEL_COST]
                        , 'gascard_type' => $this->gascard_type

                        );

                        if (in_array($row[P_GASCARD_NO], $gascard_no_user)) {
                            $orphans[] = $data;
                            continue;
                        }

                        if (isset($gascard_employee[$row[P_GASCARD_NO]])) {
                            $Employee = $gascard_employee[$row[P_GASCARD_NO]];
                        } else {
                            // Get employee by gascard
                            // $EmployeeMap = new Messerve_Model_Mapper_Employee();
                            // $Employee = $EmployeeMap->findOneByField('gascard', $row[P_GASCARD_NO]);
                            $Employee = $this->getEmployeeByGascard($row[P_GASCARD_NO]);
                            $gascard_employee[$row[P_GASCARD_NO]] = $Employee;
                        }

                        if ($Employee && $Employee->getId() > 0) {

                            $data['employee_id'] = $Employee->getId();

                            $Fuel = $this->getFuelPurchase($invoice_date, $row[P_INVOICE_NUMBER], $Employee->getId(), $this->gascard_type);

                            if ($Fuel->getId() > 0) {
                                echo "Skipped existing fuel record: ";
                                preprint($Fuel->toArray());
                                continue;
                            }

                            Messerve_Model_Eloquent_Fuelpurchase::create($data);
                            /*
                            $Fuel
                                ->setOptions($data)
                                ->save();
                            */
                            $data['employee'] = $Employee->getFirstname() . ' ' . $Employee->getLastname() . ' ' . $Employee->getEmployeeNumber();
                            $saved[] = $data;
                        } else {
                            $gascard_no_user[] = $row[P_GASCARD_NO];
                            $orphans[] = $data;
                        }

                    }
                }

                $this->view->saved = $saved;
                $this->view->orphans = $orphans;

                echo "<h1>SAVED : " . count($saved) . "</h1>";
            }
        }
    }

    protected function getFuelPurchase($invoice_date, $invoice_number, $employee_id, $gascard_type)
    {
        $Fuel = new Messerve_Model_Fuelpurchase();

        try {
            $Fuel->getMapper()->findOneByField(
                array(
                    'invoice_date'
                , 'invoice_number'
                , 'employee_id'
                , 'gascard_type'
                )
                , array(
                    $invoice_date
                , $invoice_number
                , $employee_id
                , $gascard_type
                )
                , $Fuel);
        } catch (Exception $exception) {
            die($exception->getMessage());
        }


        return $Fuel;
    }

    protected function getEmployeeByGascard($gascard_number)
    {
        $EmployeeMap = new Messerve_Model_Mapper_Employee();
        return $EmployeeMap->findOneByField($this->gascard_field, $gascard_number);

    }
}
