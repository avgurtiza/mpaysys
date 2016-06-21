<?php

class Payroll_IndexController extends Zend_Controller_Action
{
    protected $_employee_payroll, $_employer_bill, $_client, $_pay_period, $_fuelcost, $_last_date;

    protected $_user_auth, $_config;

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

        if ($this->_user_auth->type != 'admin' && $this->_user_auth->type != 'accounting') {
            throw new Exception('You are not allowed to access this module.');
        }

        $this->_fuelcost = $this->_request->getParam('fuelcost');

        $_SESSION['fuelcost'] = $this->_fuelcost;

        $this->_config = Zend_Registry::get('config');
    }

    public function indexAction()
    {
        // action body
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
    }


    public function clientreportAction()
    {


        $this->_compute();

        $client = $this->_client;

        $group_id = $this->_request->getParam('group_id');

        $Group = new Messerve_Model_Group();

        $Group->find($group_id);

        $ClientRate = new Messerve_Model_RateClient();

        // $ClientRate->find($Group->getRateClientId());

        // TODO:  find by date!

        $RateSchedule = new Messerve_Model_EmployeeRateSchedule();
        // $RateSchedule->getMapper()->findOneByField("group_id",$group_id,$RateSchedule);
        $rates = $RateSchedule->getMapper()->fetchList("group_id = $group_id", "date_active DESC");
        // preprint($rates,1);

        // $ClientRate->find($RateSchedule->getClientRateId());

        if (count($rates) > 0) {
            $ClientRate->find($rates[0]->getClientRateId());
        } else {
            $ClientRate->find($Group->getRateClientId());
            // $ClientRate->find();
        }

        $date_start = $this->_request->getParam('date_start');

        $cutoff_modifier = 1;

        if (strstr($date_start, '-16')) $cutoff_modifier = 0;

        $folder = realpath(APPLICATION_PATH . '/../public/export') . "/$date_start/$group_id/client/";

        $cmd = "mkdir -p $folder";


        $date_now = date("Y-m-d-hi");
        $filename = $folder . "Client_Report_{$this->_client->getName()}_{$Group->getName()}_{$group_id}_{$date_start}_{$date_now}.pdf";
        //  $filename = $folder .  "{$this->_client->getName()}_{$Group->getName()}_{$group_id}_{$date_start}_client_report.pdf";

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

        , 'legal_unattend' => $ClientRate->getLegalUnattend()

        , 'rest' => $ClientRate->getSpec()
        , 'rest_nd' => $ClientRate->getSpecNd()
        , 'rest_ot' => $ClientRate->getSpecOt()
        , 'rest_nd_ot' => $ClientRate->getSpecNdOt()
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


        , 'legal_unattend' => $this->_employer_bill['legal_unattend'] * $ClientRate->getLegalUnattend()

        , 'rest' => $this->_employer_bill['rest'] * $ClientRate->getSpec()
        , 'rest_nd' => $this->_employer_bill['rest_nd'] * $ClientRate->getSpecNd()
        , 'rest_ot' => $this->_employer_bill['rest_ot'] * $ClientRate->getSpecOt()
        , 'rest_nd_ot' => $this->_employer_bill['rest_nd_ot'] * $ClientRate->getSpecNdOt()
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

        // $pdf = new Zend_Pdf();
        // $page = new Zend_Pdf_Page(Zend_Pdf_Page::SIZE_LETTER);

        $template = realpath(APPLICATION_PATH . "/../library/Templates/billing_template.pdf");
        if (!file_exists($template)) die($template . ': template does not exist.');

        $pdf = Zend_Pdf::load($template);
        $page = $pdf->pages[0];

        $font = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA);
        $bold = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_BOLD);
        $italic = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_ITALIC);
        $mono = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_COURIER);

        // $logo = Zend_Pdf_Image::imageWithPath(APPLICATION_PATH . '/../public/images/messerve.png');

        $pageHeight = $page->getHeight();
        $pageWidth = $page->getWidth();

        $dim_x = 32;

        $dim_y = $pageHeight - 25;
        $total = 0;

        $dim_y -= 10;

        $imageHeight = 41;
        $imageWidth = 119;

        $bottomPos = $dim_y - $imageHeight;
        $rightPos = $dim_x + $imageWidth;

        // $page->drawImage($logo, $dim_x, $bottomPos, $rightPos, $dim_y);

        $dim_x = 102;
        $dim_y -= 56;

        $total_hours = 0;
        $total_amount = 0;


        if ($Group->getBillingName() != '') {
            $page->setFont($bold, 10)->drawText($Group->getBillingName(), $dim_x, $dim_y - 103, 'UTF8');
            $page->setFont($bold, 8)->drawText($Group->getName(), $dim_x, $dim_y - 113, 'UTF8');

            // $page->setFont($font, 7)->drawText($Group->getBillingName(), $dim_x, ($dim_y - 111));
        } else {
            $page->setFont($bold, 10)->drawText($this->_client->getName() . ' ' . $Group->getName(), $dim_x, $dim_y - 103, 'UTF8');

        }

        // $page->setFont($bold, 10)->drawText($Group->getAddress(), $dim_x, $dim_y - 126);
        $style_text = new Zend_Pdf_Style;

        $y = $dim_y - 126;


        $lines = explode("\n", $Group->getAddress());

        foreach ($lines as $line) {
            $page->setFont($font, 7)->drawText($line, $dim_x, $y);
            $y -= 8;
        }

        $page->setFont($bold, 10)->drawText($Group->getTin(), $dim_x, $dim_y - 155);

        $pay_period = str_replace('_', ' to ', $this->_request->getParam('pay_period'));

        $billing_number = date('Y', strtotime($date_start)) . str_pad($this->_client->getId(), 2, 0, STR_PAD_LEFT)
            . str_pad($group_id, 2, 0, STR_PAD_LEFT) . ((date('m', strtotime($date_start)) * 2) - $cutoff_modifier);

        $page->setFont($bold, 10)->drawText($billing_number . "-A", $dim_x + 300, $dim_y - 103);

        // $page->setFont($bold, 10)->drawText($pay_period, $dim_x + 300, $dim_y - 126);
        $good_pay_period = $date_start . " to " . $this->_request->getParam("date_end");
        $page->setFont($bold, 10)->drawText($good_pay_period, $dim_x + 300, $dim_y - 126);


        $page->setFont($bold, 10)->drawText(date('Y-m-d'), $dim_x + 300, $dim_y - 146);

        $dim_y -= 199;

        $dim_x = 62;
        /*
        preprint($ClientRate->toArray());
        preprint($client_rate_array);
        preprint($total_bill);
        preprint($this->_employer_bill,1);
        */
        foreach ($total_bill as $key => $value) {


            $page->setFont($bold, 6)->drawText(ucwords(str_replace('_', ' ', $key)), $dim_x, $dim_y);
            $page->setFont($mono, 6)->drawText($hours_description[$key]
                , $dim_x + 55, $dim_y);

            if ($this->_employer_bill[$key] > 0) {
                $page->setFont($mono, 6)->drawText(str_pad(round($this->_employer_bill[$key], 2), 8, ' ', STR_PAD_LEFT)
                    , $dim_x + 150, $dim_y);


                $page->setFont($mono, 6)->drawText(str_pad(round($client_rate_array[$key], 2), 8, ' ', STR_PAD_LEFT)
                    , $dim_x + 200, $dim_y);


                $total_hours += round($this->_employer_bill[$key], 2);
            }

            if ($value > 0) {
                $page->setFont($mono, 6)->drawText(str_pad(number_format($value, 2), 12, ' ', STR_PAD_LEFT), $dim_x + 260, $dim_y);
                $total_amount += round($value, 2);

                if (!$client->getNoVat() > 0) {
                    $vat_net = $value / 1.12;

                    $page->setFont($mono, 6)->drawText(str_pad(number_format($vat_net, 2), 12, ' ', STR_PAD_LEFT)
                        , $dim_x + 360, $dim_y);

                    $page->setFont($mono, 6)->drawText(str_pad(number_format($vat_net * 0.12, 2), 12, ' ', STR_PAD_LEFT)
                        , $dim_x + 440, $dim_y);

                }

            }

            $dim_y -= 11;
        }

        $dim_y -= 12;
        // $page->setFont($bold, 6)->drawText('Total', $dim_x, $dim_y);
        $page->setFont($mono, 6)->drawText(str_pad(round($total_hours, 2), 8, ' ', STR_PAD_LEFT), $dim_x + 150, $dim_y);
        $page->setFont($mono, 6)->drawText(str_pad(number_format($total_amount, 2), 12, ' ', STR_PAD_LEFT), $dim_x + 260, $dim_y);

        $vat_net = $total_amount / 1.12;

        if (!$client->getNoVat() > 0) $page->setFont($mono, 6)->drawText(str_pad(number_format($vat_net, 2), 12, ' ', STR_PAD_LEFT)
            , $dim_x + 360, $dim_y);

        if (!$client->getNoVat() > 0) $page->setFont($mono, 6)->drawText(str_pad(number_format($vat_net * 0.12, 2), 12, ' ', STR_PAD_LEFT)
            , $dim_x + 440, $dim_y);

        $dim_y -= 28;

        if (!$client->getNoVat() > 0) $page->setFont($mono, 6)->drawText(str_pad(number_format($vat_net, 2), 12, ' ', STR_PAD_LEFT), $dim_x + 260, $dim_y);

        $dim_y -= 12;

        if (!$client->getNoVat() > 0) $page->setFont($mono, 6)->drawText(str_pad(number_format($vat_net * 0.12, 2), 12, ' ', STR_PAD_LEFT), $dim_x + 260, $dim_y);

        $dim_y -= 22;

        $page->setFont($mono, 6)->drawText(str_pad(number_format($total_amount, 2), 12, ' ', STR_PAD_LEFT), $dim_x + 260, $dim_y);

        // $page->setFont($font, 8)->drawText('Pilipinas Messerve Inc.  |  4/F San Diego Building, 462 Carlos Palanca St., Quiapo, Manila 1001', $dim_x, 12);

        $pdf->pages[0] = $page;

        $pdf->save($filename);

        // echo $filename;
        $this->_redirect($_SERVER['HTTP_REFERER']);
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

            $first_day_id = 0;

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

                if ($i == 1) $first_day_id = $Attendance->getId();
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

        $this->_compute();
        $this->_compute(); // TODO:  Fix this!

        $this->view->payroll = $this->_employee_payroll;

        $pdf = new Zend_Pdf();
        $font = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA);
        $bold = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_BOLD);
        $italic =  Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_ITALIC);
        $mono = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_COURIER);
        $boldmono =  Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_COURIER_BOLD);

        $logo = Zend_Pdf_Image::imageWithPath(APPLICATION_PATH . '/../public/images/messerve.png');

        $folder = realpath(APPLICATION_PATH . '/../public/export') . "/$date_start/$group_id/payslips/";
        $cmd = "mkdir -p $folder";
        shell_exec($cmd); // Create folder

        $rec_copy_data = array(
            'branch' => $Client->getName() . '-' . $Group->getName()
            , 'riders' => array()
        );

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
                        // $scheduled_deductions_array[$DeductionSchedule->getType()] = $rdvalue->getAmount();
                    }

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

                    $bop_motorcycle = $BOPAttendance->getMotorcycleDeduction();
                    $bop_insurance = $BOPAttendance->getInsuranceDeduction();


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

            $page->setFont($bold, 8)->drawText('PAYSLIP', $dim_x + 240, $dim_y);

            $dim_y -= 2;

            $page->drawRectangle($dim_x - 2, $dim_y, $dim_x + 560, 60, Zend_Pdf_Page::SHAPE_DRAW_STROKE);
            $page->drawLine($dim_x + 216, $dim_y, $dim_x + 216, 60);
            $page->drawLine($dim_x + 376, $dim_y, $dim_x + 376, 60);

            $dim_y -= 10;

            $reset_y = $dim_y;

            $PayrollLib = new Messervelib_Payroll();

            $pay_period = $this->_request->getParam('pay_period');

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

                        foreach ($rvalue as $dkey => $dvalue) {
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

                // Legal adjustments for attendance less than 8 hours
                $LegalAttendanceMap = new Messerve_Model_Mapper_Attendance();

                $legal_attendance = $LegalAttendanceMap->fetchListToArray("(attendance.employee_id = '{$Employee->getId()}')
                    AND (attendance.group_id = {$group_id})
                    AND datetime_start >= '{$date_start} 00:00'
                    AND datetime_start <= '{$date_end} 23:59'
                    AND (legal > 0 OR legal_nd > 0)");

                $legal_ua_hours = 0;

                $legal_ecola_days  = 0;

                foreach($legal_attendance as $legal_day) {
                    $legal_ecola_days++;

                    if($legal_day['reg'] > 0) {
                        $legal_ua_hours += $legal_day['reg'];
                    }

                }

                if($legal_ua_hours > 0) {

                    $sss_deductions[] = ($sss / 22 / 8) * $legal_ua_hours;

                    $legal_ua_pay = ($legal_ua_hours/8) * $pay_rate;

                    $total_no_hours += $legal_ua_hours;
                    $total_pay += $legal_ua_pay;

                    $page->setFont($font, 8)->drawText("UA REG", $dim_x + 10, $dim_y);
                    $page->setFont($mono, 8)->drawText(str_pad(number_format($legal_ua_hours, 2), 8, ' ', STR_PAD_LEFT), $dim_x + 110, $dim_y);
                    $page->setFont($mono, 8)->drawText(str_pad(number_format($legal_ua_pay, 2), 8, ' ', STR_PAD_LEFT), $dim_x + 160, $dim_y);
                }

                $dim_y -= 16;
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

            if($Employee->getGroupId() == $group_id) {
                $attended_days  = $this->get_cutoff_attended_days($Employee->getId(),$date_start,$date_end);
                $ecola_addition =  $attended_days * $ecola;
                $total_pay += $ecola_addition;

                $dim_y -= 8;
                $page->setFont($font, 8)->drawText('ECOLA (' . $attended_days . ' day/s)', $dim_x + 220, $dim_y);
                $page->setFont($mono, 8)->drawText(str_pad(number_format($ecola_addition, 2), 10, ' ', STR_PAD_LEFT), $dim_x + 300, $dim_y);

                if($legal_ecola_days > 0) {
                    $legal_ecola_addition = $legal_ecola_days * $ecola;
                    $dim_y -= 8;
                    $page->setFont($font, 8)->drawText('ECOLA - Legal (' . $legal_ecola_days . ' day/s)', $dim_x + 220, $dim_y);
                    $page->setFont($mono, 8)->drawText(str_pad(number_format($legal_ecola_addition, 2), 10, ' ', STR_PAD_LEFT), $dim_x + 300, $dim_y);
                    $total_pay += $legal_ecola_addition;
                }

                // $legal_ecola_days
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
                    $page->setFont($font, 8)->drawText('Incentives', $dim_x + 220, $dim_y);
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

            if ($group_id == $Employee->getGroupId()) {
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

            $fuel_overage = 0;  // Reset
            $fuel_deduction = 0;  // Reset

            if ($Attendance->getFuelHours() > 0) {

                $fuel_overage = $Attendance->getFuelConsumed() - $Attendance->getFuelAlloted();
                $total_deduct += $fuel_deduction;

                if ($fuel_overage > 0) {
                    $fuel_deduction = round($fuel_overage * $Attendance->getFuelCost(), 2);
                    $total_deduct += $fuel_deduction;

                    $page->setFont($font, 8)->drawText('Fuel overage', $dim_x + 380, $dim_y);
                    $page->setFont($mono, 8)->drawText(str_pad($fuel_deduction, 10, ' ', STR_PAD_LEFT), $dim_x + 480, $dim_y);
                    $dim_y -= 8;

                    $page->setFont($font, 8)->drawText(' - Allotment L', $dim_x + 380, $dim_y);
                    $page->setFont($mono, 8)->drawText(str_pad($Attendance->getFuelAlloted(), 10, ' ', STR_PAD_LEFT), $dim_x + 440, $dim_y);
                    $dim_y -= 8;

                    $page->setFont($font, 8)->drawText(' - Consumed L', $dim_x + 380, $dim_y);
                    $page->setFont($mono, 8)->drawText(str_pad($Attendance->getFuelConsumed(), 10, ' ', STR_PAD_LEFT), $dim_x + 440, $dim_y);

                    $dim_y -= 8;
                    /*
                    $page->setFont($font, 8)->drawText(' - Price/L', $dim_x + 380, $dim_y);
                    $page->setFont($mono, 8)->drawText(str_pad($Attendance->getFuelCost(), 10, ' ', STR_PAD_LEFT), $dim_x + 480, $dim_y);
                    */
                }
                // $page->setFont($mono, 8)->drawText(str_pad(number_format($value['more_income']['gasoline'],2), 10, ' ', STR_PAD_LEFT), $dim_x + 300, $dim_y);

            }
            $dim_y -= 10;

            // Scheduled deductions
            if (count($scheduled_deductions) > 0) {
                // preprint($scheduled_deductions);
                foreach ($scheduled_deductions as $sdvalue) {
                    if ($sdvalue['amount'] > 0) {
                        $total_deduct += $sdvalue['amount'];
                        $total_misc_deduct += $sdvalue['amount'];

                        $page->setFont($font, 8)->drawText(ucwords(str_replace('_', ' ', $sdvalue['type'])), $dim_x + 380, $dim_y);

                        $page->setFont($mono, 8)->drawText(str_pad(number_format($sdvalue['amount'], 2), 10, ' ', STR_PAD_LEFT), $dim_x + 480, $dim_y);

                        $dim_y -= 10;
                    }
                }

                $dim_y -= 10;
            }

            $dim_y = 82;
            $dim_y -= 8;

            $page->setFont($font, 8)->drawText('TOTAL DEDUCTION', $dim_x + 220, $dim_y);
            $page->setFont($mono, 8)->drawText(str_pad(number_format($total_deduct, 2), 10, ' ', STR_PAD_LEFT), $dim_x + 300, $dim_y);

            // TODO:  Fix hacky hack
            $PayrollTemp = new Messerve_Model_PayrollTemp();

            $PayrollTemp->getMapper()->getDbTable()
                ->delete("group_id = " . $Group->getId() . " AND period_covered = '$date_start' AND employee_id = " . $Employee->getId());
            // Delete prior record

            if (!$total_pay > 0) continue;

            $dim_y = 136;
            $dim_y -= 8;
            $page->drawLine($dim_x + 480, $dim_y, $dim_x + 530, $dim_y);

            $dim_y = 128;
            $dim_y -= 8;
            // $page->setFont($font, 10)->drawText('Total deductions', $dim_x, $dim_y);
            $page->setFont($mono, 8)->drawText(str_pad(number_format($total_deduct, 2), 10, ' ', STR_PAD_LEFT), $dim_x + 480, $dim_y);

            $dim_y = 82;
            $net_pay = $total_pay - $total_deduct;
            $page->setFont($bold, 10)->drawText('Net pay', $dim_x + 380, $dim_y);

            $dim_y -= 16;
            $page->setFont($boldmono, 12)->drawText('Php ' . str_pad(number_format($net_pay, 2), 10, ' ', STR_PAD_LEFT), $dim_x + 380, $dim_y);

            $page->setFont($font, 8)->drawText('Pilipinas Messerve Inc.  |  4/F San Diego Building, 462 Carlos Palanca St., Quiapo, Manila 1001', $dim_x, 24);

            $pdf->pages[] = $page;

            $pay_array = array(
                $value['attendance']->lastname
            , $value['attendance']->firstname
            , $value['attendance']->middleinitial
            , $value['attendance']->employee_number
            , $value['attendance']->account_number
            , round($total_pay - $total_deduct, 2)
            );

            if ($group_id == $Employee->getGroupId()) {
                $is_reliever = 'no';
            } else {
                $is_reliever = 'yes';
            }

            $scheduled_deductions["sss_pair"] = $sss_deduction;
            $scheduled_deductions["sss_debug"] = $sss_debug;


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
                ->setGrossPay($total_pay)
                ->setNetPay($net_pay)
                ->setEcola($value['pay']['e_cola'])
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

        $pdf->save($filename);

        $this->summaryreportAction();
        $this->clientreportAction();

        if ($this->_request->getParam("is_ajax") != "true") {
            $this->_redirect($_SERVER['HTTP_REFERER']);
        } else {
            echo "AJAX Complete";
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

            $select
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

        $pay_period = $this->_request->getParam('pay_period');

        $employees = $this->_fetch_employees($group_id, $date_start, $date_end);

        $employee_payroll = array();

        $employer_bill = array();

        $date1 = new DateTime($date_start); //inclusive
        $date2 = new DateTime($date_end); //exclusive
        $diff = $date2->diff($date1);
        $period_size = intval($diff->format("%a")) + 1;

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

        $AttendDB = new Messerve_Model_DbTable_Attendance();

        foreach ($employees as $evalue) {
            $total_hours = 0;

            $select = $AttendDB->select();

            $select
                ->setIntegrityCheck(false)
                ->from('attendance', array(
                        'sum_fuel_overage' => 'SUM(fuel_overage)'
                        , 'sum_reg' => 'SUM(reg)'
                        , 'sum_reg_nd' => 'SUM(reg_nd)'
                        , 'sum_reg_ot' => 'SUM(reg_ot)'
                        , 'sum_reg_nd_ot' => 'SUM(reg_nd_ot)'
                        , 'sum_sun' => 'SUM(sun)'
                        , 'sum_sun_nd' => 'SUM(sun_nd)'
                        , 'sum_sun_ot' => 'SUM(sun_ot)'
                        , 'sum_sun_nd_ot' => 'SUM(sun_nd_ot)'
                        , 'sum_spec' => 'SUM(spec)'
                        , 'sum_spec_nd' => 'SUM(spec_nd)'
                        , 'sum_spec_ot' => 'SUM(spec_ot)'
                        , 'sum_spec_nd_ot' => 'SUM(spec_nd_ot)'
                        , 'sum_legal' => 'SUM(legal)'
                        , 'sum_legal_nd' => 'SUM(legal_nd)'
                        , 'sum_legal_ot' => 'SUM(legal_ot)'
                        , 'sum_legal_nd_ot' => 'SUM(legal_nd_ot)'
                        , 'sum_legal_unattend' => 'SUM(legal_unattend)'

                        , 'sum_rest' => 'SUM(rest)'
                        , 'sum_rest_nd' => 'SUM(rest_nd)'
                        , 'sum_rest_ot' => 'SUM(rest_ot)'
                        , 'sum_rest_nd_ot' => 'SUM(rest_nd_ot)'

                        , 'today' => 'SUM(today)'
                        , 'today_nd' => 'SUM(today_nd)'
                        , 'today_ot' => 'SUM(today_ot)'
                        , 'today_nd_ot' => 'SUM(today_nd_ot)'

                        , 'tomorrow' => 'SUM(tomorrow)'
                        , 'tomorrow_nd' => 'SUM(tomorrow_nd)'
                        , 'tomorrow_ot' => 'SUM(tomorrow_ot)'
                        , 'tomorrow_nd_ot' => 'SUM(tomorrow_nd_ot)'
                        , 'day_count'=> 'COUNT(*)'
                    )
                )
                ->join('employee', 'employee.id = attendance.employee_id')
                ->where('attendance.employee_id = ?', $evalue->getId())
                ->where('attendance.group_id = ?', $group_id)
                ->where("datetime_start >= '{$date_start} 00:00' AND datetime_start <= '{$date_end} 23:59'");


            $attendance = $AttendDB->fetchRow($select);

            if ($attendance->rate_id > 0) {
                $this_rate = $rates_array[$attendance->rate_id];
                // echo "Using employee rate <br />";
            } elseif ($Group->getRateId() > 0) {
                $this_rate = $rates_array[$Group->getRateid()];
                // echo "Using group rate <br />";
            } else {
                die('Process halted:  no rates found for either employee or group.');
            }

            $AttendanceMap = new Messerve_Model_Mapper_Attendance();

            $first_day = $AttendanceMap->findOneByField(
                array('datetime_start', 'employee_id', 'group_id')
                , array($date_start, $evalue->getId(), $group_id)
            );

            if (!$first_day) continue;

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
                , 'insurance' => 25
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

            $summary_bill['reg'] += round($attendance->sum_reg, 2);
            $summary_bill['reg_nd'] += round($attendance->sum_reg_nd, 2);
            $summary_bill['reg_ot'] += round($attendance->sum_reg_ot, 2);
            $summary_bill['reg_nd_ot'] += round($attendance->sum_reg_nd_ot, 2);

            $summary_bill['spec'] += round($attendance->sum_spec, 2);
            $summary_bill['spec_nd'] += round($attendance->sum_spec_nd, 2);
            $summary_bill['spec_ot'] += round($attendance->sum_spec_ot, 2);
            $summary_bill['spec_nd_ot'] += round($attendance->sum_spec_nd_ot, 2);

            $summary_bill['legal'] += round($attendance->sum_legal, 2);
            $summary_bill['legal_nd'] += round($attendance->sum_legal_nd, 2);
            $summary_bill['legal_ot'] += round($attendance->sum_legal_ot, 2);
            $summary_bill['legal_nd_ot'] += round($attendance->sum_legal_nd_ot, 2);
            $summary_bill['legal_unattend'] += round($attendance->sum_legal_unattend, 2);

            $summary_bill['rest'] += round($attendance->sum_rest, 2);
            $summary_bill['rest_nd'] += round($attendance->sum_rest_nd, 2);
            $summary_bill['rest_ot'] += round($attendance->sum_rest_ot, 2);
            $summary_bill['rest_nd_ot'] += round($attendance->sum_rest_nd_ot, 2);

            $employer_bill[$evalue->getId()]['income'] = array(
                'reg_hours' => $attendance->sum_reg
                , 'reg' => $this_rate->Reg
                , 'reg_nd_hours' => $attendance->sum_reg_nd
                , 'reg_nd' => $this_rate->RegNd
                , 'reg_ot_hours' => $attendance->sum_reg_ot
                , 'reg_ot' => $this_rate->RegOT
                , 'reg_nd_ot_hours' => $attendance->sum_reg_nd_ot
                , 'reg_nd_ot' => $this_rate->RegNdOT
                , 'sun_hours' => $attendance->sum_sun
                , 'sun' => $this_rate->Sun
                , 'sun_nd_hours' => $attendance->sum_sun_nd
                , 'sun_nd' => $this_rate->SunNd
                , 'sun_ot_hours' => $attendance->sum_sun_ot
                , 'sun_ot' => $this_rate->SunOT
                , 'sun_nd_ot_hours' => $attendance->sum_sun_nd_ot
                , 'sun_nd_ot' => $this_rate->SunNdOt
                , 'spec_hours' => $attendance->sum_spec
                , 'spec' => $this_rate->Spec
                , 'spec_nd_hours' => $attendance->sum_spec_nd
                , 'spec_nd' => $this_rate->SpecNd
                , 'spec_ot_hours' => $attendance->sum_spec_ot
                , 'spec_ot' => $this_rate->SpecOT
                , 'spec_nd_ot_hours' => $attendance->sum_spec_nd_ot
                , 'spec_nd_ot' => $this_rate->SpecNdOt
                , 'legal_hours' => $attendance->sum_legal
                , 'legal' => $this_rate->Legal
                , 'legal_nd_hours' => $attendance->sum_legal_nd
                , 'legal_nd' => $this_rate->LegalNd
                , 'legal_ot_hours' => $attendance->sum_legal_ot
                , 'legal_ot' => $this_rate->LegalOT
                , 'legal_nd_ot_hours' => $attendance->sum_legal_nd_ot
                , 'legal_nd_ot' => $this_rate->LegalNdOt
                , 'legal_unattend_hours' => $attendance->sum_legal_unattend
                , 'legal_unattend' => $this_rate->LegalUnattend
                , 'rest_hours' => $attendance->sum_rest
                , 'rest' => $this_rate->Rest
                , 'rest_nd_hours' => $attendance->sum_rest_nd
                , 'rest_nd' => $this_rate->RestNd
                , 'rest_ot_hours' => $attendance->sum_rest_ot
                , 'rest_ot' => $this_rate->RestOT
                , 'rest_nd_ot_hours' => $attendance->sum_rest_nd_ot
                , 'rest_nd_ot' => $this_rate->RestNdOt
                , 'ecola_hours' => $total_hours
                , 'ecola' => ($this_rate->Ecola / 8)
            );


            $employer_bill[$evalue->getId()]['deductions'] = array(
                'sss_ee' => ($this_rate->SSSEmployee * -1)
                , 'sss_er' => ($this_rate->SSSEmployer * -1)

                , 'philhealth_ee' => ($this_rate->PhilhealthEmployee * -1)
                , 'philhealth_er' => ($this_rate->PhilhealthEmployer * -1)

                , 'hdmf_ee' => ($this_rate->HDMFEmployee * -1)
                , 'hdmf_er' => ($this_rate->HDMFEmployer * -1)
                , 'ec' => ($this_rate->EC * -1)

                , 'bike_rehab' => 0
                , 'bike_insurance_reg' => 0
            );

            if (strtotime($attendance->bike_rehab_end) >= strtotime($date_start)) {
                $employee_payroll[$evalue->getId()]['deductions']['bike_rehab']
                    = $this_rate->BikeRehab;

                $employer_bill[$evalue->getId()]['deductions']['bike_rehab']
                    = $this_rate->BikeRehab * -1;

            }

            if (strtotime($attendance->bike_insurance_reg_end) >= strtotime($date_start)) {
                $employee_payroll[$evalue->getId()]['deductions']['bike_insurance_reg']
                    = $this_rate->BikeInsuranceReg;

                $employer_bill[$evalue->getId()]['deductions']['bike_insurance_reg']
                    = $this_rate->BikeInsuranceReg * -1;

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
                $employer_bill[$evalue->getId()]['income'] += $more_income;
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

                $employer_bill[$evalue->getId()]['deductions'] += $more_deductions;
            }

            $employer_bill[$evalue->getId()]['deductions']['cash_bond'] = $this_rate->getCashBond() * -1;

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
    }

    public function summaryreportAction()
    {

        function round_this($in)
        {
            if (is_numeric($in)) {
                return round($in, 2);
            } else {
                return $in;
            }
        }

        // action body
        $date_start = $this->_request->getParam('date_start');
        $date_end = $this->_request->getParam('date_end');

        // $this->_compute(); // TODO:  Simplify and streamline.  Just fetch the group members and their payroll
        $this->view->payroll = $this->_employee_payroll;

        $pdf = new Zend_Pdf();
        $font = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA);
        $bold = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_BOLD);
        $italic = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_ITALIC);
        $mono = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_COURIER);

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

        $day_date = (int)substr($date_start, -2, 2);

        for ($i = 1; $i <= $period_size; $i++) {
            $page->setFont($bold, 8)->drawText($day_date, $dim_x + ($i * 25) + 150, $dim_y, 'UTF8');
            $day_date++;
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

        $employee_count = 0;

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

            $total_nd = 0;
            $total_nd_ot = 0;
            $total_total_hours = 0;

            $dates = array();

            $AttendanceMap = new Messerve_Model_Mapper_Attendance();

            $first_day_id = 0;

            $current_date = $date_start;

            $employee_id = $value['attendance']->id;

            $employee_attendance_text = array();

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

                    $Attendance->find($Attendance->getId()); // TODO: Why is this necessary?
                    // Answer:  so you'll get the whole model
                }

                $dates[$current_date] = $Attendance;

                $current_date = date('Y-m-d', strtotime('+1 day', strtotime($current_date)));

                if ($i == 1) $first_day_id = $Attendance->getId();

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

                if ($Attendance->getOtApproved() == 'yes') {
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
                }


                array_walk($attendance_array, 'round_this');

                $total_reg += $attendance_array['reg'];
                $total_reg_ot += $attendance_array['reg_ot'];
                $total_reg_nd += $attendance_array['reg_nd'];
                $total_reg_nd_ot += $attendance_array['reg_nd_ot'];

                $total_sun += $attendance_array['sun'] + $attendance_array['spec'];
                $total_sun_ot += $attendance_array['sun_ot'] + $attendance_array['spec_ot'];
                $total_sun_nd += $attendance_array['sun_nd'] + $attendance_array['spec_nd'];
                $total_sun_nd_ot += $attendance_array['sun_nd_ot'] + $attendance_array['spec_nd_ot'];

                $total_legal += $attendance_array['legal'];
                $total_legal_ot += $attendance_array['legal_ot'];
                $total_legal_nd += $attendance_array['legal_nd'];
                $total_legal_nd_ot += $attendance_array['legal_nd_ot'];

                $total_legal_unattend += $attendance_array['legal_unattend'];

                $total_rest += $attendance_array['rest'];
                $total_rest_ot += $attendance_array['rest_ot'];
                $total_rest_nd += $attendance_array['rest_nd'];
                $total_rest_nd_ot += $attendance_array['rest_nd_ot'];

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
                $all_total_reg_ot += $total_reg_ot;
                $all_total_reg_nd += $total_reg_nd;
                $all_total_reg_nd_ot += $total_reg_nd_ot;

                $all_total_sun += $total_sun;
                $all_total_sun_ot += $total_sun_ot;
                $all_total_sun_nd += $total_sun_nd;
                $all_total_sun_nd_ot += $total_sun_nd_ot;

                $all_total_legal += $total_legal;
                $all_total_legal_ot += $total_legal_ot;
                $all_total_legal_nd += $total_legal_nd;
                $all_total_legal_nd_ot += $total_legal_nd_ot;

                $all_total_legal_unattend += $total_legal_unattend;

                $all_total_rest += $total_rest;
                $all_total_rest_ot += $total_rest_ot;
                $all_total_rest_nd += $total_rest_nd;
                $all_total_rest_nd_ot += $total_rest_nd_ot;

                $all_total_total_hours += $total_total_hours;

                $now_x = $dim_x + ($i * 22) + 110 + 66;
                $now_inc = 68;

                $page->setFont($font, 8)->drawText('Total ' . round($total_total_hours, 2), $dim_x + $now_x, $dim_y, 'UTF8');
                $now_x += $now_inc;

                $page->setFont($font, 8)->drawText('Reg ' . round($total_reg, 2), $dim_x + $now_x, $dim_y, 'UTF8');
                $now_x += $now_inc;

                $page->setFont($font, 8)->drawText('RegOT ' . round($total_reg_ot, 2), $dim_x + $now_x, $dim_y, 'UTF8');
                $now_x += $now_inc;

                $page->setFont($font, 8)->drawText('RegND ' . round($total_reg_nd, 2), $dim_x + $now_x, $dim_y, 'UTF8');
                $now_x += $now_inc;

                $page->setFont($font, 8)->drawText('RegNDOT ' . round($total_reg_nd_ot, 2), $dim_x + $now_x, $dim_y, 'UTF8');
                $now_x += $now_inc;

                /* New line */
                $now_x = $dim_x + ($i * 22) + 110 + 66;
                $dim_y -= 10;

                $page->setFont($font, 8)->drawText('SunSp ' . round($total_sun, 2), $dim_x + $now_x, $dim_y, 'UTF8');
                $now_x += $now_inc;

                $page->setFont($font, 8)->drawText('SunSpOT ' . round($total_sun_ot, 2), $dim_x + $now_x, $dim_y, 'UTF8');
                $now_x += $now_inc;

                $page->setFont($font, 8)->drawText('SunSpND ' . round($total_sun_nd, 2), $dim_x + $now_x, $dim_y, 'UTF8');
                $now_x += $now_inc;

                $page->setFont($font, 8)->drawText('SunSpNDOT ' . round($total_sun_nd_ot, 2), $dim_x + $now_x, $dim_y, 'UTF8');
                $now_x += $now_inc;

                /* New line */
                $now_x = $dim_x + ($i * 22) + 110 + 66;
                $dim_y -= 10;

                $page->setFont($font, 8)->drawText('RestSp ' . round($total_rest, 2), $dim_x + $now_x, $dim_y, 'UTF8');
                $now_x += $now_inc;

                $page->setFont($font, 8)->drawText('RestSpOT ' . round($total_rest_ot, 2), $dim_x + $now_x, $dim_y, 'UTF8');
                $now_x += $now_inc;

                $page->setFont($font, 8)->drawText('RestSpND ' . round($total_rest_nd, 2), $dim_x + $now_x, $dim_y, 'UTF8');
                $now_x += $now_inc;

                $page->setFont($font, 8)->drawText('RestSpNDOT ' . round($total_rest_nd_ot, 2), $dim_x + $now_x, $dim_y, 'UTF8');
                $now_x += $now_inc;


                /* New line */
                $now_x = $dim_x + ($i * 22) + 110 + 66;
                $dim_y -= 10;

                $page->setFont($font, 8)->drawText('Leg ' . round($total_legal, 2), $dim_x + $now_x, $dim_y, 'UTF8');
                $now_x += $now_inc;

                $page->setFont($font, 8)->drawText('LegOT ' . round($total_legal_ot, 2), $dim_x + $now_x, $dim_y, 'UTF8');
                $now_x += $now_inc;

                $page->setFont($font, 8)->drawText('LegND ' . round($total_legal_nd, 2), $dim_x + $now_x, $dim_y, 'UTF8');
                $now_x += $now_inc;

                $page->setFont($font, 8)->drawText('LegNDOT ' . round($total_legal_nd_ot, 2), $dim_x + $now_x, $dim_y, 'UTF8');
                $now_x += $now_inc;

                $page->setFont($font, 8)->drawText('Leg UA ' . round($total_legal_unattend, 2), $dim_x + $now_x, $dim_y, 'UTF8');
                $now_x += $now_inc;

                $dim_y -= 20;
            }

            $employee_count++;

        }

        $dim_y -= 10;

        $dim_y -= 10;

        $now_x = $dim_x + ($i * 22) + 110;

        $now_inc = 35;

        $now_x = $dim_x + ($i * 22) + 110 + 66;
        $now_inc = 68;

        $all_total_total_hours = round($all_total_reg, 2) + round($all_total_reg_ot, 2) + round($all_total_reg_nd, 2) + round($all_total_reg_nd_ot, 2)
            + round($all_total_sun, 2) + round($all_total_sun_ot, 2) + round($all_total_sun_nd, 2) + round($all_total_sun_nd_ot, 2)
            + round($all_total_rest, 2) + round($all_total_rest_ot, 2) + round($all_total_rest_nd, 2) + round($all_total_rest_nd_ot, 2)
            + round($all_total_legal, 2) + round($all_total_legal_ot, 2) + round($all_total_legal_nd, 2) + round($all_total_legal_nd_ot, 2)
            + round($all_total_legal_unattend, 2);

        $page->setFont($font, 8)->drawText('Total ' . round($all_total_total_hours, 2), $dim_x + $now_x, $dim_y, 'UTF8');
        $now_x += $now_inc;

        $page->setFont($font, 8)->drawText('Reg ' . round($all_total_reg, 2), $dim_x + $now_x, $dim_y, 'UTF8');
        $now_x += $now_inc;

        $page->setFont($font, 8)->drawText('RegOT ' . round($all_total_reg_ot, 2), $dim_x + $now_x, $dim_y, 'UTF8');
        $now_x += $now_inc;

        $page->setFont($font, 8)->drawText('RegND ' . round($all_total_reg_nd, 2), $dim_x + $now_x, $dim_y, 'UTF8');
        $now_x += $now_inc;

        $page->setFont($font, 8)->drawText('RegNDOT' . round($all_total_reg_nd_ot, 2), $dim_x + $now_x, $dim_y, 'UTF8');
        $now_x += $now_inc;

        /* New line */
        $now_x = $dim_x + ($i * 22) + 110 + 66;
        $dim_y -= 10;

        $page->setFont($font, 8)->drawText('SunSp ' . round($all_total_sun, 2), $dim_x + $now_x, $dim_y, 'UTF8');
        $now_x += $now_inc;

        $page->setFont($font, 8)->drawText('SunSpOT ' . round($all_total_sun_ot, 2), $dim_x + $now_x, $dim_y, 'UTF8');
        $now_x += $now_inc;

        $page->setFont($font, 8)->drawText('SunSpND ' . round($all_total_sun_nd, 2), $dim_x + $now_x, $dim_y, 'UTF8');
        $now_x += $now_inc;

        $page->setFont($font, 8)->drawText('SunSpNDOT ' . round($all_total_sun_nd_ot, 2), $dim_x + $now_x, $dim_y, 'UTF8');
        $now_x += $now_inc;


        /* New line */

        $now_x = $dim_x + ($i * 22) + 110 + 66;
        $dim_y -= 10;

        $page->setFont($font, 8)->drawText('RestSp ' . round($all_total_rest, 2), $dim_x + $now_x, $dim_y, 'UTF8');
        $now_x += $now_inc;

        $page->setFont($font, 8)->drawText('RestSpOT ' . round($all_total_rest_ot, 2), $dim_x + $now_x, $dim_y, 'UTF8');
        $now_x += $now_inc;

        $page->setFont($font, 8)->drawText('RestSpND ' . round($all_total_rest_nd, 2), $dim_x + $now_x, $dim_y, 'UTF8');
        $now_x += $now_inc;

        $page->setFont($font, 8)->drawText('RestSpNDOT ' . round($all_total_rest_nd_ot, 2), $dim_x + $now_x, $dim_y, 'UTF8');
        $now_x += $now_inc;

        /* New line */
        $now_x = $dim_x + ($i * 22) + 110 + 66;
        $dim_y -= 10;

        $page->setFont($font, 8)->drawText('Leg ' . round($all_total_legal, 2), $dim_x + $now_x, $dim_y, 'UTF8');
        $now_x += $now_inc;

        $page->setFont($font, 8)->drawText('LegOT ' . round($all_total_legal_ot, 2), $dim_x + $now_x, $dim_y, 'UTF8');
        $now_x += $now_inc;

        $page->setFont($font, 8)->drawText('LegND ' . round($all_total_legal_nd, 2), $dim_x + $now_x, $dim_y, 'UTF8');
        $now_x += $now_inc;

        $page->setFont($font, 8)->drawText('LegNDOT ' . round($all_total_legal_nd_ot, 2), $dim_x + $now_x, $dim_y, 'UTF8');
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

        // $this->_redirect($_SERVER['HTTP_REFERER']);

    }

    public function hdmfexportAction()
    {
        // TODO: remove relievers
        $this->_compute();
        $percov = substr(str_replace('-', '', $this->_request->getParam('date_start')), 0, -2);

        header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
        header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
        header('Content-type: text/csv');
        // header('Content-type: text/plain');
        header('Content-Disposition: attachment; filename="hdmf_export.csv"');


        foreach ($this->_employee_payroll as $value) {
            echo '""' // Series number
                . ', "' . $value['attendance']->hdmf . '"'
                . ',""' // Account number
                . ',"F1-Pag-IBIG 1"'
                . ',"' . strtoupper($value['attendance']->lastname) . '"'
                . ',"' . strtoupper($value['attendance']->firstname) . '"'
                . ',""' // Extension
                . ',"' . strtoupper($value['attendance']->middleinitial) . '"'
                . ',"' . $percov . '"' // Percov
                . ',"100.00"' // EE share
                . ',"100.00"' // ER share
                . ',""' // Remarks
                . "\n";
        }

        die();
    }

    protected function get_cutoff_attended_days($employee_id, $date_start, $date_end) {
        $AttendanceDb = new Messerve_Model_DbTable_Attendance();

        $select = $AttendanceDb->select();

        $select->from($AttendanceDb, array("COUNT(*) AS amount"))
            ->where("employee_id = $employee_id
                AND datetime_start >= '$date_start 00:00'
                AND datetime_end <= '$date_end 23:59'
                AND (start_1 >= 1 OR legal_unattend > 0)");

        $rows = $AttendanceDb->fetchAll($select);

        return($rows[0]->amount);
    }

    public function philhealthexportAction()
    {
        // TODO: remove relievers
        $this->_compute();
        $percov = substr(str_replace('-', '', $this->_request->getParam('date_start')), 0, -2);

        header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
        header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
        header('Content-type: text/csv');
        header('Content-Disposition: attachment; filename="philhealth_export.csv"');

        foreach ($this->_employee_payroll as $value) {
            $sum = array_sum($value['pay']) - $value['pay']['ecola'];

            echo '"' . strtoupper($value['attendance']->lastname) . '"'
                . ',""' // Suffix
                . ',"' . strtoupper($value['attendance']->firstname) . '"'
                . ',"' . strtoupper($value['attendance']->middleinitial) . '"'
                . ', "' . $value['attendance']->philhealth . '"'
                . ',""' // Birthdate
                . ',""' // Sex
                . ',"' . $sum . '"' // Salary
                . "\n";
        }

        die();

    }

    public function sssexportAction()
    {

    }

    public function exportAction()
    {  // CSV export
        $this->_helper->layout()->disableLayout();

        $period_covered = $this->_request->getParam('period_covered');

        header('Content-type: text/csv');
        header('Content-Disposition: attachment; filename="Payroll_report.csv"');


        $PayrollMap = new Messerve_Model_Mapper_PayrollTemp();
        $payroll = $PayrollMap->fetchList("period_covered = '{$period_covered}'", array("lastname", "firstname", "employee_number", "is_reliever DESC"));
        $payroll_array = array();


        foreach ($payroll as $pvalue) {

            // preprint($pvalue->toArray(),1);

            $employee_type = 'Regular';

            if ($pvalue->getIsReliever() == 'yes') {
                $employee_type = 'Reliever';
            }

            $payroll_meta = json_decode($pvalue->getDeductionData());

            // preprint($payroll_meta,1);

            $this_row = array(
                'Period covered' => $pvalue->getPeriodCovered()
            , 'Client name' => $pvalue->getClientName()
            , 'Group name' => strtoupper($pvalue->getGroupName())
            , 'Employee type' => $employee_type
            , 'Employee number' => $pvalue->getEmployeeNumber()
            , 'Last name' => $pvalue->getLastName()
            , 'First name' => $pvalue->getFirstName()
            , 'Middle name' => $pvalue->getMiddleName()
            , 'Account number' => $pvalue->getAccountNumber()
            , 'Ecola' => number_format(round($pvalue->getEcola(), 2), 2)
            , 'Incentives' => number_format(round($pvalue->getIncentives(), 2), 2)
            , 'BOP maintenance' => $pvalue->getBopMaintenance()
            , '13th month pay' => number_format(round($pvalue->getThirteenthMonth(), 2), 2)
            , 'Fuel addition' => number_format(round($pvalue->getFuelAddition(), 2), 2)
            , 'Misc addition' => number_format(round($pvalue->getMiscAddition(), 2), 2)
            , 'Gross pay' => number_format(round($pvalue->getGrossPay(), 2), 2)
            , 'SSS' => number_format(round($pvalue->getSss() * -1, 2), 2)
            , 'Philhealth' => number_format(round($pvalue->getPhilhealth() * -1, 2), 2)
            , 'HDMF' => number_format(round($pvalue->getHdmf() * -1, 2), 2)
            , 'Cash bond' => number_format(round($pvalue->getCashBond() * -1, 2), 2)
            , 'Insurance' => number_format(round($pvalue->getInsurance() * -1, 2), 2)
                // , 'Misc deduction'=>number_format(round($pvalue->getMiscDeduction() * -1,2),2)

            , 'SSS loan' => number_format(round($pvalue->getSSSLoan() * -1, 2), 2)
            , 'HDMF loan' => number_format(round($pvalue->getHDMFLoan() * -1, 2), 2)
            , 'Accident' => number_format(round($pvalue->getAccident() * -1, 2), 2)
            , 'Uniform' => number_format(round($pvalue->getUniform() * -1, 2), 2)
            , 'Adjustment' => number_format(round($pvalue->getAdjustment(), 2), 2)
            , 'Miscellaneous' => number_format(round($pvalue->getMiscellaneous() * -1, 2), 2)
            , 'Communication' => number_format(round($pvalue->getCommunication() * -1, 2), 2)
                // , 'Fuel overage'=>number_format(round($pvalue->getFuelOverage() * -1,2),2)


            , 'Fuel deduction' => number_format(round($pvalue->getFuelDeduction() * -1, 2), 2)
            , 'BOP motorcycle' => $pvalue->getBopMotorcycle() * -1
            , 'BOP ins/reg' => $pvalue->getBopInsurance() * -1
            , 'Net pay' => number_format(round($pvalue->getNetPay(), 2), 2)
            , 'Fuel hours' => number_format(round($pvalue->getFuelHours(), 2), 2)
            , 'Fuel allotment' => number_format(round($pvalue->getFuelAllotment(), 2), 2)
            , 'Fuel purchased' => number_format(round($pvalue->getFuelUsage(), 2), 2)
            , 'Fuel overage L' => number_format(round($pvalue->getFuelUsage() - $pvalue->getFuelAllotment(), 2), 2)
            , 'Fuel price' => number_format(round($pvalue->getFuelPrice(), 2), 2)
            , 'SSS deductions (Table/Calculated)' => $payroll_meta->sss_pair[0] . ' / ' . $payroll_meta->sss_pair[1]
            , 'SSS More data' => @$payroll_meta->sss_debug
            );

            /*
			$misc_deduction = json_decode($pvalue->getDeductionData());
			$misc_deduction_string = '';

			if(count($misc_deduction) > 0) {

				foreach ($misc_deduction as $mvalue) {
					$amount = number_format(round($mvalue->amount * -1,2),2);
					$misc_deduction_string .= "{$mvalue->type}: {$amount}, ";
				}
			}

			$this_row['Misc deduction data'] = $misc_deduction_string;
			*/

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
            ->where("datetime_start >= '{$date_start} 00:00' AND datetime_start <= '{$date_end} 23:59'");
        ;

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
}

