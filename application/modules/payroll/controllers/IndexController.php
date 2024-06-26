<?php

use Carbon\Carbon;
use Domains\Payroll\Actions\GetPayrollMetaAction;
use Domains\Payslips\Data\Bop\Metadata;
use Domains\Payslips\Data\Bop\Totals;
use Domains\Payslips\Data\Payslip;
use Illuminate\Support\Collection;
use Messerve_Model_Eloquent_FloatingAttendance as Floating;
use Messerve_Model_Eloquent_Employee as EmployeeEloq;
use Messervelib_Philhealth as Philhealth;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;

class Payroll_IndexController extends Zend_Controller_Action
{
    protected $_employee_payroll, $_employer_bill, $_messerve_bill;
    protected $_client, $_pay_period, $_fuelcost, $_last_date;
    protected $_user_auth, $_config;

    public function init()
    {
        $storage = new Zend_Auth_Storage_Session();
        $data = $storage->read();

        if (!$data && PHP_SAPI !== 'cli') {
            $this->_redirect('auth/login');
        }

        if (PHP_SAPI !== 'cli') {
            $this->_user_auth = $data;

            $this->view->user_auth = $this->_user_auth;

            if ($this->_user_auth->type !== 'admin' && $this->_user_auth->type !== 'accounting') {
                throw new Exception('You are not allowed to access this module.');
            }
        }

        $this->_fuelcost = $this->_request->getParam('fuelcost');

        $_SESSION['fuelcost'] = $this->_fuelcost;

        $this->_config = Zend_Registry::get('config');
    }

    public function dtrAnomalyAction()
    {
        $Attendance = (new Messerve_Model_Eloquent_Attendance())->select();
        $cutoff_range = $this->currentCutoffRange();

        $Attendance->where('datetime_start', '>=', $cutoff_range->start);
        $Attendance->where('datetime_end', '<=', $cutoff_range->end);

        $Attendance->where(function ($select) {
            $select->where(function ($subselect) {
                $subselect->where('extended_shift', 'yes');
                $subselect->where('approved_extended_shift', 'no');
            });

            $select->orWhere('ot_approved_hours', '>', 5);
        });

        $anomalous_attendance = $this->parseAnomalousAttendance($Attendance->get(), $cutoff_range->start);

        $this->view->anomalies = $anomalous_attendance;

        $cutoff_start = Carbon::parse($cutoff_range->start);
        $cutoff_end = Carbon::parse($cutoff_range->end);

        $pay_period = sprintf('%s-%s-%s_%s',
            $cutoff_start->year,
            str_pad($cutoff_start->month, 2, 0, STR_PAD_LEFT),
            str_pad($cutoff_start->day, 2, 0, STR_PAD_LEFT),
            str_pad($cutoff_end->day, 2, 0, STR_PAD_LEFT)
        );

        $this->view->payroll_dates = (object)[
            'period' => $pay_period,
            'start' => $cutoff_range->start,
            'end' => $cutoff_range->end,
        ];
    }

    public function updateDtrAnomalyAction()
    {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $anomaly_id = (int)$this->_request->getParam('anomaly_id');
        $is_approved = (bool)$this->_request->getParam('is_approved', 0);

        $Anomaly = Messerve_Model_Eloquent_DtrAnomaly::find($anomaly_id);

        if (!$Anomaly) {
            throw new Zend_Controller_Action_Exception('Anomaly does not exist!', 404);
        }

        $Anomaly->is_approved = $is_approved;
        $Anomaly->save();

        print(json_encode($Anomaly->toArray()));
    }

    protected function parseAnomalousAttendance(\Illuminate\Database\Eloquent\Collection $attendance, $period)
    {
        foreach ($attendance as $item) {
            Messerve_Model_Eloquent_DtrAnomaly::employeeGroupPeriod($item, $period);
        }

        return Messerve_Model_Eloquent_DtrAnomaly::payPeriod($period);
    }

    public function queuepayrollAction()
    {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $group_id = $this->_request->getParam('group_id');
        $date_start = $this->_request->getParam('date_start');
        $date_end = $this->_request->getParam('date_end');
        $fuel_cost = $this->_request->getParam('fuel_cost');

        $this->queuePayslipProcessing($group_id, $date_start, $date_end, $fuel_cost);

        echo 'OK';
    }

    public function jobtestAction()
    {

    }

    /**
     * @throws Exception
     */
    public function cliAction()
    {
        if (PHP_SAPI !== 'cli') {
            throw new Exception('Not CLI!');
        }

        $params = $this->_request->getParam('params');

        $this->_request->setParams(
            [
                'group_id' => $params[0],
                'date_start' => $params[1],
                'date_end' => $params[2],
                'fuelcost' => $params[3],
                'is_ajax' => true
            ]
        );

        $this->_fuelcost = $params[3];

        echo $this->payslipsAction();
    }

    protected function queuePayslipProcessing($group_id, $date_start, $date_end, $fuel_cost = 0)
    {
        $process = new Messervelib_ProcessGroupPayroll();

        $process->setData(
            [
                'group_id' => $group_id,
                'date_start' => $date_start,
                'date_end' => $date_end,
                'fuel_cost' => $fuel_cost
            ]
        );

        $queueAdapter = Zend_Registry::getInstance()->queueAdapter;

        $message = base64_encode(gzcompress(serialize($process)));

        $queue = new Zend_Queue($queueAdapter, ['name' => 'process-group-payroll']);
        $job = $queue->send($message);

        Messerve_Model_Eloquent_PendingPayroll::create(
            [
                'message_id' => $job->message_id,
                'group_id' => $group_id,
                'date_start' => $date_start,
                'date_end' => $date_end,
                'pay_period' => 'PP',
                'is_done' => false,
            ]
        );

    }

    protected function currentCutoffRange()
    {
        $range = [];

        $day = date('d');
        $last_month = date('m', strtotime('last month'));

        if ($day > 15) {
            $period_covered = date("Y-m-01");
            $period_end = date("Y-m-15");
        } else {
            if ($last_month == 12) {
                $period_covered = date("Y-12-16", strtotime('last year'));
                $period_end = date("Y-12-t", strtotime('last year'));
            } else {
                $period_covered = date("Y-$last_month-16");
                $period_end = date('Y-m-d', strtotime('next month -1 day', strtotime(date("Y-$last_month"))));
            }
        }

        $range['start'] = $period_covered;
        $range['end'] = $period_end;

        return (object)$range;

    }

    public function indexAction()
    {
        $day = date('d');
        $last_month = date('m', strtotime('last month'));

        if ($day > 15) {
            $period_covered = date("Y-m-01");
            $period_end = date("Y-m-15");
        } else {
            if ($last_month == 12) {
                $period_covered = date("Y-12-16", strtotime('last year'));
                $period_end = date("Y-12-t", strtotime('last year'));
                // $period_end = date('Y-m-d', strtotime('next month -1 day', strtotime(date("Y-$last_month"))));
            } else {
                $period_covered = date("Y-$last_month-16");
                $period_end = date('Y-m-d', strtotime('next month -1 day', strtotime(date("Y-$last_month"))));
            }
        }

        $this->view->period_covered = $period_covered;
        $this->view->period_end = $period_end;


        $all_periods = [];

        $periods = Messerve_Model_Eloquent_PayrollTemp::groupBy(['period_covered'])->get(['period_covered']);

        foreach ($periods as $period) {
            $all_periods[] = $period->period_covered;
        }

        $this->view->old_periods = $all_periods;

        $messerve_config = $this->_config->get('messerve');

        $this->view->api_host = $messerve_config->api_host;

        // ETPS logs

        $etps_report_1k = [];
        $etps_dir = dirname(APPLICATION_PATH) . '/../public/export/etps';


        if (file_exists($etps_dir)) {
            foreach (glob($etps_dir . '/*.csv') as $file) {

                $matches = [];

                $file_created = filectime($file);

                $file_array = explode('/', $file);
                $filename = array_pop($file_array);

                if (preg_match('/etps_\d{4}-\d{2}-\d{2}/', $filename, $matches)) {

                    $match = $matches[0];

                    if ($match) {
                        $etps_date = str_replace('etps_', '', $match);
                        $etps_report_1k[$file_created] = [
                            'link' => '/export/etps/' . $filename
                            , 'period' => $etps_date
                            , 'date' => date('Y-m-d H:i', $file_created)];
                    }
                }

            }
        }

        krsort($etps_report_1k);

        $this->view->etps_report_1k = $etps_report_1k;
    }

    public function clientreportAction()
    {
        throw new Exception('This feature is no longer available.');
    }

    protected function _process_client_report()
    {
        throw new Exception('This feature is no longer available.');
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

            $AttendanceMap = new Messerve_Model_Mapper_Attendance();

            $data = [];

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
                , 'approved_extended_shift' => $Attendance->getApprovedExtendedShift()
                , 'type' => $Attendance->getType()
                , 'model' => $Attendance
                );

                $current_date = date('Y-m-d', strtotime('+1 day', strtotime($current_date)));
            }

            if (!(count($data) > 0)) {
                throw new Exception('No group attendance data found!');
            }

            $Payroll->save_the_day($Attendance->getEmployeeId(), $group_id, $data); // TODO:  Figure out why this needs to be called twice
            $Payroll->save_the_day($Attendance->getEmployeeId(), $group_id, $data);

        }
    }

    /**
     * @throws Zend_Pdf_Exception
     */
    protected function bopSlipPage($bop_slip_data, &$PayslipData): Zend_Pdf_Page
    {

        $font = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA);
        $bold = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_BOLD);
        $mono = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_COURIER);

        $logo = Zend_Pdf_Image::imageWithPath(APPLICATION_PATH . '/../public/images/messerve.png');

        $page = new Zend_Pdf_Page(612, 396);

        $pageHeight = $page->getHeight();
        $pageWidth = $page->getWidth();

        $imageHeight = 41;
        $imageWidth = 119;


        $dim_x = 32;
        $dim_y = $pageHeight - 25;

        $bottomPos = $dim_y - $imageHeight;
        $rightPos = $dim_x + $imageWidth;

        $page->drawImage($logo, $dim_x, $bottomPos, $rightPos, $dim_y);
        $dim_y -= 12;

        $page->setFont($font, 8)->drawText('Billing period: ', $dim_x + 340, $dim_y, 'UTF8');

        $page->setFont($bold, 8)->drawText($bop_slip_data['metadata']['pay_period'], $dim_x + 393, $dim_y, 'UTF8');

        $dim_y -= 10;

        $page->setFont($font, 8)->drawText('Name: ', $dim_x + 340, $dim_y, 'UTF8');

        $page->setFont($bold, 8)->drawText($bop_slip_data['metadata']['rider'], $dim_x + 393, $dim_y, 'UTF8');

        $dim_y -= 10;

        // $page->setFont($font, 8)->drawText('Client: ', $dim_x + 340, $dim_y, 'UTF8');
        $page->setFont($bold, 8)->drawText($bop_slip_data['metadata']['client'], $dim_x + 393, $dim_y, 'UTF8');

        $dim_y -= 16;
        $page->drawLine($dim_x - 2, $dim_y, $dim_x + 560, $dim_y);
        $dim_y -= 8;

        // $page->setFont($bold, 8)->drawText('ACKNOWLEDGEMENT', $dim_x + 250, $dim_y);

        $dim_y -= 2;

        $page->drawRectangle($dim_x - 2, $dim_y, $dim_x + 560, 60, Zend_Pdf_Page::SHAPE_DRAW_STROKE);
        $page->drawLine($dim_x + 216, $dim_y, $dim_x + 216, 60);
        $page->drawLine($dim_x + 376, $dim_y, $dim_x + 376, 60);

        $dim_y -= 10;

        $page->setFont($bold, 8)->drawText('BOP ACKNOWLEDGEMENT RECEIPT', $dim_x + 5, $dim_y);
        $page->setFont($bold, 8)->drawText('ADDITION', $dim_x + 220, $dim_y);
        $page->setFont($bold, 8)->drawText('DEDUCTION', $dim_x + 380, $dim_y);

        $dim_y -= 16;

        // $bop_slip_data['metadata']['bop'] .= ' R1';


        $return_to_top = $dim_y;
        // Addition
        $additions = 0;

        /*if (isset($bop_slip_data['metadata']['bop']) && stripos($bop_slip_data['metadata']['bop'], 'R1') !== false) {
            $page->setFont($bold, 8)->drawText($bop_slip_data['metadata']['bop'], $dim_x + 220, $dim_y);
            $page->setFont($mono, 8)->drawText(str_pad(number_format($bop_slip_data['addition']['bop_motorcycle'], 2), 8, ' ', STR_PAD_LEFT), $dim_x + 320, $dim_y, 'UTF8');
            $dim_y -= 16;
            $additions += $bop_slip_data['addition']['bop_motorcycle'];
        }*/


        if (isset($bop_slip_data['addition']['maintenance']) && $bop_slip_data['addition']['maintenance'] > 0) {
            $addition_name = $bop_slip_data['metadata']['bop'] . ' - maintenance';
            $page->setFont($bold, 8)->drawText($addition_name, $dim_x + 220, $dim_y);
            $page->setFont($mono, 8)->drawText(str_pad(number_format($bop_slip_data['addition']['maintenance'], 2), 8, ' ', STR_PAD_LEFT), $dim_x + 320, $dim_y, 'UTF8');
            $additions += $bop_slip_data['addition']['maintenance'];

            $dim_y -= 16;

            $PayslipData->bop->additions->push(new Domains\Payslips\Data\Bop\Addition($addition_name, $bop_slip_data['addition']['maintenance']));
        }

        if (isset($bop_slip_data['addition']['fuel']) && $bop_slip_data['addition']['fuel'] > 0) {
            $page->setFont($bold, 8)->drawText('Gasoline', $dim_x + 220, $dim_y);
            $page->setFont($mono, 8)->drawText(str_pad(number_format($bop_slip_data['addition']['fuel'], 2), 8, ' ', STR_PAD_LEFT), $dim_x + 320, $dim_y, 'UTF8');
            $additions += $bop_slip_data['addition']['fuel'];

            $dim_y -= 16;
            $page->setFont($font, 8)->drawText(' - Allotment L', $dim_x + 220, $dim_y);
            $page->setFont($mono, 8)->drawText(str_pad($bop_slip_data['addition']['fuel_data']['allocation'], 10, ' ', STR_PAD_LEFT), $dim_x + 260, $dim_y);

            $dim_y -= 8;
            $page->setFont($font, 8)->drawText(' - Consumed L', $dim_x + 220, $dim_y);
            $page->setFont($mono, 8)->drawText(str_pad($bop_slip_data['addition']['fuel_data']['consumed'], 10, ' ', STR_PAD_LEFT), $dim_x + 260, $dim_y);

            $dim_y -= 16;

            $Metadata = new Collection();

            $Metadata->push(new Metadata('Allotment L', $bop_slip_data['addition']['fuel_data']['allocation']));
            $Metadata->push(new Metadata('Consumed L', $bop_slip_data['addition']['fuel_data']['consumed']));

            $PayslipData->bop->additions->push(new Domains\Payslips\Data\Bop\Addition(
                'Gasoline',
                round($bop_slip_data['addition']['fuel'], 2),
                $Metadata
            ));

        }


        $dim_y = $return_to_top;

        $page->setFont($bold, 10)->drawText('Total addition', $dim_x + 220, $dim_y - 180);
        $page->setFont($mono, 8)->drawText(str_pad(number_format($additions, 2), 10, ' ', STR_PAD_LEFT), $dim_x + 320, $dim_y - 180);


        $dim_y = $return_to_top;

        // Deductions
        $deductions = 0;

        if (isset($bop_slip_data['metadata']['bop']) && !stripos($bop_slip_data['metadata']['bop'], 'R1')) {
            $page->setFont($bold, 8)->drawText($bop_slip_data['metadata']['bop'], $dim_x + 380, $dim_y);
            $page->setFont($mono, 8)->drawText(str_pad(number_format($bop_slip_data['deduction']['bop_motorcycle'], 2), 8, ' ', STR_PAD_LEFT), $dim_x + 480, $dim_y, 'UTF8');
            $deductions += $bop_slip_data['deduction']['bop_motorcycle'];
            $dim_y -= 16;

            $PayslipData->bop->deductions->push(new Domains\Payslips\Data\Bop\Deduction(
                $bop_slip_data['metadata']['bop'],
                round($bop_slip_data['deduction']['bop_motorcycle'], 2),
                new Metadata("", 0)
            ));
        }

        if (isset($bop_slip_data['deduction']['fuel']) && $bop_slip_data['deduction']['fuel'] > 0) {
            $page->setFont($bold, 8)->drawText('Fuel overage', $dim_x + 380, $dim_y);
            $page->setFont($mono, 8)->drawText(str_pad(number_format($bop_slip_data['deduction']['fuel'], 2), 8, ' ', STR_PAD_LEFT), $dim_x + 480, $dim_y, 'UTF8');
            $deductions += $bop_slip_data['deduction']['fuel'];

            $dim_y -= 16;
            $page->setFont($font, 8)->drawText(' - Allotment L', $dim_x + 380, $dim_y);
            $page->setFont($mono, 8)->drawText(str_pad($bop_slip_data['deduction']['fuel_data']['allocation'], 10, ' ', STR_PAD_LEFT), $dim_x + 420, $dim_y);

            $dim_y -= 8;
            $page->setFont($font, 8)->drawText(' - Consumed L', $dim_x + 380, $dim_y);
            $page->setFont($mono, 8)->drawText(str_pad($bop_slip_data['deduction']['fuel_data']['consumed'], 10, ' ', STR_PAD_LEFT), $dim_x + 420, $dim_y);

            $dim_y -= 16;

            $Metadata = new Collection();
            $Metadata->push(new Metadata('Allotment L', $bop_slip_data['deduction']['fuel_data']['allocation']));
            $Metadata->push(new Metadata('Consumed L', $bop_slip_data['deduction']['fuel_data']['consumed']));

            $PayslipData->bop->additions->push(new Domains\Payslips\Data\Bop\Addition(
                'Fuel overage',
                round($bop_slip_data['deduction']['fuel'], 2),
                $Metadata
            ));
        }

        $dim_y = $return_to_top;

        $page->setFont($bold, 10)->drawText('Total deduction', $dim_x + 380, $dim_y - 180);
        $page->setFont($mono, 8)->drawText(str_pad(number_format($deductions, 2), 10, ' ', STR_PAD_LEFT), $dim_x + 480, $dim_y - 180);

        $page->setFont($bold, 10)->drawText('TOTAL', $dim_x + 380, 80);
        $page->setFont($mono, 8)->drawText(str_pad(number_format($additions - $deductions, 2), 10, ' ', STR_PAD_LEFT), $dim_x + 480, 80);

        $PayslipData->bop->totals = new Totals(
            round($additions, 2),
            round($deductions, 2),
            round($additions - $deductions, 2)
        );

        if (isset($bop_slip_data['deduction'])) {
            // $page->setFont($font, 8)->drawText(print_r($bop_slip_data['deduction']['fuel_data'], true), $dim_x + 20, $dim_y);
        }


        $dim_y = 92;

        $page->drawLine($dim_x - 2, $dim_y, $dim_x + 560, 92);

        $messerve_address = '91 Congressional Ave., Brgy Bahay Toro, Project 8, Quezon City 1106';

        $page->setFont($font, 8)->drawText('Pilipinas Messerve Inc.  |  ' . $messerve_address, $dim_x, 24);


        return $page;
    }

    protected function calculateEcola($employee_id, $group_id, $date_start, $date_end)
    {
        $group = Messerve_Model_Eloquent_Group::find($group_id);
        $group_rate = $group->rate;

        // Get paysplits
        $splits = Messerve_Model_Eloquent_RateSchedule::splits($group_id, $date_start, $date_end);

        $date_splits = [];
        $split_start = $date_start;


        foreach ($splits as $split) {
            $date_splits[] = [
                'start' => $split_start,
                'end' => Carbon::parse($split->date_active)->subDays(1)->toDateString(),
                'rate' => $group_rate
            ];

            $split_start = Carbon::parse($split->date_active)->toDateString();

            $group_rate = $split->rate;
        }

        $date_splits[] = [
            'start' => $split_start,
            'end' => $date_end,
            'rate' => $group_rate
        ];

        $ecola = [];

        foreach ($date_splits as $date_split) {
            $attended_days = $this->get_cutoff_attended_days($employee_id, $date_split['start'], $date_split['end']);

            $ecola_regular = [
                'start' => $date_split['start'],
                'end' => $date_split['end'],
                'days' => $attended_days,
                'pay' => $attended_days * $date_split['rate']->ecola,
                'rate' => $date_split['rate']->ecola
            ];

            $legal_attended_days = $this->get_legal_attended_days($employee_id, $date_split['start'], $date_split['end']);

            $ecola_legal = [
                'start' => $date_split['start'],
                'end' => $date_split['end'],
                'days' => $legal_attended_days,
                'pay' => $legal_attended_days * $date_split['rate']->ecola,
                'rate' => $date_split['rate']->ecola
            ];

            $ecola[] = [
                'regular' => $ecola_regular,
                'legal' => $ecola_legal,
            ];
        }

        return $ecola;
    }


    public function payslipsAction()
    {
        error_reporting(E_ERROR);
        set_time_limit(1000);

        $start = time();
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

        $rate_schedule = Messerve_Model_Eloquent_RateSchedule::byGroupAndDate($group_id, $date_start);

        $Group = new Messerve_Model_Group();
        $Group->find($group_id);

        $Rate = new Messerve_Model_Rate();
        $Rate->find($Group->getRateId());

        // $pay_rate = number_format(intval(substr($Rate->getName(), 0, -4)), 2);

        $Client = new Messerve_Model_Client();

        $Client->find($Group->getClientId());

        $this->_process_group_attendance($group_id, $date_start, $date_end);


        $this->_compute();
        $this->_compute(); // TODO:  Fix this!  Why does it need to be ran twice. Clue:  creation of new model

        $this->view->payroll = $this->_employee_payroll;


        $pdf = new Zend_Pdf();
        //$dole_pdf = new Zend_Pdf();

        $font = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA);
        $bold = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_BOLD);
        $italic = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_ITALIC);
        $mono = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_COURIER);
        $boldmono = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_COURIER_BOLD);

        $logo = Zend_Pdf_Image::imageWithPath(APPLICATION_PATH . '/../public/images/messerve.png');

        $parent = APPLICATION_PATH . '/../public/export' . "/$date_start/$group_id";
        $folder = $parent . "/payslips/";

        $cmd = "mkdir -p $folder";

        shell_exec($cmd); // Create folder

        $folder = realpath($folder) . '/';

        $cmd = "chmod -R 777 $parent";

        shell_exec($cmd); // Change permissions since queue runs this as user fixstop and will fail creation of
        // user-initiated client billing
        // TODO:  add www-data to fixstop group

        $rec_copy_data = array(
            'branch' => $Client->getName() . '-' . $Group->getName()
        , 'riders' => array()
        );

        $bop_acknowledgement = [];

        foreach ($this->_employee_payroll as $value) {
            $current_employee = Messerve_Model_Eloquent_Employee::find($value['attendance']->employee_id);
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

            $EmployeeEloq = EmployeeEloq::findOrFail($Employee->getId());

            $EmployeeRate = $EmployeeEloq->group->rate;

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

            $other_deductions = 0;
            $other_additions = 0;

            $bop_slip_data = [];

            $bop_slip_data['metadata']['rider'] = $EmployeeEloq->lastname . ', '
                . $EmployeeEloq->firstname . ' '
                . $EmployeeEloq->middleinitial . ' ' . $EmployeeEloq->employee_number;

            $bop_slip_data['metadata']['client'] = $Client->getName() . ' - ' . $Group->getName();

            if ($Attendance) {
                logger("Setting fuel cost to " . $this->_fuelcost);

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

                    // BOP deductions

                    $BOPAttendance = new Messerve_Model_BopAttendance();
                    $BOPAttendance->find(array('bop_id' => $Employee->getBopId(), 'attendance_id' => $Attendance->getId()));

                    $BOP = new Messerve_Model_Bop();
                    $BOP->find($Employee->getBopId());

                    $bop_motorcycle = $BOPAttendance->getMotorcycleDeduction();
                    $bop_insurance = $BOPAttendance->getInsuranceDeduction();

                    // For BOP acknowledgement slip
                    $bop_slip_data['metadata']['bop'] = $BOP->getName();

                    if ($bop_motorcycle > 0) {
                        $bop_slip_data['metadata']['bop_motorcycle'] = $BOP->getMotorcycle();
                        $bop_slip_data['metadata']['bop_deduction'] = $BOP->getMotorcycleDeduction();

                        $total_payment_count = ceil($BOP->getMotorcycle() / $BOP->getMotorcycleDeduction());

                        $bop_slip_data['metadata']['bop_payment_count'] = count($EmployeeEloq->bop_payments);
                        $bop_slip_data['metadata']['bop_installments_count'] = $total_payment_count;
                        $bop_slip_data['deduction']['bop_motorcycle'] = $BOPAttendance->getMotorcycleDeduction();
                        $bop_slip_data['deduction']['bop_insurance'] = $BOPAttendance->getInsuranceDeduction();

                        $other_deductions += $BOPAttendance->getMotorcycleDeduction();
                    }

                }
            }

            $page->setFont($font, 8)->drawText('Period #: ', $dim_x + 200, $dim_y, 'UTF8');

            $period_number = date('Y-');

            if (substr($date_start, -2) == '01') {
                $period_number .= intval(date('m')) * 2;
            } else {
                $period_number .= (intval(date('m')) * 2) + 1;
            }

            $bop_slip_data['metadata']['pay_period'] = $date_start . ' to ' . $date_end;

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
            $page->setFont($bold, 8)->drawText($Client->getName() . '-' . $Group->getName() . $reliever_text, $dim_x + 340, $dim_y);

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

            $page->setFont($bold, 8)->drawText('PAYSLIP', $dim_x + 260, $dim_y);

            $dim_y -= 2;

            $page->drawRectangle($dim_x - 2, $dim_y, $dim_x + 560, 60, Zend_Pdf_Page::SHAPE_DRAW_STROKE);
            $page->drawLine($dim_x + 216, $dim_y, $dim_x + 216, 60);
            $page->drawLine($dim_x + 376, $dim_y, $dim_x + 376, 60);

            $dim_y -= 10;

            $reset_y = $dim_y;

            $PayrollLib = new Messervelib_Payroll();

            $employee_pay = $PayrollLib->GetEmployeePayroll( // TODO:  fix this hack
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
            $ecola = 0;

            $legal_ecola_addition = 0;
            $legal_ecola_days = 0;

            $payroll_meta = [];
            $basic_pay = 0;

            $philhealth_basic = 0;

            $pay_rate_id = 0;

            foreach ($employee_pay as $rate_id_key => $pvalue) {
                // $ecola = 0;
                $payslip_rate = null;

                $PayslipData = new Payslip();
                $sss = 0;

                foreach ($pvalue as $rkey => $rvalue) {

                    if ($rkey === "meta") {
                        $pay_rate_id = $rvalue->employee->rate->id;

                        $payroll_meta['rate_data'] = json_encode($rvalue);

                        $pay_rate_model = Messerve_Model_Eloquent_Rate::query()->find($rate_id_key);

                        $pay_rate = $pay_rate_model->reg * 8;

                        $ecola = $rvalue->employee->rate->ecola;
                        $sss = $rvalue->employee->rate->sss_employee;

                        $minimum_wage = $pay_rate + $ecola;
                        $dim_y -= 8;

                        $page->setFont($font, 8)->drawText('Daily rate', $dim_x, $dim_y, 'UTF8');
                        $page->setFont($mono, 8)->drawText(number_format($pay_rate, 2), $dim_x + 40, $dim_y, 'UTF8');

                        $page->setFont($font, 8)->drawText('Ecola', $dim_x + 80, $dim_y, 'UTF8');
                        $page->setFont($mono, 8)->drawText(number_format($ecola, 2), $dim_x + 110, $dim_y, 'UTF8');

                        $page->setFont($font, 8)->drawText('Min. wage', $dim_x + 140, $dim_y, 'UTF8');
                        $page->setFont($mono, 8)->drawText(number_format($minimum_wage, 2), $dim_x + 180, $dim_y, 'UTF8');
                        $dim_y -= 8;

                        $payslip_rate = new Payslip\Rate($pay_rate, $ecola, $minimum_wage);

                    } else {
                        $page->setFont($font, 8)->drawText("{$rkey}", $dim_x, $dim_y);

                        if (!isset($payroll_meta[$rkey])) $payroll_meta[$rkey] = [];

                        foreach ($rvalue as $dkey => $dvalue) {
                            $payroll_meta[$rkey][$dkey] = $dvalue;

                            $dim_y -= 8;

                            $sss_deductions[] = ($sss / 22 / 8) * $dvalue['hours'];

                            $dkey = strtoupper($dkey);

                            $total_no_hours += $dvalue['hours'];
                            $total_pay += $dvalue['pay'];

                            if (stripos($dkey, 'OT') === false) {
                                $philhealth_basic += $dvalue['pay'];
                            }

                            $page->setFont($font, 8)->drawText("{$dkey}", $dim_x + 10, $dim_y);
                            $page->setFont($mono, 8)->drawText(str_pad(number_format($dvalue['hours'], 2), 8, ' ', STR_PAD_LEFT), $dim_x + 110, $dim_y);
                            $page->setFont($mono, 8)->drawText(str_pad(number_format($dvalue['pay'], 2), 8, ' ', STR_PAD_LEFT), $dim_x + 160, $dim_y);

                            $PayslipData->salary->push(new Payslip\Salary($dkey, $rkey, $dvalue['hours'], $dvalue['pay']));
                        }
                    }

                    $dim_y -= 8;
                }

                $basic_pay = $total_pay;

                // Legal adjustments for attendance less than 8 hours
                $LegalAttendanceMap = new Messerve_Model_Mapper_Attendance();

                // TODO: Move to an action
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

            if ($payslip_rate === null) {
                throw new \RuntimeException("No payslip rate found for employee {$Employee->getEmployeeNumber()}");
            }

            $PayslipData->rate = $payslip_rate;

            $dim_y = 92;

            $page->drawLine($dim_x - 2, $dim_y, $dim_x + 560, $dim_y);

            $dim_y = 82;

            $page->setFont($font, 8)->drawText('Total No. of hours', $dim_x, $dim_y);
            $page->setFont($mono, 8)->drawText(str_pad(number_format($total_no_hours, 2), 6, ' ', STR_PAD_LEFT), $dim_x + 110, $dim_y);

            $dim_y -= 8;

            $page->setFont($font, 8)->drawText('Total Hrs. pay', $dim_x, $dim_y);
            $page->setFont($mono, 8)->drawText(str_pad(substr(number_format($total_pay, 3), 0, -1), 8, ' ', STR_PAD_LEFT), $dim_x + 160, $dim_y);

            $PayslipData->totals->hours = $total_no_hours;
            $PayslipData->totals->pay = $total_pay; // After this point, additional pay may get tacked into $total_pay

            $dim_y = $reset_y;
            $page->setFont($bold, 8)->drawText('ADDITION', $dim_x + 220, $dim_y);

            $dim_y -= 8;
            $page->setFont($font, 8)->drawText('Total hours pay', $dim_x + 220, $dim_y);
            $page->setFont($mono, 8)->drawText(str_pad(substr(number_format($total_pay, 3), 0, -1), 10, ' ', STR_PAD_LEFT), $dim_x + 300, $dim_y);

            $PayslipData->additions->push(new Payslip\Addition('Total hours pay', $total_pay));

            if ($Employee->getGroupId() == $group_id) {
                $pay_splits = $this->calculateEcola($Employee->getId(), $group_id, $date_start, $date_end);

                foreach ($pay_splits as $pay_split) {
                    if (isset($pay_split['regular']) && $pay_split['regular']['pay'] > 0) {
                        // $pay_split['regular']
                        $attended_days = $pay_split['regular']['days'];
                        $ecola_addition = $pay_split['regular']['pay'];

                        $total_pay += $ecola_addition;

                        $dim_y -= 8;
                        $page->setFont($font, 8)->drawText('ECOLA (' . $attended_days . ' day/s)', $dim_x + 220, $dim_y);
                        $page->setFont($mono, 8)->drawText(str_pad(number_format($ecola_addition, 2), 10, ' ', STR_PAD_LEFT), $dim_x + 300, $dim_y);

                        $PayslipData->additions->push(new Payslip\Addition('ECOLA', $ecola_addition));

                    }

                    if (isset($pay_split['legal']) && $pay_split['legal']['pay'] > 0) {
                        // $pay_split['regular']
                        $attended_days = $pay_split['legal']['days'];
                        $ecola_addition = $pay_split['legal']['pay'];

                        $total_pay += $ecola_addition;

                        $dim_y -= 8;
                        $addition_name = 'ECOLA - Legal (' . $attended_days . ' day/s)';

                        $page->setFont($font, 8)->drawText($addition_name, $dim_x + 220, $dim_y);

                        $page->setFont($mono, 8)->drawText(str_pad(number_format($ecola_addition, 2), 10, ' ', STR_PAD_LEFT), $dim_x + 300, $dim_y);

                        $PayslipData->additions->push(new Payslip\Addition($addition_name, $ecola_addition));
                    }
                }
            }

            $prev_gross_pay = 0;
            $monthly_sss = 0;
            $prev_sss = 0;
            $monthly_pay = 0;
            $sss_bal = 0;

            if ($cutoff == 1) {
                $PayrollTemp = new Messerve_Model_PayrollTemp();

                $prev_payroll_start = date("Y-m-16", strtotime('last  month'));

                $sss_result = $PayrollTemp->getMapper()->fetchListToArray(
                    array("period_covered >= '$prev_payroll_start'"
                    , "period_covered < '$date_start'"
                    , "group_id = $group_id"
                    , "employee_id = " . $Employee->getId())
                );



                if (count($sss_result) > 0) {
                    foreach ($sss_result as $srvalue) {
                        $prev_sss += $srvalue["sss"];
                        $prev_gross_pay += $srvalue["basic_pay"] + $srvalue["ecola"];
                    }

                    $monthly_pay = $total_pay + $prev_gross_pay;
                    $monthly_sss_array = $this->get_sss_deduction($monthly_pay);

                    // As of 2024-03, the first element of the array is the deduction. According to Sally,
                    // ER does not want to use the calculated SSS deduction.
                    $monthly_sss = $monthly_sss_array[0];

                    $sss_bal = $monthly_sss - $prev_sss;

                    logger(sprintf("Employee %s, SSS %s", $Employee->getLastname(), $sss_debug));
                    $value['deductions']['sss'] = $sss_bal;
                } else {
                    $sss_deduction = $this->get_sss_deduction($total_pay);

                    // As of 2024-03, the first element of the array is the deduction. According to Sally,
                    // ER does not want to use the calculated SSS deduction.
                    $value['deductions']['sss'] = $sss_deduction[0];
                }

                $sss_debug = "THIS PAY: $total_pay, PREV PAY: $prev_gross_pay, MONTHLY: $monthly_pay, PREV SSS: $prev_sss,  MONTHLY SSS: $monthly_sss,  SSS BAL: $sss_bal";
            } else {
                $sss_deduction = $this->get_sss_deduction($total_pay);

                // As of 2024-03, the first element of the array is the deduction. According to Sally,
                // ER does not want to use the calculated SSS deduction.
                $value['deductions']['sss'] = $sss_deduction[0];

                $sss_debug = "";
            }

            if ($group_id == $Employee->getGroupId()) { // Apply adjustments only on mother group payslip
                // Apply philhealth
                $philhealth_deductions = Philhealth::getPhilhealthDeductionByRiderRate($EmployeeEloq, $date_start);
                $value['deductions']['philhealth'] = $philhealth_deductions['employee'];
            }


            if ($EmployeeRate->sss_employee <= 0) { // Rider rate is 0 sss
                logger("Rider {$Employee->getId()} is 0 sss");
                $value['deductions']['sss'] = 0;
            }

            if (isset($value['more_income'])) {
                if ($value['more_income']['misc_income'] > 0) {
                    $dim_y -= 8;
                    $page->setFont($font, 8)->drawText('Misc income', $dim_x + 220, $dim_y);
                    $page->setFont($mono, 8)->drawText(str_pad(number_format($value['more_income']['misc_income'], 2), 10, ' ', STR_PAD_LEFT), $dim_x + 300, $dim_y);
                    $total_pay += $value['more_income']['misc_income'];

                    $PayslipData->additions->push(new Payslip\Addition('Misc income', $value['more_income']['misc_income']));
                }

                if ($value['more_income']['incentives'] > 0) {
                    $dim_y -= 8;
                    $page->setFont($font, 8)->drawText('SILP', $dim_x + 220, $dim_y);
                    $page->setFont($mono, 8)->drawText(str_pad(number_format($value['more_income']['incentives'], 2), 10, ' ', STR_PAD_LEFT), $dim_x + 300, $dim_y);
                    $total_pay += $value['more_income']['incentives'];

                    $PayslipData->additions->push(new Payslip\Addition('Incentives', $value['more_income']['incentives']));

                }

                if ($value['more_income']['thirteenth_month_pay'] > 0) {
                    $dim_y -= 8;
                    $page->setFont($font, 8)->drawText('13th month pay', $dim_x + 220, $dim_y);
                    $page->setFont($mono, 8)->drawText(str_pad(number_format($value['more_income']['thirteenth_month_pay'], 2), 10, ' ', STR_PAD_LEFT), $dim_x + 300, $dim_y);
                    $total_pay += $value['more_income']['thirteenth_month_pay'];

                    $PayslipData->additions->push(new Payslip\Addition('13th month pay', $value['more_income']['thirteenth_month_pay']));

                }

                if ($value['more_income']['paternity'] > 0) {
                    $dim_y -= 8;
                    $page->setFont($font, 8)->drawText('Paternity leave', $dim_x + 220, $dim_y);
                    $page->setFont($mono, 8)->drawText(str_pad(number_format($value['more_income']['paternity'], 2), 10, ' ', STR_PAD_LEFT), $dim_x + 300, $dim_y);
                    $total_pay += $value['more_income']['paternity'];

                    $PayslipData->additions->push(new Payslip\Addition('Paternity', $value['more_income']['paternity']));
                }

                if ($value['more_income']['solo_parent_leave'] > 0) {
                    $dim_y -= 8;
                    $page->setFont($font, 8)->drawText('Solo parent leave', $dim_x + 220, $dim_y);
                    $page->setFont($mono, 8)->drawText(str_pad(number_format($value['more_income']['solo_parent_leave'], 2), 10, ' ', STR_PAD_LEFT), $dim_x + 300, $dim_y);
                    $total_pay += $value['more_income']['solo_parent_leave'];

                    $PayslipData->additions->push(new Payslip\Addition('Solo parent leave', $value['more_income']['solo_parent_leave']));
                }

                if ($value['more_income']['tl_allowance'] > 0) {
                    $dim_y -= 8;
                    $page->setFont($font, 8)->drawText('TL allowance', $dim_x + 220, $dim_y);
                    $page->setFont($mono, 8)->drawText(str_pad(number_format($value['more_income']['tl_allowance'], 2), 10, ' ', STR_PAD_LEFT), $dim_x + 300, $dim_y);
                    $total_pay += $value['more_income']['tl_allowance'];

                    $PayslipData->additions->push(new Payslip\Addition('TL allowance', $value['more_income']['tl_allowance']));
                }
            }

            // BOP
            if ($group_id == $Employee->getGroupId()) { // On parent group,  show BOP additions
                if (isset($BOPAttendance) && $BOPAttendance->getMaintenanceAddition() > 0) {
                    $bop_maintenance = $BOPAttendance->getMaintenanceAddition(); // TODO:  Evaluate if this is needed in the future.
                    $bop_slip_data['addition']['maintenance'] = $bop_maintenance;
                    $other_additions += $bop_maintenance;
                }
            }

            if (isset($value['more_income']) && $value['more_income']['gasoline'] > 0) {

                $fuel_excess = number_format($value['more_income']['gasoline'], 2, '.', '');

                $other_additions += $fuel_excess;

                $bop_slip_data['addition']['fuel'] = $fuel_excess;
                $bop_slip_data['addition']['fuel_data'] = ['allocation' => $Attendance->getFuelAlloted(), 'consumed' => $Attendance->getFuelConsumed()];
            }


            $dim_y = 82;

            $page->setFont($font, 8)->drawText('TOTAL ADDITION', $dim_x + 220, $dim_y);
            $page->setFont($mono, 8)->drawText(str_pad(number_format($total_pay, 2), 10, ' ', STR_PAD_LEFT), $dim_x + 300, $dim_y);

            $PayslipData->totals->additions = $total_pay;

            $dim_y = 136;
            $dim_y -= 8;
            $page->drawLine($dim_x + 300, $dim_y, $dim_x + 350, $dim_y);

            $dim_y = 128;
            $dim_y -= 8;
            $page->setFont($mono, 8)->drawText(str_pad(number_format($total_pay, 2), 10, ' ', STR_PAD_LEFT), $dim_x + 300, $dim_y);

            $dim_y = $reset_y;
            $page->setFont($bold, 8)->drawText('DEDUCTION', $dim_x + 380, $dim_y);

            $dim_y -= 8;

            logger("Deductions: " . print_r($value['deductions'], 1));

            foreach ($value['deductions'] as $pkey => $pvalue) {
                if ($pvalue > 0) {
                    $total_deduct += $pvalue;
                    $page->setFont($font, 8)->drawText(ucwords(str_replace('_', ' ', $pkey)), $dim_x + 380, $dim_y);
                    $page->setFont($mono, 8)->drawText(str_pad(number_format($pvalue, 2), 10, ' ', STR_PAD_LEFT), $dim_x + 480, $dim_y);
                    $dim_y -= 8;

                    $PayslipData->deductions->push(new Payslip\Deduction($pkey, round($pvalue, 2)));

                }
            }

            $split_dim_y = $dim_y;


            /* Start Messerve deductions */

            $messerve_deduct = 0;

            $fuel_overage = 0;  // Reset
            $fuel_deduction = 0;  // Reset

            if ($Attendance->getFuelHours() > 0) {

                $fuel_overage = $Attendance->getFuelConsumed() - $Attendance->getFuelAlloted();

                if ($fuel_overage > 0) {
                    $fuel_deduction = round($fuel_overage * $Attendance->getFuelCost(), 2);

                    $bop_slip_data['deduction']['fuel'] = $fuel_deduction;
                    $bop_slip_data['deduction']['fuel_data'] = ['allocation' => $Attendance->getFuelAlloted(), 'consumed' => $Attendance->getFuelConsumed()];

                    $other_deductions += $fuel_deduction;
                }
            }

            $dim_y -= 10;


            // Scheduled deductions
            if (count($scheduled_deductions) > 0) {

                foreach ($scheduled_deductions as $sdvalue) {
                    if ($sdvalue['amount'] > 0) {

                        $deficit = $total_pay - $total_deduct - $messerve_deduct - $sdvalue['amount'];

                        if ($deficit < 0 && isset($sdvalue['deduction_id']) && $sdvalue['deduction_id'] > 0) {
                            $pk = [
                                'deduction_attendance_id' => $sdvalue['deduction_id'],
                                'attendance_id' => $Attendance->id
                            ];

                            $negative_deduction = new Messerve_Model_DeductionAttendance();

                            $negative_deduction->find($pk);

                            // FIXME this always returns true
                            if ($negative_deduction) {

                                $new_amount = $negative_deduction->getAmount() + $deficit;

                                if ($new_amount < 0) {
                                    $new_amount = 0;
                                }

                                $negative_deduction->setAmount($new_amount);
                                $negative_deduction->save();

                                $sdvalue['amount'] = $new_amount;
                            } else {
                                throw new Exception('Invalid deduction attendance object: ' . print_r($pk, 1));
                            }
                        }

                        if (stripos($sdvalue['type'], 'loan') || stripos($sdvalue['type'], 'calamity')) {
                            $page->setFont($font, 8)->drawText(ucwords(str_replace('_', ' ', $sdvalue['type'])), $dim_x + 380, $dim_y);
                            $page->setFont($mono, 8)->drawText(str_pad(number_format($sdvalue['amount'], 2), 10, ' ', STR_PAD_LEFT), $dim_x + 480, $dim_y);

                            $total_deduct += $sdvalue['amount'];

                            $PayslipData->deductions->push(new Payslip\Deduction($sdvalue['type'], round($sdvalue['amount'], 2)));
                        } else {

                            $messerve_deduct += $sdvalue['amount'];
                        }

                        $total_misc_deduct += $sdvalue['amount'];

                        $dim_y -= 10;
                    }
                }

            }

            if ($messerve_deduct > 0) {
                $page->setFont($font, 8)->drawText('Misc deduction', $dim_x + 380, $split_dim_y);
                $page->setFont($mono, 8)->drawText(str_pad(number_format($messerve_deduct, 2), 10, ' ', STR_PAD_LEFT), $dim_x + 480, $split_dim_y);

                $PayslipData->deductions->push(new Payslip\Deduction('Misc deduction', $messerve_deduct));
            }


            $total_deduct += $messerve_deduct;

            /* End Messerve deductions */

            $dim_y = 82;
            $dim_y -= 8;

            $page->setFont($font, 8)->drawText('TOTAL DEDUCTION', $dim_x + 220, $dim_y);
            $page->setFont($mono, 8)->drawText(str_pad(number_format($total_deduct, 2), 10, ' ', STR_PAD_LEFT), $dim_x + 300, $dim_y);


            // TODO:  Fix hacky hack
            // Delete prior record
            $PayrollTemp = new Messerve_Model_PayrollTemp();

            $PayrollTemp->getMapper()->getDbTable()
                ->delete("group_id = " . $Group->getId() . " AND period_covered = '$date_start' AND employee_id = " . $Employee->getId());

            if (!($total_pay > 0)) {
                logger(sprintf('Total pay for %s is 0, skipping!', $EmployeeEloq->name));
                continue;
            }

            $dim_y = 136;
            $dim_y -= 8;
            $page->drawLine($dim_x + 480, $dim_y, $dim_x + 530, $dim_y);

            $dim_y = 128;
            $dim_y -= 8;
            $page->setFont($mono, 8)->drawText(str_pad(number_format($total_deduct, 2), 10, ' ', STR_PAD_LEFT), $dim_x + 480, $dim_y);

            $dim_y = 82;
            $net_pay = $total_pay - $total_deduct;
            $page->setFont($bold, 10)->drawText('Net pay', $dim_x + 380, $dim_y);

            $dim_y -= 16;
            $page->setFont($boldmono, 12)->drawText('Php ' . str_pad(number_format($net_pay, 2), 10, ' ', STR_PAD_LEFT), $dim_x + 380, $dim_y);

            $messerve_address = '91 Congressional Ave., Brgy Bahay Toro, Project 8, Quezon City 1106';

            $page->setFont($font, 8)->drawText('Pilipinas Messerve Inc.  |  ' . $messerve_address, $dim_x, 24);

            // Payslip page 1 done.  Let's build the metadata for Catchy

            $PayslipData->totals->deductions = round($total_deduct, 2);
            $PayslipData->totals->net_pay = round($net_pay, 2);


            $pdf->pages[] = $page;


            // Disable BOP slip

            if (($other_additions + $other_deductions > 0)) {
                $pdf->pages[] = $this->bopSlipPage($bop_slip_data, $PayslipData);
            }

            $payroll_meta['payslip_data'] = (array)$PayslipData;

            if ($group_id == $Employee->getGroupId()) {
                $is_reliever = 'no';
            } else {
                $is_reliever = 'yes';
            }

            /*
            if(!isset($sss_deduction) || $sss_deduction == null) {
                throw new RuntimeException("SSS deduction is null for employee {$Employee->getEmployeeNumber()}");
            }

            if(!isset($sss_debug) || $sss_debug == null) {
                $sss_debug = "";
            }
            */

            $scheduled_deductions["sss_pair"] = $sss_deduction;
            $scheduled_deductions["sss_debug"] = $sss_debug;

            $net_pay = $net_pay + $other_additions - $other_deductions; // Hack!

            $philhealth_deduction = 0;

            if (isset($philhealth_deductions['employee'])) {
                $this->resetPhilhealth($date_start, $Employee->getId());
                // Set philhealth deductions to 0 for all groups,
                // just in case the rider got transferred after payroll of their previous mother group was processed.

                $philhealth_deduction = $philhealth_deductions['employee'];
            }

            // Move to eloquent!
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
                ->setSoloParentLeave($value['more_income']['solo_parent_leave'])
                ->setTlAllowance($value['more_income']['tl_allowance'])
                ->setGrossPay($total_pay)
                ->setBasicPay($basic_pay)
                ->setNetPay($net_pay)
                ->setEcola($ecola_addition + $legal_ecola_addition)
                ->setSss($value['deductions']['sss'])
                ->setPhilhealth($philhealth_deduction)
                ->setHdmf($value['deductions']['hdmf'])
                ->setCashBond($value['deductions']['cash_bond'])
                ->setInsurance($value['deductions']['insurance'])
                ->setMiscDeduction($total_misc_deduct)// Sum of all misc deductions; Miscellaneous-type deduction in setMiscellaneous()
                ->setDeductionData(json_encode($scheduled_deductions))
                ->setSssLoan($scheduled_deductions_array['sss_loan'])
                ->setHdmfLoan($scheduled_deductions_array['hdmf_loan'])
                ->setHdmfCalamityLoan($scheduled_deductions_array['hdmf_calamity'])
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
                ->setIsReliever($is_reliever)
                ->setRateId($pay_rate_id)
                ->setPhilhealthbasic($philhealth_basic)
                ->setUpdatedAt(Carbon::now()->toDateTimeString());

            $PayrollTemp->save();


        }

        $date_start = $this->_request->getParam('date_start');

        $group_id = $this->_request->getParam('group_id');

        $Group = new Messerve_Model_Group();

        $Group->find($group_id);

        $this->_last_date = date('Y-m-d-Hi');

        $filename = $folder . "Payslips_{$this->_client->getName()}_{$Group->getName()}_{$group_id}_{$date_start}_{$this->_last_date}.pdf";

        $this->_receiving_copy($pdf, $rec_copy_data);
        // $this->_bop_acknowledgement($pdf, $bop_acknowledgement);
        // mkdir($folder . 'dole', 0777);
        // $dole_filename = $folder . "dole/Payslips_{$this->_client->getName()}_{$Group->getName()}_{$group_id}_{$date_start}_{$this->_last_date}.pdf";

        $filename = str_replace(' ', '_', $filename);

        @mkdir(dirname($filename), 0777, true);

        $pdf->save($filename);

        // $dole_pdf->save($dole_filename);

        $this->summaryreportAction();

        if ($this->_request->getParam("is_ajax") !== "true" && PHP_SAPI !== 'cli') {
            $this->_helper->getHelper('Redirector')->goToUrl($_SERVER['HTTP_REFERER']);
        } elseif ($this->_request->getParam("is_ajax") === "true") {
            return "OK";
        }

        return 'OK';
    }

    protected function resetPhilhealth($employee_id, $date_start)
    {
        return Philhealth::resetDeductionsForCutoff($employee_id, $date_start);
    }

    protected function getPhilhealthDeduction($basic_pay, $date_start, $employee_id, $group_id = 0)
    {

        return Philhealth::getPhilhealthDeduction($basic_pay, $date_start, $employee_id);

    }

    protected function _bop_acknowledgement($pdf, $bop_data)
    {
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

    /**
     * @throws Zend_Pdf_Exception
     */
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

    protected function _fetch_employees($group_id, $date_start, $date_end): array
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

            if (!count($all_attendance) > 0) {
                throw new Exception("Could not find attendance of rider " . $evalue->getId() . " in group " . $group_id . ".  Has the rider's group assignments changed?");
            }

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
                $AttendanceMap = new Messerve_Model_Mapper_Attendance();

                $first_day = $AttendanceMap->findOneByField(
                    array('datetime_start', 'employee_id', 'group_id')
                    , array($date_start, $evalue->getId(), $group_id)
                );

                if (!$first_day) {
                    // Skip this day
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

                    // $summary_bill['rest'] += $day->rest_ot;
                    // $summary_bill['rest_nd'] += $day->rest_nd_ot;

                    $messerve_bill['rest'] += $day->rest;
                    $messerve_bill['rest_nd'] += $day->rest_nd;
                    $messerve_bill['rest_ot'] += $day->rest_ot;
                    $messerve_bill['rest_nd_ot'] += $day->rest_nd_ot;


                    $summary_bill['reg'] += $day->rest;
                    $summary_bill['reg_nd'] += $day->rest_nd;
                    $messerve_bill['reg_ot'] += $day->rest_ot;
                    $messerve_bill['reg_nd_ot'] += $day->rest_nd_ot;

                } else {
                    $summary_bill['reg_ot'] += $day->reg_ot;
                    $summary_bill['reg_nd_ot'] += $day->reg_nd_ot;

                    $summary_bill['spec_ot'] += $day->spec_ot;
                    $summary_bill['spec_nd_ot'] += $day->spec_nd_ot;

                    $summary_bill['legal_ot'] += $day->legal_ot;
                    $summary_bill['legal_nd_ot'] += $day->legal_nd_ot;

                    $summary_bill['rest'] += $day->rest;
                    $summary_bill['rest_nd'] += $day->rest_nd;
                    $summary_bill['rest_ot'] += $day->rest_ot;
                    $summary_bill['rest_nd_ot'] += $day->rest_nd_ot;
                }

            }


            $attendance->id = $day->employee_id;
            $attendance->employee_id = $day->employee_id;
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
                throw new Exception('Process halted:  no rates found for either employee or group.');
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
            , 'philhealth' => 0 // Will be overidden at the paysplip, TODO: Clean up
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
        $this->_messerve_bill = $messerve_bill;
    }

    protected function writePdfLogo(Zend_Pdf_Page $page, $x1, $y1, $x2, $y2)
    {
        $logo = Zend_Pdf_Image::imageWithPath(APPLICATION_PATH . '/../public/images/messerve.png');
        $page->drawImage($logo, $x1, $y1, $x2, $y2);
    }

    protected function writePdfLine(Zend_Pdf_Page $page, $text, $position_x = 0, $position_y = 0, $font = 'normal', $size = 10)
    {
        $normal = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA);
        $bold = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_BOLD);
        $italic = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_ITALIC);
        $mono = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_COURIER);

        if (!in_array($font, array('normal', 'bold', 'italic', 'mono'))) {
            $font = 'normal';
        }

        $pdf_font = $$font;

        $page->setFont($pdf_font, $size)->drawText($text, $position_x, $position_y, 'UTF8');
    }

    protected function summaryToXls(array $payroll, Messerve_Model_Eloquent_Group $group, $filename = 'Summary_export')
    {

        $client_rate = $group->clientRate;

        $client = $group->client;

        $days_data = [];
        $total_data = [];


        // reg	reg_ot	reg_nd	reg_nd_ot	spec	spec_ot	spec_nd	spec_nd_ot	rest	rest_ot	rest_nd	rest_nd_ot	legal	legal_ot	legal_nd	legal_nd_ot	legal_unattend

        foreach ($payroll as $date => $day_row) {
            foreach ($day_row as $employee_id => $hours) {
                // $data[$employee_number][$date] = $hours;
                $Employee = EmployeeEloq::find($employee_id);
                $total_data[$employee_id][] = $hours;
                $days_data[$employee_id]['employee_number'] = $Employee->employee_number;
                $days_data[$employee_id]['employee_name'] = $Employee->name;
                $days_data[$employee_id][$date] = array_sum($hours);
            }
        }

        $total_collection = collect($total_data);

        $total_hours = []; // Holds summed rider duty hours per holiday type

        foreach ($days_data as $employee_id => &$row) {
            $row['total_hours'] = 0; // Init for position

            $collection = collect($total_collection[$employee_id]);

            $total_row = [];

            foreach (['reg', 'spec', 'rest', 'legal'] as $holiday_type) {
                $total_row[$holiday_type] = $collection->pluck($holiday_type)->sum() ?? 0;
                $total_row[$holiday_type . '_ot'] = $collection->pluck($holiday_type . '_ot')->sum() ?? 0;
                $total_row[$holiday_type . '_nd'] = $collection->pluck($holiday_type . '_nd')->sum() ?? 0;
                $total_row[$holiday_type . '_nd_ot'] = $collection->pluck($holiday_type . '_nd_ot')->sum() ?? 0;

                $total_hours[$holiday_type] += $total_row[$holiday_type];
                $total_hours[$holiday_type . '_ot'] += $total_row[$holiday_type . '_ot'];
                $total_hours[$holiday_type . '_nd'] += $total_row[$holiday_type . '_nd'];
                $total_hours[$holiday_type . '_nd_ot'] += $total_row[$holiday_type . '_nd_ot'];
            }

            $total_row['legal_unattend'] = $collection->pluck('legal_unattend')->sum() ?? 0;
            $total_hours['legal_unattend'] += $total_row['legal_unattend'];

            $row['total_hours'] = array_sum($total_row);

            $row += $total_row;
        }


        $header = [array_keys(collect($days_data)->first())];

        $client_billing = [];

        $billing_header = [];

        foreach (array_keys($total_hours) as $label) {
            $billing_header[] = $label . '_hours';
            $billing_header[] = $label . '_rate';
            $billing_header[] = $label . '_amount';
        }


        $client_billing[] = $billing_header;

        $temp_bill = [];

        foreach ($total_hours as $holiday_type => $hours) {
            $temp_bill[$holiday_type . '_hours'] = number_format($hours, 2);
            $temp_bill[$holiday_type . '_rate'] = $client_rate->$holiday_type;
            $temp_bill[$holiday_type . '_amount'] = number_format($client_rate->$holiday_type * $hours, 2);
        }

        $client_billing[] = $temp_bill;

        $all_data = array_merge([['DTR ', $group->client->name, $group->name]], $header, $days_data, [[''], ['Client billing']], $client_billing);

        $this->renderXls($all_data, $filename);
    }

    private function renderXls(array $array, $filename = null)
    {
        header('Content-Type: application/vnd.ms-excel');
        header(sprintf('Content-Disposition: attachment; filename="%s.xls"', $filename));

        $spreadsheet = new Spreadsheet();
        $spreadsheet->setActiveSheetIndex(0);

        $activesheet = $spreadsheet->getActiveSheet();

        $activesheet->fromArray($array);

        $writer = new Xls($spreadsheet);
        $writer->save('php://output');
    }

    protected function billingToXls(array $payroll)
    {

    }

    /**
     * @throws Zend_Pdf_Exception
     */
    public function summaryreportAction()
    {
        function round_this($in, $digits = 2)
        {
            if (is_numeric($in)) {
                return (float)number_format($in, $digits, '.', '');
            } else {
                return $in;
            }
        }

        // action body
        $date_start = $this->_request->getParam('date_start');
        $date_end = $this->_request->getParam('date_end');
        $standalone = $this->getParam('standalone');
        $csv_only = $this->getParam('csv');


        if (($standalone != '' && $standalone == 'true') || ($csv_only != '' && $csv_only == 'true')) {
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

        // $page->drawImage($logo, $dim_x, $bottomPos, $rightPos, $dim_y);
        $this->writePdfLogo($page, $dim_x, $bottomPos, $rightPos, $dim_y);
        $dim_y -= 12;

        $this->writePdfLine($page, $Client->getName() . '-' . $Group->getName(), $dim_x + 300, $dim_y, 'bold');
        // $page->setFont($bold, 10)->drawText($Client->getName() . '-' . $Group->getName(), $dim_x + 300, $dim_y, 'UTF8');
        $dim_y -= 10;

        $this->writePdfLine($page, 'Pay period: ' . $date_start . ' to ' . $date_end, $dim_x + 300, $dim_y, 'bold');

        $dim_y -= 32;

        $date1 = new DateTime($date_start); //inclusive
        $date2 = new DateTime($date_end); //exclusive
        $diff = $date2->diff($date1);
        $period_size = intval($diff->format("%a")) + 1;

        $date = (int)substr($date_start, -2, 2);

        for ($i = 1; $i <= $period_size; $i++) {
            // $page->setFont($bold, 8)->drawText($date, $dim_x + ($i * 25) + 150, $dim_y, 'UTF8');
            $this->writePdfLine($page, $date, $dim_x + ($i * 25) + 150, $dim_y, 'bold', 8);

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

        $all_messerve_rest = 0;;
        $all_messerve_rest_nd = 0;

        $all_messerve_rest_ot = 0;;
        $all_messerve_rest_nd_ot = 0;


        $employee_count = 0;

        $all_messerve_ot = 0;

        $split_bill_hours = [];

        $csv_data = [];

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

            $current_date = $date_start;

            $employee_id = $value['attendance']->id;

            $employee_attendance_text = array();

            $messerve_reg_ot = 0;
            $messerve_reg_nd_ot = 0;

            $messerve_rest = 0;
            $messerve_rest_nd = 0;

            $messerve_rest_ot = 0;
            $messerve_rest_nd_ot = 0;

            $messerve_sun_ot = 0;
            $messerve_sun_nd_ot = 0;

            $messerve_legal_ot = 0;
            $messerve_legal_nd_ot = 0;

            $messerve_ot = 0;


            for ($i = 1; $i <= $period_size; $i++) {

                // Init today's attendance; for split bill
                $today_reg = 0;
                $today_reg_ot = 0;
                $today_reg_nd = 0;
                $today_reg_nd_ot = 0;

                $today_sun = 0;
                $today_sun_ot = 0;
                $today_sun_nd = 0;
                $today_sun_nd_ot = 0;

                $today_legal = 0;
                $today_legal_ot = 0;
                $today_legal_nd = 0;
                $today_legal_nd_ot = 0;

                $today_legal_unattend = 0;

                $today_rest = 0;
                $today_rest_ot = 0;
                $today_rest_nd = 0;
                $today_rest_nd_ot = 0;

                // End for split bill

                $Attendance = $AttendanceMap->findOneByField(
                    array('employee_id', 'datetime_start', 'group_id')
                    , array($employee_id, "$current_date 00:00:00", $group_id)
                );


                if (!$Attendance) {
                    throw new Exception("Failed to find attendance record for: $employee_id, $current_date, $group_id");
                }

                $dates[$current_date] = $Attendance;

                $current_date = Carbon::parse($current_date)->addDay()->toDateString();

                $attendance_array = $Attendance->toArray();

                $all_hours = [ // Used for hours sum
                    $attendance_array['reg']
                    , $attendance_array['reg_nd']
                    , $attendance_array['spec'] // TODO:  is spec still used?  Or all special holidays are sun?
                    , $attendance_array['spec_nd']
                    , $attendance_array['sun']
                    , $attendance_array['sun_nd']
                    , $attendance_array['legal']
                    , $attendance_array['legal_nd']
                    , $attendance_array['legal_unattend']
                    // , $attendance_array['rest']
                    // , $attendance_array['rest_nd']
                ];


                $all_hours += [
                    'reg' => 0
                    , 'reg_ot' => 0
                    , 'reg_nd' => 0
                    , 'reg_nd_ot' => 0
                    , 'spec' => 0
                    , 'spec_ot' => 0
                    , 'spec_nd' => 0
                    , 'spec_nd_ot' => 0
                    , 'rest' => 0
                    , 'rest_ot' => 0
                    , 'rest_nd' => 0
                    , 'rest_nd_ot' => 0
                    , 'legal' => 0
                    , 'legal_ot' => 0
                    , 'legal_nd' => 0
                    , 'legal_nd_ot' => 0
                    , 'legal_unattend' => 0
                ];


                $today_reg += $attendance_array['reg'];
                $today_reg_nd += $attendance_array['reg_nd'];

                $total_reg += $attendance_array['reg'];
                $total_reg_nd += $attendance_array['reg_nd'];


                if ($Attendance->getOtApproved() == 'yes') {
                    $today_rest += $attendance_array['rest'];
                    $today_rest_nd += $attendance_array['rest_nd'];

                    $total_rest += $attendance_array['rest'];
                    $total_rest_nd += $attendance_array['rest_nd'];

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
                    , $attendance_array['rest']
                    , $attendance_array['rest_nd']
                    ));


                    // Moved here from below
                    $today_reg_ot += $attendance_array['reg_ot'];
                    $today_reg_nd_ot += $attendance_array['reg_nd_ot'];

                    $today_rest_ot += $attendance_array['rest_ot'];
                    $today_rest_nd_ot += $attendance_array['rest_nd_ot'];

                    $today_sun_ot += $attendance_array['sun_ot'] + $attendance_array['spec_ot'];
                    $today_sun_nd_ot += $attendance_array['sun_nd_ot'] + $attendance_array['spec_nd_ot'];

                    $today_legal_ot += $attendance_array['legal_ot'];
                    $today_legal_nd_ot += $attendance_array['legal_nd_ot'];

                    $total_reg_ot += $attendance_array['reg_ot'];
                    $total_reg_nd_ot += $attendance_array['reg_nd_ot'];

                    $total_rest_ot += $attendance_array['rest_ot'];
                    $total_rest_nd_ot += $attendance_array['rest_nd_ot'];

                    $total_sun_ot += $attendance_array['sun_ot'] + $attendance_array['spec_ot'];
                    $total_sun_nd_ot += $attendance_array['sun_nd_ot'] + $attendance_array['spec_nd_ot'];

                    $total_legal_ot += $attendance_array['legal_ot'];
                    $total_legal_nd_ot += $attendance_array['legal_nd_ot'];

                } elseif ($Attendance->getExtendedShift() == 'yes') { // Bill to Messerve

                    if ($Attendance->getApprovedExtendedShift() != 'yes') {
                        $floating = Floating::firstOrCreate(['attendance_id' => $Attendance->getId()]);

                        $floating->update([
                            'reg_ot' => $attendance_array['reg_ot'],
                            'reg_nd_ot' => $attendance_array['reg_nd_ot'],

                            'spec_ot' => $attendance_array['spec_ot'],
                            'spec_nd_ot' => $attendance_array['spec_nd_ot'],

                            'rest_ot' => $attendance_array['rest_ot'],
                            'rest_nd_ot' => $attendance_array['rest_nd_ot'],

                            'legal_ot' => $attendance_array['legal_ot'],
                            'legal_nd_ot' => $attendance_array['legal_nd_ot'],
                        ]);
                    }

                    $temp_ot = [
                        'reg_ot' => $attendance_array['reg_ot']
                        , 'spec_ot' => $attendance_array['spec_ot']
                        , 'sun_ot' => $attendance_array['sun_ot']
                        , 'legal_ot' => $attendance_array['legal_ot']
                        , 'rest_ot' => $attendance_array['rest_ot']
                        , 'rest' => $attendance_array['rest'] // TODO:  why is this here? -- Because Messerve gets billed for rest day
                    ];

                    $temp_nd_ot = [
                        'reg_nd_ot' => $attendance_array['reg_nd_ot']
                        , 'spec_nd_ot' => $attendance_array['spec_nd_ot']
                        , 'sun_nd_ot' => $attendance_array['sun_nd_ot']
                        , 'legal_nd_ot' => $attendance_array['legal_nd_ot']
                        , 'rest_nd_ot' => $attendance_array['rest_nd_ot']
                        , 'rest_nd' => $attendance_array['rest_nd']
                    ];

                    $messerve_ot_array = $temp_ot + $temp_nd_ot;
                    $messerve_ot += array_sum($messerve_ot_array); // Bill OT to Messerve

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

                    $all_hours['reg'] += array_sum($temp_ot);

                    // Bill client OT as non-OT

                    $today_reg += $attendance_array['reg_ot'];
                    $today_reg += $attendance_array['rest_ot'] + $attendance_array['rest'];

                    $today_sun += $attendance_array['sun_ot'] + $attendance_array['spec_ot'];
                    $today_legal += $attendance_array['legal_ot'];

                    $today_reg_nd += $attendance_array['reg_nd_ot'];
                    $today_sun_nd += $attendance_array['sun_nd_ot'] + $attendance_array['spec_nd_ot'];
                    $today_legal_nd += $attendance_array['legal_nd_ot'];
                    $today_reg_nd += $attendance_array['rest_nd_ot'] + $attendance_array['rest_nd'];


                    $total_reg += $attendance_array['reg_ot'] + $attendance_array['rest_ot'] + $attendance_array['rest'];

                    $total_sun += $attendance_array['sun_ot'] + $attendance_array['spec_ot'];
                    $total_legal += $attendance_array['legal_ot'];

                    $total_reg_nd += $attendance_array['reg_nd_ot'];
                    $total_sun_nd += $attendance_array['sun_nd_ot'] + $attendance_array['spec_nd_ot'];
                    $total_legal_nd += $attendance_array['legal_nd_ot'];
                    $total_reg_nd += $attendance_array['rest_nd_ot'] + $attendance_array['rest_nd'];
                    // $total_reg_nd += array_sum($temp_nd_ot); // Bill NDOT as RegND to client

                    $all_hours['reg_nd'] += array_sum($temp_nd_ot); // TODO:  is this still used?

                    $all_messerve_rest += $attendance_array['rest'];
                    $all_messerve_rest_nd += $attendance_array['rest_nd'];

                    $messerve_reg_ot += $attendance_array['reg_ot'];
                    $messerve_reg_nd_ot += $attendance_array['reg_nd_ot'];

                    $messerve_rest += $attendance_array['rest'];
                    $messerve_rest_nd += $attendance_array['rest_nd'];

                    $messerve_rest_ot += $attendance_array['rest_ot'];
                    $messerve_rest_nd_ot += $attendance_array['rest_nd_ot'];

                    $messerve_sun_ot += $attendance_array['sun_ot'] + $attendance_array['spec_ot'];
                    $messerve_sun_nd_ot += $attendance_array['sun_nd_ot'] + $attendance_array['spec_nd_ot'];

                    $messerve_legal_ot += $attendance_array['legal_ot'];
                    $messerve_legal_nd_ot += $attendance_array['legal_nd_ot'];

                } elseif ($Attendance->getExtendedShift() != 'yes') { // Not bill to Messerve, not OT.  Rest day pay
                    $today_rest += $attendance_array['rest'];
                    $today_rest_nd += $attendance_array['rest_nd'];

                    $total_rest += $attendance_array['rest'];
                    $total_rest_nd += $attendance_array['rest_nd'];

                    $all_hours = array_merge($all_hours, array(
                        'rest' => $attendance_array['rest']
                    , 'rest_nd' => $attendance_array['rest_nd']
                    ));
                }

                $today_sun += $attendance_array['sun'] + $attendance_array['spec'];
                $today_sun_nd += $attendance_array['sun_nd'] + $attendance_array['spec_nd'];

                $today_legal += $attendance_array['legal'];
                $today_legal_nd += $attendance_array['legal_nd'];

                $today_legal_unattend += $attendance_array['legal_unattend'];

                $total_sun += $attendance_array['sun'] + $attendance_array['spec'];
                $total_sun_nd += $attendance_array['sun_nd'] + $attendance_array['spec_nd'];

                $total_legal += $attendance_array['legal'];
                $total_legal_nd += $attendance_array['legal_nd'];

                $total_legal_unattend += $attendance_array['legal_unattend'];

                $total_hours = array_sum($all_hours);
                $total_total_hours += round($total_hours, 2);

                $employee_attendance_text[$current_date] = round($total_hours, 2);

                // $EloqAttendance = Messerve_Model_Eloquent_Attendance::find($Attendance->getId());
                // $EloqAttendancePay = $EloqAttendance->attendancePayroll->first(); // TODO: move to model, maybe?


                $split_bill_hours[Carbon::parse($Attendance->getDatetimeStart())->toDateString()][$Attendance->getEmployeeId()] = [
                    // $split_bill_hours[\Carbon\Carbon::parse($Attendance->getDatetimeStart())->toDateString()][$Attendance->getEmployeeNumber()] = [
                    // $split_bill_hours[$EloqAttendance->datetime_start->toDateString()][] = [
                    'reg' => $today_reg
                    , 'reg_ot' => $today_reg_ot
                    , 'reg_nd' => $today_reg_nd
                    , 'reg_nd_ot' => $today_reg_nd_ot
                    , 'spec' => $today_sun
                    , 'spec_ot' => $today_sun_ot
                    , 'spec_nd' => $today_sun_nd
                    , 'spec_nd_ot' => $today_sun_nd_ot
                    , 'rest' => $today_rest
                    , 'rest_ot' => $today_rest_ot
                    , 'rest_nd' => $today_rest_nd
                    , 'rest_nd_ot' => $today_rest_nd_ot
                    , 'legal' => $today_legal
                    , 'legal_ot' => $today_legal_ot
                    , 'legal_nd' => $today_legal_nd
                    , 'legal_nd_ot' => $today_legal_nd_ot
                    , 'legal_unattend' => $today_legal_unattend
                    // , 'employee'=> EmployeeEloq::findByEmployeeNumber($Attendance->getEmployeeNumber())
                ];

            }


            if ($total_total_hours > 0) {
                $i = 1;

                foreach ($employee_attendance_text as $evalue) {
                    $page->setFont($font, 8)->drawText(round_this($evalue, 2), $dim_x + ($i * 25) + 150, $dim_y, 'UTF8');
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

                // $all_total_total_hours += $total_total_hours;

                $now_x = $dim_x + ($i * 22) + 110 + 66;
                $now_inc = 70;

                // TODO:  Set totals here!

                /*
                $all_hours_array = [
                    'reg' => $all_total_reg
                    , 'reg_ot' => $all_total_reg_ot
                    , 'reg_nd' => $all_total_reg_nd
                    , 'reg_nd_ot' => $all_total_reg_nd_ot
                    , 'spec' => $all_total_sun
                    , 'spec_ot' => $all_total_sun_ot
                    , 'spec_nd' => $all_total_sun_nd
                    , 'spec_nd_ot' => $all_total_sun_nd_ot
                    , 'rest' => $all_total_rest
                    , 'rest_ot' => $all_total_rest_ot
                    , 'rest_nd' => $all_total_rest_nd
                    , 'rest_nd_ot' => $all_total_rest_nd_ot
                    , 'legal' => $all_total_legal
                    , 'legal_ot' => $all_total_legal_ot
                    , 'legal_nd' => $all_total_legal_nd
                    , 'legal_nd_ot' => $all_total_legal_nd_ot
                    , 'legal_unattend' => $all_total_legal_unattend
                ];
                */


                // $page->setFont($font, 8)->drawText('Total ' . round_this($total_total_hours, 2), $dim_x + $now_x, $dim_y, 'UTF8');
                $this->writePdfLine($page, 'Total ' . round_this($total_total_hours, 2), $dim_x + $now_x, $dim_y, 'bold', 8);

                if ($messerve_ot > 0) {
                    $all_messerve_ot += $messerve_ot;
                    // $page->setFont($italic, 8)->drawText($messerve_ot, $dim_x + $now_x + 47, $dim_y, 'UTF8');
                    $this->writePdfLine($page, $messerve_ot, $dim_x + $now_x + 47, $dim_y, 'italic', 8);
                }


                $now_x += $now_inc;

                $ot_font = $font;


                // $page->setFont($font, 8)->drawText('Reg ' . round_this($total_reg, 2), $dim_x + $now_x, $dim_y, 'UTF8');
                $this->writePdfLine($page, 'Reg ' . round_this($total_reg, 2), $dim_x + $now_x, $dim_y, 'normal', 8);
                $now_x += $now_inc;

                // $page->setFont($ot_font, 8)->drawText('RegOT ' . round_this($total_reg_ot, 2), $dim_x + $now_x, $dim_y, 'UTF8');
                $this->writePdfLine($page, 'RegOT ' . round_this($total_reg_ot, 2), $dim_x + $now_x, $dim_y, 'normal', 8);

                if ($messerve_reg_ot > 0) {
                    // $page->setFont($italic, 8)->drawText($messerve_reg_ot, $dim_x + $now_x + 47, $dim_y, 'UTF8');
                    $this->writePdfLine($page, $messerve_reg_ot, $dim_x + $now_x + 47, $dim_y, 'normal', 8);
                }
                $now_x += $now_inc;

                // $page->setFont($font, 8)->drawText('RegND ' . round_this($total_reg_nd, 2), $dim_x + $now_x, $dim_y, 'UTF8');
                $this->writePdfLine($page, 'RegND ' . round_this($total_reg_nd, 2), $dim_x + $now_x, $dim_y, 'normal', 8);
                $now_x += $now_inc;

                // $page->setFont($ot_font, 8)->drawText('RegNDOT ' . round_this($total_reg_nd_ot, 2), $dim_x + $now_x, $dim_y, 'UTF8');
                $this->writePdfLine($page, 'RegNDOT ' . round_this($total_reg_nd_ot, 2), $dim_x + $now_x, $dim_y, 'normal', 8);

                if ($messerve_reg_nd_ot > 0) {
                    // $page->setFont($italic, 8)->drawText($messerve_reg_nd_ot, $dim_x + $now_x + 56, $dim_y, 'UTF8');
                    $this->writePdfLine($page, $messerve_reg_nd_ot, $dim_x + $now_x + 56, $dim_y, 'italic', 8);
                }

                /* New line */
                $now_x = $dim_x + ($i * 22) + 110 + 66;
                $dim_y -= 10;

                // $page->setFont($font, 8)->drawText('SunSp ' . round_this($total_sun, 2), $dim_x + $now_x, $dim_y, 'UTF8');
                $this->writePdfLine($page, 'SunSp ' . round_this($total_sun, 2), $dim_x + $now_x, $dim_y, 'normal', 8);
                $now_x += $now_inc;

                // $page->setFont($ot_font, 8)->drawText('SunSpOT ' . round_this($total_sun_ot, 2), $dim_x + $now_x, $dim_y, 'UTF8');
                $this->writePdfLine($page, 'SunSpOT ' . round_this($total_sun_ot, 2), $dim_x + $now_x, $dim_y, 'normal', 8);

                if ($messerve_sun_ot > 0) {
                    // $page->setFont($italic, 8)->drawText($messerve_sun_ot, $dim_x + $now_x + 47, $dim_y, 'UTF8');
                    $this->writePdfLine($page, $messerve_sun_ot, $dim_x + $now_x + 47, $dim_y, 'italic', 8);
                }

                $now_x += $now_inc;

                $page->setFont($font, 8)->drawText('SunSpND ' . round_this($total_sun_nd, 2), $dim_x + $now_x, $dim_y, 'UTF8');
                $this->writePdfLine($page, 'SunSpND ' . round_this($total_sun_nd, 2), $dim_x + $now_x, $dim_y, 'normal', 8);
                $now_x += $now_inc;

                // $page->setFont($ot_font, 8)->drawText('SunSpNDOT ' . round_this($total_sun_nd_ot, 2), $dim_x + $now_x, $dim_y, 'UTF8');
                $this->writePdfLine($page, 'SunSpNDOT ' . round_this($total_sun_nd_ot, 2), $dim_x + $now_x, $dim_y, 'normal', 8);

                if ($messerve_sun_nd_ot > 0) {
                    // $page->setFont($italic, 8)->drawText($messerve_sun_nd_ot, $dim_x + $now_x + 56, $dim_y, 'UTF8');
                    $this->writePdfLine($page, $messerve_sun_nd_ot, $dim_x + $now_x + 56, $dim_y, 'italic', 8);
                }

                /* New line */
                $now_x = $dim_x + ($i * 22) + 110 + 66;
                $dim_y -= 10;

                // $page->setFont($font, 8)->drawText('RestSp ' . round_this($total_rest, 2), $dim_x + $now_x, $dim_y, 'UTF8');
                $this->writePdfLine($page, 'RestSp ' . round_this($total_rest, 2), $dim_x + $now_x, $dim_y, 'normal', 8);

                if ($messerve_rest > 0) {
                    // $page->setFont($italic, 8)->drawText($messerve_rest, $dim_x + $now_x + 47, $dim_y, 'UTF8');
                    $this->writePdfLine($page, $messerve_rest, $dim_x + $now_x + 47, $dim_y, 'italic', 8);
                }
                $now_x += $now_inc;

                // $page->setFont($ot_font, 8)->drawText('RestSpOT ' . round_this($total_rest_ot, 2), $dim_x + $now_x, $dim_y, 'UTF8');
                $this->writePdfLine($page, 'RestSpOT ' . round_this($total_rest_ot, 2), $dim_x + $now_x, $dim_y, 'normal', 8);

                if ($messerve_rest_ot > 0) {
                    // $page->setFont($italic, 8)->drawText($messerve_rest_ot, $dim_x + $now_x + 47, $dim_y, 'UTF8');
                    $this->writePdfLine($page, $messerve_rest_ot, $dim_x + $now_x + 47, $dim_y, 'italic', 8);
                }
                $now_x += $now_inc;

                // $page->setFont($font, 8)->drawText('RestSpND ' . round_this($total_rest_nd, 2), $dim_x + $now_x, $dim_y, 'UTF8');
                $this->writePdfLine($page, 'RestSpND ' . round_this($total_rest_nd, 2), $dim_x + $now_x, $dim_y, 'normal', 8);

                if ($messerve_rest_nd > 0) {
                    // $page->setFont($italic, 8)->drawText($messerve_rest_nd, $dim_x + $now_x + 47, $dim_y, 'UTF8');
                    $this->writePdfLine($page, $messerve_rest_nd, $dim_x + $now_x + 47, $dim_y, 'italic', 8);
                }

                $now_x += $now_inc;

                // $page->setFont($ot_font, 8)->drawText('RestSpNDOT ' . round_this($total_rest_nd_ot, 2), $dim_x + $now_x, $dim_y, 'UTF8');
                $this->writePdfLine($page, 'RestSpNDOT ' . round_this($total_rest_nd_ot, 2), $dim_x + $now_x, $dim_y, 'normal', 8);

                if ($messerve_rest_nd_ot > 0) {
                    // $page->setFont($italic, 8)->drawText($messerve_rest_nd_ot, $dim_x + $now_x + 47, $dim_y, 'UTF8');
                    $this->writePdfLine($page, $messerve_rest_nd_ot, $dim_x + $now_x + 47, $dim_y, 'italic', 8);
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

                $csv_data[$value['attendance']->id] = [
                    'employee_number' => $value['attendance']->employee_number,
                ];
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


        $all_hours_array = [
            'reg' => $all_total_reg
            , 'reg_ot' => $all_total_reg_ot
            , 'reg_nd' => $all_total_reg_nd
            , 'reg_nd_ot' => $all_total_reg_nd_ot
            , 'spec' => $all_total_sun
            , 'spec_ot' => $all_total_sun_ot
            , 'spec_nd' => $all_total_sun_nd
            , 'spec_nd_ot' => $all_total_sun_nd_ot
            , 'rest' => $all_total_rest
            , 'rest_ot' => $all_total_rest_ot
            , 'rest_nd' => $all_total_rest_nd
            , 'rest_nd_ot' => $all_total_rest_nd_ot
            , 'legal' => $all_total_legal
            , 'legal_ot' => $all_total_legal_ot
            , 'legal_nd' => $all_total_legal_nd
            , 'legal_nd_ot' => $all_total_legal_nd_ot
            , 'legal_unattend' => $all_total_legal_unattend
        ];


        if ($all_messerve_ot > 0) {
            $page->setFont($font, 8)->drawText('Total ' . round($all_total_total_hours, 2), $dim_x + $now_x, $dim_y, 'UTF8');
            $page->setFont($italic, 8)->drawText($all_messerve_ot, $dim_x + $now_x + 47, $dim_y, 'UTF8');
        } else {
            $page->setFont($font, 8)->drawText('Total ' . round($all_total_total_hours, 2), $dim_x + $now_x, $dim_y, 'UTF8');
        }

        $now_x += $now_inc;


        if ($all_messerve_ot > 0) { // Messerve pays OT,  add OT to client's billable reg hours
            $page->setFont($font, 8)->drawText('Reg ' . round($all_total_reg, 2), $dim_x + $now_x, $dim_y, 'UTF8');
        } else {
            $page->setFont($font, 8)->drawText('Reg ' . round($all_total_reg, 2), $dim_x + $now_x, $dim_y, 'UTF8');
        }

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
        if ($all_messerve_rest > 0) {
            $page->setFont($italic, 8)->drawText($all_messerve_rest, $dim_x + $now_x + 47, $dim_y, 'UTF8');
        }
        $now_x += $now_inc;

        $page->setFont($font, 8)->drawText('RestSpOT ' . round($all_total_rest_ot, 2), $dim_x + $now_x, $dim_y, 'UTF8');
        if ($all_messerve_rest_ot > 0) {
            $page->setFont($italic, 8)->drawText($all_messerve_rest_ot, $dim_x + $now_x + 47, $dim_y, 'UTF8');
        }
        $now_x += $now_inc;

        $page->setFont($font, 8)->drawText('RestSpND ' . round($all_total_rest_nd, 2), $dim_x + $now_x, $dim_y, 'UTF8');
        if ($all_messerve_rest_nd > 0) {
            $page->setFont($italic, 8)->drawText($all_messerve_rest_nd, $dim_x + $now_x + 47, $dim_y, 'UTF8');
        }
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

        $page->setFont($font, 8)->drawText('Pilipinas Messerve Inc.  |  91 Congressional Avenue, Brgy. Bahay Toro, Project 8, Quezon City', $dim_x, 12);

        $pdf->pages[] = $page;

        $date_start = $this->_request->getParam('date_start');

        $group_id = $this->_request->getParam('group_id');

        $Group = new Messerve_Model_Group();

        $Group->find($group_id);

        $filename = $folder . "Summary_{$this->_client->getName()}_{$Group->getName()}_{$group_id}_{$date_start}_{$this->_last_date}.pdf";


        if ($csv_only) {
            $this->_helper->viewRenderer->setNoRender(true);
            $this->_helper->layout->disableLayout();

            $this->summaryToXls(
                $split_bill_hours,
                (Messerve_Model_Eloquent_Group::find($group_id)),
                sprintf('Summary_export_%s_%s_%s_%s_%s',
                    $this->_client->getName(), $Group->getName(), $group_id, $date_start, $this->_last_date)
            );
            return;
        }

        $pdf->save($filename);

        // TODO:  split client bill here
        // Wage increase happened mid-cutoff?

        $client_rate_schedule = Messerve_Model_Eloquent_ClientRateSchedule::cutoffRates($group_id, $date_start);

        if ($client_rate_schedule->count() > 0) { // Client has a rate change scheduled?
            $split_bill = $this->splitClientBill($client_rate_schedule, $split_bill_hours, $date_start);

            $i = 'A';
            foreach ($split_bill as $bill) {
                $this->_createBillPdf($bill['sums'], $bill['client_rate_id'], "$i");
                $i++;
            }
        } else {
            $this->_createBillPdf($all_hours_array);
        }

        if ($standalone != '' && $standalone == 'true') {
            $this->_redirect($_SERVER['HTTP_REFERER']);
        }

    }

    protected function arrayToCSV($data)
    {
        $rows = [];

        $i = 0;
        foreach ($data as $key => $row) {
            if ($i === 0) {
                $rows += [array_keys(array_shift($row))];
            }
            $rows = array_merge($rows, array_values($row));
            $i = 1;
        }

        $Excel = new PHPExcel();
        $sheet = $Excel->getActiveSheet();

        $sheet->fromArray($rows);


        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="kimi_no_nawa.xls"');
        header('Cache-Control: max-age=0');

        $writer = PHPExcel_IOFactory::createWriter($Excel, 'Excel5');

        // This line will force the file to download
        $writer->save('php://output');


    }

    protected function splitClientBill($client_rate_schedule, $split_bill_hours, $cutoff_date)
    { // For mid-cutoff wage increases

        $rate_change_dates = [];

        foreach ($client_rate_schedule as $schedule) {
            $rate_change_dates[$schedule->rate->id] = $schedule->date_active->toDateString();
        }

        $hours_array = [];
        $hours_array[$cutoff_date] = [];
        $hours_array[$cutoff_date]['hours'] = [];
        $hours_array[$cutoff_date]['client_rate_id'] = 0;

        ksort($split_bill_hours);

        $current_date = $cutoff_date;


        foreach ($split_bill_hours as $key => $hours) {

            $value_key = array_search($key, $rate_change_dates);

            if ($value_key !== false) {
                $current_date = $key;
                $hours_array[$current_date] = [];
                $hours_array[$current_date]['hours'] = [];
                $hours_array[$current_date]['client_rate_id'] = $value_key;
                unset($rate_change_dates[$value_key]);
            }

            foreach ($hours as $hour) {
                $hours_array[$current_date]['hours'][] = $hour;
                $total_reg[$key] = array_sum(array_column($hour, 'reg'));
            }

        }

        $column_map = ['reg'
            , 'reg_ot'
            , 'reg_nd'
            , 'reg_nd_ot'
            , 'spec'
            , 'spec_ot'
            , 'spec_nd'
            , 'spec_nd_ot'
            , 'rest'
            , 'rest_ot'
            , 'rest_nd'
            , 'rest_nd_ot'
            , 'legal'
            , 'legal_ot'
            , 'legal_nd'
            , 'legal_nd_ot'
            , 'legal_unattend'];


        $return = [];

        foreach ($hours_array as $hkey => &$date) {

            foreach ($column_map as $column) {
                $column_sum = array_sum(array_column($date['hours'], $column));
                $date['sums'][$column] = $column_sum;
            }

            if (!isset($total_reg[$key])) $total_reg[$key] = 0;

            $return[$hkey] = [
                'sums' => $date['sums'],
                'client_rate_id' => $date['client_rate_id']
            ];
        }


        return $return;
    }


    protected function _createBillPdf($all_hours_array, $rate = 0, $extension = '')
    {
        $client = $this->_client;

        $group_id = $this->_request->getParam('group_id');

        $Group = new Messerve_Model_Group();

        $Group->find($group_id);

        // TODO:  find by date!

        // $RateSchedule = new Messerve_Model_EmployeeRateSchedule();
        // $rates = $RateSchedule->getMapper()->fetchList("group_id = $group_id", "date_active DESC");

        $ClientRate = new Messerve_Model_RateClient();

        if ($rate > 0) {
            $ClientRate->find($rate);
        } else {
            $ClientRate->find($Group->getRateClientId());
        }

        $date_start = $this->_request->getParam('date_start');

        $cutoff_modifier = 1;

        if (strstr($date_start, '-16')) $cutoff_modifier = 0;

        $folder = realpath(APPLICATION_PATH . '/../public/export') . "/$date_start/$group_id/client/";

        $cmd = "mkdir -p $folder";

        $date_now = date("Y-m-d-hi");

        if ($extension != '') {
            $extension = "-$extension";
        }
        $filename = $folder . "Client_Report_{$this->_client->getName()}_{$Group->getName()}_{$group_id}_{$date_start}_{$date_now}{$extension}.pdf";

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
            'reg' => $all_hours_array['reg'] * $ClientRate->getReg()
        , 'reg_nd' => $all_hours_array['reg_nd'] * $ClientRate->getRegNd()
        , 'reg_ot' => $all_hours_array['reg_ot'] * $ClientRate->getRegOt()
        , 'reg_nd_ot' => $all_hours_array['reg_nd_ot'] * $ClientRate->getRegNdOt()

        , 'spec' => $all_hours_array['spec'] * $ClientRate->getSpec()
        , 'spec_nd' => $all_hours_array['spec_nd'] * $ClientRate->getSpecNd()
        , 'spec_ot' => $all_hours_array['spec_ot'] * $ClientRate->getSpecOt()
        , 'spec_nd_ot' => $all_hours_array['spec_nd_ot'] * $ClientRate->getSpecNdOt()

        , 'legal' => $all_hours_array['legal'] * $ClientRate->getLegal()
        , 'legal_nd' => $all_hours_array['legal_nd'] * $ClientRate->getLegalNd()
        , 'legal_ot' => $all_hours_array['legal_ot'] * $ClientRate->getLegalOt()
        , 'legal_nd_ot' => $all_hours_array['legal_nd_ot'] * $ClientRate->getLegalNdOt()


        , 'rest' => $all_hours_array['rest'] * $ClientRate->getSpec()
        , 'rest_nd' => $all_hours_array['rest_nd'] * $ClientRate->getSpecNd()
        , 'rest_ot' => $all_hours_array['rest_ot'] * $ClientRate->getSpecOt()
        , 'rest_nd_ot' => $all_hours_array['rest_nd_ot'] * $ClientRate->getSpecNdOt()

        , 'legal_unattend' => $all_hours_array['legal_unattend'] * $ClientRate->getLegalUnattend()
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

        // $this->view->client = $this->_client;
        // $this->view->group = $Group;
        // $this->view->bill = $total_bill;
        // $this->view->hours = $this->_employer_bill;

        /* PDF */

        $template = realpath(APPLICATION_PATH . "/../library/Templates/oh.pdf");

        if (!file_exists($template)) {
            throw new \RuntimeException($template . ': template does not exist.');
        }

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


        $page->setFont($font, 10)->drawText($billing_number . $extension, $dim_x + 380, $dim_y);

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

        if (!function_exists('romanic_number')) {
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
        }

        $i = 0;

        $total_hours = 0;

        foreach ($total_bill as $key => $value) {
            $i++;

            $page->setFont($font, $bill_font_size)->drawText(str_pad(romanic_number($i, true), 5, ' ', STR_PAD_RIGHT), $dim_x - 28, $dim_y);

            $page->setFont($font, $bill_font_size)->drawText($hours_label[$key], $dim_x, $dim_y);

            $page->setFont($font, $bill_font_size)->drawText($hours_description[$key]
                , $dim_x + 60, $dim_y);

            if ($this->_employer_bill[$key] > 0) {
                $clean_hours = round($all_hours_array[$key], 2);
                $this_hours = number_format($clean_hours, 2);
                $page->setFont($mono, $bill_font_size)->drawText(str_pad($this_hours, 8, ' ', STR_PAD_LEFT)
                    , $dim_x + 165, $dim_y);


                $page->setFont($mono, $bill_font_size)->drawText(str_pad(number_format(round($client_rate_array[$key], 2), 2), 8, ' ', STR_PAD_LEFT)
                    , $dim_x + 220, $dim_y);

                $total_hours += $clean_hours;
            }

            if ($value > 0) {
                $page->setFont($mono, $bill_font_size)->drawText(str_pad(number_format($value, 2), 12, ' ', STR_PAD_LEFT), $dim_x + 280, $dim_y);
                $total_amount += round($value, 2);

                if ($Group->getNonVat() == 0) {
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
        $page->setFont($mono_bold, $bill_font_size)->drawText(str_pad(number_format(round($total_hours, 2), 2), 8, ' ', STR_PAD_LEFT), $dim_x + 165, $dim_y);
        $page->setFont($mono_bold, $bill_font_size)->drawText(str_pad(number_format($total_amount, 2), 12, ' ', STR_PAD_LEFT), $dim_x + 280, $dim_y);

        $vat_net = $total_amount / 1.12;


        if ($Group->getNonVat() == 0) {
            $page->setFont($mono_bold, $bill_font_size)->drawText(str_pad(number_format($vat_net, 2), 12, ' ', STR_PAD_LEFT)
                , $dim_x + 355, $dim_y);

            $page->setFont($mono_bold, $bill_font_size)->drawText(str_pad(number_format($vat_net * 0.12, 2), 12, ' ', STR_PAD_LEFT)
                , $dim_x + 435, $dim_y);
        }

        $dim_y -= 32;

        $page->setFont($font, $bill_font_size)->drawText('VATABLE SALES', $dim_x, $dim_y);

        if ($Group->getNonVat() == 0) {
            $page->setFont($mono, $bill_font_size)->drawText(str_pad(number_format($vat_net, 2), 12, ' ', STR_PAD_LEFT), $dim_x + 285, $dim_y);
        }

        $dim_y -= 12;

        $page->setFont($font, $bill_font_size)->drawText('VALUE ADDED TAX (VAT)', $dim_x, $dim_y);
        $page->setFont($bold, $bill_font_size)->drawText('12%', $dim_x + 185, $dim_y);

        if ($Group->getNonVat() == 0) {
            $page->setFont($mono, $bill_font_size)->drawText(str_pad(number_format($vat_net * 0.12, 2), 12, ' ', STR_PAD_LEFT), $dim_x + 285, $dim_y);
        }

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
    }

    protected function get_legal_attended_days($employee_id, $date_start, $date_end)
    {
        $AttendanceDb = new Messerve_Model_DbTable_Attendance();

        $select = $AttendanceDb->select();

        $select->from($AttendanceDb, array("COUNT(*) AS amount"))
            ->where("employee_id = $employee_id
                AND datetime_start >= '$date_start 00:00'
                AND datetime_start <= '$date_end 23:59'
                AND (legal > 0 OR legal_ot > 0)");

        $rows = $AttendanceDb->fetchAll($select);

        return ($rows[0]->amount);
    }

    protected function get_cutoff_attended_days($employee_id, $date_start, $date_end)
    {
        $AttendanceDb = new Messerve_Model_DbTable_Attendance();

        $select = $AttendanceDb->select();

        $select->from($AttendanceDb, array("COUNT(*) AS amount"))
            ->where("employee_id = $employee_id
                AND datetime_start >= '$date_start 00:00'
                AND datetime_start <= '$date_end 23:59'
                AND (start_1 >= 1 OR legal_unattend > 0)");

        $rows = $AttendanceDb->fetchAll($select);

        return ($rows[0]->amount);
    }

    public function exportAction()
    {  // CSV export
        $this->_helper->layout()->disableLayout();

        $period_covered = $this->_request->getParam('period_covered');

        $PayrollMap = new Messerve_Model_Mapper_PayrollTemp();
        $payroll = $PayrollMap->fetchList("period_covered = '{$period_covered}'"
            , array("lastname", "firstname", "employee_number", "is_reliever DESC"));

        $payroll_array = array();

        $hours_struct = [
            'Regular' => [
                'reg' => ['hours' => 0, 'pay' => 0],
                'ot' => ['hours' => 0, 'pay' => 0],
                'nd' => ['hours' => 0, 'pay' => 0],
                'nd_ot' => ['hours' => 0, 'pay' => 0],
            ],
            'Special' => [
                'reg' => ['hours' => 0, 'pay' => 0],
                'ot' => ['hours' => 0, 'pay' => 0],
                'nd' => ['hours' => 0, 'pay' => 0],
                'nd_ot' => ['hours' => 0, 'pay' => 0],
            ],
            'Rest' => [
                'reg' => ['hours' => 0, 'pay' => 0],
                'ot' => ['hours' => 0, 'pay' => 0],
                'nd' => ['hours' => 0, 'pay' => 0],
                'nd_ot' => ['hours' => 0, 'pay' => 0],
            ],
            'Legal' => [
                'reg' => ['hours' => 0, 'pay' => 0],
                'ot' => ['hours' => 0, 'pay' => 0],
                'nd' => ['hours' => 0, 'pay' => 0],
                'nd_ot' => ['hours' => 0, 'pay' => 0],
                // 'unattend' => ['hours' => 0, 'pay' => 0],
            ],
            'Legal unattended' => [
                'reg' => ['hours' => 0, 'pay' => 0]
            ],
        ];


        /** @var Messerve_Model_PayrollTemp $pvalue */
        foreach ($payroll as $pvalue) {

            $employee_type = 'Regular';

            if ($pvalue->getIsReliever() === 'yes') {
                $employee_type = 'Reliever';
            }

            $payroll_meta = json_decode($pvalue->getDeductionData());

            $BOP = new Messerve_Model_Bop();
            $Employee = new Messerve_Model_Employee();
            $Employee->find($pvalue->getEmployeeId());

            $maintenance = $pvalue->getBopMaintenance();

            $EmployeeEloq = EmployeeEloq::find($pvalue->getEmployeeId());

            if ($maintenance <= 0 && $EmployeeEloq->hasBop()) {

                $BopAttendance = Messerve_Model_Eloquent_BopAttendance::findByEmployeeAndDate($EmployeeEloq, $period_covered);

                if ($BopAttendance) {
                    $maintenance = $BopAttendance->maintenance_addition;
                }
            }

            $bop_maintenance = $maintenance;
            $bop_rental = 0;

            if ($Employee->getBopId() > 0) {
                $BOP->find($Employee->getBopId());

                if (stripos($BOP->getName(), 'R1') === false) {
                    //
                } else {
                    $bop_maintenance = 0;
                    $bop_rental = $maintenance;
                }
            }

            $other_deductions = $pvalue->getBopMotorcycle() + $pvalue->getBopInsurance()
                + $pvalue->getAccident() + $pvalue->getUniform()
                + $pvalue->getAdjustment() + $pvalue->getMiscellaneous()
                + $pvalue->getCommunication() + $pvalue->getFuelDeduction()
                + $pvalue->lost_card + $pvalue->food;

            $other_deductions = number_format(round($other_deductions * -1, 2), 2);

            $this_row = [
                'Period covered' => $pvalue->getPeriodCovered()
                , 'Client name' => $pvalue->getClientName()
                , 'Group name' => strtoupper($pvalue->getGroupName())
                , 'Employee type' => $employee_type
                , 'TIN number' => $Employee->getTin()
                , 'Employee number' => $pvalue->getEmployeeNumber()


                , 'Last name' => $pvalue->getLastName()
                , 'First name' => $pvalue->getFirstName()
                , 'Middle name' => $pvalue->getMiddleName()
                , 'Ecola' => number_format(round($pvalue->getEcola(), 2), 2)
            ];


            $hours_meta = (new GetPayrollMetaAction())($pvalue);

            foreach ($hours_struct as $type => $pay) {
                foreach ($pay as $title => $breakdown) {
                    if (isset($hours_meta[$type]) && isset($hours_meta[$type][$title])) {
                        $this_row["$type $title hours"] = $hours_meta[$type][$title]['hours'];
                        $this_row["$type $title pay"] = $hours_meta[$type][$title]['pay'];
                    } else {
                        $this_row["$type $title hours"] = $breakdown['hours'];
                        $this_row["$type $title pay"] = $breakdown['pay'];
                    }
                }
            }

            $sss_ec = ($pvalue->getGrossPay() >= 14750) ? -30 : -10;

            if ($pvalue->getGroupId() != $Employee->getGroupId()) { // Payroll not for parent group?  Reset.
                $bop_rental = 0;
                $bop_maintenance = 0;
                $sss_ec = 0;
            }

            $misc_deduction = json_decode($pvalue->getDeductionData());
            $misc_deduction_string = '';

            $sss_calamity_loan_amount = 0;

            // TODO: Hacky! Fix this!
            $fuel_overage_scheduled_amount = 0;
            $maintenance_scheduled_amount = 0;

            if (count($misc_deduction) > 0) {
                foreach ($misc_deduction as $mkey => $mvalue) {
                    if (is_numeric($mkey) && property_exists($mvalue, 'type')) {
                        $amount = $mvalue->amount;
                        $misc_deduction_string .= "{$mvalue->type}: {$amount}, ";

                        if ($mvalue->type === "sss_calamity") {
                            $sss_calamity_loan_amount = $amount;
                        }

                        if ($mvalue->type === "fuel_overage") {
                            $fuel_overage_scheduled_amount = $amount;
                        }

                        if ($mvalue->type === "maintenance_deduct") {
                            $maintenance_scheduled_amount = $amount;
                        }
                    }
                }
            }

            $this_row += [
                'BasicPay' => number_format($pvalue->getBasicPay(), 2)

                , 'Incentives' => number_format(round($pvalue->getIncentives(), 2), 2)
                , 'BOP maintenance' => $bop_maintenance
                , 'BOP rental' => $bop_rental

                , '13th month pay' => number_format(round($pvalue->getThirteenthMonth(), 2), 2)

                , 'Fuel addition' => number_format(round($pvalue->getFuelAddition(), 2), 2)

                , 'Misc addition' => number_format(round($pvalue->getMiscAddition(), 2), 2)

                , 'Paternity' => number_format(round($pvalue->getPaternity(), 2), 2)

                , 'Solo parent leave' => number_format(round($pvalue->getSoloParentLeave(), 2), 2)

                , 'TL allowance' => number_format(round($pvalue->getTlAllowance(), 2), 2)

                , 'Gross pay' => number_format(round($pvalue->getGrossPay(), 2), 2)

                , 'SSS EE' => number_format(round($pvalue->getSss() * -1, 2), 2)
                , 'SSS ER' => number_format(round($pvalue->getSss() * -2, 2), 2)
                , 'SSS EC' => $sss_ec

                , 'Philhealth EE' => number_format(round($pvalue->getPhilhealth() * -1, 2), 2)
                , 'Philhealth ER' => number_format(round($pvalue->getPhilhealth() * -1, 2), 2)

                , 'HDMF EE' => number_format(round($pvalue->getHdmf() * -1, 2), 2)
                , 'HDMF ER' => number_format(round($pvalue->getHdmf() * -1, 2), 2)

                , 'SSS loan' => number_format(round($pvalue->getSSSLoan() * -1, 2), 2)
                , 'SSS Calamity loan' => number_format(round($sss_calamity_loan_amount * -1, 2), 2)

                , 'HDMF loan' => number_format(round($pvalue->getHDMFLoan() * -1, 2), 2)
                , 'HDMF Calamity loan' => number_format(round($pvalue->getHdmfCalamityLoan() * -1, 2), 2)

                , 'Net pay' => number_format(round($pvalue->getNetPay(), 2), 2)
                , 'Account number' => $pvalue->getAccountNumber()

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

                , 'Fuel overage (scheduled)' => number_format(round($fuel_overage_scheduled_amount * -1, 2), 2)
                , 'Maintenance (scheduled)' => number_format(round($maintenance_scheduled_amount * -1, 2), 2)

            ];


            $this_row['Misc deduction data'] = $misc_deduction_string;

            $this_row['Philhealth Basic'] = $pvalue->getPhilhealthBasic();

            $payroll_array[] = $this_row;

        }

        header('Content-type: text/csv');
        header('Content-Disposition: attachment; filename="Payroll_report-' . $period_covered . '.csv"');

        $this->view->payroll = $payroll_array;
    }

    public function etpsAction()
    {  // CSV export
        $this->_helper->layout()->disableLayout();

        $period_covered = $this->_request->getParam('period_covered');

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="ETPS_export.xls"');

        $PayrollMap = new Messerve_Model_Mapper_PayrollTemp();
        $payroll = $PayrollMap->fetchList("period_covered = '{$period_covered}'",
            array("lastname", "firstname", 'middlename', "employee_number", "is_reliever DESC"));

        $payroll_array = array();

        $etps_dir = realpath(APPLICATION_PATH . '/../public/export/') . '/etps';

        if (!file_exists($etps_dir)) {
            mkdir($etps_dir);
            chmod($etps_dir, 0777);
        }

        $etps_csv = $etps_dir . "/etps_" . $period_covered . date('_Y-m-d_H-i-is') . ".csv";

        foreach ($payroll as $pvalue) {

            $account_number = (int)$pvalue->getAccountNumber();
            if (!($account_number > 0)) continue;

            $employee_type = 'Regular';

            if ($pvalue->getIsReliever() === 'yes') {
                $employee_type = 'Reliever';
            }

            $employee_number = $pvalue->getEmployeeNumber();

            if (isset($payroll_array[$employee_number])) {
                $payroll_array[$employee_number]['Amount'] += round($pvalue->getNetPay(), 2);
            } else {
                $payroll_array[$employee_number] = [
                    'Last Name' => $pvalue->getLastName()
                    , 'First Name' => $pvalue->getFirstName()
                    , 'Middle Name' => $pvalue->getMiddleName()
                    , 'Employee Account Number' => "073" . strtoupper($pvalue->getAccountNumber())
                    , 'Amount' => round($pvalue->getNetPay(), 2)
                ];
            }
        }

        foreach ($payroll_array as $pavalue) {
            $salary = (float)$pavalue['salary'];

            if ($salary <= 1000) {
                error_log(implode(',', $pavalue) . "\n", 3, $etps_csv);
            }
        }

        $spreadsheet = new Spreadsheet();
        $spreadsheet->setActiveSheetIndex(0);

        $activesheet = $spreadsheet->getActiveSheet();

        $activesheet->fromArray($payroll_array);

        $writer = new Xls($spreadsheet);
        $writer->save('php://output');

        // $this->view->payroll = $payroll_array;
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

            $pre_jan = $this->_get_work_duration($evalue->getId(), 0, $last_year . '-11-16', $last_year . '-12-31 23:59');
            $post_jan = $this->_get_work_duration($evalue->getId(), 0, $this_year . '-01-01', $this_year . '-11-15 23:59');

            if (!($pre_jan + $post_jan) > 0) {
                continue;
            }
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

        $result = $AttendDB->fetchRow($select);

        return (float)$result->total;
    }

    protected function get_sss_deduction($total_pay): array
    {
        $SSS = new Messerve_Model_DbTable_Sss();

        $result = $SSS->fetchRow("`min` <= $total_pay AND `max` >= $total_pay");

        $table_sss = $result->employee;

        $multiplier = .045;

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

        $rows = $db->fetchAll($sql);

        unset($db);

        return $rows;
    }

    protected function get_range_attendance_data($rows, $date, $period = 1): array
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

        return "OK";
    }

    public function pendingAction()
    {
        $this->view->pending_payroll = Messerve_Model_Eloquent_PendingPayroll::orderBy('id', 'DESC')->get();
    }

    public function accrualAction()
    {

        $two_months_ago = Carbon\Carbon::today()->subMonth(2);

        $this->view->silp_options = [
            'This month' => Carbon\Carbon::today()->format('Y-m'),
            'Last month' => Carbon\Carbon::today()->subMonth(1)->format('Y-m'),
            $two_months_ago->format('F Y') => $two_months_ago->format('Y-m'),
            'Anniversary-to-date' => 'atd'
        ];

        $thirteenth_month_options = [];

        $today = Carbon::today();

        if ($today->day <= 15) {
            $today->subMonth(1);
            $thirteenth_month_options[$today->format('Y-m-16')] = $today->format('Y-m-16');
        }

        $thirteenth_month_options[$today->format('Y-m-01')] = $today->format('Y-m-01');

        for ($i = 1; $i <= 2; $i++) {
            $today->subMonth(1);
            $thirteenth_month_options[$today->format('Y-m-16')] = $today->format('Y-m-16');
            $thirteenth_month_options[$today->format('Y-m-01')] = $today->format('Y-m-01');
        }

        $last_month = Carbon::today()->subMonth(1);
        $thirteenth_month_options['November-to-' . $two_months_ago->format('M')] = 'nt:' . $two_months_ago->format('Y-m');
        $thirteenth_month_options['November-to-' . $last_month->format('M')] = 'nt:' . $last_month->format('Y-m');
        // $thirteenth_month_options['November-to-date'] = 'nt:' . \Carbon\Carbon::today()->format('Y-m');
        $thirteenth_month_options['November-to-date'] = 'ntd';
        $thirteenth_month_options['Nov 16 previous year - Nov 15 this year'] = 'nt:nov15';

        $this->view->thirteenth_month_options = $thirteenth_month_options;
        $this->view->api_host = ($this->_config->get('messerve'))->api_host;
    }
}