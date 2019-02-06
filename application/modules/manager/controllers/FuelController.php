<?php

class Manager_FuelController extends Zend_Controller_Action
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

        if ($this->_user_auth->type != 'admin') {
            throw new Exception('You are not allowed to access this module.');
        }
    }

    public function indexAction()
    {
        // action body
    }

    protected function caltexAction()
    {
        ini_set('memory_limit', '1G');

        /*
        define('C_GASCARD_NO', 12);
        define('C_STATEMENT_DATE', 11);
        define('C_INVOICE_DATE', 9);
        define('C_INVOICE_TIME', 10);
        define('C_PRODUCT_QUANTITY', 23);
        define('C_INVOICE_NUMBER', 8);
        define('C_STATION_NAME', 1);
        define('C_PRODUCT', 22);
        define('C_FUEL_NET', 25);
        define('C_VAT', 27);
        */

        // 2018-12-05

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

        /*
        define('C_GASCARD_NO', 9);
        define('C_STATEMENT_DATE', 8);
        define('C_INVOICE_DATE', 6);
        define('C_INVOICE_TIME', 7);
        define('C_PRODUCT_QUANTITY', 21);
        define('C_INVOICE_NUMBER', 5);
        define('C_STATION_NAME', 3);
        define('C_PRODUCT', 20);
        define('C_FUEL_NET', 24);
        define('C_VAT', 25);
        */

        if ($this->_request->isPost()) {
            set_time_limit(0);
            $upload = new Zend_File_Transfer_Adapter_Http();

            $upload->setDestination('/tmp');


            if (!$upload->receive()) {
                $messages = $upload->getMessages();
                die(implode("\n", $messages));
            } else {

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

                    /*if($i < 3) {
                        $i++; continue; // skip header row

                    }*/

                    // preprint($row, true);

                    if (!isset($row[C_GASCARD_NO]) || $row[C_GASCARD_NO] == '') {
                        echo "No gas card number<br/>";
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
                            $invoice_date = \Carbon\Carbon::createFromFormat('d/m/Y His', $full_date)->toDateTimeString();
                        } catch (Exception $exception) {
                            echo "Invalid date d/m/Y His -- " . $full_date . "...";
                        }

                        try {
                            $invoice_date = \Carbon\Carbon::createFromFormat('d/m/Y H:i', $full_date)->toDateTimeString();
                        } catch (Exception $exception) {
                            echo "Invalid date d/m/Y H:i -- " . $full_date . "...";
                        }

                        try {
                            $invoice_date = \Carbon\Carbon::createFromFormat('d/m/Y H:i:s', $full_date)->toDateTimeString();
                        } catch (Exception $exception) {
                            echo "Invalid date d/m/Y H:i -- " . $full_date . "...";
                        }


                        /*
                        try {
                            $invoice_date = \Carbon\Carbon::createFromFormat('m/d/Y H:i', $row[C_INVOICE_DATE] . ' ' . $row[C_INVOICE_TIME]);
                        } catch (Exception $exception) {
                            echo "Invalid date m/d/Y H:i -- " . $row[C_INVOICE_DATE] . ' ' . $row[C_INVOICE_TIME] . "...";
                            // continue;
                        }
                        */

                        try {
                            if ($invoice_date->year >= \Carbon\Carbon::now()->year) {
                                throw new Exception('HALT.  Invoice date is in the future! At line ' . $row_count);
                            }
                        } catch (Exception $exception) {
                            $invoice_date = false;
                        }


                        if (!$invoice_date && is_numeric($row[C_GASCARD_NO])) {
                            throw new Exception('Invoice date is invalid! Expecting DD/MM/YYYY HH:MM:SS but got: ' . $full_date . ' at line ' . $row_count);
                            echo "No valid invoice date found; skipping. <br>";
                            continue;
                        }

                        $statement_date = false;

                        /*
                        try {
                            $statement_date = \Carbon\Carbon::createFromFormat('d/m/Y', $row[C_STATEMENT_DATE])->toDateString();
                        } catch (Exception $exception) {
                            echo "Invalid statement date d/m/Y -- " . $row[C_STATEMENT_DATE] . "...";
                        }

                         */
                        try {
                            $statement_date = \Carbon\Carbon::createFromFormat('m/d/Y', $row[C_STATEMENT_DATE])->toDateString();
                        } catch (Exception $exception) {
                            echo "Invalid statement date m/d/Y -- " . $row[C_STATEMENT_DATE] . "...";
                        }

                        if (!$statement_date) {
                            echo "No valid statement date found; skipping. <br>";
                            continue;
                        }
                        // echo "$invoice_date : $statement_date<br>"; continue;


                        $data = array(
                            'gascard' => $row[C_GASCARD_NO]
                        , 'raw_invoice_date' => $invoice_date
                        , 'statement_date' => $statement_date
                        , 'invoice_date' => $invoice_date
                        , 'product_quantity' => $row[C_PRODUCT_QUANTITY]
                        , 'invoice_number' => $row[C_INVOICE_NUMBER]
                        , 'station_name' => $row[C_STATION_NAME]
                        , 'product' => $row[C_PRODUCT]
                        , 'fuel_cost' => $row[C_FUEL_NET] + $row[C_VAT]
                        );

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
                            $Fuel = new Messerve_Model_Fuelpurchase();

                            $Fuel->getMapper()->findOneByField(
                                array(
                                    'invoice_date'
                                , 'invoice_number'
                                , 'employee_id'
                                )
                                , array(
                                    $invoice_date
                                , $row[C_INVOICE_NUMBER]
                                , $Employee->id
                                )
                                , $Fuel);

                            if ($Fuel->getId() > 0) {
                                echo "Skipped existing fuel record: ";
                                preprint($Fuel->toArray());
                                continue;
                            }

                            $Fuel
                                ->setOptions($data)
                                ->setEmployeeId($Employee->id)
                                ->save();

                            $data['employee'] = $Employee->firstname . ' ' . $Employee->lastname . ' ' . $Employee->employee_number;
                            $saved[] = $data;
                        } else {
                            $gascard_no_user[] = $row[C_GASCARD_NO];
                            $orphans[] = $data;
                        }

                    } else {
                        // echo "Skipping: " .  preprint($row);
                    }

                }

                $this->view->saved = $saved;
                $this->view->orphans = $orphans;

                echo "<h1>SAVED : " . count($saved) . "</h1>";
            }
        }
    }

    public function importAction() // Petron import
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

                /*
                 *  ORIGINAL MAP
                 *
                 *
                 $data = array(
                    'gascard' => $row[7]
                , 'raw_invoice_date' => $raw_invoice_date
                , 'statement_date' => $statement_date
                , 'invoice_date' => $invoice_date
                , 'product_quantity' => $row[17]
                , 'invoice_number' => $row[15]
                , 'station_name' => $row[13]
                , 'product' => $row[16]
                , 'fuel_cost' => $row[18]
                );
                */

                foreach ($file as $row) {
                    array_map('trim', $row);

                    /*if($i < 3) {
                        $i++; continue; // skip header row
                    }*/

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
                            'gascard' => $row[P_GASCARD_NO]
                        , 'raw_invoice_date' => $raw_invoice_date
                        , 'statement_date' => $statement_date
                        , 'invoice_date' => $invoice_date
                        , 'product_quantity' => $row[P_PRODUCT_QUANTITY]
                        , 'invoice_number' => $row[P_INVOICE_NUMBER]
                        , 'station_name' => $row[P_STATION_NAME]
                        , 'product' => $row[P_PRODUCT]
                        , 'fuel_cost' => $row[P_FUEL_COST]
                        );

                        if (in_array($row[P_GASCARD_NO], $gascard_no_user)) {
                            $orphans[] = $data;
                            continue;
                        }

                        if (isset($gascard_employee[$row[P_GASCARD_NO]])) {
                            $Employee = $gascard_employee[$row[P_GASCARD_NO]];
                        } else {
                            $EmployeeMap = new Messerve_Model_Mapper_Employee();
                            $Employee = $EmployeeMap->findOneByField('gascard', $row[P_GASCARD_NO]);
                            $gascard_employee[$row[P_GASCARD_NO]] = $Employee;
                        }

                        if ($Employee && $Employee->getId() > 0) {
                            $Fuel = new Messerve_Model_Fuelpurchase();

                            $Fuel->getMapper()->findOneByField(
                                array(
                                    'invoice_date'
                                , 'invoice_number'
                                , 'employee_id'
                                )
                                , array(
                                    $invoice_date
                                , $row[P_INVOICE_NUMBER]
                                , $Employee->getId()
                                )
                                , $Fuel);

                            if ($Fuel->getId() > 0) {
                                echo "Skipped existing fuel record: ";
                                preprint($Fuel->toArray());
                                continue;
                            }

                            $Fuel
                                ->setOptions($data)
                                ->setEmployeeId($Employee->getId())
                                ->save();

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
}
