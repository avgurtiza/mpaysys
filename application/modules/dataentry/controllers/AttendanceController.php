<?php

use Carbon\Carbon;
use Domains\Attendance\Actions\GetHistoryChanges;
use Domains\Attendance\Actions\LockDTR;
use Domains\Attendance\Actions\UnlockDTR;
use Domains\Attendance\Actions\ValidateDtrPost;
use Domains\Attendance\Collections\DTRSubmission;
use Illuminate\Support\Collection;

class Dataentry_AttendanceController extends Zend_Controller_Action
{

    protected $_max_regular_hours = null, $_init_night_diff_start, $_init_night_diff_end;
    protected $_round_to_ten_minutes;

    protected $_midnight, $_night_diff_start, $_night_diff_end;

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

        $this->_max_regular_hours = 8;
        $this->_init_night_diff_start = '2200';
        $this->_init_night_diff_end = '0600';

        $this->_round_to_ten_minutes = false;

        if (!in_array($this->_user_auth->type, ['admin', 'supervisor', 'accounting'])) {
            throw new Exception('You are not allowed to access this module.');
        }

    }

    public function indexAction()
    {
        // action body
        $GroupMap = new Messerve_Model_Mapper_Group();

        $groups = $GroupMap->fetchList("id > 0 ", array('client_id ASC', 'name ASC'));

        $Client = new Messerve_Model_Mapper_Client();

        $clients = array();

        foreach ($Client->fetchList('is_active = 1', 'name ASC') as $cvalue) {
            $clients[$cvalue->getId()] = $cvalue->getName();
        }

        $groups_array = array();

        foreach ($groups as $gvalue) {

            if (!array_key_exists($gvalue->getClientId(), $clients)) {
                continue;
            }

            $Employee = new Messerve_Model_DbTable_Employee();

            $employee_count = $Employee->countByQuery('group_id = ' . $gvalue->getId());

            $groups_array[$gvalue->getId()] = $clients[$gvalue->getClientId()] . ' ' . $gvalue->getName()
                . ' (' . $employee_count . ')';
        }


        asort($groups_array);

        $form = new Messerve_Form_Attendance();
        $form->setAction('/dataentry/attendance/employees');

        if (isset($_SESSION['pay_period'])) {
            $pay_period_field = $form->getElement('pay_period');
            $pay_period_field->setValue($_SESSION['pay_period']);
        }

        $groups = $form->getElement('group_id');

        $groups->setMultiOptions($groups_array);

        $this->view->form = $form;
    }

    /**
     * @throws Zend_Form_Exception
     * @throws Exception
     */
    private function saveSubmit(
        Messerve_Model_Attendance    $Attendance,
        Messerve_Form_EditAttendance $form,
        string                       $date,
        int                          $employee_id,
        int                          $pay_period,
        string                       $date_start,
        string                       $date_end,
        int                          $group_id): Messerve_Form_EditAttendance
    {

        throw  new Exception('Not implemented');

        $start_1 = strtotime($date . ' T' . str_pad($this->_request->getPost('start_1'), 4, 0, STR_PAD_LEFT));
        $end_1 = strtotime($date . ' T' . str_pad($this->_request->getPost('end_1'), 4, 0, STR_PAD_LEFT));

        $start_2 = strtotime($date . ' T' . str_pad($this->_request->getPost('start_2'), 4, 0, STR_PAD_LEFT));
        $end_2 = strtotime($date . ' T' . str_pad($this->_request->getPost('end_2'), 4, 0, STR_PAD_LEFT));

        $break_duration = 0;

        if ($end_1 > 0 && $start_1 > 0) {
            $break_duration = ($start_2 - $end_1) / 3600;
        }

        $midnight = strtotime($this->_request->getPost('datetime_start') . ' + 1 day');
        $night_diff_start = strtotime($this->_request->getPost('datetime_start') . 'T' . $this->_night_diff_start);

        $weekday = date('D', $start_1);
        $weekday_midnight = date('D', $midnight);

        // Duration past 8 hours?  Must be OT
        $total_duration = ($end_2 - $start_1) / 3600;
        $total_duration = $total_duration - $break_duration;

        $ot_duration = $total_duration - $this->_max_regular_hours;

        $post_mn_ot = 0;
        $nd_ot = 0;
        $ot = 0;

        if ($ot_duration > 0) {
            // OT happens towards the end of shift
            $end_of_shift = $end_2;

            if (!$end_of_shift > 0) { // No valid end-of-shift time.  Stop (in the name of love).
                throw new Exception('Invalid end-of-shift time.');
            }

            // Get start time of OT
            $start_of_ot = $end_of_shift - ($ot_duration * 3600);

            // Time crossed midnight?  Check for holiday rates for the following day
            $ot_start_time = (int)date('Hi', $start_of_ot);
            $ot_end_time = (int)date('Hi', $end_of_shift);

            if ($ot_start_time > $ot_end_time || $ot_start_time == 0) {
                $post_mn_ot = ($end_of_shift - $midnight) / 3600;
            }

            if ($start_of_ot > $midnight && $end_of_shift > $midnight) {
                // echo "<br /> Time crossed meridian. B";
                $post_mn_ot = ($end_of_shift - $start_of_ot) / 3600;
                // TODO:  Recalc if time crosses 6AM
            }


            // Check for night OT
            if ($start_of_ot >= $night_diff_start) { // OT began on/after night diff threshold
                if (!$start_of_ot > $midnight) {
                    $nd_ot = ($midnight - $start_of_ot) / 3600;
                }
            } elseif ($end_of_shift > $night_diff_start) { // OT began before night diff threshold
                if ($post_mn_ot > 0) { // Crossed midnight
                    $nd_ot = ($midnight - $night_diff_start) / 3600;
                } else {
                    $nd_ot = ($end_of_shift - $night_diff_start) / 3600;
                }
            }

            $ot = $ot_duration - $nd_ot - $post_mn_ot;
        } else {
            $ot_duration = 0;
        }

        $regular_duration = $total_duration - $ot_duration;

        $attendance_array = $this->_request->getPost();

        if ($form->isValid($attendance_array)) {

            if ($weekday === 'Sun') {
                $attendance_array['sun'] = $regular_duration;
                $attendance_array['sun_ot'] = $ot;
                $attendance_array['sun_nd_ot'] = $nd_ot;
            } else {
                $attendance_array['reg'] = $regular_duration;
                $attendance_array['reg_ot'] = $ot;
                $attendance_array['reg_nd_ot'] = $nd_ot;
            }

            if ($post_mn_ot > 0) {
                if ($weekday_midnight === 'Sun') {
                    $attendance_array['sun_nd_ot'] = $post_mn_ot;
                } else {
                    $attendance_array['reg_nd_ot'] = $post_mn_ot;
                }

            }

            if (!($form->getValue('id') > 0)) {
                $form->removeElement('id');
            }

            $Attendance
                ->setOptions($attendance_array)
                ->save();

            $this->redirect("/dataentry/attendance/employee/id/$employee_id/pay_period/$pay_period/date_start/$date_start/date_end/$date_end/group_id/$group_id");
        }

        return $form;
    }

    public function editAction()
    {
        throw new Exception('Deprecated');
        // action body
        $employee_id = (int)$this->_request->getParam('employee_id');

        $Employee = new Messerve_Model_Employee();
        $Employee->find($employee_id);
        $this->view->employee = $Employee;

        $group_id = (int)$this->_request->getParam('group_id');

        $pay_period = $this->_request->getParam('pay_period');
        $this->view->pay_period = $pay_period;

        $date_start = $this->_request->getParam('date_start');
        $date_end = $this->_request->getParam('date_end');

        $date = $this->_request->getParam('date');


        $attendance_id = (int)$this->_request->getParam('attendance_id');
        $Attendance = new Messerve_Model_Attendance();

        $Attendance->find($attendance_id);
        $Attendance->setDatetimeStart($date);

        $form = new Messerve_Form_EditAttendance();

        if ($this->_request->isPost()) { // Save submit
            $form = $this->saveSubmit($Attendance, $form, $date, $employee_id, $pay_period, $date_start, $date_end, $group_id);
        }

        $form->populate($Attendance->toArray());
        $this->view->form = $form;
    }


    public function _save_the_day($employee_id, $group_id, $data)
    {
        $Payroll = new Messervelib_Payroll();
        $Payroll->save_the_day($employee_id, $group_id, $data);
    }

    public function employeesAction()
    {
        $this->view->fuelcost = isset($_SESSION['fuelcost']) ? $_SESSION['fuelcost'] : 0;

        $group_id = (int)$this->_request->getParam('group_id');
        $Group = new Messerve_Model_Group();
        $Group->find($group_id);
        $this->view->group = $Group;

        $Client = new Messerve_Model_Client();
        $Client->find($Group->getClientId());
        $this->view->client = $Client;

        $clients = $Client->getMapper()->fetchList('id > 0', 'name ASC');

        $groups_array = array();

        foreach ($clients as $cvalue) {
            $client_groups = $Group->getMapper()->fetchList('client_id = ' . $cvalue->getId(), 'name ASC');

            foreach ($client_groups as $gvalue) {
                $Employee = new Messerve_Model_DbTable_Employee();

                $employee_count = $Employee->countByQuery('group_id = ' . $gvalue->getId());

                // if ($employee_count > 0) {
                $groups_array[] = array(
                    'client_id' => $cvalue->getId()
                , 'client_name' => $cvalue->getName()
                , 'group_id' => $gvalue->getId()
                , 'group_name' => $gvalue->getName()
                , 'employee_count' => $employee_count
                );
                // }

            }
        }

        $this->view->groups_array = $groups_array;

        $pay_period = $this->_request->getParam('pay_period');
        $this->view->pay_period = $pay_period;

        $_SESSION['pay_period'] = $pay_period;

        $period_array = explode('-', $pay_period);

        $year_month = "{$period_array[0]}-{$period_array[1]}";

        if ($period_array[2] === '1_15') {
            $date_start = date('Y-m-d', strtotime($year_month . '-1'));
            $date_end = date('Y-m-d', strtotime($year_month . '-15'));
        } else {
            $date_start = date('Y-m-d', strtotime($year_month . '-16'));
            $date_end = date('Y-m-d', strtotime('next month -1 day', strtotime($year_month)));
        }

        $this->view->date_start = $date_start;
        $this->view->date_end = $date_end;

        if ($this->_request->isPost()) {
            $filename = realpath($_FILES['file']['tmp_name']);

            $ELoqGroup = Messerve_Model_Eloquent_Group::find($group_id);
            // if ($Group->getClientId() == 14) {

            if ($ELoqGroup->client->usesBiometrics()) {
                $this->readAaiBiometrics($filename, $ELoqGroup, $date_start);
            } else {
                $row = 0;

                $missing = [];

                if (($handle = fopen($filename, "rb")) !== false) {

                    while (!feof($handle)) {
                        $line = fgets($handle, 1024);

                        if (count(explode("\t", $line)) > 1) {
                            $delimiter = "\t";
                            echo "<br /> TAB" . count(explode("\t", $line));

                        } elseif (count(explode(",", $line)) > 1) {
                            echo "<br /> COMMA";
                            $delimiter = "," . count(explode("\t", $line));
                        } else {
                            continue;
                        }

                        $data = str_getcsv($line, $delimiter);
                        $data = array_map('trim', $data);

                        if ($row > 0) { // Skip first row, headers
                            $employee_number = isset($data[6]) ? (int)$data[6] : 0;

                            if ($employee_number > 0) {
                                $data['date'] = date('Y-m-d', strtotime($data[0]));
                                $date_now = $data['date'];

                                $data['start_1'] = date('Y-m-d H:i', strtotime($data[0] . ' ' . $data[7]));

                                if (
                                    (strpos($data[7], 'PM') > 0 && strpos($data[8], 'AM') > 0)
                                    // || (strpos($data[7], 'AM') > 0 && strpos($data[8], 'PM') > 0)

                                ) {
                                    $date_now = date('Y-m-d', strtotime('+1 day', strtotime($data[0])));
                                }

                                $data['end_1'] = date('Y-m-d H:i', strtotime($date_now . ' ' . $data[8]));

                                if ($data[9] != '' & $data[10] != '') {
                                    $data['start_2'] = date('Y-m-d H:i', strtotime($date_now . ' ' . $data[9]));

                                    if (
                                        strpos($data[9], 'PM') > 0 && strpos($data[10], 'AM') > 0
                                        // || strpos($data[9], 'AM') > 0 && strpos($data[10], 'PM') > 0
                                    ) {
                                        $date_now = date('Y-m-d', strtotime('+1 day', strtotime($data[0])));
                                    }

                                    $data['end_2'] = date('Y-m-d H:i', strtotime($date_now . ' ' . $data[10]));

                                }

                                if ($data[11] != '' && $data[12] != '') {
                                    $data['start_3'] = date('Y-m-d H:i', strtotime($date_now . ' ' . $data[11]));

                                    if (
                                        strpos($data[11], 'PM') > 0 && strpos($data[12], 'AM') > 0
                                    ) {
                                        $date_now = date('Y-m-d', strtotime('+1 day', strtotime($data[0])));
                                    }

                                    $data['end_3'] = date('Y-m-d H:i', strtotime($date_now . ' ' . $data[12]));

                                }

                                $Employee = new Messerve_Model_Employee();

                                $Employee = $Employee->getMapper()->findOneByField('employee_number', $employee_number);

                                if ($Employee) {
                                    // Find attendance
                                    $Attendance = new Messerve_Model_Attendance();

                                    $Attendance = $Attendance->getMapper()->findOneByField(
                                        array(
                                            'datetime_start', 'employee_id'
                                            // ,'group_id'
                                        )
                                        , array(
                                            $data['date'], $Employee->getId()
                                            // , $group_id
                                        )
                                    );

                                    $save_this_day = array();

                                    if (!$Attendance) {
                                        $Attendance = new Messerve_Model_Attendance();
                                    }

                                    $Attendance
                                        ->setEmployeeId($Employee->getId())
                                        ->setEmployeeNumber($employee_number)
                                        ->setGroupId($group_id)
                                        ->setDatetimeStart($data['date'])
                                        ->setStart1(date('Hi', strtotime($data    ['start_1'])))
                                        ->setEnd1(date('Hi', strtotime($data['end_1'])));

                                    $save_this_day = array(
                                        'start_1' => $Attendance->getStart1()
                                    , 'end_1' => $Attendance->getEnd1()
                                    );

                                    if (isset($data['start_2'])) {
                                        $Attendance
                                            ->setStart2(date('Hi', strtotime($data['start_2'])))
                                            ->setEnd2(date('Hi', strtotime($data['end_2'])));

                                        $save_this_day += array(
                                            'start_2' => $Attendance->getStart2()
                                        , 'end_2' => $Attendance->getEnd2()
                                        );

                                    }

                                    if (isset($data['start_3'])) {
                                        $Attendance
                                            ->setStart3(date('Hi', strtotime($data['start_3'])))
                                            ->setEnd3(date('Hi', strtotime($data['end_3'])));

                                        $save_this_day += array(
                                            'start_3' => $Attendance->getStart3()
                                        , 'end_3' => $Attendance->getEnd3()
                                        );

                                    }


                                    if (isset($data[17])) {

                                        $approved_ot = (float)$data[17];

                                        if ($data[18] != '' && $approved_ot > 0) {
                                            $Attendance->setOtApproved('yes')->setOtApprovedHours($approved_ot);
                                            $Attendance->save();
                                            $save_this_day['ot_approved'] = 'yes';
                                            $save_this_day['ot_approved_hours'] = $approved_ot;
                                            // preprint($data); preprint($Attendance->toArray(),1);
                                        } else {
                                            $Attendance->setOtApproved('no')->setOtApprovedHours(0);
                                        }
                                    }


                                    $Attendance->save();

                                    $save_this_day['id'] = $Attendance->getId();
                                    $save_this_day['type'] = 'regular';
                                    // $save_this_day['ot_approved'] = $data[18];

                                    $this->_save_the_day($Attendance->getEmployeeId(), $Attendance->getGroupId(),
                                        array($data['date'] => $save_this_day));

                                } else {
                                    echo "<br />MISSING $employee_number:";
                                    preprint($data);
                                    $missing[$data[6]] = $data;
                                    echo "End MISSING";
                                }
                            }

                        } else { // First row
                            // preprint($data);
                            $csv_group_code = $data[3];
                            $csv_date_start = date('Y-m-d', strtotime($data[1]));
                            $csv_date_end = date('Y-m-d', strtotime($data[2]));
                            /*
                             echo "<br /> {$Group->getCode()}: $csv_group_code";
                            echo "<br /> $date_start : " . $csv_date_start;
                            echo "<br /> $date_end : " . $csv_date_end;
                            */
                            $errors = array();

                            if ($Group->getCode() != $csv_group_code) {
                                $errors[] = "Outlet codes do not match ({$Group->getCode()} : $csv_group_code)";
                            }

                            if ($date_start != $csv_date_start) {
                                $errors[] = "Start dates do not match ($date_start} : $csv_date_start)";
                            }

                            if ($date_start != $csv_date_start) {
                                $errors[] = "Start dates do not match ($date_end} : $csv_date_end)";
                            }

                            if (count($errors) > 0) {
                                echo implode("<br />", $errors);
                                die('<br />Click back to try again.');
                            }

                            // preprint($data,1);
                        }

                        $row++;

                    }

                    foreach ($missing as $value) {
                        echo("Missing: $value[1]\t$value[6]\t$value[4]\t$value[3] \n");
                    }

                    fclose($handle);
                } else {
                    die('File fail.');
                }
            }

        }

        $EmployeeMap = new Messerve_Model_Mapper_Employee();

        $employees = $EmployeeMap->fetchList("group_id = $group_id", array('lastname ASC', 'firstname ASC'));

        $AttendDB = new Messerve_Model_DbTable_Attendance();

        // Search for relievers
        $permanents = [];

        foreach ($employees as $evalue) {
            $permanents[] = $evalue->getId();
        }

        $select = Messerve_Model_Eloquent_Attendance::select('employee_id');

        if (count($permanents) > 0) {
            $select->whereNotIn("employee_id", $permanents);
        }

        $relievers_result = $select
            ->where('group_id', $group_id)
            ->whereBetween('datetime_start', [$date_start, $date_end])
            ->groupBy('employee_id')
            ->get();

        if (count($relievers_result) > 0) {
            foreach ($relievers_result as $rvalue) {
                $Reliever = new Messerve_Model_Employee();
                $Reliever->find($rvalue->employee_id);
                $employees[] = $Reliever;
            }
        }

        unset($select);

        $employee_hours = array();

        foreach ($employees as $evalue) {
            $select = $AttendDB->select();
            $select
                ->from(
                    ['a' => 'attendance'],
                    [
                        'mysum' =>
                            'ROUND(SUM(reg) + SUM(reg_nd) + SUM(reg_ot)	+ SUM(reg_nd_ot)	+ SUM(sun)	+ SUM(sun_nd)	
                        + SUM(sun_ot)	+ SUM(sun_nd_ot)	+ SUM(spec)	+ SUM(spec_nd)	+ SUM(spec_ot)	+ SUM(spec_nd_ot)	
                        + SUM(legal)	+ SUM(legal_nd)	+ SUM(legal_ot)	+ SUM(legal_nd_ot)	+ SUM(rest)	+ SUM(rest_nd)	
                        + SUM(rest_ot)	+ SUM(rest_nd_ot),2)'
                    ])
                ->where('employee_id = ?', $evalue->getId())
                ->where('group_id = ?', $group_id)
                ->where("datetime_start BETWEEN '$date_start' AND '$date_end'");

            $employee_hours[$evalue->getId()] = $AttendDB->fetchRow($select);
        }

        $this->view->employee_hours = $employee_hours;

        $this->view->employees = $employees;

        $this->view->rates = $this->_getRates();

        /* PDF reports */

        $path_name = realpath(APPLICATION_PATH . '/../public/export') . "/$date_start/$group_id/";

        $this->view->report_path = "export/$date_start/$group_id";

        $client_path = $path_name . 'client/';

        $client_reports = array();

        if ($client_path && file_exists($client_path)) {

            $dir = new DirectoryIterator($client_path);

            foreach ($dir as $fileInfo) {
                if (!$fileInfo->isDot()) {
                    $client_reports[$fileInfo->getMTime()][] = $fileInfo->__toString();
                }
            }

            krsort($client_reports);

            $client_reports = call_user_func_array('array_merge', $client_reports);
        }


        $this->view->client_reports = $client_reports;


        $payslip_path = $path_name . 'payslips/';

        $payslips = array();

        if ($payslip_path && file_exists($payslip_path)) {

            $dir = new DirectoryIterator($payslip_path);

            foreach ($dir as $fileInfo) {
                if (!$fileInfo->isDot() && !$fileInfo->isDir()) {
                    $payslips[] = $fileInfo->__toString();
                }
            }
        }

        rsort($payslips);

        $this->view->payslips = $payslips;

        $summary_path = $path_name . 'summary/';

        $summaries = array();

        if ($summary_path && file_exists($summary_path)) {

            $dir = new DirectoryIterator($summary_path);

            foreach ($dir as $fileInfo) {
                if ($fileInfo->isDot()) {
                    // do nothing
                } else {
                    $summaries[] = $fileInfo->__toString();
                }
            }
        }

        rsort($summaries);

        $this->view->summaries = $summaries;

        $this->view->late_dtr = $this->getLateDtr();

    }

    protected function writeAttendance(
        $row,
        Messerve_Model_Eloquent_Group $group,
        Messerve_Model_Eloquent_Employee $employee
    ) : bool
    {
        $group_id = $group->id;

        if ($employee->group_id != $group_id) { // Write attendance only if rider belongs to group
            return false;
        }

        if (!$row[4]) {
            return false;
        }

        $date_now = $row[2];

        $start = $date_now . ' ' . $row[4];
        $end = null;

        foreach ([$row[5], $row[6], $row[7]] as $maybe_end) {
            if ($maybe_end) {
                $end = $date_now . ' ' . $maybe_end;
            }
        }

        if (!$end) {
            return false;
        }

        // TODO: End of year parsing
        $start = Carbon::createFromFormat('d-F h:i A', $start);
        $end = Carbon::createFromFormat('d-F h:i A', $end);
        $date_now = Carbon::createFromFormat('d-F', $date_now);

        $attendance = $employee->attendance()->firstOrCreate([
            'datetime_start' => $start->format('Y-m-d 00:00:00'),
            'employee_id' => $employee->id
        ]);

        $attendance->employee_number = $employee->employee_number;
        $attendance->group_id = $group_id;
        $attendance->start_1 = $start->format('Hi');
        $attendance->end_1 = $end->format('Hi');

        $attendance->save();

        $save_this_day = [
            'start_1' => $attendance->start_1
            , 'end_1' => $attendance->end_1
            , 'id' => $attendance->id,
            'type' => 'regular'
        ];

        $this->_save_the_day($employee->id, $group_id, [$date_now->toDateString() => $save_this_day]);

        return true;
    }

    protected function readAaiBiometrics($filename, Messerve_Model_Eloquent_Group $group, $cutoff_start)
    {
        $reader = new  \PhpOffice\PhpSpreadsheet\Reader\Xls();
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($filename);

        $sheet = $spreadsheet->getActiveSheet();

        $start_date = null;
        $current_employee = null;

        foreach ($sheet->toArray() as $row) {
            array_map('trim', $row);

            if (!$current_employee) {
                if (strtolower($row[0]) === 'daily time record') {
                    continue;
                }

                if (strtolower($row[0]) === 'start date') {
                    $start_date = Carbon::parse($row[1]);
                    continue;
                }

                if (strtolower($row[0]) === 'end date') {
                    $end_date = Carbon::parse($row[1]);
                    continue;
                }

                if (strtolower($row[0]) === 'employee no.') {
                    continue;
                }


                if (!$row[2] && !$row[3]) {
                    continue;
                }
            }


            $cutoff_start = Carbon::parse($cutoff_start);

            if ($start_date != $cutoff_start) {
                throw new Exception("DTR period start does not match payroll cutoff being processed! {$start_date->toDateString()} <> {$cutoff_start->toDateString()}");
            }

            // TODO: Add start (and end) date checks.  Make sure it matches the cutoff period!

            if ($employee = $this->rowIsEmployee($row)) {
                if ($current_employee !== $employee) {
                    $current_employee = $employee;
                    preprint($employee->toArray());
                }
            }

            $this->writeAttendance($row, $group, $current_employee);
        }
    }

    protected function rowIsEmployee(array $row)
    {
        if (!is_numeric($row[0])) {
            return false;
        }

        if (!$row[1]) {
            return false;
        }

        return Messerve_Model_Eloquent_Employee::findByEmployeeNumber($row[0]);
    }

    protected function _getRates()
    {
        $RateMap = new Messerve_Model_Mapper_Rate();

        $rate_options = $RateMap->fetchList('1', 'name ASC');

        $rate_array = array();

        foreach ($rate_options as $rovalue) {
            $rate_array[$rovalue->getId()] = $rovalue->getName();
        }

        return $rate_array;
    }

    /**
     * @throws Zend_Form_Exception
     * @throws Zend_Exception
     * @throws Zend_Controller_Dispatcher_Exception
     * @throws Exception
     */
    public function employeeAction()
    {
        if ($this->_user_auth->type != 'admin' && $this->_user_auth->type != 'supervisor' && $this->_user_auth->type != 'encoder') {
            $this->_helper->viewRenderer('employee-read-only');
        }

        $employee_id = (int)$this->_request->getParam('id');
        $this->view->employee_id = $employee_id;

        $group_id = (int)$this->_request->getParam('group_id');
        $this->view->group_id = $group_id;

        $Employee = (new Messerve_Model_Employee())->find($employee_id);

        $this->view->employee = $Employee;

        $pay_period = $this->_request->getParam('pay_period');
        $this->view->pay_period = $pay_period;

        $date_start = $this->_request->getParam('date_start');
        $this->view->date_start = $date_start;

        $date_end = $this->_request->getParam('date_end');
        $this->view->date_end = $date_end;

        $date1 = new DateTime($date_start); //inclusive
        $date2 = new DateTime($date_end); //exclusive

        $EloquentEmployee = Messerve_Model_Eloquent_Employee::find($employee_id);

        $this->view->rest_days = $EloquentEmployee->restDaysByRange($date1, $date2)->get()->pluck('date');

        $diff = $date2->diff($date1);
        $period_size = intval($diff->format("%a")) + 1;

        $current_date = $date_start;

        $current_year = date('Y', strtotime($date_start));

        $dates = array();

        $AttendanceMap = new Messerve_Model_Mapper_Attendance();

        $first_day_id = 0;

        for ($i = 1; $i <= $period_size; $i++) {

            $Attendance = $AttendanceMap->findOneByField(
                array('employee_id', 'datetime_start', 'group_id')
                , array($employee_id, $current_date, $group_id)
            );


            if (!$Attendance) {
                $new_attendance = new Messerve_Model_Attendance();

                $new_attendance->setEmployeeId($employee_id)
                    ->setDatetimeStart($current_date)
                    ->setDatetimeEnd($current_date)
                    ->setGroupId($group_id)
                    ->setEmployeeNumber($Employee->getEmployeeNumber());

                if (!$new_attendance->save(true)) {
                    throw new RuntimeException('Did not insert initial attendance.');
                }

                try {
                    $Attendance = new Messerve_Model_Attendance();
                    $Attendance->find($new_attendance->id);
                } catch (Exception $e) {
                    logger($e->getMessage(), "error");
                    logger($e->getTraceAsString(), "error");
                    throw new RuntimeException("Did not find attendance record with ID {$new_attendance->id}");
                }

            }

            $dates[$current_date] = $Attendance;

            $current_date = date('Y-m-d', strtotime('+1 day', strtotime($current_date)));

            if ($i == 1) {
                $first_day_id = $Attendance->getId();
                $firstAttendance = $Attendance->eloquent();
            }

        }

        $this->view->dates = $dates;
        $this->view->period_size = $period_size;

        $form = new Messerve_Form_EditAddDeduct();

        $AddIncome = new Messerve_Model_Addincome();
        $AddIncome = $AddIncome->getMapper()->findOneByField('attendance_id', $first_day_id);

        if (!$AddIncome) {
            $AddIncome = new Messerve_Model_Addincome();
            $AddIncome->setAttendanceId($first_day_id)->save();

        }

        $Deductions = new Messerve_Model_Deductions();
        $Deductions = $Deductions->getMapper()->findOneByField('attendance_id', $first_day_id);

        if (!$Deductions) {
            $Deductions = new Messerve_Model_Deductions();
            $Deductions->setAttendanceId($first_day_id)->save();
        }

        $this->view->validationErrors = null;

        if ($this->_request->isPost()) { // Save submit

            if (Zend_Registry::get('Cache')->load('dtr_locked')) {
                throw new Zend_Controller_Dispatcher_Exception('DTR is locked!', 403);
            }

            $postvars = $this->_request->getPost();

            $this->logActivity($Employee->eloquent(), $postvars, $dates);

            if ($form->isValid($postvars)) {
                // Check for overlaps
                $validator = (new ValidateDtrPost())($employee_id, $group_id, DTRSubmission::fromFormArray($postvars));

                if (!$validator->fails()) {

                    $Deductions->setOptions($postvars)->save();

                    $AddIncome->setOptions($postvars)->save();

                    $MPayroll = new Messervelib_Payroll();
                    $MPayroll->save_the_day($employee_id, $group_id, $postvars);
                    $MPayroll->save_the_day($employee_id, $group_id, $postvars);
                    // TODO:  figure out why this needs to run twice

                    $this->redirect("/dataentry/attendance/employee/id/$employee_id/pay_period/$pay_period/date_start/$date_start/date_end/$date_end/group_id/$group_id");
                } else {
                    $this->view->validationErrors = $validator->errors();
                }

            }

            // Log action
        }

        $form->populate($AddIncome->toArray());
        $form->populate($Deductions->toArray());
        $form->populate(array('attendance_id' => $first_day_id));
        $this->view->form = $form;

        // Holidays
        $Group = new Messerve_Model_Group();
        $Group->find($group_id);

        $this->view->group = $Group;

        $calendars = array();
        $holidays = array();

        if ($Group->getCalendars()) {
            $calendars = json_decode($Group->getCalendars());
        }

        foreach ($calendars as $cvalue) {
            $Calendar = new Messerve_Model_Mapper_CalendarEntry();
            $calendar_entries = $Calendar->fetchList("(`year` = '0000' OR `year` = '$current_year') AND calendar_id = " . $cvalue,
                'date ASC');

            foreach ($calendar_entries as $cevalue) {
                $holiday_date = $cevalue->getDate();

                if (strlen($holiday_date) < 10) {
                    $holiday_date = $current_year . '-' . $holiday_date;
                }

                if (!isset($holidays[$holiday_date]) || $cevalue->getType() == 'legal') {
                    $holidays[$holiday_date] = $cevalue;
                }
            }
        }

        $this->view->holidays = $holidays;

        if (isset($firstAttendance) && $firstAttendance instanceof Messerve_Model_Eloquent_Attendance) {
            $this->view->history = (new GetHistoryChanges())($firstAttendance);
        }

    }

    public function logActivity(Messerve_Model_Eloquent_Employee $employee, array $post, array $dates)
    {
        // TODO:  Get other data besides attendance
        $old_data = null;

        $new_data = $post;

        $first_attendance = null;

        foreach ($dates as $date => $attendance) {
            if ($attendance instanceof Messerve_Model_Attendance) {
                $today = Carbon::parse($date);

                if ($today->day == 1 || $today->day == 16) {
                    $first_attendance = $attendance;
                }

                $old_data[$date] = [
                    "id" => $attendance->id,
                    "start_1" => $attendance->Start1,
                    "end_1" => $attendance->End1,
                    "start_2" => $attendance->Start2,
                    "end_2" => $attendance->End2,
                    "start_3" => $attendance->Start3,
                    "end_3" => $attendance->End3,
                    "ot_approved" => $attendance->getOtApproved(),
                    "ot_approved_hours" => $attendance->OtApprovedHours,
                    "type" => $attendance->Type,
                    "extended_shift" => $attendance->getExtendedShift(),
                    "approved_extended_shift" => $attendance->getApprovedExtendedShift()
                ];
            }
        }

        if (!$first_attendance) {
            throw new \RuntimeException("Failed to save DTR because first day (1 or 16) was not found.");
        }

        try {
            activity()
                ->performedOn($first_attendance->eloquent())
                ->causedBy(Messerve_Model_Eloquent_User::find($this->_user_auth->id))
                ->withProperties([
                    "old" => $old_data,
                    "new" => $new_data,
                    "all" => $post
                ])
                ->log("Attendance submitted");

        } catch (Exception $exception) {
            logger($exception->getMessage(), "error");
            logger($exception->getTraceAsString());

        }
    }

    public function searchrelieverAction()
    {
        // AJAX
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $keyword = $this->_request->getParam('term');
        $group_id = $this->_request->getParam('group_id');

        $where = "(firstname LIKE '%$keyword%' OR lastname LIKE '%$keyword%')";

        $EmployeeMap = new Messerve_Model_Mapper_Employee();

        $result = $EmployeeMap->fetchList($where, array('firstname ASC', 'lastname ASC'));

        $json_array = array();

        if (count($result) > 0) {
            foreach ($result as $value) {

                if (!$value->getGroupId()) {
                    continue;
                }

                $Group = new Messerve_Model_Group();
                $Group->find($value->getGroupId());

                $json_array[] = array(
                    'id' => $value->getId()
                , 'label' => $value->getFirstName() . ' ' . $value->getLastName() . ' (' . $Group->getName() . ')'
                , 'value' => $value->getFirstName() . ' ' . $value->getLastName() . ' (' . $Group->getName() . ')'
                );
            }
        }

        print(json_encode($json_array));
    }

    public function addrelieverAction()
    { // AJAX
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $employee_id = $this->_request->getParam('reliever_id');
        $datetime_start = $this->_request->getParam('datetime_start');
        $group_id = $this->_request->getParam('group_id');

        $Attendance = new Messerve_Model_Attendance();

        $where = "employee_id = '$employee_id'
		AND datetime_start = '$datetime_start'
		AND group_id = '$group_id'";

        $result = $Attendance->getMapper()->fetchList($where);

        if (!count($result) > 0) {
            $Employee = new Messerve_Model_Employee();
            $Employee->find($employee_id);

            if ($Employee->getId() > 0) {
                $Attendance->setEmployeeId($employee_id)
                    ->setEmployeeNumber($Employee->getEmployeeNumber())
                    ->setGroupId($group_id)
                    ->setDatetimeStart($datetime_start)->save();
            }
        }
    }

    public function toggleRestDayAction()
    { // AJAX


        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        if (Zend_Registry::get('Cache')->load('dtr_locked')) {
            throw new Zend_Controller_Dispatcher_Exception('DTR is locked!', 403);
        }

        $attendance_id = (int)$this->_request->getParam('attendance_id');

        $Attendance = Messerve_Model_Eloquent_Attendance::find($attendance_id);

        if (!$Attendance) {
            $this->getResponse()->setHttpResponseCode(404);
            return;
        }

        $date = Carbon::parse($Attendance->datetime_start);


        $Employee = $Attendance->employee;

        $rest_day = $Employee->restDays()->where('date', $date->toDateString());

        if ($rest_day->count()) {
            $rest_day->delete();
        } else {
            $rest_day = Messerve_Model_Eloquent_RestDay::create([
                'employee_id' => $Employee->id,
                'date' => $date->toDateString()
            ]);

            $this->_helper->json($rest_day);
            return json_encode($rest_day->toArray());
        }


        return;
    }

    public function resetdayAction()
    { // AJAX
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $attendance_id = (int)$this->_request->getParam('attendance_id');

        $Attendance = new Messerve_Model_Attendance();
        $Attendance->find($attendance_id);

        if ($Attendance->getId() > 0) {
            // print_r($Attendance->toArray());

            $reset = array(
                'start_1' => 0, 'start_2' => 0, 'start_3' => 0,
                'end_1' => 0, 'end_2' => 0, 'end_3' => 0,
                'today' => 0, 'today_nd' => 0, 'today_ot' => 0, 'today_nd_ot' => 0
            , 'tomorrow' => 0, 'tomorrow_nd' => 0, 'tomorrow_ot' => 0, 'tomorrow_nd_ot' => 0

            , 'reg' => 0, 'reg_nd' => 0, 'reg_ot' => 0, 'reg_nd_ot' => 0
            , 'spec' => 0, 'spec_nd' => 0, 'spec_ot' => 0, 'spec_nd_ot' => 0
            , 'legal' => 0, 'legal_nd' => 0, 'legal_ot' => 0, 'legal_nd_ot' => 0, 'legal_unattend' => 0
            , 'rest' => 0, 'rest_nd' => 0, 'rest_ot' => 0, 'rest_nd_ot' => 0
            , 'fuel_overage' => 0, 'fuel_hours' => 0, 'fuel_alloted' => 0, 'fuel_consumed' => 0
            , 'fuel_cost' => 0, 'ot_approved' => 'no', 'ot_approved_hours' => 0, 'ot_actual_hours' => 0
            , 'extended_shift' => 'no'
            );

            $Attendance->setOptions($reset)->save();

            $AttPay = Messerve_Model_Eloquent_AttendancePayroll::where('attendance_id', $attendance_id);

            $AttPay->delete();

        }
    }

    protected function getLateDtr()
    {
        return "--";
    }

    /**
     * @throws Zend_Exception
     */
    public function lockAction()
    {
        if ($this->_user_auth && $this->_user_auth->type == 'admin') {
            (new LockDTR())();
            $this->getResponse()->setRedirect($_SERVER['HTTP_REFERER']);
        }

        throw new Zend_Controller_Dispatcher_Exception('You are not allowed to unlock the DTR', 403);
    }

    /**
     * @throws Zend_Exception
     */
    public function unlockAction()
    {
        if ($this->_user_auth && $this->_user_auth->type == 'admin') {
            (new UnlockDTR())();
            $this->getResponse()->setRedirect($_SERVER['HTTP_REFERER']);
        }

        throw new Zend_Controller_Dispatcher_Exception('You are not allowed to unlock the DTR', 403);

    }

}
