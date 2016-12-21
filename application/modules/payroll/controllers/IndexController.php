<?php

class Payroll_IndexController extends Zend_Controller_Action
{
    protected $_employee_payroll, $_employer_bill, $_messerve_bill;
    protected $_client, $_pay_period, $_fuelcost, $_last_date;
    protected $_user_auth, $_config;

    public function init()
    {
        $storage = new Zend_Auth_Storage_Session();
        $data = $storage->read();

        if (!$data) {
            $this->_redirect('auth/login');
        }

        $this->_user_auth = $data;

        $this->view->user_auth = $this->_user_auth;

        if ($this->_user_auth->type != 'admin' && $this->_user_auth->type != 'accounting') {
            throw new Exception('You are not allowed to access this module.');
        }

        $this->_fuelcost = $this->_request->getParam('fuelcost');

        $_SESSION['fuelcost'] = $this->_fuelcost;

        $this->_config = Zend_Registry::get('config');
    }

    public function indexAction()
    {
        $day = date('d');
        $last_month = date('m', strtotime('last month'));

        if ($day > 15) {
            $period_covered = date("Y-m-01");
            $period_end = date("Y-m-15");
        } else {
            $period_covered = date("Y-$last_month-16");
            $period_end = date('Y-m-d', strtotime('next month -1 day', strtotime(date("Y-$last_month"))));
        }

        $this->view->period_covered = $period_covered;
        $this->view->period_end = $period_end;

        $PayrollTempDb = new Messerve_Model_DbTable_PayrollTemp();

        $select = $PayrollTempDb->select();
        $select->group(array('period_covered'))->order('period_covered DESC');

        $all_periods = array();

        foreach ($PayrollTempDb->fetchAll($select) as $period) {
            $all_periods[] = $period->period_covered;
        }

        $this->view->old_periods = $all_periods;

        $messerve_config = $this->_config->get('messerve');

        $this->view->api_host = $messerve_config->api_host;


    }

    public function clientreportAction()
    {
        $this->_compute();

        $client = $this->_client;

        $group_id = $this->_request->getParam('group_id');

        $Group = new Messerve_Model_Group();

        $Group->find($group_id);

        $ClientRate = new Messerve_Model_RateClient();

        // TODO:  find by date!

        $RateSchedule = new Messerve_Model_EmployeeRateSchedule();
        $rates = $RateSchedule->getMapper()->fetchList("group_id = $group_id", "date_active DESC");

        if (count($rates) > 0) {
            $ClientRate->find($rates[0]->getClientRateId());
        } else {
            $ClientRate->find($Group->getRateClientId());
        }

        $date_start = $this->_request->getParam('date_start');

        $cutoff_modifier = 1;

        if (strstr($date_start, '-16')) $cutoff_modifier = 0;

        $folder = realpath(APPLICATION_PATH . '/../public/export') . "/$date_start/$group_id/client/";

        $cmd = "mkdir -p $folder";

        $date_now = date("Y-m-d-hi");
        $filename = $folder . "Client_Report_{$this->_client->getName()}_{$Group->getName()}_{$group_id}_{$date_start}_{$date_now}.pdf";

        shell_exec($cmd);

        $client_rate_array = array(
            'reg' => $ClientRate->getReg()
        , 'reg_nd' => $ClientRate->getRegNd()
        , 'reg_ot' => $ClientRate->getRegOt()
        , 'reg_nd_ot' => $ClientRate->getRegNdOt()

        , 'spec' => $ClientRate->getSpec()
        , 'spec_nd' => $ClientRate->getSpecNd()
        , 'spec_ot' => $ClientRate->getSpecOt()
        , 'spec_nd_ot' => $ClientRate->getSpecNdOt()

        , 'legal' => $ClientRate->getLegal()
        , 'legal_nd' => $ClientRate->getLegalNd()
        , 'legal_ot' => $ClientRate->getLegalOt()
        , 'legal_nd_ot' => $ClientRate->getLegalNdOt()


        , 'rest' => $ClientRate->getSpec()
        , 'rest_nd' => $ClientRate->getSpecNd()
        , 'rest_ot' => $ClientRate->getSpecOt()
        , 'rest_nd_ot' => $ClientRate->getSpecNdOt()

        , 'legal_unattend' => $ClientRate->getLegalUnattend()
        );

        $total_bill = array(
            'reg' => $this->_employer_bill['reg'] * $ClientRate->getReg()
        , 'reg_nd' => $this->_employer_bill['reg_nd'] * $ClientRate->getRegNd()
        , 'reg_ot' => $this->_employer_bill['reg_ot'] * $ClientRate->getRegOt()
        , 'reg_nd_ot' => $this->_employer_bill['reg_nd_ot'] * $ClientRate->getRegNdOt()

        , 'spec' => $this->_employer_bill['spec'] * $ClientRate->getSpec()
        , 'spec_nd' => $this->_employer_bill['spec_nd'] * $ClientRate->getSpecNd()
        , 'spec_ot' => $this->_employer_bill['spec_ot'] * $ClientRate->getSpecOt()
        , 'spec_nd_ot' => $this->_employer_bill['spec_nd_ot'] * $ClientRate->getSpecNdOt()

        , 'legal' => $this->_employer_bill['legal'] * $ClientRate->getLegal()
        , 'legal_nd' => $this->_employer_bill['legal_nd'] * $ClientRate->getLegalNd()
        , 'legal_ot' => $this->_employer_bill['legal_ot'] * $ClientRate->getLegalOt()
        , 'legal_nd_ot' => $this->_employer_bill['legal_nd_ot'] * $ClientRate->getLegalNdOt()


        , 'rest' => $this->_employer_bill['rest'] * $ClientRate->getSpec()
        , 'rest_nd' => $this->_employer_bill['rest_nd'] * $ClientRate->getSpecNd()
        , 'rest_ot' => $this->_employer_bill['rest_ot'] * $ClientRate->getSpecOt()
        , 'rest_nd_ot' => $this->_employer_bill['rest_nd_ot'] * $ClientRate->getSpecNdOt()

        , 'legal_unattend' => $this->_employer_bill['legal_unattend'] * $ClientRate->getLegalUnattend()
        );

        $hours_label = array(
            'reg' => 'RH'
        , 'reg_nd' => 'RND '
        , 'reg_ot' => 'ROT'
        , 'reg_nd_ot' => 'RNDOT'

        , 'spec' => 'SPH'
        , 'spec_nd' => 'SPHND'
        , 'spec_ot' => 'SPHOT'
        , 'spec_nd_ot' => 'SPHNDOT'

        , 'legal' => 'LH'
        , 'legal_nd' => 'LHND'
        , 'legal_ot' => 'LHOT'
        , 'legal_nd_ot' => 'LHNDOT'


        , 'legal_unattend' => 'UNLH'

        , 'rest' => 'RST/SUN'
        , 'rest_nd' => 'RSTND'
        , 'rest_ot' => 'RSTOT'
        , 'rest_nd_ot' => 'RSTNDOT'
        );

        $hours_description = array(
            'reg' => 'Reg. Hours'
        , 'reg_nd' => 'Reg. Hours ND '
        , 'reg_ot' => 'Reg. Overtime'
        , 'reg_nd_ot' => 'Reg. ND/OT'

        , 'spec' => 'Special Holiday'
        , 'spec_nd' => 'Special Holiday ND'
        , 'spec_ot' => 'Special Holiday OT'
        , 'spec_nd_ot' => 'Special Holiday ND/OT'

        , 'legal' => 'Legal Holiday'
        , 'legal_nd' => 'Legal Holiday ND'
        , 'legal_ot' => 'Legal Holiday OT'
        , 'legal_nd_ot' => 'Legal Holiday ND/OT'


        , 'legal_unattend' => 'Unworked Legal Holiday'

        , 'rest' => 'Restday/Sunday'
        , 'rest_nd' => 'Restday/Sunday ND'
        , 'rest_ot' => 'Restday/Sunday OT'
        , 'rest_nd_ot' => 'Restday/Sunday ND/OT'
        );

        $this->view->client = $this->_client;
        $this->view->group = $Group;
        $this->view->bill = $total_bill;
        $this->view->hours = $this->_employer_bill;

        /* PDF */

        $template = realpath(APPLICATION_PATH . "/../library/Templates/oh.pdf");
        if (!file_exists($template)) die($template . ': template does not exist.');

        $pdf = Zend_Pdf::load($template);
        $page = $pdf->pages[0];

        $font = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA);
        $bold = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_BOLD);
        $italic = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_ITALIC);
        $mono = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_COURIER);
        $mono_bold = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_COURIER_BOLD);

        $bill_font_size = 10;

        $pageHeight = $page->getHeight();

        $dim_y = $pageHeight - 36;
        $total = 0;

        $dim_x = 46;
        $dim_y -= 75;

        $page->setFont($font, 10)->drawText("Address: 91 Congressional Avenue, Brgy. Bahay Toro, Project 8, Quezon City", $dim_x, $dim_y, 'UTF8');
        $dim_y -= 14;
        $page->setFont($font, 10)->drawText("VAT REG. TIN NO. 219-634-798-000", $dim_x, $dim_y, 'UTF8');
        $dim_y -= 14;
        $page->setFont($bold, 10)->drawText("BILLING", $dim_x, $dim_y, 'UTF8');
        $dim_y -= 14;
        $carr_return_dim_y = $dim_y;

        $total_hours = 0;
        $total_amount = 0;


        $page->setFont($font, 10)->drawText("Client:", $dim_x, $dim_y, 'UTF8');

        if ($Group->getBillingName() != '') {
            $page->setFont($bold, 10)->drawText($Group->getBillingName(), $dim_x + 50, $dim_y, 'UTF8');
            $dim_y -= 12;
            $page->setFont($bold, 10)->drawText($Group->getName(), $dim_x + 50, $dim_y, 'UTF8');
        } else {
            $page->setFont($bold, 10)->drawText($this->_client->getName() . ' ' . $Group->getName(), $dim_x + 50, $dim_y, 'UTF8');
        }

        $dim_y -= 12;

        $page->setFont($font, 10)->drawText("Address:", $dim_x, $dim_y, 'UTF8');

        $lines = explode("\n", $Group->getAddress());

        foreach ($lines as $line) {
            $page->setFont($font, 10)->drawText($line, $dim_x + 50, $dim_y);
            $dim_y -= 12;
        }

        $page->setFont($font, 10)->drawText("TIN:", $dim_x, $dim_y, 'UTF8');
        $page->setFont($font, 10)->drawText($Group->getTin(), $dim_x + 50, $dim_y);

        $pay_period = str_replace('_', ' to ', $this->_request->getParam('pay_period'));

        $billing_number = date('Y', strtotime($date_start)) . str_pad($this->_client->getId(), 2, 0, STR_PAD_LEFT)
            . str_pad($group_id, 2, 0, STR_PAD_LEFT) . ((date('m', strtotime($date_start)) * 2) - $cutoff_modifier);

        $dim_y = $carr_return_dim_y;

        $page->setFont($font, 10)->drawText("Billing No:", $dim_x + 300, $dim_y, 'UTF8');
        $page->setFont($font, 10)->drawText($billing_number . "-A", $dim_x + 380, $dim_y);

        $dim_y -= 12;
        $dim_y -= 12;

        $page->setFont($font, 10)->drawText("Billing Period:", $dim_x + 300, $dim_y, 'UTF8');
        $good_pay_period = $date_start . " to " . $this->_request->getParam("date_end");
        $page->setFont($font, 10)->drawText($good_pay_period, $dim_x + 380, $dim_y);

        $dim_y -= 12;
        $dim_y -= 12;


        $page->setFont($font, 10)->drawText("Billing date:", $dim_x + 300, $dim_y, 'UTF8');
        $page->setFont($font, 10)->drawText(date('Y-m-d'), $dim_x + 380, $dim_y);

        $dim_y -= 12;
        $dim_y -= 8;

        $page->setFont($bold, $bill_font_size)->drawText("Hrs code", $dim_x - 2, $dim_y);
        $page->setFont($bold, $bill_font_size)->drawText("Description", $dim_x + 80, $dim_y);
        $page->setFont($bold, $bill_font_size)->drawText("No of hrs", $dim_x + 172, $dim_y);
        $page->setFont($bold, $bill_font_size)->drawText("Rate per hr", $dim_x + 220, $dim_y);
        $page->setFont($bold, $bill_font_size)->drawText("Gross amount", $dim_x + 283, $dim_y);
        $page->setFont($bold, $bill_font_size)->drawText("Net of VAT", $dim_x + 368, $dim_y);
        $page->setFont($bold, $bill_font_size)->drawText("Output VAT", $dim_x + 448, $dim_y);

        $dim_y -= 12;
        $dim_y -= 8;

        function romanic_number($integer, $upcase = true)
        {
            $table = array('M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400, 'C' => 100, 'XC' => 90, 'L' => 50, 'XL' => 40, 'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1);
            $return = '';
            while ($integer > 0) {
                foreach ($table as $rom => $arb) {
                    if ($integer >= $arb) {
                        $integer -= $arb;
                        $return .= $rom;
                        break;
                    }
                }
            }

            return $return;
        }

        $i = 0;

        foreach ($total_bill as $key => $value) {
            $i++;

            $page->setFont($font, $bill_font_size)->drawText(str_pad(romanic_number($i, true), 5, ' ', STR_PAD_RIGHT), $dim_x - 28, $dim_y);

            $page->setFont($font, $bill_font_size)->drawText($hours_label[$key], $dim_x, $dim_y);

            $page->setFont($font, $bill_font_size)->drawText($hours_description[$key]
                , $dim_x + 60, $dim_y);

            if ($this->_employer_bill[$key] > 0) {
                $page->setFont($mono, $bill_font_size)->drawText(str_pad(number_format(round($this->_employer_bill[$key], 2), 2), 8, ' ', STR_PAD_LEFT)
                    , $dim_x + 165, $dim_y);


                $page->setFont($mono, $bill_font_size)->drawText(str_pad(round($client_rate_array[$key], 2), 8, ' ', STR_PAD_LEFT)
                    , $dim_x + 220, $dim_y);

                $total_hours += round($this->_employer_bill[$key], 2);
            }

            if ($value > 0) {
                $page->setFont($mono, $bill_font_size)->drawText(str_pad(number_format($value, 2), 12, ' ', STR_PAD_LEFT), $dim_x + 280, $dim_y);
                $total_amount += round($value, 2);

                if (!$client->getNoVat() > 0) {
                    $vat_net = $value / 1.12;

                    $page->setFont($mono, $bill_font_size)->drawText(str_pad(number_format($vat_net, 2), 12, ' ', STR_PAD_LEFT)
                        , $dim_x + 355, $dim_y);

                    $page->setFont($mono, $bill_font_size)->drawText(str_pad(number_format($vat_net * 0.12, 2), 12, ' ', STR_PAD_LEFT)
                        , $dim_x + 435, $dim_y);

                }

            }

            $dim_y -= 17;
        }

        $dim_y -= 0;

        $page->setFont($bold, $bill_font_size)->drawText('TOTAL', $dim_x + 90, $dim_y);
        $page->setFont($mono_bold, $bill_font_size)->drawText(str_pad(round($total_hours, 2), 8, ' ', STR_PAD_LEFT), $dim_x + 165, $dim_y);
        $page->setFont($mono_bold, $bill_font_size)->drawText(str_pad(number_format($total_amount, 2), 12, ' ', STR_PAD_LEFT), $dim_x + 280, $dim_y);

        $vat_net = $total_amount / 1.12;


        if (!$client->getNoVat() > 0) $page->setFont($mono_bold, $bill_font_size)->drawText(str_pad(number_format($vat_net, 2), 12, ' ', STR_PAD_LEFT)
            , $dim_x + 355, $dim_y);

        if (!$client->getNoVat() > 0) $page->setFont($mono_bold, $bill_font_size)->drawText(str_pad(number_format($vat_net * 0.12, 2), 12, ' ', STR_PAD_LEFT)
            , $dim_x + 435, $dim_y);

        $dim_y -= 32;

        $page->setFont($font, $bill_font_size)->drawText('VATABLE SALES', $dim_x, $dim_y);


        if (!$client->getNoVat() > 0) $page->setFont($mono, $bill_font_size)->drawText(str_pad(number_format($vat_net, 2), 12, ' ', STR_PAD_LEFT), $dim_x + 285, $dim_y);

        $dim_y -= 12;

        $page->setFont($font, $bill_font_size)->drawText('VALUE ADDED TAX (VAT)', $dim_x, $dim_y);
        $page->setFont($bold, $bill_font_size)->drawText('12%', $dim_x + 185, $dim_y);

        if (!$client->getNoVat() > 0) $page->setFont($mono, $bill_font_size)->drawText(str_pad(number_format($vat_net * 0.12, 2), 12, ' ', STR_PAD_LEFT), $dim_x + 285, $dim_y);

        $dim_y -= 12;

        $page->setFont($font, $bill_font_size)->drawText('TOTAL AMOUNT PAYABLE', $dim_x, $dim_y);
        $page->setFont($mono_bold, $bill_font_size)->drawText(str_pad(number_format($total_amount, 2), 12, ' ', STR_PAD_LEFT), $dim_x + 285, $dim_y);

        $dim_y -= 12;
        $dim_y -= 12;
        $dim_y -= 12;

        $page->setFont($font, $bill_font_size)->drawText('Billing statement verified by:', $dim_x, $dim_y);

        $dim_y -= 12;
        $dim_y -= 12;
        $dim_y -= 12;
        $page->setFont($font, 8)->drawText('(Signature over printed name)', $dim_x, $dim_y);
        $page->setFont($font, 8)->drawText('Designation', $dim_x + 275, $dim_y);
        $page->setFont($font, 8)->drawText('Date received', $dim_x + 385, $dim_y);

        $dim_y -= 12;
        $dim_y -= 12;
        $dim_y -= 8;

        $page->setFont($font, $bill_font_size)->drawText('Prepared by:', $dim_x, $dim_y);
        $page->setFont($font, 8)->drawText('Noted by', $dim_x + 295, $dim_y);

        $dim_y -= 12;
        $dim_y -= 12;
        $dim_y -= 6;
        $page->setFont($font, 8)->drawText('(Signature over printed name / Date)', $dim_x, $dim_y);
        $page->setFont($font, 8)->drawText('(Signature)', $dim_x + 295, $dim_y);

        $pdf->pages[0] = $page;

        $pdf->save($filename);

        // echo $filename;
        $this->_redirect($_SERVER['HTTP_REFERER'] . '#billing');
    }

    protected function _process_client_report()
    {

    }

    protected function _process_group_attendance($group_id, $date_start, $date_end)
    {

        $employees = $this->_fetch_employees($group_id, $date_start, $date_end);

        $Payroll = new Messervelib_Payroll();

        foreach ($employees as $evalue) {
            $employee_id = $evalue->getId();

            $Attendance = new Messerve_Model_Attendance();

            $date1 = new DateTime($date_start); //inclusive
            $date2 = new DateTime($date_end); //exclusive
            $diff = $date2->diff($date1);
            $period_size = intval($diff->format("%a")) + 1;

            $current_date = $date_start;

            $current_year = date('Y', strtotime($date_start));

            $AttendanceMap = new Messerve_Model_Mapper_Attendance();

            $first_id = 0;

            for ($i = 1; $i <= $period_size; $i++) {
                // echo "$employee_id, $current_date, $group_id <br />";

                $Attendance = $AttendanceMap->findOneByField(
                    array('employee_id', 'datetime_start', 'group_id')
                    , array($employee_id, $current_date, $group_id)
                );

                if (!$Attendance) {
                    $Attendance = new Messerve_Model_Attendance();

                    $Attendance->setEmployeeId($employee_id)
                        ->setDatetimeStart($current_date)
                        ->setGroupId($group_id)
                        ->save();

                    $Attendance->find($Attendance->getId());
                }

                $data[$current_date] = array(
                    'id' => $Attendance->getId()
                , 'start_1' => $Attendance->getStart1()
                , 'end_1' => $Attendance->getEnd1()
                , 'start_2' => $Attendance->getStart2()
                , 'end_2' => $Attendance->getEnd2()
                , 'start_3' => $Attendance->getStart3()
                , 'end_3' => $Attendance->getEnd3()
                , 'extended_shift' => $Attendance->getExtendedShift()
                , 'ot_approved' => $Attendance->getOtApproved()
                , 'ot_approved_hours' => $Attendance->getOtApprovedHours()
                , 'type' => $Attendance->getType()
                );

                $current_date = date('Y-m-d', strtotime('+1 day', strtotime($current_date)));

                if ($i == 1) $first_id = $Attendance->getId();
            }

            $Payroll->save_the_day($Attendance->getEmployeeId(), $group_id, $data); // TODO:  Figure out why this needs to be called twice
            $Payroll->save_the_day($Attendance->getEmployeeId(), $group_id, $data);

        }
    }


    public function payslipsAction()
    {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        // action body
        $date_start = $this->_request->getParam('date_start');
        $date_end = $this->_request->getParam('date_end');

        $group_id = $this->_request->getParam('group_id');

        $timestamp_start = strtotime($date_start);
        $cutoff_date = date("d", $timestamp_start);

        if ($cutoff_date <= 15) {
            $cutoff = 1;
        } else {
            $cutoff = 2;
        }

        $Group = new Messerve_Model_Group();
        $Group->find($group_id);

        $Rate = new Messerve_Model_Rate();
        $Rate->find($Group->getRateId());

        $pay_rate = number_format(intval(substr($Rate->getName(), 0, -4)), 2);

        $Client = new Messerve_Model_Client();

        $Client->find($Group->getClientId());

        $this->_process_group_attendance($group_id, $date_start, $date_end);

        // die('OH : ' . __LINE__);

        $this->_compute();
        $this->_compute(); // TODO:  Fix this!  Why does it need to be ran twice. Clue:  creation of new model

        $this->view->payroll = $this->_employee_payroll;

        $pdf = new Zend_Pdf();
        $dole_pdf = new Zend_Pdf();

        $font = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA);
        $bold = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_BOLD);
        $italic = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_ITALIC);
        $mono = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_COURIER);
        $boldmono = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_COURIER_BOLD);

        $logo = Zend_Pdf_Image::imageWithPath(APPLICATION_PATH . '/../public/images/messerve.png');

        $folder = realpath(APPLICATION_PATH . '/../public/export') . "/$date_start/$group_id/payslips/";
        $cmd = "mkdir -p $folder";
        shell_exec($cmd); // Create folder

        $rec_copy_data = array(
            'branch' => $Client->getName() . '-' . $Group->getName()
        , 'riders' => array()
        );

        $bop_acknowledgement = [];

        // preprint($this->_employee_payroll,1);

        foreach ($this->_employee_payroll as $value) {
            $page = new Zend_Pdf_Page(612, 396);

            $pageHeight = $page->getHeight();
            $pageWidth = $page->getWidth();

            $dim_x = 32;
            $dim_y = $pageHeight - 25;

            $imageHeight = 41;
            $imageWidth = 119;

            $bottomPos = $dim_y - $imageHeight;
            $rightPos = $dim_x + $imageWidth;

            $page->drawImage($logo, $dim_x, $bottomPos, $rightPos, $dim_y);
            $dim_y -= 12;

            $Employee = new Messerve_Model_Employee();

            $Employee->find($value['attendance']->id);

            // Get first attendance day
            $Attendance = new Messerve_Model_Attendance();

            $Attendance = $Attendance->getMapper()->findOneByField(
                array('employee_id', 'datetime_start', 'group_id')
                , array($Employee->getId(), $date_start . ' 00:00:00', $group_id)
            );

            // Process scheduled deductions
            $bop_motorcycle = 0;
            $bop_insurance = 0;
            $bop_maintenance = 0;

            $scheduled_deductions = array();
            $scheduled_deductions_array = array();

            if ($Attendance) {
                $Attendance->setFuelCost($this->_fuelcost)->save();

                if ($group_id == $Employee->getGroupId()) { // Apply adjustments only on mother group payslip
                    $DeductionAttendanceMap = new Messerve_Model_Mapper_DeductionAttendance();
                    $raw_deductions = $DeductionAttendanceMap->fetchList('attendance_id = ' . $Attendance->getId());

                    foreach ($raw_deductions as $rdvalue) {
                        $DeductionSchedule = new Messerve_Model_DeductionSchedule();
                        $DeductionSchedule->find($rdvalue->getDeductionScheduleId());

                        $scheduled_deductions[] = array(
                            'type' => $DeductionSchedule->getType()
                        , 'amount' => $rdvalue->getAmount()
                        , 'deduction_id' => $rdvalue->getDeductionScheduleId()
                        );

                        if (isset($scheduled_deductions_array[$DeductionSchedule->getType()])) {
                            $scheduled_deductions_array[$DeductionSchedule->getType()] += $rdvalue->getAmount();
                        } else {
                            $scheduled_deductions_array[$DeductionSchedule->getType()] = $rdvalue->getAmount();
                        }
                    }

                    // if ($Group->getClientId() != 6 && $Group->getClientId() != 10) {
                        // BOP deductions

                        $BOPAttendance = new Messerve_Model_BopAttendance();
                        $BOPAttendance->find(array('bop_id' => $Employee->getBopId(), 'attendance_id' => $Attendance->getId()));

                        $BOP = new Messerve_Model_Bop();
                        $BOP->find($Employee->getBopId());

                        $scheduled_deductions[] = array(
                            'type' => 'BOP - ' . $BOP->getName()
                        , 'amount' => $BOPAttendance->getMotorcycleDeduction()
                        );

                        $scheduled_deductions[] = array(
                            'type' => 'BOP insurance/registration'
                        , 'amount' => $BOPAttendance->getInsuranceDeduction()
                        );
                    // }

                    // For BOP acknowledgement slip
                    /*

                    $bop_motorcycle = $BOPAttendance->getMotorcycleDeduction();
                    $bop_insurance = $BOPAttendance->getInsuranceDeduction();

                    if ($bop_motorcycle > 0) {
                        $bop_acknowledgement[] =
                            [
                                'employee_number' => $Employee->getEmployeeNumber()
                                , 'name' => $value['attendance']->lastname . ', '
                                . $value['attendance']->firstname . ' '
                                . $value['attendance']->middleinitial
                                , 'pay_period' => $date_start . ' to ' . $date_end
                                , 'program' => 'BOP - ' . $BOP->getName()
                                , 'amount' => $BOPAttendance->getMotorcycleDeduction()
                                , 'insurance' => $BOPAttendance->getInsuranceDeduction()
                            ];
                    }
                    */

                }
            }

            $page->setFont($font, 8)->drawText('Period #: ', $dim_x + 200, $dim_y, 'UTF8');

            $period_number = date('Y-');

            if (substr($date_start, -2) == '01') {
                $period_number .= intval(date('m')) * 2;
            } else {
                $period_number .= (intval(date('m')) * 2) + 1;
            }

            $page->setFont($bold, 8)->drawText($period_number, $dim_x + 250, $dim_y, 'UTF8');

            $page->setFont($font, 8)->drawText('Payroll period: ' . $date_start . ' to ' . $date_end, $dim_x + 340, $dim_y);

            $dim_y -= 10;
            $page->setFont($font, 8)->drawText('Rider Name: ', $dim_x + 340, $dim_y, 'UTF8');

            $page->setFont($bold, 8)->drawText(
                $value['attendance']->lastname . ', '
                . $value['attendance']->firstname . ' '
                . $value['attendance']->middleinitial, $dim_x + 393, $dim_y, 'UTF8');

            $dim_y -= 10;

            $page->setFont($font, 8)->drawText('Rider Code: ', $dim_x + 200, $dim_y, 'UTF8');
            $page->setFont($bold, 8)->drawText($Employee->getEmployeeNumber(), $dim_x + 250, $dim_y, 'UTF8');

            $reliever_text = $group_id == $Employee->getGroupId() ? '' : ' (Reliever)';
            $page->setFont($bold, 8)->drawText($Client->getName() . '-' . $Group->getName() . $reliever_text, $dim_x + 340, $dim_y, 'UTF-8');

            $rec_copy_data['riders'][$Employee->getId()] = array(
                'employee_number' => $Employee->getEmployeeNumber()
            , 'name' => $value['attendance']->lastname . ', '
                    . $value['attendance']->firstname . ' '
                    . $value['attendance']->middleinitial
            , 'pay_period' => $date_start . ' to ' . $date_end
            );

            $dim_y -= 16;
            $page->drawLine($dim_x - 2, $dim_y, $dim_x + 560, $dim_y);
            $dim_y -= 8;

            $page->setFont($bold, 8)->drawText('ACKNOWLEDGEMENT', $dim_x + 240, $dim_y);

            $dim_y -= 2;

            $page->drawRectangle($dim_x - 2, $dim_y, $dim_x + 560, 60, Zend_Pdf_Page::SHAPE_DRAW_STROKE);
            $page->drawLine($dim_x + 216, $dim_y, $dim_x + 216, 60);
            $page->drawLine($dim_x + 376, $dim_y, $dim_x + 376, 60);

            $dim_y -= 10;

            $reset_y = $dim_y;

            $PayrollLib = new Messervelib_Payroll();

            $employee_pay = $PayrollLib->GetEmployeePayroll(
                $Employee->getId()
                , $group_id
                , $this->_request->getParam('date_start')
            );

            $total_no_hours = 0;
            $total_pay = 0;
            $total_deduct = 0;
            $total_misc_deduct = 0;

            $sss_deductions = array();

            $ecola_addition = 0;
            $legal_ecola_addition = 0;

            $payroll_meta = [];
            $basic_pay = 0;

            foreach ($employee_pay as $pkey => $pvalue) {
                $ecola = 0;
                $sss = 0;


                foreach ($pvalue as $rkey => $rvalue) {

                    if ($rkey == "meta") {

                        $pay_rate = 8 * $rvalue->employee->rate->reg;

                        $ecola = $rvalue->employee->rate->ecola;
                        $sss = $rvalue->employee->rate->sss_employee;


                        $page->setFont($font, 8)->drawText('Daily rate', $dim_x, $dim_y, 'UTF8');
                        $page->setFont($mono, 8)->drawText(number_format($pay_rate, 2), $dim_x + 40, $dim_y, 'UTF8');

                        $page->setFont($font, 8)->drawText('Ecola', $dim_x + 80, $dim_y, 'UTF8');
                        $page->setFont($mono, 8)->drawText(number_format($ecola, 2), $dim_x + 110, $dim_y, 'UTF8');

                        $page->setFont($font, 8)->drawText('Min. wage', $dim_x + 140, $dim_y, 'UTF8');
                        $page->setFont($mono, 8)->drawText(number_format($pay_rate + $ecola, 2), $dim_x + 180, $dim_y, 'UTF8');
                        $dim_y -= 8;
                    } else {
                        $page->setFont($font, 8)->drawText("{$rkey}", $dim_x, $dim_y);

                        if(!isset($payroll_meta[$rkey])) $payroll_meta[$rkey] = [];


                        foreach ($rvalue as $dkey => $dvalue) {
                            $payroll_meta[$rkey][$dkey] = $dvalue;

                            $dim_y -= 8;

                            $sss_deductions[] = ($sss / 22 / 8) * $dvalue['hours'];

                            $dkey = strtoupper($dkey);

                            $total_no_hours += $dvalue['hours'];
                            $total_pay += $dvalue['pay'];

                            $page->setFont($font, 8)->drawText("{$dkey}", $dim_x + 10, $dim_y);
                            $page->setFont($mono, 8)->drawText(str_pad(number_format($dvalue['hours'], 2), 8, ' ', STR_PAD_LEFT), $dim_x + 110, $dim_y);
                            $page->setFont($mono, 8)->drawText(str_pad(number_format($dvalue['pay'], 2), 8, ' ', STR_PAD_LEFT), $dim_x + 160, $dim_y);

                        }
                    }

                    $dim_y -= 8;

                }

                $basic_pay = $total_pay;

                // Legal adjustments for attendance less than 8 hours
                $LegalAttendanceMap = new Messerve_Model_Mapper_Attendance();

                $legal_attendance = $LegalAttendanceMap->fetchListToArray("(attendance.employee_id = '{$Employee->getId()}')
                    AND (attendance.group_id = {$group_id})
                    AND datetime_start >= '{$date_start} 00:00'
                    AND datetime_start <= '{$date_end} 23:59'
                    AND (
                        legal > 0  OR legal_ot > 0
                        -- OR legal_nd_ot > 0 OR legal_nd > 0 // TODO:  This may break legal UA calcs.   Check this if it happens
                    )");

                $legal_ua_hours = 0;

                $legal_ecola_days = 0;

                foreach ($legal_attendance as $legal_day) {
                    $legal_ecola_days++;

                }

            }

            $dim_y = 92;

            $page->drawLine($dim_x - 2, $dim_y, $dim_x + 560, $dim_y);

            $dim_y = 82;

            $page->setFont($font, 8)->drawText('Total No. of hours', $dim_x, $dim_y);
            $page->setFont($mono, 8)->drawText(str_pad(number_format($total_no_hours, 2), 6, ' ', STR_PAD_LEFT), $dim_x + 110, $dim_y);

            $dim_y -= 8;
            $page->setFont($font, 8)->drawText('Total Hrs. pay', $dim_x, $dim_y);
            $page->setFont($mono, 8)->drawText(str_pad(substr(number_format($total_pay, 3), 0, -1), 8, ' ', STR_PAD_LEFT), $dim_x + 160, $dim_y);


            $dim_y = $reset_y;
            $page->setFont($bold, 8)->drawText('ADDITION', $dim_x + 220, $dim_y);

            $dim_y -= 8;
            $page->setFont($font, 8)->drawText('Total hours pay', $dim_x + 220, $dim_y);
            $page->setFont($mono, 8)->drawText(str_pad(substr(number_format($total_pay, 3), 0, -1), 10, ' ', STR_PAD_LEFT), $dim_x + 300, $dim_y);

            if ($Employee->getGroupId() == $group_id) {
                $attended_days = $this->get_cutoff_attended_days($Employee->getId(), $date_start, $date_end);
                $ecola_addition = $attended_days * $ecola;
                $total_pay += $ecola_addition;

                $dim_y -= 8;
                $page->setFont($font, 8)->drawText('ECOLA (' . $attended_days . ' day/s)', $dim_x + 220, $dim_y);
                $page->setFont($mono, 8)->drawText(str_pad(number_format($ecola_addition, 2), 10, ' ', STR_PAD_LEFT), $dim_x + 300, $dim_y);

                if ($legal_ecola_days > 0) {
                    $legal_ecola_addition = $legal_ecola_days * $ecola;
                    $dim_y -= 8;
                    $page->setFont($font, 8)->drawText('ECOLA - Legal (' . $legal_ecola_days . ' day/s)', $dim_x + 220, $dim_y);
                    $page->setFont($mono, 8)->drawText(str_pad(number_format($legal_ecola_addition, 2), 10, ' ', STR_PAD_LEFT), $dim_x + 300, $dim_y);
                    $total_pay += $legal_ecola_addition;
                }

            }

            $sss_deduction = $this->get_sss_deduction($total_pay);

            $prev_sss = 0;
            $prev_gross_pay = 0;

            if ($sss_deduction[0] < $sss_deduction[1] && $sss_deduction[0] > 0) {
                $value['deductions']['sss'] = $sss_deduction[0];
            } else {
                $value['deductions']['sss'] = $sss_deduction[1];
            }

            $sss_debug = "";

            if ($cutoff == 1) {
                // Placeholder
            } else {
                $PayrollTemp = new Messerve_Model_PayrollTemp();

                $prev_payroll_start = date("Y-m-01", $timestamp_start);

                $sss_result = $PayrollTemp->getMapper()->fetchListToArray(
                    array("period_covered >= '$prev_payroll_start'"
                    , "period_covered < '$date_start'"
                    , "group_id = $group_id"
                    , "employee_id = " . $Employee->getId())
                );

                if (count($sss_result) > 0) {
                    foreach ($sss_result as $srvalue) {
                        $prev_sss += $srvalue["sss"];
                        $prev_gross_pay = $srvalue["gross_pay"];
                    }

                    $monthly_pay = $total_pay + $prev_gross_pay;
                    $monthly_sss_array = $this->get_sss_deduction($monthly_pay);

                    if ($monthly_sss_array[0] < $monthly_sss_array[1] && $monthly_sss_array[0] > 0) {
                        $monthly_sss = $monthly_sss_array[0];
                    } else {
                        $monthly_sss = $monthly_sss_array[1];
                    }

                    $sss_bal = $monthly_sss - $prev_sss;
                    $sss_debug = "THIS PAY: $total_pay, PREV PAY: $prev_gross_pay, MONTHLY: $monthly_pay, PREV SSS: $prev_sss,  MONTHLY SSS: $monthly_sss,  SSS BAL: $sss_bal";

                    $value['deductions']['sss'] = $sss_bal;
                }

            }

            if (isset($value['more_income'])) {
                if ($value['more_income']['misc_income'] > 0) {
                    $dim_y -= 8;
                    $page->setFont($font, 8)->drawText('Misc income', $dim_x + 220, $dim_y);
                    $page->setFont($mono, 8)->drawText(str_pad(number_format($value['more_income']['misc_income'], 2), 10, ' ', STR_PAD_LEFT), $dim_x + 300, $dim_y);
                    $total_pay += $value['more_income']['misc_income'];
                }

                if ($value['more_income']['incentives'] > 0) {
                    $dim_y -= 8;
                    $page->setFont($font, 8)->drawText('SILP', $dim_x + 220, $dim_y);
                    $page->setFont($mono, 8)->drawText(str_pad(number_format($value['more_income']['incentives'], 2), 10, ' ', STR_PAD_LEFT), $dim_x + 300, $dim_y);
                    $total_pay += $value['more_income']['incentives'];
                }

                if ($value['more_income']['thirteenth_month_pay'] > 0) {
                    $dim_y -= 8;
                    $page->setFont($font, 8)->drawText('13th month pay', $dim_x + 220, $dim_y);
                    $page->setFont($mono, 8)->drawText(str_pad(number_format($value['more_income']['thirteenth_month_pay'], 2), 10, ' ', STR_PAD_LEFT), $dim_x + 300, $dim_y);
                    $total_pay += $value['more_income']['thirteenth_month_pay'];
                }

                if ($value['more_income']['paternity'] > 0) {
                    $dim_y -= 8;
                    $page->setFont($font, 8)->drawText('Paternity leave', $dim_x + 220, $dim_y);
                    $page->setFont($mono, 8)->drawText(str_pad(number_format($value['more_income']['paternity'], 2), 10, ' ', STR_PAD_LEFT), $dim_x + 300, $dim_y);
                    $total_pay += $value['more_income']['paternity'];
                }
            }

            if ($group_id == $Employee->getGroupId()) { // On parent group,  show BOP additions
                if (isset($BOPAttendance) && $BOPAttendance->getMaintenanceAddition() > 0) {
                    $dim_y -= 8;
                    $page->setFont($font, 8)->drawText('BOP Maintenance', $dim_x + 220, $dim_y);
                    $page->setFont($mono, 8)->drawText(str_pad(number_format($BOPAttendance->getMaintenanceAddition(), 2), 10, ' ', STR_PAD_LEFT), $dim_x + 300, $dim_y);
                    $total_pay += $BOPAttendance->getMaintenanceAddition();
                    $bop_maintenance = $BOPAttendance->getMaintenanceAddition();
                }
            }

            if (isset($value['more_income']) && $value['more_income']['gasoline'] > 0) {
                $dim_y -= 8;
                $page->setFont($font, 8)->drawText('Gasoline', $dim_x + 220, $dim_y);
                $page->setFont($mono, 8)->drawText(str_pad(number_format($value['more_income']['gasoline'], 2), 10, ' ', STR_PAD_LEFT), $dim_x + 300, $dim_y);

                $total_pay += $value['more_income']['gasoline'];

                $dim_y -= 8;
                $page->setFont($font, 8)->drawText(' - Allotment L', $dim_x + 220, $dim_y);
                $page->setFont($mono, 8)->drawText(str_pad($Attendance->getFuelAlloted(), 10, ' ', STR_PAD_LEFT), $dim_x + 260, $dim_y);

                $dim_y -= 8;
                $page->setFont($font, 8)->drawText(' - Consumed L', $dim_x + 220, $dim_y);
                $page->setFont($mono, 8)->drawText(str_pad($Attendance->getFuelConsumed(), 10, ' ', STR_PAD_LEFT), $dim_x + 260, $dim_y);

            }

            $dim_y = 82;

            $page->setFont($font, 8)->drawText('TOTAL ADDITION', $dim_x + 220, $dim_y);
            $page->setFont($mono, 8)->drawText(str_pad(number_format($total_pay, 2), 10, ' ', STR_PAD_LEFT), $dim_x + 300, $dim_y);

            $dim_y = 136;
            $dim_y -= 8;
            $page->drawLine($dim_x + 300, $dim_y, $dim_x + 350, $dim_y);

            $dim_y = 128;
            $dim_y -= 8;
            $page->setFont($mono, 8)->drawText(str_pad(number_format($total_pay, 2), 10, ' ', STR_PAD_LEFT), $dim_x + 300, $dim_y);

            $dim_y = $reset_y;
            $page->setFont($bold, 8)->drawText('DEDUCTION', $dim_x + 380, $dim_y);

            $dim_y -= 8;

            foreach ($value['deductions'] as $pkey => $pvalue) {
                if ($pvalue > 0) {
                    $total_deduct += $pvalue;
                    $page->setFont($font, 8)->drawText(ucwords(str_replace('_', ' ', $pkey)), $dim_x + 380, $dim_y);
                    $page->setFont($mono, 8)->drawText(str_pad(number_format($pvalue, 2), 10, ' ', STR_PAD_LEFT), $dim_x + 480, $dim_y);
                    $dim_y -= 8;
                }
            }

            $split_dim_y = $dim_y;


            /* Start Messerve deductions */

            $messerve_deduct = 0;

            $fuel_overage = 0;  // Reset
            $fuel_deduction = 0;  // Reset

            // if ($Group->getClientId() != 6 && $Group->getClientId() != 10) {

                if ($Attendance->getFuelHours() > 0) {

                    $fuel_overage = $Attendance->getFuelConsumed() - $Attendance->getFuelAlloted();

                    if ($fuel_overage > 0) {
                        $fuel_deduction = round($fuel_overage * $Attendance->getFuelCost(), 2);
                        $messerve_deduct += $fuel_deduction;
                        // $total_deduct += $fuel_deduction;
                    }
                }
            // }
            // $messerve_deduct = 0;

            $dim_y -= 10;

            $dole_page = clone $page;

            // Scheduled deductions
            if (count($scheduled_deductions) > 0) {
                foreach ($scheduled_deductions as $sdvalue) {
                    if ($sdvalue['amount'] > 0) {
                        $messerve_deduct += $sdvalue['amount'];
                        $total_misc_deduct += $sdvalue['amount'];

                        $page->setFont($font, 8)->drawText(ucwords(str_replace('_', ' ', $sdvalue['type'])), $dim_x + 380, $dim_y);

                        $page->setFont($mono, 8)->drawText(str_pad(number_format($sdvalue['amount'], 2), 10, ' ', STR_PAD_LEFT), $dim_x + 480, $dim_y);

                        $dim_y -= 10;
                    }
                }

                $dim_y -= 10;
            }

            if ($fuel_overage > 0) {
                // $dim_y = $split_dim_y;

                $page->setFont($font, 8)->drawText('Fuel overage', $dim_x + 380, $dim_y);
                $page->setFont($mono, 8)->drawText(str_pad($fuel_deduction, 10, ' ', STR_PAD_LEFT), $dim_x + 480, $dim_y);
                $dim_y -= 8;

                $page->setFont($font, 8)->drawText(' - Allotment L', $dim_x + 380, $dim_y);
                $page->setFont($mono, 8)->drawText(str_pad($Attendance->getFuelAlloted(), 10, ' ', STR_PAD_LEFT), $dim_x + 440, $dim_y);
                $dim_y -= 8;

                $page->setFont($font, 8)->drawText(' - Consumed L', $dim_x + 380, $dim_y);
                $page->setFont($mono, 8)->drawText(str_pad($Attendance->getFuelConsumed(), 10, ' ', STR_PAD_LEFT), $dim_x + 440, $dim_y);

                $dim_y -= 8;
            }

            if ($messerve_deduct > 0) {
                $dole_page->setFont($font, 8)->drawText('Other deductions', $dim_x + 380, $split_dim_y);
                $dole_page->setFont($mono, 8)->drawText(str_pad(number_format($messerve_deduct, 2), 10, ' ', STR_PAD_LEFT), $dim_x + 480, $split_dim_y);
            }


            $total_deduct += $messerve_deduct;
            /* End Messerve deductions */

            $dim_y = 82;
            $dim_y -= 8;

            $page->setFont($font, 8)->drawText('TOTAL DEDUCTION', $dim_x + 220, $dim_y);
            $page->setFont($mono, 8)->drawText(str_pad(number_format($total_deduct, 2), 10, ' ', STR_PAD_LEFT), $dim_x + 300, $dim_y);
            $dole_page->setFont($font, 8)->drawText('TOTAL DEDUCTION', $dim_x + 220, $dim_y);
            $dole_page->setFont($mono, 8)->drawText(str_pad(number_format($total_deduct, 2), 10, ' ', STR_PAD_LEFT), $dim_x + 300, $dim_y);


            // TODO:  Fix hacky hack
            $PayrollTemp = new Messerve_Model_PayrollTemp();

            $PayrollTemp->getMapper()->getDbTable()
                ->delete("group_id = " . $Group->getId() . " AND period_covered = '$date_start' AND employee_id = " . $Employee->getId());
            // Delete prior record

            if (!$total_pay > 0) continue;

            $dim_y = 136;
            $dim_y -= 8;
            $page->drawLine($dim_x + 480, $dim_y, $dim_x + 530, $dim_y);
            $dole_page->drawLine($dim_x + 480, $dim_y, $dim_x + 530, $dim_y);

            $dim_y = 128;
            $dim_y -= 8;
            $page->setFont($mono, 8)->drawText(str_pad(number_format($total_deduct, 2), 10, ' ', STR_PAD_LEFT), $dim_x + 480, $dim_y);
            $dole_page->setFont($mono, 8)->drawText(str_pad(number_format($total_deduct, 2), 10, ' ', STR_PAD_LEFT), $dim_x + 480, $dim_y);

            $dim_y = 82;
            $net_pay = $total_pay - $total_deduct;
            $page->setFont($bold, 10)->drawText('Net pay', $dim_x + 380, $dim_y);
            $dole_page->setFont($bold, 10)->drawText('Net pay', $dim_x + 380, $dim_y);

            $dim_y -= 16;
            $page->setFont($boldmono, 12)->drawText('Php ' . str_pad(number_format($net_pay, 2), 10, ' ', STR_PAD_LEFT), $dim_x + 380, $dim_y);
            $dole_page->setFont($boldmono, 12)->drawText('Php ' . str_pad(number_format($net_pay, 2), 10, ' ', STR_PAD_LEFT), $dim_x + 380, $dim_y);

            $page->setFont($font, 8)->drawText('Pilipinas Messerve Inc.  |  4/F San Diego Building, 462 Carlos Palanca St., Quiapo, Manila 1001', $dim_x, 24);
            $dole_page->setFont($font, 8)->drawText('Pilipinas Messerve Inc.  |  4/F San Diego Building, 462 Carlos Palanca St., Quiapo, Manila 1001', $dim_x, 24);

            $pdf->pages[] = $page;
            $dole_pdf->pages[] = $dole_page;

            /*
            $pay_array = array(
                $value['attendance']->lastname
                , $value['attendance']->firstname
                , $value['attendance']->middleinitial
                , $value['attendance']->employee_number
                , $value['attendance']->account_number
                , round($total_pay - $total_deduct, 2)
            );
            */

            if ($group_id == $Employee->getGroupId()) {
                $is_reliever = 'no';
            } else {
                $is_reliever = 'yes';
            }

            $scheduled_deductions["sss_pair"] = $sss_deduction;
            $scheduled_deductions["sss_debug"] = $sss_debug;


            // preprint(json_encode($payroll_meta),1);

            $PayrollTemp->setEmployeeId($Employee->getId())
                ->setGroupId($group_id)
                ->setEmployeeNumber($Employee->getEmployeeNumber())
                ->setPeriodCovered($date_start)
                ->setFirstname($Employee->getFirstname())
                ->setMiddlename($Employee->getMiddleinitial())
                ->setLastname($Employee->getLastname())
                ->setClientName($Client->getName())
                ->setGroupName($Group->getName())
                ->setAccountNumber($Employee->getAccountNumber())
                ->setPayrollMeta(json_encode($payroll_meta))
                ->setPaternity($value['more_income']['paternity'])
                ->setGrossPay($total_pay)
                ->setBasicPay($basic_pay)
                ->setNetPay($net_pay)
                ->setEcola($ecola_addition + $legal_ecola_addition)
                ->setSss($value['deductions']['sss'])
                ->setPhilhealth($value['deductions']['philhealth'])
                ->setHdmf($value['deductions']['hdmf'])
                ->setCashBond($value['deductions']['cash_bond'])
                ->setInsurance($value['deductions']['insurance'])
                ->setMiscDeduction($total_misc_deduct)
                ->setDeductionData(json_encode($scheduled_deductions))
                ->setSssLoan($scheduled_deductions_array['sss_loan'])
                ->setHdmfLoan($scheduled_deductions_array['hdmf_loan'])
                ->setUniform($scheduled_deductions_array['uniform'])
                ->setAccident($scheduled_deductions_array['accident'])
                ->setAdjustment($scheduled_deductions_array['adjustment'])
                ->setMiscellaneous($scheduled_deductions_array['misc'])
                ->setCommunication($scheduled_deductions_array['communication'])
                ->setLostCard($scheduled_deductions_array['lost_card'])
                ->setFood($scheduled_deductions_array['food'])
                ->setMiscAddition($value['more_income']['misc_income'])
                ->setBopInsurance($bop_insurance)
                ->setBopMotorcycle($bop_motorcycle)
                ->setBopMaintenance($bop_maintenance)
                ->setFuelOverage($fuel_overage)
                ->setFuelAddition($value['more_income']['gasoline'])
                ->setFuelDeduction($fuel_deduction)
                ->setFuelAllotment($Attendance->getFuelAlloted())
                ->setFuelUsage($Attendance->getFuelConsumed())
                ->setFuelHours($Attendance->getFuelHours())
                ->setFuelPrice($Attendance->getFuelCost())
                ->setThirteenthMonth($value['more_income']['thirteenth_month_pay'])
                ->setIncentives($value['more_income']['incentives'])
                ->setIsReliever($is_reliever);

            $PayrollTemp->save();

        }

        $date_start = $this->_request->getParam('date_start');

        $group_id = $this->_request->getParam('group_id');

        $Group = new Messerve_Model_Group();

        $Group->find($group_id);

        $this->_last_date = date('Y-m-d-Hi');

        $filename = $folder . "Payslips_{$this->_client->getName()}_{$Group->getName()}_{$group_id}_{$date_start}_{$this->_last_date}.pdf";

        $this->_receiving_copy($pdf, $rec_copy_data);
        $this->_bop_acknowledgement($pdf, $bop_acknowledgement);

        mkdir($folder . 'dole', 0777);

        $dole_filename = $folder . "dole/Payslips_{$this->_client->getName()}_{$Group->getName()}_{$group_id}_{$date_start}_{$this->_last_date}.pdf";

        $pdf->save($filename);
        $dole_pdf->save($dole_filename);


        $this->summaryreportAction();
        $this->clientreportAction();

        if ($this->_request->getParam("is_ajax") != "true") {
            $this->_redirect($_SERVER['HTTP_REFERER']);
        } else {
            echo "AJAX Complete";
        }

    }

    protected function _bop_acknowledgement($pdf, $bop_data)
    {


        /*
                                 [
                            'employee_number' => $Employee->getEmployeeNumber()
                            , 'name' => $value['attendance']->lastname . ', '
                            . $value['attendance']->firstname . ' '
                            . $value['attendance']->middleinitial
                            , 'pay_period' => $date_start . ' to ' . $date_end
                            , 'program' => 'BOP - ' . $BOP->getName()
                            , 'amount' => $BOPAttendance->getMotorcycleDeduction()
                            , 'insurance' => $BOPAttendance->getInsuranceDeduction()
                        ]
         */
        foreach ($bop_data as $bop) {
            $page = new Zend_Pdf_Page(612, 199);
            $logo = Zend_Pdf_Image::imageWithPath(APPLICATION_PATH . '/../public/images/messerve.png');
            $font = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA);
            $bold = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_BOLD);

            $pageHeight = $page->getHeight();

            $imageHeight = 41;
            $imageWidth = 119;

            $dim_x = 32;
            $dim_y = $pageHeight - 25;

            $bottomPos = $dim_y - $imageHeight;
            $rightPos = $dim_x + $imageWidth;

            $page->drawImage($logo, $dim_x, $bottomPos, $rightPos, $dim_y);
            $dim_y -= 24;
            $dim_y -= 24;
            $dim_y -= 12;
            $page->setFont($bold, 10)->drawText("ACKNOWLEDGEMENT", $dim_x, $dim_y);
            $dim_y -= 24;
            $page->setFont($font, 8)->drawText("{$bop['employee_number']}   {$bop['name']}", $dim_x, $dim_y);
            $page->setFont($font, 8)->drawText("Pay period: {$bop['pay_period']}", $dim_x + 180, $dim_y);
            $dim_y -= 12;
            $page->setFont($font, 8)->drawText("{$bop['program']}", $dim_x, $dim_y);
            $page->setFont($font, 8)->drawText("{$bop['amount']}", $dim_x + 180, $dim_y);
            $dim_y -= 12;
            $page->setFont($font, 8)->drawText("Insurance/registration", $dim_x, $dim_y);
            $page->setFont($font, 8)->drawText("{$bop['insurance']}", $dim_x + 180, $dim_y);
            $dim_y -= 12;
            $page->setFont($font, 8)->drawText("TOTAL", $dim_x, $dim_y);

            $total = number_format($bop['insurance'] + $bop['amount'], 2);

            $page->setFont($font, 8)->drawText("Php {$total}", $dim_x + 180, $dim_y);

            $dim_y -= 24;
            $page->setFont($font, 8)->drawText("__________________________", $dim_x, $dim_y);
            $dim_y -= 12;
            $page->setFont($font, 8)->drawText("Printed name and signature", $dim_x, $dim_y);

            $pdf->pages[] = $page;
        }


    }

    protected function _receiving_copy($pdf, $rec_copy_data)
    {
        $page = new Zend_Pdf_Page(Zend_Pdf_Page::SIZE_A4);
        $logo = Zend_Pdf_Image::imageWithPath(APPLICATION_PATH . '/../public/images/messerve.png');
        $font = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA);
        $bold = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_BOLD);

        $pageHeight = $page->getHeight();
        $pageWidth = $page->getWidth();

        $dim_x = 32;
        $dim_y = $pageHeight - 25;

        $imageHeight = 41;
        $imageWidth = 119;

        $bottomPos = $dim_y - $imageHeight;
        $rightPos = $dim_x + $imageWidth;

        $page->drawImage($logo, $dim_x, $bottomPos, $rightPos, $dim_y);
        $dim_y -= 64;

        $page->setFont($bold, 14)->drawText("{$rec_copy_data['branch']}", $dim_x, $dim_y);
        $dim_y -= 24;
        $page->setFont($bold, 10)->drawText("Emp#  Name", $dim_x, $dim_y);
        $page->setFont($bold, 10)->drawText("Pay period                            Signature ", $dim_x + 180, $dim_y);

        foreach ($rec_copy_data['riders'] as $rider) {
            $dim_y -= 24;
            $page->setFont($font, 8)->drawText("{$rider['employee_number']}   {$rider['name']}", $dim_x, $dim_y);
            $page->setFont($font, 8)->drawText("{$rider['pay_period']}               __________________________", $dim_x + 180, $dim_y);
        }

        $pdf->pages[] = $page;
    }

    protected function _fetch_employees($group_id, $date_start, $date_end)
    {
        $EmployeeMap = new Messerve_Model_Mapper_Employee();

        $employees = $EmployeeMap->fetchList("group_id = $group_id", array('lastname ASC', 'firstname ASC'));

        $AttendDB = new Messerve_Model_DbTable_Attendance();

        $temp_employees = array();

        foreach ($employees as $evalue) {
            $temp_employees[] = $evalue->getId();
        }

        // Search for relievers
        if (count($temp_employees) > 0) {
            $permanents = implode(',', $temp_employees);

            $select = $AttendDB->select(true);

            $select->where("employee_id NOT IN ({$permanents})")
                ->where('group_id = ?', $group_id)
                ->where("datetime_start >= '{$date_start} 00:00' AND datetime_start <= '{$date_end} 23:59'")
                ->group('employee_id');

            $relievers_result = $AttendDB->fetchAll($select);

            if (count($relievers_result) > 0) {
                foreach ($relievers_result as $rvalue) {
                    $Reliever = new Messerve_Model_Employee();
                    $Reliever->find($rvalue->employee_id);
                    $employees[] = $Reliever;
                }
            }

            unset($select);

        } else {
            $select = $AttendDB->select(true);

            $select->where('group_id = ?', $group_id)
                ->where("datetime_start >= '{$date_start} 00:00' AND datetime_start <= '{$date_end} 23:59'")
                ->group('employee_id');

            $relievers_result = $AttendDB->fetchAll($select);

            if (count($relievers_result) > 0) {
                foreach ($relievers_result as $rvalue) {
                    $Reliever = new Messerve_Model_Employee();
                    $Reliever->find($rvalue->employee_id);
                    $employees[] = $Reliever;
                }
            }

            unset($select);

        }

        return $employees;
    }

    protected function _compute()
    {
        $date_start = $this->_request->getParam('date_start');
        $date_end = $this->_request->getParam('date_end');

        $Rate = new Messerve_Model_Mapper_Rate();
        $rates = $Rate->fetchAll();

        $rates_array = array();

        foreach ($rates as $value) {
            $rates_array[$value->getId()] = $value;
        }

        $group_id = (int)$this->_request->getParam('group_id');
        $Group = new Messerve_Model_Group();
        $Group->find($group_id);


        $Client = new Messerve_Model_Client();
        $Client->find($Group->getClientId());

        $this->_client = $Client;

        // $pay_period = $this->_request->getParam('pay_period');

        $employees = $this->_fetch_employees($group_id, $date_start, $date_end);

        $employee_payroll = array();

        $employer_bill = array();

        $date1 = new DateTime($date_start); //inclusive
        $date2 = new DateTime($date_end); //exclusive
        $diff = $date2->diff($date1);
        // $period_size = intval($diff->format("%a")) + 1;

        $summary_bill = array(
            'reg' => 0
        , 'reg_nd' => 0
        , 'reg_ot' => 0
        , 'reg_nd_ot' => 0

        , 'spec' => 0
        , 'spec_nd' => 0
        , 'spec_ot' => 0
        , 'spec_nd_ot' => 0

        , 'legal' => 0
        , 'legal_nd' => 0
        , 'legal_ot' => 0
        , 'legal_nd_ot' => 0
        , 'legal_unattend' => 0

        , 'rest' => 0
        , 'rest_nd' => 0
        , 'rest_ot' => 0
        , 'rest_nd_ot' => 0
        );

        $messerve_bill = $summary_bill; // Reset to zero

        $AttendDB = new Messerve_Model_DbTable_Attendance();


        foreach ($employees as $evalue) {
            $total_hours = 0;

            $select = $AttendDB->select();

            $select
                ->setIntegrityCheck(false)
                ->from('attendance')
                ->join('employee', 'employee.id = attendance.employee_id', [
                    'employee_number'
                    , 'firstname'
                    , 'middleinitial'
                    , 'lastname'
                    , 'tin'
                    , 'sss'
                    , 'hdmf'
                    , 'philhealth'
                    , 'dateemployed'
                ])
                ->where('attendance.employee_id = ?', $evalue->getId())
                ->where('attendance.group_id = ?', $group_id)
                ->where("datetime_start >= '{$date_start} 00:00' AND datetime_start <= '{$date_end} 23:59'");

            $all_attendance = $AttendDB->fetchAll($select);

            $attendance = (object)[
                'employee_id' => '',
                'group_id' => '',
                'datetime_start' => '',
                'employee_number' => '',
                'firstname' => '',
                'middleinitial' => '',
                'lastname' => '',
                'tin' => '',
                'sss' => '',
                'philhealth' => '',
                'dateemployed' => '',

                'extended_shift' => 'no',
                'sum_fuel_overage' => 0,

                'sum_reg' => 0,
                'sum_reg_nd' => 0,
                'sum_reg_ot' => 0,
                'sum_reg_nd_ot' => 0,
                'sum_sun' => 0,
                'sum_sun_nd' => 0,
                'sum_sun_ot' => 0,
                'sum_sun_nd_ot' => 0,
                'sum_spec' => 0,
                'sum_spec_nd' => 0,
                'sum_spec_ot' => 0,
                'sum_spec_nd_ot' => 0,
                'sum_legal' => 0,
                'sum_legal_nd' => 0,
                'sum_legal_ot' => 0,
                'sum_legal_nd_ot' => 0,
                'sum_legal_unattend' => 0,
                'sum_rest' => 0,
                'sum_rest_nd' => 0,
                'sum_rest_ot' => 0,
                'sum_rest_nd_ot' => 0,

                'rate_id' => 0,
            ];

            foreach ($all_attendance as $day) {
                // echo "{$evalue->firstname} {$day->datetime_start} <br>";

                $AttendanceMap = new Messerve_Model_Mapper_Attendance();

                $first_day = $AttendanceMap->findOneByField(
                    array('datetime_start', 'employee_id', 'group_id')
                    , array($date_start, $evalue->getId(), $group_id)
                );

                if (!$first_day) {
                    echo "SKIP";
                    continue;
                }

                $attendance->sum_fuel_overage += $day->fuel_overage;

                $attendance->sum_reg += $day->reg;
                $attendance->sum_reg_nd += $day->reg_nd;

                $attendance->sum_sun += $day->sun;
                $attendance->sum_sun_nd += $day->sun_nd;

                $attendance->sum_spec += $day->spec;
                $attendance->sum_spec_nd += $day->spec_nd;


                $attendance->sum_legal += $day->legal;
                $attendance->sum_legal_nd += $day->legal_nd;

                $attendance->sum_rest += $day->rest;
                $attendance->sum_rest_nd += $day->rest_nd;

                /* OT */

                $attendance->sum_reg_ot += $day->reg_ot;
                $attendance->sum_reg_nd_ot += $day->reg_nd_ot;

                $attendance->sum_sun_ot += $day->sun_ot;
                $attendance->sum_sun_nd_ot += $day->sun_nd_ot;

                $attendance->sum_spec_ot += $day->spec_ot;
                $attendance->sum_spec_nd_ot += $day->spec_nd_ot;

                $attendance->sum_legal_ot += $day->legal_ot;
                $attendance->sum_legal_nd_ot += $day->legal_nd_ot;

                $attendance->sum_rest_ot += $day->rest_ot;
                $attendance->sum_rest_nd_ot += $day->rest_nd_ot;

                $attendance->sum_legal_unattend += $day->legal_unattend;

                // Fork for-employer attendance

                $summary_bill['reg'] += $day->reg;
                $summary_bill['reg_nd'] += $day->reg_nd;

                $summary_bill['spec'] += $day->spec;
                $summary_bill['spec_nd'] += $day->spec_nd;

                $summary_bill['rest'] += $day->rest;
                $summary_bill['rest_nd'] += $day->rest_nd;

                $summary_bill['legal'] += $day->legal;
                $summary_bill['legal_nd'] += $day->legal_nd;

                $summary_bill['legal_unattend'] += $day->legal_unattend;


                if ($day->extended_shift == 'yes') { // Has extended shift, bill OT to Messerve
                    $summary_bill['reg'] += $day->reg_ot;
                    $summary_bill['reg_nd'] += $day->reg_nd_ot;

                    $messerve_bill['reg_ot'] += $day->reg_ot;
                    $messerve_bill['reg_nd_ot'] += $day->reg_nd_ot;

                    $summary_bill['spec'] += $day->spec_ot;
                    $summary_bill['spec_nd'] += $day->spec_nd_ot;

                    $messerve_bill['spec_ot'] += $day->spec_ot;
                    $messerve_bill['spec_nd_ot'] += $day->spec_nd_ot;

                    $summary_bill['legal'] += $day->legal_ot;
                    $summary_bill['legal_nd'] += $day->legal_nd;

                    $messerve_bill['legal_ot'] += $day->legal_nd_ot;
                    $messerve_bill['legal_nd_ot'] += $day->legal_nd_ot;

                    $summary_bill['legal'] += $day->legal;
                    $summary_bill['legal_nd'] += $day->legal_nd;

                    $summary_bill['rest'] += $day->rest_ot;
                    $summary_bill['rest_nd'] += $day->rest_nd_ot;

                    $messerve_bill['rest_ot'] += $day->rest_ot;
                    $messerve_bill['rest_nd_ot'] += $day->rest_nd_ot;
                } else {
                    $summary_bill['reg_ot'] += $day->reg_ot;
                    $summary_bill['reg_nd_ot'] += $day->reg_nd_ot;

                    $summary_bill['spec_ot'] += $day->spec_ot;
                    $summary_bill['spec_nd_ot'] += $day->spec_nd_ot;

                    $summary_bill['legal_ot'] += $day->legal_ot;
                    $summary_bill['legal_nd_ot'] += $day->legal_nd_ot;

                    $summary_bill['rest_ot'] += $day->rest_ot;
                    $summary_bill['rest_nd_ot'] += $day->rest_nd_ot;
                }

            }


            $attendance->id = $day->employee_id;
            $attendance->group_id = $day->group_id;
            $attendance->employee_number = $day->employee_number;
            $attendance->firstname = $day->firstname;
            $attendance->middleinitial = $day->middleinitial;
            $attendance->lastname = $day->lastname;

            $attendance->tin = $day->tin;
            $attendance->sss = $day->sss;
            $attendance->hdmf = $day->hdmf;
            $attendance->philhealth = $day->philhealth;

            $attendance->dateemployed = $day->dateemployed;

            if ($attendance->rate_id > 0) { //  Using employee rate
                $this_rate = $rates_array[$attendance->rate_id];
            } elseif ($Group->getRateId() > 0) {//  Using group rate
                $this_rate = $rates_array[$Group->getRateid()];
            } else {
                die('Process halted:  no rates found for either employee or group.');
            }


            $total_hours = $attendance->sum_reg
                + $attendance->sum_reg_nd
                + $attendance->sum_reg_ot
                + $attendance->sum_reg_nd_ot
                + $attendance->sum_sun
                + $attendance->sum_sun_nd
                + $attendance->sum_sun_ot
                + $attendance->sum_sun_nd_ot
                + $attendance->sum_spec
                + $attendance->sum_spec_nd
                + $attendance->sum_spec_ot
                + $attendance->sum_spec_nd_ot
                + $attendance->sum_legal
                + $attendance->sum_legal_nd
                + $attendance->sum_legal_ot
                + $attendance->sum_legal_nd_ot
                + $attendance->sum_legal_unattend
                + $attendance->sum_rest
                + $attendance->sum_rest_nd
                + $attendance->sum_rest_ot
                + $attendance->sum_rest_nd_ot;


            $employee_payroll[$evalue->getId()]['pay'] = array(
                'reg' => $attendance->sum_reg * $this_rate->Reg
            , 'reg_nd' => $attendance->sum_reg_nd * $this_rate->RegNd

            , 'reg_ot' => $attendance->sum_reg_ot * $this_rate->RegOT
            , 'reg_nd_ot' => $attendance->sum_reg_nd_ot * $this_rate->RegNdOT

            , 'sun' => $attendance->sum_sun * $this_rate->Sun
            , 'sun_nd' => $attendance->sum_sun_nd * $this_rate->SunNd
            , 'sun_ot' => $attendance->sum_sun_ot * $this_rate->SunOT
            , 'sun_nd_ot' => $attendance->sum_sun_nd_ot * $this_rate->SunNdOt

            , 'spec' => $attendance->sum_spec * $this_rate->Spec
            , 'spec_nd' => $attendance->sum_spec_nd * $this_rate->SpecNd
            , 'spec_ot' => $attendance->sum_spec_ot * $this_rate->SpecOT
            , 'spec_nd_ot' => $attendance->sum_spec_nd_ot * $this_rate->SpecNdOt

            , 'legal' => $attendance->sum_legal * $this_rate->Legal
            , 'legal_nd' => $attendance->sum_legal_nd * $this_rate->LegalNd
            , 'legal_ot' => $attendance->sum_legal_ot * $this_rate->LegalOT
            , 'legal_nd_ot' => $attendance->sum_legal_nd_ot * $this_rate->LegalNdOt
            , 'legal_unattend' => $attendance->sum_legal_unattend * $this_rate->LegalUnattend

            , 'rest' => $attendance->sum_rest * $this_rate->Spec
            , 'rest_nd' => $attendance->sum_rest_nd * $this_rate->SpecNd
            , 'rest_ot' => $attendance->sum_rest_ot * $this_rate->SpecOT
            , 'rest_nd_ot' => $attendance->sum_rest_nd_ot * $this_rate->SpecNdOt
            );


            $sss_deduct = $total_hours * ($this_rate->SSSEmployee / 22 / 8);

            $employee_payroll[$evalue->getId()]['deductions'] = array(
                'sss' => ($sss_deduct)
            , 'philhealth' => ($this_rate->PhilhealthEmployee / 2)
            , 'hdmf' => ($this_rate->HDMFEmployee / 2)
            , 'cash_bond' => ($this_rate->CashBond / 2)
                // , 'insurance' => 25
            , 'bike_rehab' => 0
            , 'bike_insurance_reg' => 0
            );

            $employer_bill[$evalue->getId()]['info'] = array(
                'employee_number' => $attendance->employee_number
            , 'first_name' => $attendance->firstname
            , 'middle_name' => $attendance->middleinitial
            , 'last_name' => $attendance->lastname

            , 'tin' => $attendance->tin
            , 'sss' => $attendance->sss
            , 'hdmf' => $attendance->hdmf
            , 'philhealth' => $attendance->philhealth
            , 'date_employed' => $attendance->dateemployed
            );


            if (property_exists($attendance, 'bike_rehab_end') && strtotime($attendance->bike_rehab_end) >= strtotime($date_start)) {
                $employee_payroll[$evalue->getId()]['deductions']['bike_rehab']
                    = $this_rate->BikeRehab;
            }

            if (property_exists($attendance, 'bike_insurance_reg_end') && strtotime($attendance->bike_insurance_reg_end) >= strtotime($date_start)) {
                $employee_payroll[$evalue->getId()]['deductions']['bike_insurance_reg']
                    = $this_rate->BikeInsuranceReg;
            }

            $AddIncome = new Messerve_Model_Addincome();
            $AddIncome = $AddIncome->getMapper()->findOneByField('attendance_id', $first_day->getId());
            if (!$AddIncome) $AddIncome = new Messerve_Model_Addincome();

            $fuel_consumption = $first_day->getFuelConsumed() - $first_day->getFuelAlloted();

            if ($fuel_consumption < 0) { // Negative fuel overage, creating additional income
                $AddIncome
                    ->setAttendanceId($first_day->getId())
                    ->setGasoline($fuel_consumption * $this->_fuelcost * -1)
                    ->save();
            } else {
                $AddIncome
                    ->setAttendanceId($first_day->getId())
                    ->setGasoline(0)
                    ->save();
            }

            if ($AddIncome) {
                $more_income = $AddIncome->toArray();
                unset($more_income['id'], $more_income['attendance_id']);

                $employee_payroll[$evalue->getId()]['more_income'] = $more_income;
            }

            $Deductions = new Messerve_Model_Deductions();
            $Deductions = $Deductions->getMapper()->findOneByField('attendance_id', $first_day->getId());

            if ($Deductions) {
                $more_deductions = $Deductions->toArray();
                unset($more_deductions['id'], $more_deductions['attendance_id']);

                $employee_payroll[$evalue->getId()]['more_deductions'] = $more_deductions;

                foreach ($more_deductions as $dkey => $dvalue) {
                    $dvalue = $more_deductions[$dkey] = $dvalue;
                }
            }

            $employee_payroll[$evalue->getId()]['attendance'] = $attendance;

            if ($evalue->getGroupId() != $Group->getId()) { // Reliever?  Remove basic deductions.
                $employee_payroll[$evalue->getId()]['deductions']['insurance'] = 0;
                $employee_payroll[$evalue->getId()]['deductions']['cash_bond'] = 0;
                $employee_payroll[$evalue->getId()]['deductions']['philhealth'] = 0;
                $employee_payroll[$evalue->getId()]['deductions']['hdmf'] = 0;
                $employee_payroll[$evalue->getId()]['deductions']['bike_rehab'] = 0;
                $employee_payroll[$evalue->getId()]['deductions']['bike_insurance_reg'] = 0;
            }

        }


        $this->_employee_payroll = $employee_payroll;
        $this->_employer_bill = $summary_bill;

        // preprint($summary_bill, true);

        $this->_messerve_bill = $messerve_bill;

        // preprint($messerve_bill,1);

    }

    public function summaryreportAction()
    {

        function round_this($in)
        {
            if (is_numeric($in)) {
                // return round($in, 2);
                return number_format($in, 2, '.', '');
            } else {
                return $in;
            }
        }

        // action body
        $date_start = $this->_request->getParam('date_start');
        $date_end = $this->_request->getParam('date_end');
        $standalone = $this->getParam('standalone');

        if ($standalone != '' && $standalone == 'true') {
            $this->_last_date = date('Y-m-d-Hi');
            $this->_compute();
        } // TODO:  Simplify and streamline.  Just fetch the group members and their payroll

        $this->view->payroll = $this->_employee_payroll;

        $pdf = new Zend_Pdf();


        $font = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA);
        $bold = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_BOLD);
        $italic = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_ITALIC);
        // $mono = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_COURIER);

        $logo = Zend_Pdf_Image::imageWithPath(APPLICATION_PATH . '/../public/images/messerve.png');

        $group_id = $this->_request->getParam('group_id');

        $Group = new Messerve_Model_Group();
        $Group->find($group_id);

        $Client = new Messerve_Model_Client();

        $Client->find($Group->getClientId());

        $folder = realpath(APPLICATION_PATH . '/../public/export') . "/$date_start/$group_id/summary/";
        $cmd = "mkdir -p $folder";
        shell_exec($cmd); // Create folder

        $page = new Zend_Pdf_Page(1008, 612);

        $pageHeight = $page->getHeight();
        // $pageWidth = $page->getWidth();

        $dim_x = 32;
        $dim_y = $pageHeight - 25;

        $imageHeight = 41;
        $imageWidth = 119;

        $bottomPos = $dim_y - $imageHeight;
        $rightPos = $dim_x + $imageWidth;

        $page->drawImage($logo, $dim_x, $bottomPos, $rightPos, $dim_y);
        $dim_y -= 12;

        $page->setFont($bold, 10)->drawText($Client->getName() . '-' . $Group->getName(), $dim_x + 300, $dim_y, 'UTF8');
        $dim_y -= 10;

        $page->setFont($bold, 10)->drawText('Pay period: ' . $date_start . ' to ' . $date_end, $dim_x + 300, $dim_y);
        $dim_y -= 32;

        $date1 = new DateTime($date_start); //inclusive
        $date2 = new DateTime($date_end); //exclusive
        $diff = $date2->diff($date1);
        $period_size = intval($diff->format("%a")) + 1;

        $date = (int)substr($date_start, -2, 2);

        for ($i = 1; $i <= $period_size; $i++) {
            $page->setFont($bold, 8)->drawText($date, $dim_x + ($i * 25) + 150, $dim_y, 'UTF8');
            $date++;
        }

        $now_x = $dim_x + ($i * 22) + 110;

        $now_inc = 35;
        $dim_y -= 10;


        $all_total_reg = 0;
        $all_total_reg_ot = 0;
        $all_total_reg_nd = 0;
        $all_total_reg_nd_ot = 0;

        $all_total_sun = 0;
        $all_total_sun_ot = 0;
        $all_total_sun_nd = 0;
        $all_total_sun_nd_ot = 0;

        /*
        $all_total_spec = 0;
        $all_total_spec_ot = 0;
        $all_total_spec_nd = 0;
        $all_total_spec_nd_ot = 0;
        */

        $all_total_legal = 0;
        $all_total_legal_ot = 0;
        $all_total_legal_nd = 0;
        $all_total_legal_nd_ot = 0;

        $all_total_legal_unattend = 0;

        $all_total_rest = 0;
        $all_total_rest_ot = 0;
        $all_total_rest_nd = 0;
        $all_total_rest_nd_ot = 0;

        $all_total_total_hours = 0;


        $all_messerve_reg_ot = 0;
        $all_messerve_reg_nd_ot = 0;;

        $all_messerve_sun_ot = 0;
        $all_messerve_sun_nd_ot = 0;

        $all_messerve_spec_ot = 0;
        $all_messerve_spec_nd_ot = 0;

        $all_messerve_legal_ot = 0;
        $all_messerve_legal_nd_ot = 0;

        $all_messerve_rest_ot = 0;;
        $all_messerve_rest_nd_ot = 0;


        $employee_count = 0;

        $all_messerve_ot = 0;

        foreach ($this->_employee_payroll as $value) {
            if ($employee_count >= 8) {
                $employee_count = 0;

                $pdf->pages[] = $page;

                $page = new Zend_Pdf_Page(1008, 612);

                $pageHeight = $page->getHeight();
                $pageWidth = $page->getWidth();

                $dim_x = 32;
                $dim_y = $pageHeight - 25;

            }
            $total_reg = 0;
            $total_reg_ot = 0;
            $total_reg_nd = 0;
            $total_reg_nd_ot = 0;

            $total_sun = 0;
            $total_sun_ot = 0;
            $total_sun_nd = 0;
            $total_sun_nd_ot = 0;

            $total_legal = 0;
            $total_legal_ot = 0;
            $total_legal_nd = 0;
            $total_legal_nd_ot = 0;

            $total_legal_unattend = 0;

            $total_rest = 0;
            $total_rest_ot = 0;
            $total_rest_nd = 0;
            $total_rest_nd_ot = 0;

            $total_total_hours = 0;


            $dates = array();

            $AttendanceMap = new Messerve_Model_Mapper_Attendance();

            $first_id = 0;

            $current_date = $date_start;

            $employee_id = $value['attendance']->id;

            $employee_attendance_text = array();

            $messerve_reg_ot = 0;
            $messerve_reg_nd_ot = 0;

            $messerve_rest_ot = 0;
            $messerve_rest_nd_ot = 0;

            $messerve_sun_ot = 0;
            $messerve_sun_nd_ot = 0;

            $messerve_legal_ot = 0;
            $messerve_legal_nd_ot = 0;

            $messerve_ot = 0;

            for ($i = 1; $i <= $period_size; $i++) {
                $Attendance = $AttendanceMap->findOneByField(
                    array('employee_id', 'datetime_start', 'group_id')
                    , array($employee_id, $current_date, $group_id)
                );

                if (!$Attendance) {
                    $Attendance = new Messerve_Model_Attendance();

                    $Attendance->setEmployeeId($employee_id)
                        ->setDatetimeStart($current_date)
                        ->setGroupId($group_id)
                        ->save();

                    $Attendance->find($Attendance->getId()); // Get the whole model
                }


                $dates[$current_date] = $Attendance;

                $current_date = date('Y-m-d', strtotime('+1 day', strtotime($current_date)));

                if ($i == 1) $first_id = $Attendance->getId();

                $attendance_array = $Attendance->toArray();

                $all_hours = array(
                    $attendance_array['reg']
                , $attendance_array['reg_nd']
                , $attendance_array['spec']
                , $attendance_array['spec_nd']
                , $attendance_array['sun']
                , $attendance_array['sun_nd']
                , $attendance_array['legal']
                , $attendance_array['legal_nd']
                , $attendance_array['legal_unattend']
                , $attendance_array['rest']
                , $attendance_array['rest_nd']

                );


                if ($Attendance->getOtApproved() == 'yes'
                ) {
                    $all_hours = array_merge($all_hours, array(
                        $attendance_array['reg_ot']
                    , $attendance_array['reg_nd_ot']
                    , $attendance_array['spec_ot']
                    , $attendance_array['spec_nd_ot']
                    , $attendance_array['sun_ot']
                    , $attendance_array['sun_nd_ot']
                    , $attendance_array['legal_ot']
                    , $attendance_array['legal_nd_ot']
                    , $attendance_array['rest_ot']
                    , $attendance_array['rest_nd_ot']
                    ));

                } elseif ($Attendance->getExtendedShift() == 'yes') { // Bill to Messerve

                    $all_messerve_reg_ot += $attendance_array['reg_ot'];
                    $all_messerve_reg_nd_ot += $attendance_array['reg_nd_ot'];

                    $all_messerve_sun_ot += $attendance_array['sun_ot'];
                    $all_messerve_sun_nd_ot += $attendance_array['sun_ot'];

                    $all_messerve_spec_ot += $attendance_array['spec_ot'];
                    $all_messerve_spec_nd_ot += $attendance_array['spec_ot'];

                    $all_messerve_legal_ot += $attendance_array['legal_ot'];
                    $all_messerve_legal_nd_ot += $attendance_array['legal_ot'];

                    $all_messerve_rest_ot += $attendance_array['rest_ot'];
                    $all_messerve_rest_nd_ot += $attendance_array['rest_ot'];

                    $messerve_ot_array = [
                        'reg_ot' => $attendance_array['reg_ot']
                        , 'reg_nd_ot' => $attendance_array['reg_nd_ot']
                        , 'spec_ot' => $attendance_array['spec_ot']
                        , 'spec_nd_ot' => $attendance_array['spec_nd_ot']
                        , 'sun_ot' => $attendance_array['sun_ot']
                        , 'sun_nd_ot' => $attendance_array['sun_nd_ot']
                        , 'legal_ot' => $attendance_array['legal_ot']
                        , 'legal_nd_ot' => $attendance_array['legal_nd_ot']
                        , 'rest_ot' => $attendance_array['rest_ot']
                        , 'rest_nd_ot' => $attendance_array['rest_nd_ot']
                    ];

                    $messerve_ot += array_sum($messerve_ot_array);
                    // $messerve_ot = round_this($messerve_ot);

                    $all_messerve_ot += $messerve_ot;

                    $messerve_reg_ot += $attendance_array['reg_ot'];
                    $messerve_reg_nd_ot += $attendance_array['reg_nd_ot'];

                    $messerve_rest_ot += $attendance_array['rest_ot'];
                    $messerve_rest_nd_ot += $attendance_array['rest_nd_ot'];

                    $messerve_sun_ot += $attendance_array['sun_ot'] + $attendance_array['spec_ot'];
                    $messerve_sun_nd_ot += $attendance_array['sun_nd_ot'] + $attendance_array['spec_nd_ot'];

                    $messerve_legal_ot += $attendance_array['legal_ot'];
                    $messerve_legal_nd_ot += $attendance_array['legal_nd_ot'];
                }

                // array_walk($attendance_array, 'round_this');


                $total_reg += $attendance_array['reg'];
                $total_reg_nd += $attendance_array['reg_nd'];

                $total_sun += $attendance_array['sun'] + $attendance_array['spec'];
                $total_sun_nd += $attendance_array['sun_nd'] + $attendance_array['spec_nd'];

                $total_legal += $attendance_array['legal'];
                $total_legal_nd += $attendance_array['legal_nd'];

                $total_legal_unattend += $attendance_array['legal_unattend'];

                $total_rest += $attendance_array['rest'];
                $total_rest_nd += $attendance_array['rest_nd'];

                if ($Attendance->getOtApproved() == 'yes') {
                    $total_reg_ot += $attendance_array['reg_ot'];
                    $total_reg_nd_ot += $attendance_array['reg_nd_ot'];

                    $total_rest_ot += $attendance_array['rest_ot'];
                    $total_rest_nd_ot += $attendance_array['rest_nd_ot'];

                    $total_sun_ot += $attendance_array['sun_ot'] + $attendance_array['spec_ot'];
                    $total_sun_nd_ot += $attendance_array['sun_nd_ot'] + $attendance_array['spec_nd_ot'];

                    $total_legal_ot += $attendance_array['legal_ot'];
                    $total_legal_nd_ot += $attendance_array['legal_nd_ot'];
                }

                $total_hours = array_sum($all_hours);
                $total_total_hours += round($total_hours, 2);

                $employee_attendance_text[] = round($total_hours, 2);

                // $page->setFont($font, 8)->drawText(round($total_hours,2), $dim_x + ($i * 25) + 150, $dim_y, 'UTF8');
            }

            if ($total_total_hours > 0) {
                $i = 1;

                foreach ($employee_attendance_text as $evalue) {
                    $page->setFont($font, 8)->drawText($evalue, $dim_x + ($i * 25) + 150, $dim_y, 'UTF8');
                    $i++;
                }

                $page->setFont($font, 8)->drawText($value['attendance']->employee_number, $dim_x, $dim_y);

                $page->setFont($font, 8)->drawText(
                    $value['attendance']->lastname . ', '
                    . $value['attendance']->firstname . ' '
                    . $value['attendance']->middleinitial, $dim_x + 32, $dim_y, 'UTF8');


                $all_total_reg += $total_reg;
                $all_total_reg_nd += $total_reg_nd;

                $all_total_sun += $total_sun;
                $all_total_sun_nd += $total_sun_nd;

                $all_total_legal += $total_legal;
                $all_total_legal_nd += $total_legal_nd;

                $all_total_legal_unattend += $total_legal_unattend;

                $all_total_rest += $total_rest;
                $all_total_rest_nd += $total_rest_nd;

                $all_total_reg_ot += $total_reg_ot;
                $all_total_reg_nd_ot += $total_reg_nd_ot;

                $all_total_sun_ot += $total_sun_ot;
                $all_total_sun_nd_ot += $total_sun_nd_ot;

                $all_total_legal_ot += $total_legal_ot;
                $all_total_legal_nd_ot += $total_legal_nd_ot;

                $all_total_rest_ot += $total_rest_ot;
                $all_total_rest_nd_ot += $total_rest_nd_ot;

                $all_total_total_hours += $total_total_hours;

                $now_x = $dim_x + ($i * 22) + 110 + 66;
                $now_inc = 70;


                $page->setFont($font, 8)->drawText('Total ' . round_this($total_total_hours, 2), $dim_x + $now_x, $dim_y, 'UTF8');

                if ($messerve_ot > 0) {
                    $page->setFont($italic, 8)->drawText($messerve_ot, $dim_x + $now_x + 47, $dim_y, 'UTF8');
                }


                $now_x += $now_inc;

                $ot_font = $font;

                $page->setFont($font, 8)->drawText('Reg ' . round_this($total_reg, 2), $dim_x + $now_x, $dim_y, 'UTF8');
                $now_x += $now_inc;

                $page->setFont($ot_font, 8)->drawText('RegOT ' . round_this($total_reg_ot, 2), $dim_x + $now_x, $dim_y, 'UTF8');
                if ($messerve_reg_ot > 0) {
                    $page->setFont($italic, 8)->drawText($messerve_reg_ot, $dim_x + $now_x + 47, $dim_y, 'UTF8');
                }
                $now_x += $now_inc;

                $page->setFont($font, 8)->drawText('RegND ' . round_this($total_reg_nd, 2), $dim_x + $now_x, $dim_y, 'UTF8');
                $now_x += $now_inc;

                $page->setFont($ot_font, 8)->drawText('RegNDOT ' . round_this($total_reg_nd_ot, 2), $dim_x + $now_x, $dim_y, 'UTF8');

                if ($messerve_reg_nd_ot > 0) {
                    $page->setFont($italic, 8)->drawText($messerve_reg_nd_ot, $dim_x + $now_x + 56, $dim_y, 'UTF8');
                } else {
                    // $page->setFont($italic, 8)->drawText("NO MESS ND OT", $dim_x + $now_x + 56, $dim_y, 'UTF8');
                }

                /* New line */
                $now_x = $dim_x + ($i * 22) + 110 + 66;
                $dim_y -= 10;

                $page->setFont($font, 8)->drawText('SunSp ' . round_this($total_sun, 2), $dim_x + $now_x, $dim_y, 'UTF8');
                $now_x += $now_inc;

                $page->setFont($ot_font, 8)->drawText('SunSpOT ' . round_this($total_sun_ot, 2), $dim_x + $now_x, $dim_y, 'UTF8');
                if ($messerve_sun_ot > 0) {
                    $page->setFont($italic, 8)->drawText($messerve_sun_ot, $dim_x + $now_x + 47, $dim_y, 'UTF8');
                }
                $now_x += $now_inc;

                $page->setFont($font, 8)->drawText('SunSpND ' . round_this($total_sun_nd, 2), $dim_x + $now_x, $dim_y, 'UTF8');
                $now_x += $now_inc;

                $page->setFont($ot_font, 8)->drawText('SunSpNDOT ' . round_this($total_sun_nd_ot, 2), $dim_x + $now_x, $dim_y, 'UTF8');
                if ($messerve_sun_ot > 0) {
                    $page->setFont($italic, 8)->drawText($messerve_sun_ot, $dim_x + $now_x + 47, $dim_y, 'UTF8');
                }

                /* New line */
                $now_x = $dim_x + ($i * 22) + 110 + 66;
                $dim_y -= 10;

                $page->setFont($font, 8)->drawText('RestSp ' . round_this($total_rest, 2), $dim_x + $now_x, $dim_y, 'UTF8');
                $now_x += $now_inc;

                $page->setFont($ot_font, 8)->drawText('RestSpOT ' . round_this($total_rest_ot, 2), $dim_x + $now_x, $dim_y, 'UTF8');
                if ($messerve_rest_ot > 0) {
                    $page->setFont($italic, 8)->drawText($messerve_rest_ot, $dim_x + $now_x + 47, $dim_y, 'UTF8');
                }
                $now_x += $now_inc;

                $page->setFont($font, 8)->drawText('RestSpND ' . round_this($total_rest_nd, 2), $dim_x + $now_x, $dim_y, 'UTF8');
                $now_x += $now_inc;

                $page->setFont($ot_font, 8)->drawText('RestSpNDOT ' . round_this($total_rest_nd_ot, 2), $dim_x + $now_x, $dim_y, 'UTF8');
                if ($messerve_rest_nd_ot > 0) {
                    $page->setFont($italic, 8)->drawText($messerve_rest_nd_ot, $dim_x + $now_x + 47, $dim_y, 'UTF8');
                }


                /* New line */
                $now_x = $dim_x + ($i * 22) + 110 + 66;
                $dim_y -= 10;

                $page->setFont($font, 8)->drawText('Leg ' . round_this($total_legal, 2), $dim_x + $now_x, $dim_y, 'UTF8');
                $now_x += $now_inc;

                $page->setFont($ot_font, 8)->drawText('LegOT ' . round_this($total_legal_ot, 2), $dim_x + $now_x, $dim_y, 'UTF8');
                if ($messerve_legal_ot > 0) {
                    $page->setFont($italic, 8)->drawText($messerve_legal_ot, $dim_x + $now_x + 47, $dim_y, 'UTF8');
                }
                $now_x += $now_inc;

                $page->setFont($font, 8)->drawText('LegND ' . round_this($total_legal_nd, 2), $dim_x + $now_x, $dim_y, 'UTF8');
                $now_x += $now_inc;

                $page->setFont($ot_font, 8)->drawText('LegNDOT ' . round_this($total_legal_nd_ot, 2), $dim_x + $now_x, $dim_y, 'UTF8');
                if ($messerve_legal_nd_ot > 0) {
                    $page->setFont($italic, 8)->drawText($messerve_legal_nd_ot, $dim_x + $now_x + 47, $dim_y, 'UTF8');
                }
                $now_x += $now_inc;

                $page->setFont($font, 8)->drawText('Leg UA ' . round_this($total_legal_unattend, 2), $dim_x + $now_x, $dim_y, 'UTF8');

                $dim_y -= 20;
            }

            $employee_count++;

        }

        $dim_y -= 20;

        $now_x = $dim_x + ($i * 22) + 110 + 66;

        $now_inc = 70;

        $all_total_total_hours = round($all_total_reg, 2) + round($all_total_reg_ot, 2) + round($all_total_reg_nd, 2) + round($all_total_reg_nd_ot, 2)
            + round($all_total_sun, 2) + round($all_total_sun_ot, 2) + round($all_total_sun_nd, 2) + round($all_total_sun_nd_ot, 2)
            + round($all_total_rest, 2) + round($all_total_rest_ot, 2) + round($all_total_rest_nd, 2) + round($all_total_rest_nd_ot, 2)
            + round($all_total_legal, 2) + round($all_total_legal_ot, 2) + round($all_total_legal_nd, 2) + round($all_total_legal_nd_ot, 2)
            + round($all_total_legal_unattend, 2);


        $page->setFont($font, 8)->drawText('Total ' . round($all_total_total_hours, 2), $dim_x + $now_x, $dim_y, 'UTF8');

        if ($all_messerve_ot > 0) {
            $page->setFont($italic, 8)->drawText($all_messerve_ot, $dim_x + $now_x + 47, $dim_y, 'UTF8');
        }

        $now_x += $now_inc;

        $page->setFont($font, 8)->drawText('Reg ' . round($all_total_reg, 2), $dim_x + $now_x, $dim_y, 'UTF8');
        $now_x += $now_inc;

        $page->setFont($font, 8)->drawText('RegOT ' . round($all_total_reg_ot, 2), $dim_x + $now_x, $dim_y, 'UTF8');
        if ($all_messerve_reg_ot > 0) {
            $page->setFont($italic, 8)->drawText($all_messerve_reg_ot, $dim_x + $now_x + 47, $dim_y, 'UTF8');
        }
        $now_x += $now_inc;

        $page->setFont($font, 8)->drawText('RegND ' . round($all_total_reg_nd, 2), $dim_x + $now_x, $dim_y, 'UTF8');
        $now_x += $now_inc;

        $page->setFont($font, 8)->drawText('RegNDOT ' . round($all_total_reg_nd_ot, 2), $dim_x + $now_x, $dim_y, 'UTF8');
        if ($all_messerve_reg_nd_ot > 0) {
            $page->setFont($italic, 8)->drawText($all_messerve_reg_nd_ot, $dim_x + $now_x + 50, $dim_y, 'UTF8');
        }
        $now_x += $now_inc;

        /* New line */
        $now_x = $dim_x + ($i * 22) + 110 + 66;
        $dim_y -= 10;

        $page->setFont($font, 8)->drawText('SunSp ' . round($all_total_sun, 2), $dim_x + $now_x, $dim_y, 'UTF8');
        $now_x += $now_inc;

        $page->setFont($font, 8)->drawText('SunSpOT ' . round($all_total_sun_ot, 2), $dim_x + $now_x, $dim_y, 'UTF8');
        if ($all_messerve_sun_ot > 0) {
            $page->setFont($italic, 8)->drawText($all_messerve_sun_ot, $dim_x + $now_x + 47, $dim_y, 'UTF8');
        }
        $now_x += $now_inc;

        $page->setFont($font, 8)->drawText('SunSpND ' . round($all_total_sun_nd, 2), $dim_x + $now_x, $dim_y, 'UTF8');
        $now_x += $now_inc;

        $page->setFont($font, 8)->drawText('SunSpNDOT ' . round($all_total_sun_nd_ot, 2), $dim_x + $now_x, $dim_y, 'UTF8');
        if ($all_messerve_sun_nd_ot > 0) {
            $page->setFont($italic, 8)->drawText($all_messerve_sun_nd_ot, $dim_x + $now_x + 47, $dim_y, 'UTF8');
        }
        $now_x += $now_inc;


        /* New line */

        $now_x = $dim_x + ($i * 22) + 110 + 66;
        $dim_y -= 10;

        $page->setFont($font, 8)->drawText('RestSp ' . round($all_total_rest, 2), $dim_x + $now_x, $dim_y, 'UTF8');
        $now_x += $now_inc;

        $page->setFont($font, 8)->drawText('RestSpOT ' . round($all_total_rest_ot, 2), $dim_x + $now_x, $dim_y, 'UTF8');
        if ($all_messerve_rest_ot > 0) {
            $page->setFont($italic, 8)->drawText($all_messerve_rest_ot, $dim_x + $now_x + 47, $dim_y, 'UTF8');
        }
        $now_x += $now_inc;

        $page->setFont($font, 8)->drawText('RestSpND ' . round($all_total_rest_nd, 2), $dim_x + $now_x, $dim_y, 'UTF8');
        $now_x += $now_inc;

        $page->setFont($font, 8)->drawText('RestSpNDOT ' . round($all_total_rest_nd_ot, 2), $dim_x + $now_x, $dim_y, 'UTF8');
        if ($all_messerve_rest_nd_ot > 0) {
            $page->setFont($italic, 8)->drawText($all_messerve_rest_nd_ot, $dim_x + $now_x + 47, $dim_y, 'UTF8');
        }
        $now_x += $now_inc;

        /* New line */
        $now_x = $dim_x + ($i * 22) + 110 + 66;
        $dim_y -= 10;

        $page->setFont($font, 8)->drawText('Leg ' . round($all_total_legal, 2), $dim_x + $now_x, $dim_y, 'UTF8');
        $now_x += $now_inc;

        $page->setFont($font, 8)->drawText('LegOT ' . round($all_total_legal_ot, 2), $dim_x + $now_x, $dim_y, 'UTF8');
        if ($all_messerve_legal_ot > 0) {
            $page->setFont($italic, 8)->drawText($all_messerve_legal_ot, $dim_x + $now_x + 47, $dim_y, 'UTF8');
        }
        $now_x += $now_inc;

        $page->setFont($font, 8)->drawText('LegND ' . round($all_total_legal_nd, 2), $dim_x + $now_x, $dim_y, 'UTF8');
        $now_x += $now_inc;

        $page->setFont($font, 8)->drawText('LegNDOT ' . round($all_total_legal_nd_ot, 2), $dim_x + $now_x, $dim_y, 'UTF8');
        if ($all_messerve_legal_nd_ot > 0) {
            $page->setFont($italic, 8)->drawText($all_messerve_legal_nd_ot, $dim_x + $now_x + 47, $dim_y, 'UTF8');
        }
        $now_x += $now_inc;

        $page->setFont($font, 8)->drawText('Leg UA ' . round($all_total_legal_unattend, 2), $dim_x + $now_x, $dim_y, 'UTF8');
        $now_x += $now_inc;

        $page->setFont($font, 8)->drawText('Pilipinas Messerve Inc.  |  4/F San Diego Building, 462 Carlos Palanca St., Quiapo, Manila 1001', $dim_x, 12);

        $pdf->pages[] = $page;

        $date_start = $this->_request->getParam('date_start');

        $group_id = $this->_request->getParam('group_id');

        $Group = new Messerve_Model_Group();

        $Group->find($group_id);

        $filename = $folder . "Summary_{$this->_client->getName()}_{$Group->getName()}_{$group_id}_{$date_start}_{$this->_last_date}.pdf";

        $pdf->save($filename);

        if ($standalone != '' && $standalone == 'true') $this->_redirect($_SERVER['HTTP_REFERER']);

    }


    protected function get_cutoff_attended_days($employee_id, $date_start, $date_end)
    {
        $AttendanceDb = new Messerve_Model_DbTable_Attendance();

        $select = $AttendanceDb->select();

        $select->from($AttendanceDb, array("COUNT(*) AS amount"))
            ->where("employee_id = $employee_id
                AND datetime_start >= '$date_start 00:00'
                AND datetime_end <= '$date_end 23:59'
                AND (start_1 >= 1 OR legal_unattend > 0)");

        $rows = $AttendanceDb->fetchAll($select);

        return ($rows[0]->amount);
    }

    public function exportAction()
    {  // CSV export
        $this->_helper->layout()->disableLayout();

        $period_covered = $this->_request->getParam('period_covered');

        header('Content-type: text/csv');
        header('Content-Disposition: attachment; filename="Payroll_report-' . $period_covered . '.csv"');


        $PayrollMap = new Messerve_Model_Mapper_PayrollTemp();
        $payroll = $PayrollMap->fetchList("period_covered = '{$period_covered}'", array("lastname", "firstname", "employee_number", "is_reliever DESC"));
        $payroll_array = array();


        foreach ($payroll as $pvalue) {

            $employee_type = 'Regular';

            if ($pvalue->getIsReliever() == 'yes') {
                $employee_type = 'Reliever';
            }

            $payroll_meta = json_decode($pvalue->getDeductionData());

            $bop_maintenance = $pvalue->getBopMaintenance();
            $bop_rental = 0;

            $BOP = new Messerve_Model_Bop();
            $Employee = new Messerve_Model_Employee();
            $Employee->find($pvalue->getEmployeeId());

            if ($Employee->getBopId() > 0) {
                $BOP->find($Employee->getBopId());

                if (stripos($BOP->getName(), 'R1') === false) {
                    //
                } else {
                    $bop_maintenance = 0;
                    $bop_rental = $pvalue->getBopMaintenance();
                }
            }

            $other_deductions = $pvalue->getBopMotorcycle() + $pvalue->getBopInsurance()
                + $pvalue->getAccident() + $pvalue->getUniform()
                + $pvalue->getAdjustment() + $pvalue->getMiscellaneous()
                + $pvalue->getCommunication() + $pvalue->getFuelDeduction()
                + $pvalue->lost_card + $pvalue->food;

            $other_deductions = number_format(round($other_deductions * -1, 2), 2);

            $this_row = array(
                'Period covered' => $pvalue->getPeriodCovered()
                , 'Client name' => $pvalue->getClientName()
                , 'Group name' => strtoupper($pvalue->getGroupName())
                , 'Employee type' => $employee_type
                , 'Employee number' => $pvalue->getEmployeeNumber()

                , 'Account number' => $pvalue->getAccountNumber()

                , 'Last name' => $pvalue->getLastName()
                , 'First name' => $pvalue->getFirstName()
                , 'Middle name' => $pvalue->getMiddleName()

                , 'BasicPay' => number_format($pvalue->getBasicPay(), 2)

                , 'Ecola' => number_format(round($pvalue->getEcola(), 2), 2)


                , 'Ecola' => number_format(round($pvalue->getEcola(), 2), 2)

                , 'Incentives' => number_format(round($pvalue->getIncentives(), 2), 2)
                , '13th month pay' => number_format(round($pvalue->getThirteenthMonth(), 2), 2)

                , 'BOP maintenance' => $bop_maintenance
                , 'BOP rental' => $bop_rental

                , 'Fuel addition' => number_format(round($pvalue->getFuelAddition(), 2), 2)

                , 'Misc addition' => number_format(round($pvalue->getMiscAddition(), 2), 2)
                , 'Paternity' => number_format(round($pvalue->getPaternity(), 2), 2)

                , 'Gross pay' => number_format(round($pvalue->getGrossPay(), 2), 2)


                , 'SSS' => number_format(round($pvalue->getSss() * -1, 2), 2)
                , 'Philhealth' => number_format(round($pvalue->getPhilhealth() * -1, 2), 2)
                , 'HDMF' => number_format(round($pvalue->getHdmf() * -1, 2), 2)

                    // , 'Cash bond' => number_format(round($pvalue->getCashBond() * -1, 2), 2)
                    // , 'Insurance' => number_format(round($pvalue->getInsurance() * -1, 2), 2)
                    // , 'Misc deduction'=>number_format(round($pvalue->getMiscDeduction() * -1,2),2)

                , 'SSS loan' => number_format(round($pvalue->getSSSLoan() * -1, 2), 2)
                , 'HDMF loan' => number_format(round($pvalue->getHDMFLoan() * -1, 2), 2)

                    // , 'Other deductions' => $other_deductions

                    // , 'Net pay' => 0


                , 'Net pay' => number_format(round($pvalue->getNetPay(), 2), 2)

                , 'SSS deductions (Table/Calculated)' => $payroll_meta->sss_pair[0] . ' / ' . $payroll_meta->sss_pair[1]
                , 'SSS More data' => @$payroll_meta->sss_debug

                , 'Fuel hours' => number_format(round($pvalue->getFuelHours(), 2), 2)
                , 'Fuel allotment' => number_format(round($pvalue->getFuelAllotment(), 2), 2)
                , 'Fuel purchased' => number_format(round($pvalue->getFuelUsage(), 2), 2)
                , 'Fuel overage L' => number_format(round($pvalue->getFuelUsage() - $pvalue->getFuelAllotment(), 2), 2)
                , 'Fuel price' => number_format(round($pvalue->getFuelPrice(), 2), 2)
                , 'Fuel overage' => number_format(round($pvalue->getFuelOverage() * -1, 2), 2)


                , 'Accident' => number_format(round($pvalue->getAccident() * -1, 2), 2)
                , 'Uniform' => number_format(round($pvalue->getUniform() * -1, 2), 2)
                , 'Adjustment' => number_format(round($pvalue->getAdjustment() * -1, 2), 2)
                , 'Miscellaneous' => number_format(round($pvalue->getMiscellaneous() * -1, 2), 2)
                , 'Communication' => number_format(round($pvalue->getCommunication() * -1, 2), 2)
                , 'Fuel deduction' => number_format(round($pvalue->getFuelDeduction() * -1, 2), 2)
                , 'Lost card' => number_format(round($pvalue->lost_card * -1, 2), 2)
                , 'Food' => number_format(round($pvalue->food * -1, 2), 2)


                , 'BOP motorcycle' => $pvalue->getBopMotorcycle() * -1
                , 'BOP ins/reg' => $pvalue->getBopInsurance() * -1
            , 'PayrollMeta' => $pvalue->getPayrollMeta()


            );


            $misc_deduction = json_decode($pvalue->getDeductionData());
            $misc_deduction_string = '';

            if (count($misc_deduction) > 0) {

                foreach ($misc_deduction as $mkey=>$mvalue) {
                    if(is_numeric($mkey) && property_exists($mvalue,'type')) {
                        $amount = number_format(round($mvalue->amount * -1, 2), 2);
                        $misc_deduction_string .= "{$mvalue->type}: {$amount}, ";
                    }
                }
            }

            $this_row['Misc deduction data'] = $misc_deduction_string;


            $payroll_array[] = $this_row;

            // preprint($this_row,1);
        }
        // preprint($payroll_array);

        $this->view->payroll = $payroll_array;
    }

    public function etpsAction()
    {  // CSV export
        $this->_helper->layout()->disableLayout();

        $period_covered = $this->_request->getParam('period_covered');

        header('Content-type: text/csv');
        header('Content-Disposition: attachment; filename="ETPS_export.csv"');

        $PayrollMap = new Messerve_Model_Mapper_PayrollTemp();
        $payroll = $PayrollMap->fetchList("period_covered = '{$period_covered}'", array("lastname", "firstname", "employee_number", "is_reliever DESC"));

        $payroll_array = array();

        foreach ($payroll as $pvalue) {
            $account_number = (int)$pvalue->getAccountNumber();
            if (!$account_number > 0) continue;

            $employee_type = 'Regular';

            if ($pvalue->getIsReliever() == 'yes') {
                $employee_type = 'Reliever';
            }

            $employee_number = $pvalue->getEmployeeNumber();

            if (isset($payroll_array[$employee_number])) {
                $payroll_array[$employee_number]['salary'] += round($pvalue->getNetPay(), 2);
            } else {

                $this_row = array(
                    'empno' => $employee_number
                , 'emplname' => $pvalue->getLastName()
                , 'salary' => round($pvalue->getNetPay(), 2)
                , 'actno' => strtoupper($pvalue->getAccountNumber())
                , 'empfname' => $pvalue->getFirstName()
                , 'depbrcode' => '73'
                );


                $payroll_array[$employee_number] = $this_row;
            }
        }

        // preprint($payroll_array,1);

        $this->view->payroll = $payroll_array;
    }

    public function thirteenthAction()
    {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout->disableLayout();
        header('Content-type', 'text/csv');
        header("Content-Disposition: attachment;filename=thirteen.csv");
        header('Content-Type: text/html; charset=utf-8');

        $EmployeeMap = new Messerve_Model_Mapper_Employee();
        $employees = $EmployeeMap->fetchList('1', array('group_id ASC', 'lastname ASC', 'firstname ASC'));

        $last_year = date('Y', strtotime('last year'));
        $this_year = date('Y');

        foreach ($employees as $evalue) {
            $Group = new Messerve_Model_Group();
            $Group->find($evalue->getGroupId());
            $Rate = new Messerve_Model_Rate();
            $Rate->find($Group->getRateId());

            // preprint($Rate->toArray(),1);

            $pre_jan = $this->_get_work_duration($evalue->getId(), 0, $last_year . '-11-16', $last_year . '-12-31 23:59');
            $post_jan = $this->_get_work_duration($evalue->getId(), 0, $this_year . '-01-01', $this_year . '-11-15 23:59');

            if (!($pre_jan + $post_jan) > 0) continue;

            /*if($Group->getRateId() == '6') {
                $Rate5 = new Messerve_Model_Rate();
                $Rate5->find(5);
                echo "\n{$evalue->getEmployeeNumber()}\t{$Group->getName()}\t{$evalue->getLastName()}\t{$evalue->getFirstName()}\t{$pre_jan}\t{$Rate5->getReg()}\t{$post_jan}\t{$Rate->getReg()}";
            } else {
                echo "\n{$evalue->getEmployeeNumber()}\t{$Group->getName()}\t{$evalue->getLastName()}\t{$evalue->getFirstName()}\t0\t0\t{$post_jan}\t{$Rate->getReg()}";
            }*/
            echo "\n{$evalue->getEmployeeNumber()}\t{$Group->getName()}\t{$evalue->getLastName()}\t{$evalue->getFirstName()}\t{$pre_jan}\t{$Rate->getReg()}\t{$post_jan}\t{$Rate->getReg()}";

            // echo "\n{$evalue->getEmployeeNumber()}\t{$Group->getName()}\t{$evalue->getLastName()}\t{$evalue->getFirstName()}\t0\t0\t{$post_jan}\t{$Rate->getReg()}";

        }

    }

    protected function _get_work_duration($employee_id, $group_id = 0, $date_start, $date_end)
    {

        $AttendDB = new Messerve_Model_DbTable_Attendance();

        $select = $AttendDB->select();

        $select
            ->setIntegrityCheck(false)
            ->from('attendance', array(
                    'total' => '(SUM(reg) + SUM(reg_nd))')
            )
            ->join('employee', 'employee.id = attendance.employee_id')
            ->where('attendance.employee_id = ?', $employee_id)
            ->where("datetime_start >= '{$date_start} 00:00' AND datetime_start <= '{$date_end} 23:59'");;

        if ($group_id > 0) {
            $select->where('attendance.group_id = ?', $group_id);
        }

        // die($select->assemble());
        $result = $AttendDB->fetchRow($select);

        return (float)$result->total;
    }

    protected function get_sss_deduction($total_pay)
    {
        $SSS = new Messerve_Model_DbTable_Sss();

        $result = $SSS->fetchRow("`min` <= $total_pay AND `max` >= $total_pay");

        $table_sss = (int)$result->employee;

        $multiplier = .0363;

        $calculated_sss = $multiplier * $total_pay;

        return array($table_sss, $calculated_sss);
    }

    protected function get_philhealth_deduction($base_pay)
    {
        $philhealth = new Messerve_Model_DbTable_Philhealth();

        $result = $philhealth->fetchRow("`min` <= $base_pay AND `max` >= $base_pay");

        $table_philhealth = (int)$result->employee;

        return $table_philhealth;
    }


    public function philhealthAction()
    {
        $this->_helper->layout()->disableLayout();

        $period_covered = $this->_request->getParam('period_covered');
        $first_cutoff = date('Y-m-01', strtotime($period_covered));

        header('Content-type: text/csv');
        header('Content-Disposition: attachment; filename="Philhealth_report-' . $first_cutoff . '-' . $period_covered . '.csv"');


        $PayrollMap = new Messerve_Model_Mapper_PayrollTemp();
        $payroll = $PayrollMap->fetchList("(period_covered = '{$period_covered}' OR period_covered = '{$first_cutoff}')
            AND is_reliever = 'no'"
            , array("period_covered", "lastname", "firstname", "employee_number"));

        $payroll_array = array();

        foreach ($payroll as $pvalue) {
            if (!array_key_exists($pvalue->employee_id, $payroll_array)) {
                $Employee = new Messerve_Model_Employee();
                $Employee->find($pvalue->employee_id);

                $payroll_array[$pvalue->employee_id] = array(
                    'Employee number' => $pvalue->employee_number
                , 'Last name' => $pvalue->lastname
                , 'First name' => $pvalue->firstname
                , 'Middle name' => $pvalue->middlename
                , 'Birth date' => ''
                , 'Philhealth no' => $Employee->getPhilhealth()
                , '1st half' => 0
                , '2nd half' => 0
                , 'Total deduction' => 0
                , 'EE' => 0
                , 'ER' => 0
                , 'Total' => 0
                );
            }

            if ($pvalue->period_covered == $first_cutoff) { // First cut-off
                $payroll_array[$pvalue->employee_id]['1st half'] = $pvalue->philhealth;
            } else { // Second cut-off
                $payroll_array[$pvalue->employee_id]['2nd half'] = $pvalue->philhealth;
            }
        }

        foreach ($payroll_array as $key => $value) {
            $total_employee_share = $payroll_array[$key]['1st half'] + $payroll_array[$key]['2nd half'];
            $payroll_array[$key]['Total deduction'] = $total_employee_share;
            $payroll_array[$key]['EE'] = $total_employee_share;
            $payroll_array[$key]['ER'] = $total_employee_share;
            $payroll_array[$key]['Total'] = $payroll_array[$key]['EE'] + $payroll_array[$key]['ER'];
        }


        $this->view->payroll = $payroll_array;
    }

    public function hdmfAction()
    {
        $this->_helper->layout()->disableLayout();

        $period_covered = $this->_request->getParam('period_covered');
        $first_cutoff = date('Y-m-01', strtotime($period_covered));

        header('Content-type: text/csv');
        header('Content-Disposition: attachment; filename="HDMF_report-' . $first_cutoff . '-' . $period_covered . '.csv"');

        $PayrollMap = new Messerve_Model_Mapper_PayrollTemp();
        $payroll = $PayrollMap->fetchList("(period_covered = '{$period_covered}' OR period_covered = '{$first_cutoff}')
            AND is_reliever = 'no'"
            , array("period_covered", "lastname", "firstname", "employee_number"));

        $payroll_array = array();

        foreach ($payroll as $pvalue) {
            if (!array_key_exists($pvalue->employee_id, $payroll_array)) {
                $Employee = new Messerve_Model_Employee();
                $Employee->find($pvalue->employee_id);

                $payroll_array[$pvalue->employee_id] = array(
                    'Employee number' => $pvalue->employee_number
                , 'Last name' => $pvalue->lastname
                , 'First name' => $pvalue->firstname
                , 'Middle name' => $pvalue->middlename
                , 'Birth date' => ''
                , 'HDMF no' => $Employee->getHdmf()
                , '1st half' => 0
                , '2nd half' => 0
                , 'Total deduction' => 0
                , 'EE' => 0
                , 'ER' => 0
                , 'Total' => 0
                );
            }

            if ($pvalue->period_covered == $first_cutoff) { // First cut-off
                $payroll_array[$pvalue->employee_id]['1st half'] = $pvalue->hdmf;
            } else { // Second cut-off
                $payroll_array[$pvalue->employee_id]['2nd half'] = $pvalue->hdmf;
            }
        }

        foreach ($payroll_array as $key => $value) {
            $total_employee_share = $payroll_array[$key]['1st half'] + $payroll_array[$key]['2nd half'];
            $payroll_array[$key]['Total deduction'] = $total_employee_share;
            $payroll_array[$key]['EE'] = $total_employee_share;
            $payroll_array[$key]['ER'] = $total_employee_share;
            $payroll_array[$key]['Total'] = $payroll_array[$key]['EE'] + $payroll_array[$key]['ER'];
        }

        $this->view->payroll = $payroll_array;
    }

    public function sssAction()
    {
        $this->_helper->layout()->disableLayout();

        $period_covered = $this->_request->getParam('period_covered');
        $first_cutoff = date('Y-m-01', strtotime($period_covered));

        header('Content-type: text/csv');
        header('Content-Disposition: attachment; filename="SSS_report-' . $first_cutoff . '-' . $period_covered . '.csv"');


        $PayrollMap = new Messerve_Model_Mapper_PayrollTemp();
        $payroll = $PayrollMap->fetchList("(period_covered = '{$period_covered}' OR period_covered = '{$first_cutoff}')
            AND is_reliever = 'no'"
            , array("period_covered", "lastname", "firstname", "employee_number"));

        $payroll_array = array();

        foreach ($payroll as $pvalue) {
            if (!array_key_exists($pvalue->employee_id, $payroll_array)) {
                $Employee = new Messerve_Model_Employee();
                $Employee->find($pvalue->employee_id);

                $payroll_array[$pvalue->employee_id] = array(
                    'Employee number' => $pvalue->employee_number
                , 'Last name' => $pvalue->lastname
                , 'First name' => $pvalue->firstname
                , 'Middle name' => $pvalue->middlename
                , 'Birth date' => ''
                , 'SSS no' => $Employee->getSss()
                , '1st half' => 0
                , '2nd half' => 0
                , 'Total deduction' => 0
                , 'EE' => 0
                , 'ER' => 0
                , 'Total' => 0
                , 'Total gross pay' => 0
                , 'Gross pay 1' => 0
                , 'Gross pay 2' => 0
                );
            }

            if ($pvalue->period_covered == $first_cutoff) { // First cut-off
                $payroll_array[$pvalue->employee_id]['1st half'] = $pvalue->sss;
                $payroll_array[$pvalue->employee_id]['Gross pay 1'] = $pvalue->gross_pay;
            } else { // Second cut-off
                $payroll_array[$pvalue->employee_id]['2nd half'] = $pvalue->sss;
                $payroll_array[$pvalue->employee_id]['Gross pay 2'] = $pvalue->gross_pay;
            }
        }

        foreach ($payroll_array as $key => $value) {
            $total_employee_share = $payroll_array[$key]['1st half'] + $payroll_array[$key]['2nd half'];
            $total_gross_pay = $payroll_array[$key]['Gross pay 1'] + $payroll_array[$key]['Gross pay 2'];

            $payroll_array[$key]['Total deduction'] = $total_employee_share;
            $payroll_array[$key]['Total gross pay'] = $total_gross_pay;

            // $payroll_array[$key]['EE'] = $total_employee_share;
            // $payroll_array[$key]['ER'] = $total_employee_share;
            // $payroll_array[$key]['Total'] = $payroll_array[$key]['EE'] + $payroll_array[$key]['ER'];
        }

        $this->view->payroll = $payroll_array;
    }

    // TODO:  DRY sss, hdmf, philhealth reports

    protected function get_range_attendance($date_start, $date_end)
    {
        $DB = new Messerve_Model_DbTable_Attendance();
        $db = $DB->getAdapter();

        $sql = "SELECT
                c.name as client_name, g.code as group_code
                , r.name as RiderRate
                , r.reg as RiderReg
                , r2.name as GroupRate
                , r2.reg GroupReg
                , e.rate_id, e.`firstname`, e.`middleinitial`, e.`lastname`
                , e.employee_number as empno
                , a.*
                from attendance a
                INNER JOIN employee e ON a.employee_id = e.id
                INNER JOIN `group` g ON g.id = e.group_id
                INNER JOIN `client` c ON c.id = g.client_id
                LEFT OUTER JOIN rate r on r.id = e.rate_id
                LEFT OUTER JOIN `rate` r2 ON r.id = g.rate_id

                WHERE
                datetime_start >= '$date_start'
                AND datetime_start < '$date_end'
                AND end_1 > 0
                ORDER BY employee_id, datetime_start
                ";

        // die($sql);
        $rows = $db->fetchAll($sql);

        unset($db);

        return $rows;
    }

    protected function get_range_attendance_data($rows, $date, $period = 1)
    {

        $first_cutoff = [
            '01' => 0
            , '02' => 0
            , '03' => 0
            , '04' => 0
            , '05' => 0
            , '06' => 0
            , '07' => 0
            , '08' => 0
            , '09' => 0
            , '10' => 0
            , '11' => 0
            , '12' => 0
            , '13' => 0
            , '14' => 0
            , '15' => 0
        ];

        $second_cutoff = [
            '16' => 0
            , '17' => 0
            , '18' => 0
            , '19' => 0
            , '20' => 0
            , '21' => 0
            , '22' => 0
            , '23' => 0
            , '24' => 0
            , '25' => 0
            , '26' => 0
            , '27' => 0
            , '28' => 0
            , '29' => 0
            , '30' => 0
            , '31' => 0
        ];

        $cut_off_dates = $first_cutoff;

        if ($period == 2) {
            $cut_off_dates = $second_cutoff;
        }

        $employees = [];

        foreach ($rows as $row) {
            if (!isset($employees[$row['employee_id']])) {

                $employee_id = $row['employee_id'];

                $employees[$employee_id] = [
                        'period' => $date
                        , 'client_name' => $row['client_name']
                        , 'group_name' => $row['group_code']
                        , 'empno' => $row['empno']
                        , 'lastname' => $row['lastname']
                        , 'middlename' => $row['middleinitial']
                        , 'firstname' => $row['firstname']
                    ]
                    + $cut_off_dates +
                    [
                        'cutoff_total' => 0
                        , 'reg_total' => 0
                        , 'reg_nd_total' => 0
                        , 'reg_ot_total' => 0
                        , 'reg_nd_ot_total' => 0

                        , 'sun_total' => 0
                        , 'sun_nd_total' => 0
                        , 'sun_ot_total' => 0
                        , 'sun_nd_ot_total' => 0

                        , 'spec_total' => 0
                        , 'spec_nd_total' => 0
                        , 'spec_ot_total' => 0
                        , 'spec_nd_ot_total' => 0

                        , 'legal_total' => 0
                        , 'legal_nd_total' => 0
                        , 'legal_ot_total' => 0
                        , 'legal_nd_ot_total' => 0
                        , 'legal_unattend' => 0

                        , 'rest_total' => 0
                        , 'rest_nd_total' => 0
                        , 'rest_ot_total' => 0
                        , 'rest_nd_ot_total' => 0
                    ];
            }

            $today = date('d', strtotime($row['datetime_start']));

            $todays_hours = array_sum([
                $row['reg'], $row['reg_ot'], $row['reg_nd'], $row['reg_nd_ot']
                , $row['sun'], $row['sun_ot'], $row['sun_nd'], $row['sun_nd_ot']
                , $row['spec'], $row['spec_ot'], $row['spec_nd'], $row['spec_nd_ot']
                , $row['legal'], $row['legal_ot'], $row['legal_nd'], $row['legal_nd_ot'], $row['legal_unattend']
                , $row['rest'], $row['rest_ot'], $row['rest_nd'], $row['rest_nd_ot']
            ]);

            $employees[$employee_id]['reg_total'] += $row['reg'];
            $employees[$employee_id]['reg_nd_total'] += $row['reg_nd'];
            $employees[$employee_id]['reg_ot_total'] += $row['reg_ot'];
            $employees[$employee_id]['reg_nd_ot_total'] += $row['reg_nd_ot'];

            $employees[$employee_id]['sun_total'] += $row['sun'];
            $employees[$employee_id]['sun_nd_total'] += $row['sun_nd'];
            $employees[$employee_id]['sun_ot_total'] += $row['sun_ot'];
            $employees[$employee_id]['sun_nd_ot_total'] += $row['sun_nd_ot'];

            $employees[$employee_id]['spec_total'] += $row['spec'];
            $employees[$employee_id]['spec_nd_total'] += $row['spec_nd'];
            $employees[$employee_id]['spec_ot_total'] += $row['spec_ot'];
            $employees[$employee_id]['spec_nd_ot_total'] += $row['spec_nd_ot'];

            $employees[$employee_id]['legal_total'] += $row['legal'];
            $employees[$employee_id]['legal_nd_total'] += $row['legal_nd'];
            $employees[$employee_id]['legal_ot_total'] += $row['legal_ot'];
            $employees[$employee_id]['legal_nd_ot_total'] += $row['legal_nd_ot'];
            $employees[$employee_id]['legal_unattend'] += $row['legal_unattend'];

            $employees[$employee_id]['rest_total'] += $row['rest'];
            $employees[$employee_id]['rest_nd_total'] += $row['rest_nd'];
            $employees[$employee_id]['rest_ot_total'] += $row['rest_ot'];
            $employees[$employee_id]['rest_nd_ot_total'] += $row['rest_nd_ot'];

            $employees[$employee_id][$today] = $todays_hours;
            $employees[$employee_id]['cutoff_total'] += $todays_hours;

        }

        return $employees;
    }

    public function retrolegalAction()
    {
        set_time_limit(0);


        $folder = realpath(APPLICATION_PATH . '/../public/export');

        $date = '2015-01-01';

        while ($date < '2016-07-01') {

            $date_15 = date('Y-m-15', strtotime($date));
            $date_16 = date('Y-m-16', strtotime($date));

            $next_month = strtotime('next month', strtotime($date));
            $date_last = date('Y-m-d', strtotime("yesterday", $next_month));

            echo "$date - $date_15 / $date_16 - $date_last <br>";


            $rows = $this->get_range_attendance($date, $date_15);

            $employees = $this->get_range_attendance_data($rows, $date);
            $headers = ['header' => []];

            foreach (array_keys(array_values($employees)[0]) as $head) {
                $headers['header'][$head] = $head;
            }

            $Excel = new PHPExcel();

            // Rename worksheet
            $Excel->getActiveSheet()->setTitle('Oh hai');
            // Set active sheet index to the first sheet, so Excel opens this as the first sheet
            $Excel->setActiveSheetIndex(0);

            $Excel->getActiveSheet()->fromArray($headers + $employees, null, 'A1');

            $writer = PHPExcel_IOFactory::createWriter($Excel, 'Excel5');

            $writer->save($folder . '/' . $date . '.xls');

            // $writer->save('/home/vagrant/Code/projects/rolling/public/export/' . $date . '.xls');

            $rows = $this->get_range_attendance($date_16, $date_last);

            $employees = $this->get_range_attendance_data($rows, $date_16, 2);
            $headers = ['header' => []];

            foreach (array_keys(array_values($employees)[0]) as $head) {
                $headers['header'][$head] = $head;
            }

            $Excel = new PHPExcel();

            // Rename worksheet
            $Excel->getActiveSheet()->setTitle('Oh hai');
            // Set active sheet index to the first sheet, so Excel opens this as the first sheet
            $Excel->setActiveSheetIndex(0);

            $Excel->getActiveSheet()->fromArray($headers + $employees, null, 'A1');

            $writer = PHPExcel_IOFactory::createWriter($Excel, 'Excel5');

            $writer->save($folder . '/' . $date_16 . '.xls');

            $date = date('Y-m-d', $next_month);
        }
        die('OH HAI');
    }
}

