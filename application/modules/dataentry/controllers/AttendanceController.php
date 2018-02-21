<?php

class Dataentry_AttendanceController extends Zend_Controller_Action
{

    protected $_max_regular_hours = null, $_init_night_diff_start, $_init_night_diff_end;
    protected $_employee_id;
    protected $_round_to_ten_minutes;

    protected $_date, $_midnight, $_night_diff_start, $_night_diff_end, $_ot_start;

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

        if ($this->_user_auth->type != 'admin' && $this->_user_auth->type != 'supervisor') {
            throw new Exception('You are not allowed to access this module.');
        }

    }

    public function indexAction()
    {
        // action body
        $GroupMap = new Messerve_Model_Mapper_Group();

        $groups = $GroupMap->fetchList("id > 0", array('client_id ASC', 'name ASC'));

        $Client = new Messerve_Model_Mapper_Client();

        $clients = array();

        foreach ($Client->fetchList('1', 'name ASC') as $cvalue) {
            $clients[$cvalue->getId()] = $cvalue->getName();
        }

        $groups_array = array();

        foreach ($groups as $gvalue) {
            $Employee = new Messerve_Model_DbTable_Employee();

            $employee_count = $Employee->countByQuery('group_id = ' . $gvalue->getId());

            // if ($employee_count > 0) {
                $groups_array[$gvalue->getId()] = $clients[$gvalue->getClientId()] . ' ' . $gvalue->getName()
                    . ' (' . $employee_count . ')';
            //}
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

    public function editAction()
    {
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
            // preprint($_POST);

            $start_1 = strtotime($date . ' T' . str_pad($this->_request->getPost('start_1'), 4, 0, STR_PAD_LEFT));
            $end_1 = strtotime($date . ' T' . str_pad($this->_request->getPost('end_1'), 4, 0, STR_PAD_LEFT));

            $start_2 = strtotime($date . ' T' . str_pad($this->_request->getPost('start_2'), 4, 0, STR_PAD_LEFT));
            $end_2 = strtotime($date . ' T' . str_pad($this->_request->getPost('end_2'), 4, 0, STR_PAD_LEFT));

            /*
             if($end_2 < $start_1) { // Adjust date for time crossing midnight
            $end_2 = strtotime($date . ' T' . str_pad($this->_request->getPost('end_2'),4,0,STR_PAD_LEFT) . ' +1 day');
            }
            */

            $break_duration = 0;

            if ($end_1 > 0 && $start_1 > 0) {
                /* echo "<br />Break start: " . date('Y-m-d Hi', $end_1);
                 echo "<br />Break end: " . date('Y-m-d Hi', $start_2); */
                $break_duration = ($start_2 - $end_1) / 3600;
            }

            $midnight = strtotime($this->_request->getPost('datetime_start') . ' + 1 day');
            $night_diff_start = strtotime($this->_request->getPost('datetime_start') . 'T' . $this->_night_diff_start);

            /* echo "<br />Weekday: " . date('D', $start_1);
             echo "<br />Midnight: " . date('Y-m-d Hi', $midnight);
            echo "<br />Midnight Weekday: " . date('D', $midnight);
            echo "<br />Night: " . date('Y-m-d Hi', $night_diff_start);
            echo "<br />Start: " . date('Y-m-d Hi', $start_1);
            echo "<br />End: " . date('Y-m-d Hi', $end_2);*/

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
                    die('Invalid end-of-shift time.');
                }

                // Get start time of OT
                $start_of_ot = $end_of_shift - ($ot_duration * 3600);

                // echo "<br />Start of OT: " . date('Y-m-d Hi', $start_of_ot);

                // Time crossed midnight?  Check for holiday rates for the following day
                $ot_start_time = (int)date('Hi', $start_of_ot);
                $ot_end_time = (int)date('Hi', $end_of_shift);

                if ($ot_start_time > $ot_end_time || $ot_start_time == 0) {
                    echo "<br /> Time crossed meridian. A";
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
                    $ot = ($night_diff_start - $start_of_ot) / 3600;

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

            $time_array = array(
                'start_1' => $start_1
            , 'end_1' => $end_1
            , 'start_2' => $start_2
            , 'end_2' => $end_2
            , 'total_duration' => $total_duration
            , 'regular_duration' => $regular_duration
            , 'break_duration' => $break_duration
            , 'ot_duration' => $ot_duration
            , 'ot' => $ot
            , 'nd_ot' => $nd_ot
            , 'post_mn_ot' => $post_mn_ot
            );

            $postvars = $this->_request->getPost();

            if ($form->isValid($postvars)) {

                // preprint($time_array);

                $attendance_array = $this->_request->getPost();

                if ($weekday == 'Sun') {
                    $attendance_array['sun'] = $regular_duration;
                    $attendance_array['sun_ot'] = $ot;
                    $attendance_array['sun_nd_ot'] = $nd_ot;
                } else {
                    $attendance_array['reg'] = $regular_duration;
                    $attendance_array['reg_ot'] = $ot;
                    $attendance_array['reg_nd_ot'] = $nd_ot;
                }

                if ($post_mn_ot > 0) {
                    if ($weekday_midnight == 'Sun') {
                        $attendance_array['sun_nd_ot'] = $post_mn_ot;
                    } else {
                        $attendance_array['reg_nd_ot'] = $post_mn_ot;
                    }

                }

                // preprint($attendance_array);

                if (!$form->getValue('id') > 0) {
                    $form->removeElement('id');
                }

                $Attendance
                    // ->setOptions($form->getValues())
                    ->setOptions($attendance_array)
                    ->save();

                $this->_redirect("/dataentry/attendance/employee/id/{$employee_id}/pay_period/{$pay_period}/date_start/{$date_start}/date_end/{$date_end}/group_id/{$group_id}");

            }
        }

        $form->populate($Attendance->toArray());
        $this->view->form = $form;
    }


    public function _save_the_day($employee_id, $group_id, $data)
    {
        $Payroll = new Messervelib_Payroll();
        $Payroll->save_the_day($employee_id, $group_id, $data);
    }


    protected function _process_deductions($attendance_id, $employee_id, $cutoff)
    {
        // Fetch all deductions
        $DeductSchedMap = new Messerve_Model_Mapper_DeductionSchedule();

        $deductions = $DeductSchedMap->fetchList("(cutoff = '3' OR cutoff = '$cutoff') AND employee_id = {$employee_id}");

        $DeductAttendMap = new Messerve_Model_Mapper_DeductionAttendance();

        // RESET! Delete all employee deductions for this payroll period
        $DeductAttendMap->getDbTable()->delete('attendance_id = ' . $attendance_id);

        if ($deductions) {
            foreach ($deductions as $dvalue) {
                // Total all deductions,  balance matches max deduction value + this deduction?

                $select = $DeductAttendMap->getDbTable()->select();

                $select
                    ->from('deduction_attendance', array('mysum' => 'SUM(amount)'))
                    ->where('deduction_schedule_id = ?', $dvalue->getId());

                $this_sum = $DeductAttendMap->getDbTable()->fetchRow($select)->mysum;

                if ($this_sum < $dvalue->getAmount()) { // Has balance

                    $deduct_now = 0;
                    // Will the new deduction breach the balance?  Adjust.
                    if (($this_sum + $dvalue->getDeduction()) > $dvalue->getAmount()) {
                        $deduct_now = $dvalue->getAmount() - $this_sum;
                    } else {
                        $deduct_now = $dvalue->getDeduction();
                    }

                    // Save deduction
                    $DeductAttend = new Messerve_Model_DeductionAttendance();

                    $DeductAttend
                        ->setDeductionScheduleId($dvalue->getId())
                        ->setAttendanceId($attendance_id)
                        ->setAmount($deduct_now)
                        ->save();

                } else {
                    // Paid up, do nothing
                }
            }
        }
    }

    protected function _fetch_holidays($group_id, $date)
    {
        // Fetch group holidays
        $Group = new Messerve_Model_Group();
        $Group->find($group_id);

        $calendars = array();

        $today_holiday = array();
        $tomorrow_holiday = array();

        if ($Group->getCalendars()) {
            $calendars = json_decode($Group->getCalendars());
        }

        if (count($calendars) > 0) {
            $Calendar = new Messerve_Model_Mapper_CalendarEntry();

            $today_holiday = false;
            $tomorrow_holiday = false;

            $m_d = date('m-d', strtotime($date));
            $y = date('Y', strtotime($date));

            $select = $Calendar->getDbTable()->select(true);
            $select->setIntegrityCheck(false)
                ->where("(`date` LIKE '{$m_d}' AND `year` LIKE '0000') OR (`date` LIKE '{$m_d}' AND `year` LIKE '{$y}')")
                ->where("calendar_id IN (" . implode(',', $calendars) . ")");

            $today_holidays = $Calendar->getDbTable()->fetchAll($select);

            foreach ($today_holidays as $thvalue) {
                if (!$today_holiday || $thvalue->type == 'legal') {
                    $today_holiday = $thvalue;
                }
            }

            if ($today_holiday) {
                $today_holiday_array = $today_holiday->toArray();

                $today_holiday = new Messerve_Model_CalendarEntry();
                $today_holiday->setOptions($today_holiday_array);
            }

            $m_d = date('m-d', strtotime("+1 day", strtotime($date)));
            $y = date('Y', strtotime($date));

            $select = $Calendar->getDbTable()->select(true);
            $select->setIntegrityCheck(false)
                ->where("(`date` LIKE '{$m_d}' AND `year` LIKE '0000') OR (`date` LIKE '{$m_d}' AND `year` LIKE '{$y}')")
                ->where("calendar_id IN (" . implode(',', $calendars) . ")");

            $tomorrow_holidays = $Calendar->getDbTable()->fetchAll($select);

            foreach ($tomorrow_holidays as $twvalue) {
                if (!$tomorrow_holiday || $twvalue->type == 'legal') {
                    $tomorrow_holiday = $twvalue;
                }
            }

            if ($tomorrow_holiday) {
                $tomorrow_holiday_array = $tomorrow_holiday->toArray();

                $tomorrow_holiday = new Messerve_Model_CalendarEntry();
                $tomorrow_holiday->setOptions($tomorrow_holiday_array);
            }

        }

        return array("today" => $today_holiday, "tomorrow" => $tomorrow_holiday);

    }


    protected function _break_it_down($start, $end, $duration_id)
    {

        $broken_array = array('today' => 0, 'today_nd' => 0, 'tomorrow_nd' => 0, 'tomorrow' => 0);

        // echo "<br /> Duration $duration_id // {$this->_date} :: Start - " . date('Y-m-d H:i', $start) . " // End - ". date('Y-m-d H:i', $end); if($this->_ot_start) echo " // OT - ". date('Y-m-d H:i', $this->_ot_start);
        // echo " // ND start - " . date('Y-m-d H:i', $this->_night_diff_start) . " // ND end - " . date('Y-m-d H:i', $this->_night_diff_end) ;

        /* Today */
        if ($end < $this->_night_diff_start) {
            $today = $end - $start;
            if ($today > 0) $broken_array['today'] += $today;
        }

        /* ND */
        if ($end <= $this->_midnight && $end >= $this->_night_diff_start) {
            if ($start <= $this->_night_diff_start) {
                echo "<br /> ND :A ";
                $today = $this->_night_diff_start - $start;
                if ($today > 0) $broken_array['today'] += $today;

                $today_nd = $end - $this->_night_diff_start;
                if ($today_nd > 0) $broken_array['today_nd'] += $today_nd;

            } else {
                echo "<br /> ND :B ";
                // $today = $start - $this->_night_diff_start;
                // if($today > 0) $broken_array['today'] +=  $today;

                $today_nd = $end - $start;
                if ($today_nd > 0) $broken_array['today_nd'] += $today_nd;
            }

        }

        /* Tomorrow */
        if ($end > $this->_midnight) {
            echo "<br />End beyond MN";
            if ($end > $this->_night_diff_end) { // Breached next day's 6AM
                echo "<br /> Duration $duration_id ND end breach";
                if ($start > $this->_midnight) {
                    echo "<br />Start after MN";

                    $tomorrow_nd = $this->_night_diff_end - $start;
                    $broken_array['tomorrow_nd'] += $tomorrow_nd;
                } else {
                    echo "<br />Start before MN";
                    $today_nd = $this->_midnight - $start;
                    if ($today_nd > 0) $broken_array['today_nd'] += $today_nd;

                    $tomorrow_nd = $this->_night_diff_end - $this->_midnight;
                    $broken_array['tomorrow_nd'] += $tomorrow_nd;
                }

                $tomorrow = $end - $this->_night_diff_end;
                if ($tomorrow > 0) $broken_array['tomorrow'] += $tomorrow;

            } else {
                echo "<br /> Duration $duration_id ND end safe";
                if ($start >= $this->_midnight) {
                    echo "<br />Start after MN";
                    $tomorrow_nd = $end - $start;
                    if ($tomorrow_nd > 0) $broken_array['tomorrow_nd'] += $tomorrow_nd;
                } else {
                    echo "<br />Start before MN - ";

                    $tomorrow_nd = $end - $this->_midnight;
                    if ($tomorrow_nd > 0) $broken_array['tomorrow_nd'] += $tomorrow_nd;

                    if ($start >= $this->_night_diff_start) {
                        echo " after ND";
                        $today_nd = $this->_midnight - $start;
                        if ($today_nd > 0) $broken_array['today_nd'] += $today_nd;


                    } else { // $start less than nd
                        echo " before ND";
                        $today = $this->_night_diff_start - $start;
                        if ($today > 0) $broken_array['today'] += $today;

                        $today_nd = $this->_midnight - $this->_night_diff_start;
                        if ($today_nd > 0) $broken_array['today_nd'] += $today_nd;
                    }
                }

            }
        }

        foreach ($broken_array as $key => $value) {
            // if(!$value > 0) unset($broken_array[$key]);
        }

        $broken_array = array_map(function ($x) {
            return $x / 3600;
        }, $broken_array);

        /*
         if($this->_round_to_ten_minutes) {
        foreach($broken_array as $bkey=>$bvalue) {
        $minutes = $bvalue * 60;
        // $rounded = round($minutes,-1);
        $rounded = floor($minutes / 10) * 10; // round down to nearest 10 minutes
        $broken_array[$bkey] = $rounded/60;
        }
        }
        */

        return array($duration_id => $broken_array);
    }

    public function groupAction()
    {
        // action body

    }

    public function employeesAction()
    {
        // action body

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

                if ($employee_count > 0) {
                    $groups_array[] = array(
                        'client_id' => $cvalue->getId()
                    , 'client_name' => $cvalue->getName()
                    , 'group_id' => $gvalue->getId()
                    , 'group_name' => $gvalue->getName()
                    );
                }


            }
        }

        $this->view->groups_array = $groups_array;

        $pay_period = $this->_request->getParam('pay_period');
        $this->view->pay_period = $pay_period;

        $_SESSION['pay_period'] = $pay_period;

        $period_array = explode('-', $pay_period);

        $year_month = "{$period_array[0]}-{$period_array[1]}";

        if ($period_array[2] == '1_15') {
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

            $row = 0;

            $attendance = array();

            $missing = array();

            if (($handle = fopen($filename, "r")) !== FALSE) {

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
                                    array('datetime_start', 'employee_id'
                                        // ,'group_id'
                                    )
                                    , array($data['date'], $Employee->getId()
                                        // , $group_id
                                    )
                                );

                                $save_this_day = array();

                                if (!$Attendance) {
                                    $Attendance = new Messerve_Model_Attendance();
                                } else {
                                    //  echo "OLD ATTENDANCE";  preprint($Attendance->toArray());
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

                                $this->_save_the_day($Attendance->getEmployeeId(), $Attendance->getGroupId(), array($data['date'] => $save_this_day));

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
                    echo("Missing: {$value[1]}\t{$value[6]}\t{$value[4]}\t{$value[3]} \n");
                }

                fclose($handle);
            } else {
                die('File fail.');
            }


        }

        $EmployeeMap = new Messerve_Model_Mapper_Employee();

        $employees = $EmployeeMap->fetchList("group_id = $group_id", array('lastname ASC', 'firstname ASC'));

        $AttendDB = new Messerve_Model_DbTable_Attendance();

        // Search for relievers
        $temp_employees = array();

        foreach ($employees as $evalue) {
            $temp_employees[] = $evalue->getId();
        }


        $permanents = implode(',', $temp_employees);
        $select = $AttendDB->select(true);

        if (count($temp_employees) > 0) {
            $select->where("employee_id NOT IN ({$permanents})");
        }

        $select
            ->where('group_id = ?', $group_id)
            ->where("datetime_start BETWEEN '{$date_start}' AND '{$date_end}'")
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

        foreach ($employees as $evalue) {
            // preprint($evalue->toArray());
        }

        $employee_hours = array();


        foreach ($employees as $evalue) {
            $select = $AttendDB->select(true);
            $select
                ->columns(array('mysum' => 'ROUND(SUM(reg) + SUM(reg_nd) + SUM(reg_ot)	+ SUM(reg_nd_ot)	+ SUM(sun)	+ SUM(sun_nd)	+ SUM(sun_ot)	+ SUM(sun_nd_ot)	+ SUM(spec)	+ SUM(spec_nd)	+ SUM(spec_ot)	+ SUM(spec_nd_ot)	+ SUM(legal)	+ SUM(legal_nd)	+ SUM(legal_ot)	+ SUM(legal_nd_ot)	+ SUM(rest)	+ SUM(rest_nd)	+ SUM(rest_ot)	+ SUM(rest_nd_ot),2)'))
                ->where('employee_id = ?', $evalue->getId())
                ->where('group_id = ?', $group_id)
                ->where("datetime_start BETWEEN '{$date_start}' AND '{$date_end}'");

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
                if ($fileInfo->isDot()) {

                } else {
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
                if ($fileInfo->isDot() || $fileInfo->isDir()) {
                    // do nothing
                } else {
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

    public function employeeAction()
    {
        // action body
        $employee_id = (int)$this->_request->getParam('id');
        $this->view->employee_id = $employee_id;

        $group_id = (int)$this->_request->getParam('group_id');
        $this->view->group_id = $group_id;


        $Employee = new Messerve_Model_Employee();
        $Employee->find($employee_id);
        $this->view->employee = $Employee;


        $pay_period = $this->_request->getParam('pay_period');
        $this->view->pay_period = $pay_period;

        $date_start = $this->_request->getParam('date_start');
        $this->view->date_start = $date_start;

        $date_end = $this->_request->getParam('date_end');
        $this->view->date_end = $date_end;

        $date1 = new DateTime($date_start); //inclusive
        $date2 = new DateTime($date_end); //exclusive
        $diff = $date2->diff($date1);
        $period_size = intval($diff->format("%a")) + 1;

        $current_date = $date_start;

        $current_year = date('Y', strtotime($date_start));

        $dates = array();

        $AttendanceMap = new Messerve_Model_Mapper_Attendance();

        // $Db = $AttendanceMap->getDbTable()->getAdapter();

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
                    ->setEmployeeNumber($Employee->getEmployeeNumber())
                ;

                if(!$new_attendance->save(true)) {
                    throw new Exception('Did not insert initial attendance.');
                } else {
                    echo "New attendance"; preprint($new_attendance->toArray());
                }

                try {
                    $Attendance = new Messerve_Model_Attendance();
                    $Attendance->find($new_attendance->id);
                } catch ( Exception $e) {
                    die( 'Caught exception: ' . $e->getMessage() . "\n");
                }

            }

            $dates[$current_date] = $Attendance;



            $current_date = date('Y-m-d', strtotime('+1 day', strtotime($current_date)));

            if ($i == 1) $first_day_id = $Attendance->getId();

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

        if ($this->_request->isPost()) { // Save submit
            $postvars = $this->_request->getPost();

            if ($form->isValid($postvars)) {

                $Deductions
                    ->setOptions($postvars)
                    ->save();

                $AddIncome
                    ->setOptions($postvars)
                    ->save();

                $this->_save_the_day($employee_id, $group_id, $this->_request->getPost()); // TODO:  figure out why this needs to run twice
                $this->_save_the_day($employee_id, $group_id, $this->_request->getPost());

                $this->_redirect("/dataentry/attendance/employee/id/{$employee_id}/pay_period/{$pay_period}/date_start/{$date_start}/date_end/{$date_end}/group_id/{$group_id}");
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
            $calendar_entries = $Calendar->fetchList("(`year` = '0000' OR `year` = '$current_year') AND calendar_id = " . $cvalue, 'date ASC');

            foreach ($calendar_entries as $cevalue) {
                $holiday_date = $cevalue->getDate();

                if (strlen($holiday_date) < 10) $holiday_date = $current_year . '-' . $holiday_date;

                if (!isset($holidays[$holiday_date]) || $cevalue->getType() == 'legal') {
                    $holidays[$holiday_date] = $cevalue;
                }

            }
        }

        $this->view->holidays = $holidays;

    }

    public function dtrAction()
    {
        // action body
    }

    public function searchrelieverAction()
    { // AJAX
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $keyword = $this->_request->getParam('term');
        $group_id = $this->_request->getParam('group_id');

        $where = "(firstname LIKE '%{$keyword}%' OR lastname LIKE '%{$keyword}%')";

        $EmployeeMap = new Messerve_Model_Mapper_Employee();

        $result = $EmployeeMap->fetchList($where, array('firstname ASC', 'lastname ASC'));

        $json_array = array();

        if (count($result) > 0) {
            foreach ($result as $value) {

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
            , 'extended_shift' => 'no');

            $Attendance->setOptions($reset)->save();

            $AttPay = Messerve_Model_Eloquent_AttendancePayroll::where('attendance_id', $attendance_id);

            $AttPay->delete();

        }
    }

    protected function getLateDtr() {
        return "--";
    }
}
