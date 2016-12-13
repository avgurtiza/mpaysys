<?php

class Messervelib_Payroll
{
    protected $_max_regular_hours = null, $_init_night_diff_start, $_init_night_diff_end
    , $_night_diff_start, $_night_diff_end, $_midnight, $_date
    , $_ot_start, $_round_to_ten_minutes // TODO: Clean up
    ;
    protected $_config;
    protected $_client_rates, $_employee_rates;

    public function __construct()
    {
        // TODO:  Create registry settings for these defaults
        $this->_max_regular_hours = 8;
        $this->_init_night_diff_start = '2200';
        $this->_init_night_diff_end = '0600';

        $this->_config = Zend_Registry::get('config');
    }

    public function save_the_day($employee_id, $group_id, $data)
    {
        $time_start = microtime(true);

        $Employee = new Messerve_Model_Employee();

        $Employee->find($employee_id);

        $Group = new Messerve_Model_Group();
        $Group->find($group_id);

        echo '<br> ' . __LINE__ . ': ' . (microtime(true) - $time_start);
        $time_start = microtime(true);

        if ($Group->getRoundOff10() == 'yes') {
            $this->_round_to_ten_minutes = true;
        }

        $cutoff_total_duration = 0;

        $first_day_id = null; // cache record of first day for fuel calcs

        foreach ($data as $date => $attendance) {

            if (!isset($attendance['id']) || !$attendance['id'] > 0) continue;

            $Attendance = new Messerve_Model_Attendance();
            $Attendance->find($attendance['id']);

            echo '<br> ' . __LINE__ . ': ' . (microtime(true) - $time_start);
            $time_start = microtime(true);

            $holiday_today = false;
            $holiday_tomorrow = false;

            $holidays = $this->_fetch_holidays($group_id, $date);

            echo '<br> ' . __LINE__ . ': ' . (microtime(true) - $time_start);
            $time_start = microtime(true);

            if ($holidays) {
                if ($holidays['today']) $holiday_today = $holidays['today'];
                if ($holidays['tomorrow']) $holiday_tomorrow = $holidays['tomorrow'];
            }

            $this->_date = $date;

            if (date('d', strtotime($date)) == '1' || date('d', strtotime($date)) == '16') {
                $first_day = $Attendance;

                // Day 1 or 16? process deductions
                $unix_date = strtotime($date);

                if (date('d', $unix_date) == '1') {
                    $cutoff = '1';
                    $rate_date_start = date('Y-m-01', $unix_date);
                    $rate_date_end = date('Y-m-15', $unix_date);
                    // } elseif (date('d', $unix_date) == '16') {
                } else {
                    $cutoff = '2';
                    $rate_date_start = date('Y-m-16', $unix_date);
                    $rate_date_end = date('Y-m-d', strtotime("last day of this month", $unix_date));
                }

                $this->_get_rates($group_id, $rate_date_start, $rate_date_end);

                $DeductAttendMap = new Messerve_Model_Mapper_DeductionAttendance();

                // RESET! Delete all employee deductions for this payroll period
                $DeductAttendMap->getDbTable()->delete('attendance_id = ' . $attendance['id']);

                echo '<br> ' . __LINE__ . ': ' . (microtime(true) - $time_start);
                $time_start = microtime(true);

                // Is this the employee's home group? If it is, process deductions and BOP
                if ($Employee->getGroupId() == $group_id) {
                    $this->_process_deductions($attendance['id'], $employee_id, $cutoff);
                    $BOP = $this->_process_bop($attendance['id'], $Employee, $cutoff, $date);
                }

                echo '<br> ' . __LINE__ . ': ' . (microtime(true) - $time_start);
                $time_start = microtime(true);

            }

            $reset = [
                'today' => 0, 'today_nd' => 0, 'today_ot' => 0, 'today_nd_ot' => 0
                , 'tomorrow' => 0, 'tomorrow_nd' => 0, 'tomorrow_ot' => 0, 'tomorrow_nd_ot' => 0
                , 'reg' => 0, 'reg_nd' => 0, 'reg_ot' => 0, 'reg_nd_ot' => 0
                , 'spec' => 0, 'spec_nd' => 0, 'spec_ot' => 0, 'spec_nd_ot' => 0
                , 'legal' => 0, 'legal_nd' => 0, 'legal_ot' => 0, 'legal_nd_ot' => 0, 'legal_unattend' => 0
                , 'rest' => 0, 'rest_nd' => 0, 'rest_ot' => 0, 'rest_nd_ot' => 0
                , 'fuel_overage' => 0, 'fuel_hours' => 0, 'fuel_alloted' => 0, 'fuel_consumed' => 0
                , 'fuel_cost' => 0];

            $Attendance
                ->setGroupId($group_id)
                ->setOptions($reset)
                ->setEmployeeId($employee_id);

            if (isset($attendance['ot_approved']) && $attendance['ot_approved'] == 'yes') {
                $Attendance->setOtApproved('yes');
                $ot_approved_hours_array = explode(':', $attendance['ot_approved_hours']);
                $approved_minutes = 0;

                if (isset($ot_approved_hours_array[1])) {
                    $approved_minutes = floatval($ot_approved_hours_array[1] / 60);
                }

                $attendance['ot_approved_hours'] = floatval($ot_approved_hours_array[0]) + $approved_minutes;
            } else {
                $Attendance->setOtApproved('no')->setOtApprovedHours(0);
            }

            $Attendance->save();

            /*
            $time_array = ['today' => 0, 'today_nd' => 0, 'today_ot' => 0, 'today_nd_ot' => 0
                , 'tomorrow' => 0, 'tomorrow_nd' => 0, 'tomorrow_ot' => 0, 'tomorrow_nd_ot' => 0];
            */

            $time_array = [];

            // Determine employee and client rates for today and tomorrow
            $rates_today = $this->_get_rate_today($date);

            echo '<br> ' . __LINE__ . ': ' . (microtime(true) - $time_start);
            $time_start = microtime(true);

            $date_tommorow = date('Y-m-d 00:00:00', strtotime('+1 day', strtotime($date)));

            $rates_tomorrow = $this->_get_rate_today($date_tommorow);

            if (!$attendance['start_1'] > 0) { // Skipping records with no start_1 times
                // TODO:  Mod to accommodate 12MN start dates

                $legal_unattended_viable = false;

                if ($holiday_today && $holiday_today->getType() == 'legal') { // Unattended legal holiday

                    // Has attendance yesterday
                    $date_yesterday = date('Y-m-d 00:00:00', strtotime('-1 day', strtotime($date)));

                    $AttendanceY = new Messerve_Model_Attendance();
                    $AttendanceY = $AttendanceY->getMapper()->findByField(
                        array('employee_id', 'datetime_start')
                        , array($employee_id, $date_yesterday)
                    );

                    $legal_unattended_group = 0; // Init.  This will be the group that will be billed for Legal UA

                    foreach ($AttendanceY as $avalue) {
                        if ($avalue->getStart1() > 0) {
                            $legal_unattended_viable = true;
                            // Day before attendance gets priority for billing group
                            $legal_unattended_group = $avalue->getGroupId();
                            break;
                        }
                    }

                    // Check if there is actual legal holiday attendance for other outlets
                    $AttendanceN = new Messerve_Model_Attendance();
                    $AttendanceN = $AttendanceN->getMapper()->findByField(
                        array('employee_id', 'datetime_start'),
                        array($employee_id, $date)
                    );


                    foreach ($AttendanceN as $avalue) {
                        if ($avalue->getStart1() > 0) {
                            // Employee has legal holiday attendance, no longer qualified for unattended legal holiday pay
                            $legal_unattended_viable = false;
                            break;
                        }
                    }

                    $PayrollToday = new Messerve_Model_AttendancePayroll();

                    $PayrollToday->getMapper()->findOneByField(
                        array("attendance_id", "date", "group_id", "employee_id")
                        , array($Attendance->getId(), $date, $legal_unattended_group, $employee_id)
                        , $PayrollToday
                    );

                    $PayrollToday
                        ->setAttendanceId($attendance["id"])
                        ->setEmployeeId($employee_id)
                        ->setGroupId($Attendance->getGroupId())
                        ->setEmployee($Employee->getFirstname() . " " . $Employee->getLastname())
                        ->setRateId($rates_today['employee']['rate']['id'])
                        ->setClientRateId($rates_today['client']['rate']['id'])
                        ->setHolidayType("")
                        ->setDate($date)
                        ->setPeriodStart($rate_date_start)
                        ->setRegHours(0)
                        ->setRegPay(0)
                        ->setOtHours(0)
                        ->setNdHours(0)
                        ->setNdOtHours(0)
                        ->setOtPay(0)
                        ->setNdPay(0)
                        ->setNdOtPay(0)
                        ->setDateProcessed(date("Y-m-d H:i:s"));


                    if ($legal_unattended_viable && $legal_unattended_group == $group_id) {
                        // TODO:  Moon prism power clean up.
                        // Apply legal unattended pay
                        $PayrollToday
                            ->setRateData(json_encode($rates_today))
                            ->setGroupId($legal_unattended_group)
                            ->setHolidayType("Legal unattended")
                            ->setRegHours($this->_max_regular_hours)
                            ->setRegPay($this->_max_regular_hours * $rates_today['employee']['rate']['legal_unattend']);

                        if ($group_id != $legal_unattended_group) {
                            $time_array['legal_unattend'] = 0;
                        } else {
                            $time_array['legal_unattend'] = $this->_max_regular_hours;
                        }
                        // }
                    } else {
                        $time_array['legal_unattend'] = 0;
                    }


                    if ($employee_id > 0 && $attendance["id"] > 0) { // TODO: Fix hacky hack
                        $PayrollToday->save();
                    }

                    $Attendance
                        ->setGroupId($group_id)
                        ->setOptions($attendance + $time_array)
                        ->setEmployeeId($employee_id);

                    $Attendance->save();

                } else { // Not a legal holiday?  Reset payroll-attendance records

                    $PayrollToday = new Messerve_Model_AttendancePayroll();

                    $PayrollToday->getMapper()->findOneByField(
                        array("attendance_id", "date")
                        , array($Attendance->getId(), substr($Attendance->getDatetimeStart(), 0, 10))
                        , $PayrollToday
                    );

                    $PayrollToday->setEmployeeId($employee_id)
                        ->setAttendanceId($attendance["id"])
                        ->setGroupId($Attendance->getGroupId())
                        ->setEmployee($Employee->getFirstname() . " " . $Employee->getLastname())
                        ->setRateId($rates_tomorrow['employee']['rate']['id'])
                        ->setClientRateId($rates_tomorrow['client']['rate']['id'])
                        ->setHolidayType("")
                        ->setDate($date)
                        ->setPeriodStart($rate_date_start)
                        ->setRegHours(0)
                        ->setRegPay(0)
                        ->setOtHours(0)
                        ->setNdHours(0)
                        ->setNdOtHours(0)
                        ->setOtPay(0)
                        ->setNdPay(0)
                        ->setNdOtPay(0)
                        ->setDateProcessed(date("Y-m-d H:i:s"))
                        ->save();
                    echo '<br> ' . __LINE__ . ': ' . (microtime(true) - $time_start);
                    $time_start = microtime(true);


                    $new_date_tomorrow = date("Y-m-d", strtotime('tomorrow', strtotime($Attendance->getDatetimeStart())));

                    $PayrollTomorrow = new Messerve_Model_AttendancePayroll();

                    $PayrollTomorrow->getMapper()->findOneByField(
                        array("attendance_id", "date")
                        , array($Attendance->getId(), $new_date_tomorrow)
                        , $PayrollTomorrow
                    );

                    $PayrollTomorrow
                        ->setAttendanceId($Attendance->getId())
                        ->setEmployeeId($employee_id)
                        ->setGroupId($group_id)
                        ->setEmployee($Employee->getFirstname() . " " . $Employee->getLastname())
                        ->setRateId($rates_tomorrow['employee']['rate']['id'])
                        ->setClientRateId($rates_tomorrow['client']['rate']['id'])
                        ->setRateData(json_encode($rates_tomorrow))
                        ->setHolidayType("")
                        ->setDate($new_date_tomorrow)
                        ->setPeriodStart($rate_date_start)
                        ->setRegHours(0)
                        ->setRegPay(0)
                        ->setOtHours(0)
                        ->setNdHours(0)
                        ->setNdOtHours(0)
                        ->setOtPay(0)
                        ->setNdPay(0)
                        ->setNdOtPay(0)
                        ->setDateProcessed(date("Y-m-d H:i:s"))
                        ->save();

                    echo '<br> ' . __LINE__ . ': ' . (microtime(true) - $time_start);
                    $time_start = microtime(true);


                }
                // Done processing unattended legal holiday, move to next day

                continue;
            }


            $midnight = strtotime($date . ' + 1 day');
            $night_diff_start = strtotime($date . 'T' . $this->_init_night_diff_start);
            $night_diff_end = strtotime('+1 day', strtotime($date . 'T' . $this->_init_night_diff_end));

            $this->_midnight = $midnight;
            $this->_night_diff_start = $night_diff_start;
            $this->_night_diff_end = $night_diff_end;

            $start_1 = strtotime($date . ' T' . str_pad($attendance['start_1'], 4, 0, STR_PAD_LEFT));

            $end_1 = $attendance['end_1'] != '' ? strtotime($date . ' T' . str_pad($attendance['end_1'], 4, 0, STR_PAD_LEFT)) : 0;

            if ($end_1 < $start_1) {
                $date = date('Y-m-d', strtotime('+1 day', strtotime($date)));
                $end_1 = $attendance['end_1'] != '' ? strtotime($date . ' T' . str_pad($attendance['end_1'], 4, 0, STR_PAD_LEFT)) : 0;
            }

            $start_2 = isset($attendance['start_2']) && $attendance['start_2'] != '' ? strtotime($date . ' T' . str_pad($attendance['start_2'], 4, 0, STR_PAD_LEFT)) : 0;
            $end_2 = isset($attendance['end_2']) && $attendance['end_2'] != '' ? strtotime($date . ' T' . str_pad($attendance['end_2'], 4, 0, STR_PAD_LEFT)) : 0;

            if ($end_1 > $start_2) {
                $date = date('Y-m-d', strtotime('+1 day', strtotime($date)));
                $start_2 = isset($attendance['start_2']) && $attendance['start_2'] != '' ? strtotime($date . ' T' . str_pad($attendance['start_2'], 4, 0, STR_PAD_LEFT)) : 0;
                $end_2 = isset($attendance['end_2']) && $attendance['end_2'] != '' ? strtotime($date . ' T' . str_pad($attendance['end_2'], 4, 0, STR_PAD_LEFT)) : 0;
            } elseif ($end_2 < $start_2) {
                $date = date('Y-m-d', strtotime('+1 day', strtotime($date)));
                $end_2 = isset($attendance['end_2']) && $attendance['end_2'] != '' ? strtotime($date . ' T' . str_pad($attendance['end_2'], 4, 0, STR_PAD_LEFT)) : 0;
            }

            $start_3 = isset($attendance['start_3']) && $attendance['start_3'] != '' ? strtotime($date . ' T' . str_pad($attendance['start_3'], 4, 0, STR_PAD_LEFT)) : 0;
            $end_3 = isset($attendance['end_3']) && $attendance['end_3'] != '' ? strtotime($date . ' T' . str_pad($attendance['end_3'], 4, 0, STR_PAD_LEFT)) : 0;

            if ($end_1 > $start_2) {
                $date = date('Y-m-d', strtotime('+1 day', strtotime($date)));
                $start_3 = isset($attendance['start_3']) && $attendance['start_3'] != '' ? strtotime($date . ' T' . str_pad($attendance['start_3'], 4, 0, STR_PAD_LEFT)) : 0;
                $end_3 = isset($attendance['end_3']) && $attendance['end_3'] != '' ? strtotime($date . ' T' . str_pad($attendance['end_3'], 4, 0, STR_PAD_LEFT)) : 0;
            } elseif ($end_3 < $start_3) {
                $date = date('Y-m-d', strtotime('+1 day', strtotime($date)));
                $end_3 = isset($attendance['end_3']) && $attendance['end_3'] != '' ? strtotime($date . ' T' . str_pad($attendance['end_3'], 4, 0, STR_PAD_LEFT)) : 0;
            }

            $weekday = date('D', $start_1);
            $weekday_midnight = date('D', $midnight);

            $total_duration = 0;

            $duration_1 = ($end_1 - $start_1) / 3600;
            $duration_2 = ($end_2 - $start_2) / 3600;
            $duration_3 = 0;

            if ($start_3 > 0) $duration_3 = ($end_3 - $start_3) / 3600;

            $break_duration_1 = $break_duration_2 = 0;


            if ($duration_3 > 0) {
                $break_duration_2 = ($start_3 - $end_2) / 3600;
            }

            if ($duration_2 > 0) {
                $break_duration_1 = ($start_2 - $end_1) / 3600;;
            }

            $work_duration = $duration_1 + $duration_2 + $duration_3;

            $cutoff_total_duration += $work_duration;

            $total_break_duration = $break_duration_1 + $break_duration_2;
            $total_duration = $work_duration + $total_break_duration;

            $end_of_shift = $start_1 + ($total_duration * 3600);

            $has_extended_shift = false;

            $ot_duration = 0;

            $ot_approved = false;

            if (isset($attendance['ot_approved']) && $attendance['ot_approved'] == 'yes') {
                $ot_duration = $work_duration - $this->_max_regular_hours;
                $ot_approved = true;
            }

            if (isset($attendance['extended_shift']) && $attendance['extended_shift'] == 'yes') {
                $ot_duration = $work_duration - $this->_max_regular_hours;
                $has_extended_shift = true;
                echo "Extended <br>";
            }

            if(!$ot_approved && !$has_extended_shift && $work_duration > $this->_max_regular_hours) {
                $work_duration = $this->_max_regular_hours;
                echo "OVER: " . $work_duration . '<br>';
                $ot_duration = 0;
            }

            $ot_start = 0;

            echo '<br> ' . __LINE__ . ': ' . (microtime(true) - $time_start);
            $time_start = microtime(true);

            if ($ot_duration > 0) {
                /* When does OT start? */
                if ($work_duration >= $this->_max_regular_hours) {
                    if ($duration_1 >= $this->_max_regular_hours) { // OT starts in D1
                        $ot_start = $start_1 + ($this->_max_regular_hours * 3600);
                    } elseif ($duration_2 + $duration_2 >= $this->_max_regular_hours) { // OT starts in D1
                        $ot_start = $start_1 + $break_duration_1 + ($this->_max_regular_hours * 3600);
                    } elseif ($duration_2 + $duration_2 + $duration_3 >= $this->_max_regular_hours) { // OT starts in D3
                        $ot_start = $end_3 - ($work_duration - ($this->_max_regular_hours * 3600));
                    }
                }
            } else { // Just in case negatives turn up
                $ot_duration = 0;
            }
            echo '<br> ' . __LINE__ . ': ' . (microtime(true) - $time_start);
            $time_start = microtime(true);

            $this->_ot_start = $ot_start;

            if ($duration_1 > 0) {
                $d1_attendance = $this->_break_it_down($start_1, $end_1, 1, $attendance);
                $time_array = array_merge($time_array, $d1_attendance);
            }
            echo '<br> ' . __LINE__ . ': ' . (microtime(true) - $time_start);
            $time_start = microtime(true);

            if ($duration_2 > 0) {
                $d2_attendance = $this->_break_it_down($start_2, $end_2, 2, $attendance);
                $time_array = array_merge($time_array, $d2_attendance);
            }

            echo '<br> ' . __LINE__ . ': ' . (microtime(true) - $time_start);
            $time_start = microtime(true);

            if ($duration_3 > 0) {
                $d3_attendance = $this->_break_it_down($start_3, $end_3, 3, $attendance);
                $time_array = array_merge($time_array, $d3_attendance);
            }
            echo '<br> ' . __LINE__ . ': ' . (microtime(true) - $time_start);
            $time_start = microtime(true);

            $reg = 0;
            $nd = 0;
            $ot = 0;
            $nd_ot = 0;

            $tomorrow = 0;
            $tomorrow_nd = 0;
            $tomorrow_ot = 0;
            $tomorrow_nd_ot = 0;

            if ($ot_duration > 0) {
                $ot_balance = $ot_duration;

                for ($i = 3; $i >= 0; $i--) {

                    if (isset($time_array[$i])) {

                        echo "<br>OT $i - ";
                        preprint($time_array[$i]);

                        if (isset($time_array[$i]['tomorrow']) && $time_array[$i]['tomorrow'] > 0) {
                            if (($time_array[$i]['tomorrow'] - $ot_balance) >= 0) {
                                $tomorrow += $time_array[$i]['tomorrow'] - $ot_balance;
                                $tomorrow_ot += $ot_balance;
                                $ot_balance = 0;
                            } elseif ($ot_balance > 0) {
                                $ot_balance -= $time_array[$i]['tomorrow'];
                                $tomorrow_ot += $time_array[$i]['tomorrow'];
                            } else {
                                $tomorrow += $time_array[$i]['tomorrow'];
                            }
                        }

                        if (isset($time_array[$i]['tomorrow_nd']) && $time_array[$i]['tomorrow_nd'] > 0) {
                            if (($time_array[$i]['tomorrow_nd'] - $ot_balance) >= 0) {
                                $tomorrow_nd += $time_array[$i]['tomorrow_nd'] - $ot_balance;
                                $tomorrow_nd_ot += $ot_balance;
                                $ot_balance = 0;
                            } elseif ($ot_balance > 0) {
                                $ot_balance -= $time_array[$i]['tomorrow_nd'];
                                $tomorrow_nd_ot += $time_array[$i]['tomorrow_nd'];
                            } else {
                                $tomorrow_nd += $time_array[$i]['tomorrow_nd'];
                            }
                        }

                        if (isset($time_array[$i]['today_nd']) && $time_array[$i]['today_nd'] > 0) {
                            if (($time_array[$i]['today_nd'] - $ot_balance) >= 0) {
                                $nd += $time_array[$i]['today_nd'] - $ot_balance;
                                $nd_ot += $ot_balance;
                                $ot_balance = 0;
                            } elseif ($ot_balance > 0) {
                                $ot_balance -= $time_array[$i]['today_nd'];
                                $nd_ot += $time_array[$i]['today_nd'];
                            } else {
                                $nd += $time_array[$i]['today_nd'];
                            }
                        }

                        if (isset($time_array[$i]['today']) && $time_array[$i]['today'] > 0) {
                            if (($time_array[$i]['today'] - $ot_balance) >= 0) {
                                $reg += $time_array[$i]['today'] - $ot_balance;
                                $ot += $ot_balance;
                                $ot_balance = 0;
                            } elseif ($ot_balance > 0) {
                                $ot_balance -= $time_array[$i]['today'];
                                $ot += $time_array[$i]['today'];
                            } else {
                                $reg += $time_array[$i]['today'];
                            }
                        }

                    }
                }
            } else {
                echo "<br> NO OT <br>";
                $reg_balance = $this->_max_regular_hours;
                for ($i = 3; $i >= 0; $i--) {
                    if (isset($time_array[$i]['tomorrow'])) {
                        if (($reg_balance - $time_array[$i]['tomorrow']) >= 0) {
                            $reg += $time_array[$i]['tomorrow'];
                            $reg_balance -= $time_array[$i]['tomorrow'];
                        } else {
                            $reg += $reg_balance;
                        }
                    }

                    if (isset($time_array[$i]['tomorrow_nd'])) {
                        if (($reg_balance - $time_array[$i]['tomorrow_nd']) >= 0) {
                            $tomorrow_nd += $time_array[$i]['tomorrow_nd'];
                            $reg_balance -= $time_array[$i]['tomorrow_nd'];
                        } else {
                            $tomorrow += $reg_balance;
                        }
                    }

                    if (isset($time_array[$i]['today_nd'])) {
                        if (($reg_balance - $time_array[$i]['today_nd']) >= 0) {
                            $nd += $time_array[$i]['today_nd'];
                            $reg_balance -= $time_array[$i]['today_nd'];
                        } else {
                            $nd += $reg_balance;
                        }
                    }

                    if (isset($time_array[$i]['today'])) {
                        if (($reg_balance - $time_array[$i]['today']) >= 0) {
                            $reg += $time_array[$i]['today'];
                            $reg_balance -= $time_array[$i]['today'];
                        } else {
                            $reg += $reg_balance;
                        }
                    }
                }
            }

            $time_array = array_merge($time_array, [
                'nd_start_time' => date('Y-m-d H:i', $night_diff_start)
                , 'nd_end_time' => date('Y-m-d H:i', $night_diff_end)
                , 'end_of_shift_time' => date('Y-m-d H:i', $end_of_shift)

                , 'duration_1' => $duration_1
                , 'duration_2' => $duration_2
                , 'duration_3' => $duration_3

                , 'break_duration_1' => $break_duration_1
                , 'break_duration_2' => $break_duration_2
                , 'total_break_duration' => $total_break_duration

                , 'work_duration' => $work_duration
                , 'total_duration' => $total_duration
                , 'ot_duration' => $ot_duration

                , 'today' => $reg
                , 'today_ot' => $ot
                , 'today_nd' => $nd
                , 'today_nd_ot' => $nd_ot
                , 'tomorrow' => $tomorrow

                , 'tomorrow_nd' => $tomorrow_nd
                , 'tomorrow_ot' => $tomorrow_ot
                , 'tomorrow_nd_ot' => $tomorrow_nd_ot

                , 'legal' => 0
                , 'legal_nd' => 0
                , 'legal_ot' => 0
                , 'legal_nd_ot' => 0
            ]);


            $time_array['ot_actual_hours'] = $tomorrow_ot + $tomorrow_nd_ot + $nd_ot + $ot;

            if (!$has_extended_shift && $ot_duration > $Attendance->getOtApprovedHours()) {
                $excess_ot = $ot_duration - $Attendance->getOtApprovedHours();

                if ($tomorrow_ot > 0 && $excess_ot > 0) {
                    $ot_check = $tomorrow_ot - $excess_ot;

                    if ($ot_check > 0) {
                        $excess_ot = $excess_ot - $tomorrow_ot;
                        $tomorrow_ot = $ot_check;
                    } else {
                        $tomorrow_ot = 0;
                        $excess_ot = $ot_check * -1;
                    }
                }

                if ($tomorrow_nd_ot > 0 && $excess_ot > 0) {
                    $ot_check = $tomorrow_nd_ot - $excess_ot;

                    if ($ot_check > 0) {
                        $excess_ot = $excess_ot - $tomorrow_nd_ot;
                        $tomorrow_nd_ot = $ot_check;
                    } else {
                        $tomorrow_nd_ot = 0;
                        $excess_ot = $ot_check * -1;
                    }
                }

                if ($nd_ot > 0 && $excess_ot > 0) {
                    $ot_check = $nd_ot - $excess_ot;

                    if ($ot_check > 0) {
                        $excess_ot = $excess_ot - $nd_ot;
                        $nd_ot = $ot_check;
                    } else {
                        $nd_ot = 0;
                        $excess_ot = $ot_check * -1;
                    }
                }

                if ($ot > 0 && $excess_ot > 0) {
                    $ot_check = $ot - $excess_ot;

                    if ($ot_check > 0) {
                        $ot = $ot_check;
                    }
                }

            }

            $time_array['reg'] = 0;
            $time_array['reg_ot'] = 0;
            $time_array['reg_nd'] = 0;
            $time_array['reg_nd_ot'] = 0;

            $time_array['rest'] = 0;
            $time_array['rest_ot'] = 0;
            $time_array['rest_nd'] = 0;
            $time_array['rest_nd_ot'] = 0;

            $time_array['legal'] = 0;
            $time_array['legal_nd'] = 0;
            $time_array['legal_ot'] = 0;
            $time_array['legal_nd_ot'] = 0;

            $time_array['spec'] = 0;
            $time_array['spec_nd'] = 0;
            $time_array['spec_ot'] = 0;
            $time_array['spec_nd_ot'] = 0;

            if ($attendance['type'] == 'rest') {
                if (!$holiday_tomorrow) {
                    $time_array['rest'] = $reg + $tomorrow;
                    $time_array['rest_ot'] = $ot + $tomorrow_ot;
                    $time_array['rest_nd'] = $nd + $tomorrow_nd;
                    $time_array['rest_nd_ot'] = $nd_ot + $tomorrow_nd_ot;
                } else {
                    $time_array['rest'] = $reg;
                    $time_array['rest_ot'] = $ot;
                    $time_array['rest_nd'] = $nd;
                    $time_array['rest_nd_ot'] = $nd_ot;
                }
            } else { // Regular
                if (!$holiday_tomorrow) {
                    echo "Reg, not hol tom <br>";
                    $time_array['reg'] = $reg + $tomorrow;
                    $time_array['reg_ot'] = $ot + $tomorrow_ot;
                    $time_array['reg_nd'] = $nd + $tomorrow_nd;
                    $time_array['reg_nd_ot'] = $nd_ot + $tomorrow_nd_ot;
                } else {
                    $time_array['reg'] = $reg;
                    $time_array['reg_ot'] = $ot;
                    $time_array['reg_nd'] = $nd;
                    $time_array['reg_nd_ot'] = $nd_ot;
                }
            }

            if ($holiday_today) {
                // Reset values set by above
                $time_array['reg'] = 0;
                $time_array['reg_ot'] = 0;
                $time_array['reg_nd'] = 0;
                $time_array['reg_nd_ot'] = 0;

                $time_array['rest'] = 0;
                $time_array['rest_ot'] = 0;
                $time_array['rest_nd'] = 0;
                $time_array['rest_nd_ot'] = 0;

                switch ($holiday_today->getType()) {
                    case 'legal':
                        if (!$work_duration > 0) { // Do unattended OT calcs
                            $time_array['legal_unattend'] = $this->_max_regular_hours;
                        } else {
                            $time_array['legal'] = $reg;
                            $time_array['legal_nd'] = $nd;
                            $time_array['legal_ot'] = $ot;
                            $time_array['legal_nd_ot'] = $nd_ot;

                            if (!$holiday_tomorrow) {
                                $time_array['legal_nd'] += $tomorrow_nd;
                                $time_array['legal_nd_ot'] += $tomorrow_nd_ot;
                            }
                        }

                        break;
                    case 'special':
                        $time_array['spec'] = $reg;
                        $time_array['spec_nd'] = $nd;
                        $time_array['spec_ot'] = $ot;
                        $time_array['spec_nd_ot'] = $nd_ot;

                        if (!$holiday_tomorrow) {
                            $time_array['spec_ot'] += $tomorrow_nd;
                            $time_array['spec_nd_ot'] += $tomorrow_nd_ot;
                        }
                        break;
                    default:
                        throw new Exception('Holiday type is invalid: ' . $holiday_today->getType());
                        break;
                }
            }

            if ($holiday_tomorrow) {
                switch ($holiday_tomorrow->getType()) {
                    case 'legal':
                        $time_array['legal'] = $tomorrow;
                        $time_array['legal_ot'] = $tomorrow_ot;

                        if (!$holiday_today) {
                            $time_array['legal_nd'] = $tomorrow_nd;
                            $time_array['legal_nd_ot'] = $tomorrow_nd_ot;
                        }
                        break;
                    case 'special':
                        $time_array['spec'] = $tomorrow;
                        $time_array['spec_ot'] = $tomorrow_ot;

                        if (!$holiday_today) {
                            $time_array['spec_nd'] = $tomorrow_nd;
                            $time_array['spec_nd_ot'] = $tomorrow_nd_ot;
                        }

                        break;
                    default:
                        throw new Exception('Holiday type is invalid: ' . $holiday_today->getType());
                        break;
                }
            }


            if ($Attendance->getOtApproved() != 'yes' && !$has_extended_shift) {
                echo "Resetting OT <br>";

                $time_array['reg_ot'] = 0;
                $time_array['reg_nd_ot'] = 0;

                $time_array['spec_ot'] = 0;
                $time_array['spec_nd_ot'] = 0;

                $time_array['rest_ot'] = 0;
                $time_array['rest_nd_ot'] = 0;

                $time_array['legal_ot'] = 0;
                $time_array['legal_nd_ot'] = 0;

                $time_array['today_ot'] = 0;
                $time_array['today_nd_ot'] = 0;

                $time_array['tomorrow_ot'] = 0;
                $time_array['tomorrow_nd_ot'] = 0;
            } else {
                echo "Not resetting OT <br>";
            }

            foreach ($time_array as $tkey => $tvalue) {
                if (is_numeric($tvalue)) {
                    // $time_array[$tkey] = round($tvalue, 2);
                    $time_array[$tkey] = number_format($tvalue, 2, '.', '');
                }
            }

            /*
            if($Attendance->id == 492582) {
                preprint($time_array);
                echo "THIS: " . $attendance['id'] . " -- R $reg ND $nd OT $ot NDOT $nd_ot T $tomorrow TOT $tomorrow_ot TND $tomorrow_nd TNDOT $tomorrow_nd_ot <br>";
                die('495922');
            }
            */

            $options = $time_array;

            if (is_array($attendance)) {
                $options = array_merge($options, $attendance);

                $Attendance
                    ->setGroupId($group_id)
                    ->setOptions($options)
                    ->setEmployeeId($employee_id);

                $Attendance->save();

                $holiday_type_today = "Regular";

                $pay_rate_prefix = "reg";

                if ($attendance['type'] == "rest") {
                    $holiday_type_today = "Rest";
                    $pay_rate_prefix = "sun";
                }

                if ($holiday_today) {
                    $holiday_type_today = ucfirst($holiday_today->getType());
                    $pay_rate_prefix = strtolower($holiday_today->getType());
                }

                if ($pay_rate_prefix == 'special') $pay_rate_prefix = "spec";

                $PayrollToday = new Messerve_Model_AttendancePayroll();

                $new_date_today = date("Y-m-d", strtotime($Attendance->getDatetimeStart()));

                $rates_today['_prefix'] = $pay_rate_prefix;

                if ($employee_id > 0 && $Attendance->getId() > 0) {

                    $PayrollToday->getMapper()->findOneByField(
                        array("employee_id", "attendance_id", "date")
                        , array($employee_id, $Attendance->getId(), $new_date_today)
                        , $PayrollToday
                    );

                    $PayrollToday
                        ->setAttendanceId($Attendance->getId())
                        ->setEmployeeId($employee_id)
                        ->setGroupId($group_id)
                        ->setEmployee($Employee->getFirstname() . " " . $Employee->getLastname())
                        ->setRateId($rates_today['employee']['rate']['id'])
                        ->setClientRateId($rates_today['client']['rate']['id'])
                        ->setRateData(json_encode($rates_today))
                        ->setHolidayType($holiday_type_today)
                        ->setDate($new_date_today)
                        ->setPeriodStart($rate_date_start)
                        ->setRegHours($options['today'])
                        ->setOtHours($options['today_ot'])

                        ->setRegPay($options['today'] * $rates_today['employee']['rate'][$pay_rate_prefix])
                        ->setOtPay($options['today_ot'] * $rates_today['employee']['rate'][$pay_rate_prefix . "_ot"])
                        ->setDateProcessed(date("Y-m-d H:i:s"));

                    if ($holiday_today) {
                        $PayrollToday
                            ->setNdPay(($options['today_nd'] + $options['tomorrow_nd']) * $rates_today['employee']['rate'][$pay_rate_prefix . "_nd"])
                            ->setNdOtPay(($options['today_nd_ot'] + $options['tomorrow_nd_ot']) * $rates_today['employee']['rate'][$pay_rate_prefix . "_nd_ot"])
                            ->setNdHours($options['today_nd'] + $options['tomorrow_nd'])
                            ->setNdOtHours($options['today_nd_ot'] + $options['tomorrow_nd_ot']);

                    } else {
                        $PayrollToday
                            ->setNdPay($options['today_nd'] * $rates_today['employee']['rate'][$pay_rate_prefix . "_nd"])
                            ->setNdOtPay($options['today_nd_ot'] * $rates_today['employee']['rate'][$pay_rate_prefix . "_nd_ot"])
                            ->setNdHours($options['today_nd'])
                            ->setNdOtHours($options['today_nd_ot']);
                    }

                    $PayrollToday->save();
                }

                $holiday_type_tomorrow = "Regular";
                $pay_rate_prefix = "reg";

                $new_date_tomorrow = date("Y-m-d", strtotime('tomorrow', strtotime($Attendance->getDatetimeStart())));

                $PayrollTomorrow = new Messerve_Model_AttendancePayroll();

                $PayrollTomorrow->getMapper()->findOneByField(
                    array("employee_id", "attendance_id", "date")
                    , array($employee_id, $Attendance->getId(), $new_date_tomorrow)
                    , $PayrollTomorrow
                );

                if ($holiday_tomorrow) {
                    $holiday_type_tomorrow = ucfirst($holiday_tomorrow->getType());
                    $pay_rate_prefix = strtolower($holiday_tomorrow->getType());
                }

                if ($pay_rate_prefix == 'special') $pay_rate_prefix = "spec";

                $rates_tomorrow['_prefix'] = $pay_rate_prefix;

                if ($employee_id > 0 && $Attendance->getId() > 0) {
                    $PayrollTomorrow
                        ->setAttendanceId($Attendance->getId())
                        ->setEmployeeId($employee_id)
                        ->setGroupId($group_id)
                        ->setEmployee($Employee->getFirstname() . " " . $Employee->getLastname())
                        ->setRateId($rates_tomorrow['employee']['rate']['id'])
                        ->setClientRateId($rates_tomorrow['client']['rate']['id'])
                        ->setRateData(json_encode($rates_tomorrow))
                        ->setHolidayType($holiday_type_tomorrow)
                        ->setDate($new_date_tomorrow)
                        ->setPeriodStart($rate_date_start)
                        ->setRegHours($options['tomorrow'])
                        ->setOtHours($options['tomorrow_ot'])

                        //->setNdHours($options['tomorrow_nd'])
                        //->setNdOtHours($options['tomorrow_nd_ot'])

                        ->setRegPay($options['tomorrow'] * $rates_tomorrow['employee']['rate'][$pay_rate_prefix])
                        ->setOtPay($options['tomorrow_ot'] * $rates_tomorrow['employee']['rate'][$pay_rate_prefix . "_ot"])
                        ->setDateProcessed(date("Y-m-d H:i:s"));

                    if (!$holiday_today) {
                        $PayrollTomorrow
                            ->setNdPay($options['tomorrow_nd'] * $rates_tomorrow['employee']['rate'][$pay_rate_prefix . "_nd"])
                            ->setNdOtPay($options['tomorrow_nd_ot'] * $rates_tomorrow['employee']['rate'][$pay_rate_prefix . "_nd_ot"])

                            ->setNdHours($options['tomorrow_nd'])
                            ->setNdOtHours($options['tomorrow_nd_ot']);
                    } else {
                        $PayrollTomorrow
                            ->setNdPay(0)
                            ->setNdOtPay(0)
                            ->setNdHours(0)
                            ->setNdOtHours(0);
                    }

                    $PayrollTomorrow->save();
                }
            }

            if($attendance['id'] == 492582) {
                echo $attendance['id'] . " -- R $reg ND $nd OT $ot NDOT $nd_ot T $tomorrow TOT $tomorrow_ot TND $tomorrow_nd TNDOT $tomorrow_nd_ot";
            }
        }

        if (!$cutoff_total_duration > 0) {

            $reset = array('today' => 0, 'today_nd' => 0, 'today_ot' => 0, 'today_nd_ot' => 0, 'tomorrow_nd_ot' => 0
            , 'reg' => 0, 'reg_nd' => 0, 'reg_ot' => 0, 'reg_nd_ot' => 0
            , 'spec' => 0, 'spec_nd' => 0, 'spec_ot' => 0, 'spec_nd_ot' => 0
            , 'legal' => 0, 'legal_nd' => 0, 'legal_ot' => 0, 'legal_nd_ot' => 0, 'legal_unattend' => 0
            , 'rest' => 0, 'rest_nd' => 0, 'rest_ot' => 0, 'rest_nd_ot' => 0);

        } else {
            if ($cutoff == 2) {
                if ($Employee->getGroupId() == $group_id) { // Parent group?  Process fuel calcs
                    $first_day
                        ->setFuelHours(0)
                        ->setFuelAlloted(0)
                        ->setFuelConsumed(0)
                        ->setFuelOverage(0)
                        ->save();  // Reset fuel consumption

                    if ($Employee->getGascard() > 0) {

                        // Get previous cutoff duration!
                        $prev_date_start = date('Y-m-28', strtotime('last month', strtotime($first_day->getDatetimeStart())));
                        $prev_date_end = date('Y-m-27', strtotime($first_day->getDatetimeStart()));

                        if (strtotime($Employee->getBopStart()) > strtotime($prev_date_start)) {
                            $prev_date_start = $Employee->getBopStart();
                        }

                        $monthly_work_duration = $this->_get_work_duration($employee_id, 0, $prev_date_start, $prev_date_end);

                        $fuel_alloted = $Group->getFuelperhour() * $monthly_work_duration;

                        $fuel_purchased = $this->_get_monthly_fuel_purchase($first_day, $monthly_work_duration, $prev_date_start, $prev_date_end);

                        $fuel_consumption = $fuel_purchased - $fuel_alloted;

                        $first_day
                            ->setFuelHours($monthly_work_duration)
                            ->setFuelAlloted($fuel_alloted)
                            ->setFuelConsumed($fuel_purchased)
                            ->setFuelOverage($fuel_consumption)
                            ->save();
                    }
                }
            }
        }

        $this->_clean_up_holidays($Employee, $rate_date_start, $group_id);
    }

    protected function _clean_up_holidays($Employee, $period_start, $group_id)
    {
        $AttendancePayroll = new Messerve_Model_Mapper_AttendancePayroll();

        $where = "attendance_id > 0 AND employee_id = {$Employee->getId()}
        	AND period_start = '$period_start' AND group_id = $group_id";

        foreach ($AttendancePayroll->fetchList($where) as $apval) {
            if ($apval->getHolidayType() == "Legal unattended") {
                $where = "employee_id = {$Employee->getId()} AND datetime_start = '{$apval->getDate()}'
                    AND (legal > 0 OR legal_ot > 0 OR legal_nd > 0 OR legal_nd_ot > 0)";

                $Attendance = new Messerve_Model_Mapper_Attendance();
                $legal_attendance = $Attendance->fetchListToArray($where);

                if (count($legal_attendance) > 0) {
                    $apval->setRegHours(0)->setRegPay(0)->save();
                }
            }
            /*else {
                $where = "employee_id = {$Employee->getId()} AND datetime_start = '{$apval->getDate()}'
                    AND NOT (reg > 0 OR reg_ot > 0 OR reg_nd > 0 OR reg_nd_ot > 0)";

                $Attendance = new Messerve_Model_Mapper_Attendance();
                $reg_attendance = $Attendance->fetchListToArray($where);

                if(!count($reg_attendance) > 0) {
                    $apval->setRegHours(0)->setRegPay(0)->save();
                }

            }*/


        }
    }

    function __call($name, $arguments)
    {
        // TODO: Implement __call() method.
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

    protected function _get_rates($group_id, $date_start, $date_end)
    {
        $rates = array();

        $Group = new Messerve_Model_Group();
        $Group->find($group_id);

        $EmployeeRateMap = new Messerve_Model_Mapper_EmployeeRateSchedule();

        $result = $EmployeeRateMap->fetchList("group_id = $group_id
            AND (date_active >= '$date_start' AND date_active <= '$date_end')", "date_active ASC");

        $EmployeeRate = new Messerve_Model_Rate();
        $EmployeeRate->find($Group->getRateId());

        $rates['employee']['default'] = $EmployeeRate->toArray();

        foreach ($result as $rvalue) {
            $rates['employee']['schedule'][$rvalue->getDateActive()] = $rvalue->toArray();
            $rates['employee']['schedule'][$rvalue->getDateActive()]['rate'] = $EmployeeRate->find($rvalue->getRateId())->toArray();
        }

        $ClientRateMap = new Messerve_Model_Mapper_ClientRateSchedule();

        $result = $ClientRateMap->fetchList("group_id = $group_id
            AND (date_active >= '$date_start' AND date_active <= '$date_end')", "date_active ASC");


        $ClientRate = new Messerve_Model_RateClient();
        $ClientRate->find($Group->getRateClientId());

        $rates['client']['default'] = $ClientRate->toArray();

        foreach ($result as $rvalue) {
            $rates['client']['schedule'][$rvalue->getDateActive()] = $rvalue->toArray();
            $rates['client']['schedule'][$rvalue->getDateActive()]['rate'] = $ClientRate->find($rvalue->getClientRateId())->toArray();
        }


        $this->_client_rates = $rates['client'];
        $this->_employee_rates = $rates['employee'];

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
            // echo "<br /> $attendance_id HAS DEDUCTIONS";
            foreach ($deductions as $dvalue) {
                // Total all deductions,  balance matches max deduction value + this deduction?

                $select = $DeductAttendMap->getDbTable()->select();

                $select
                    ->from('deduction_attendance', array('mysum' => 'SUM(amount)'))
                    ->where('deduction_schedule_id = ?', $dvalue->getId());

                $this_sum = $DeductAttendMap->getDbTable()->fetchRow($select)->mysum;
                // echo "BALANCE:  $this_sum";

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

    protected function _process_bop($attendance_id, Messerve_Model_Employee $Employee, $cutoff, $date)
    { // Bike ownership program
        $BOP = new Messerve_Model_Bop();
        $BOP->find($Employee->getBopId());

        $employee_bop_start = $Employee->getBopStart();

        $then = date('Ymd', strtotime($employee_bop_start));
        $diff = date('Ymd') - $then;
        $bop_age = (int)substr($diff, 0, -4);

        switch ($bop_age) {
            case(0):
                $maintenance = $BOP->getMaintenance1();
                break;
            case(1):
                $maintenance = $BOP->getMaintenance2();
                break;
            case(2):
                $maintenance = $BOP->getMaintenance3();
                break;
            case(3):
                $maintenance = $BOP->getMaintenance4();
                break;
            default:
                $maintenance = 0;
                break;
        }

        $maintenance_total = 0;

        $previous_month_hours = 0;

        if ($cutoff == 1) {
            if ($BOP->getId() == '5') {
                $last_month_start = date('Y-m-28', strtotime('-2 months'));
                $last_month_end = date('Y-m-27', strtotime('previous month'));

            } else {
                $last_month_start = date('Y-m-d', strtotime('first day of previous month'));
                $last_month_end = date('Y-m-d', strtotime('last day of previous month'));
            }

            $previous_month_hours = $this->_get_work_duration($Employee->getId(), 0, $last_month_start, $last_month_end);
            $maintenance_total = round(($maintenance / 26 / 8) * $previous_month_hours, 2);

        }

        $insurance_deduction = $BOP->getInsuranceDeduction();
        $motorcycle_deduction = $BOP->getMotorcycleDeduction();

        $BOPAttendance = new Messerve_Model_BopAttendance();
        $BOPAttendance->find(array('bop_id' => $BOP->getId(), 'attendance_id' => $attendance_id));
        $BOPAttendance->getMapper()->delete($BOPAttendance);

        $BOPAttendance
            ->setBopId($BOP->getId())
            ->setAttendanceId($attendance_id)
            ->setMaintenanceAddition($maintenance_total)
            ->setPreviousMonthHours($previous_month_hours);

        // BOP deductions split per cut-off
        $BOPAttendance->setMotorcycleDeduction($motorcycle_deduction / 2)
            ->setInsuranceDeduction($insurance_deduction / 2);

        $BOPAttendance->save();

        return $BOPAttendance;
    }

    protected function _get_work_duration($employee_id, $group_id = 0, $date_start, $date_end)
    {

        $AttendDB = new Messerve_Model_DbTable_Attendance();

        $select = $AttendDB->select();

        $select
            ->setIntegrityCheck(false)
            ->from('attendance', array(
                    'total' => '(SUM(reg) + SUM(reg_nd) + SUM(reg_ot) + SUM(reg_nd_ot) + SUM(sun) +SUM(sun_nd) + SUM(sun_ot) +SUM(sun_nd_ot)
						+ SUM(spec) + SUM(spec_nd) + SUM(spec_ot) + SUM(spec_nd_ot) + SUM(legal) + SUM(legal_nd) + SUM(legal_ot)
						+ SUM(legal_nd_ot) + SUM(legal_unattend) + SUM(rest) + SUM(rest_nd) + SUM(rest_ot) + SUM(rest_nd_ot))')
            )
            ->join('employee', 'employee.id = attendance.employee_id')
            ->where('attendance.employee_id = ?', $employee_id)
            ->where("datetime_start >= '{$date_start}' AND datetime_start <='{$date_end} 23:59'");

        if ($group_id > 0) {
            $select->where('attendance.group_id = ?', $group_id);
        }

        $result = $AttendDB->fetchRow($select);

        return (float)$result->total;
    }

    protected function _get_rate_today($date)
    {

        $employee_rate = false;

        if (isset($this->_employee_rates['schedule'])) {
            foreach ($this->_employee_rates['schedule'] as $erkey => $ervalue) { // Iterate through the rates and get latest
                if (strtotime($date) >= strtotime($ervalue['date_active'])) {
                    $employee_rate = $ervalue;
                }
            }
        }

        if (!$employee_rate) $employee_rate['rate'] = $this->_employee_rates['default'];

        $client_rate = false;

        if (isset($this->_client_rates['schedule'])) {

            foreach ($this->_client_rates['schedule'] as $crkey => $crvalue) { // Iterate through the rates and get latest
                if (strtotime($date) >= strtotime($crvalue['date_active'])) {
                    $client_rate = $crvalue;
                }
            }
        }
        if (!$client_rate) $client_rate['rate'] = $this->_client_rates['default'];

        return (array("employee" => $employee_rate, "client" => $client_rate));
    }

    protected function _break_it_down($start, $end, $duration_id, $Attendance = null)
    {

        $broken_array = array('today' => 0, 'today_nd' => 0, 'tomorrow_nd' => 0, 'tomorrow' => 0);


        /* Today */
        if ($end < $this->_night_diff_start) {
            $today = $end - $start;
            if ($today > 0) $broken_array['today'] += $today;
        }

        /* ND */

        if ($end <= $this->_midnight && $end >= $this->_night_diff_start) {

            // echo "<br />Duration: $duration_id ";

            if ($start <= $this->_night_diff_start) {
                $today = $this->_night_diff_start - $start;
                if ($today > 0) $broken_array['today'] += $today;

                $today_nd = $end - $this->_night_diff_start;

                if ($today_nd > 0) $broken_array['today_nd'] += $today_nd;

            } else {
                // echo "<br /> ND :B ";
                // $today = $start - $this->_night_diff_start;
                // if($today > 0) $broken_array['today'] +=  $today;

                $today_nd = $end - $start;
                if ($today_nd > 0) $broken_array['today_nd'] += $today_nd;
            }

        }

        /* Early ND */
        $early_night_diff_end = strtotime(date('Y-m-d ' . $this->_init_night_diff_end, $start));

        if ($start < $early_night_diff_end
            && $end < $this->_midnight
            // && !$start > $this->_midnight
        ) {
            if ($end > $early_night_diff_end) {
                $broken_array['today_nd'] = $early_night_diff_end - $start;
                $broken_array['today'] = $end - $early_night_diff_end;
            } else {
                $broken_array['today_nd'] = $end - $start;
                $broken_array['today'] = 0;
            }
        }


        /* Tomorrow */
        if ($end > $this->_midnight) {
            // echo "<br />End beyond MN";
            if ($end > $this->_night_diff_end) { // Breached next day's 6AM
                // echo "<br /> Duration $duration_id ND end breach";

                if ($start > $this->_midnight) {
                    // echo "<br />Start after MN";

                    $tomorrow_nd = $this->_night_diff_end - $start;
                    $broken_array['tomorrow_nd'] += $tomorrow_nd;
                } else {
                    // echo "<br />Start before MN";
                    $today_nd = $this->_midnight - $start;
                    if ($today_nd > 0) $broken_array['today_nd'] += $today_nd;

                    $tomorrow_nd = $this->_night_diff_end - $this->_midnight;
                    $broken_array['tomorrow_nd'] += $tomorrow_nd;
                }

                $tomorrow = $end - $this->_night_diff_end;
                if ($tomorrow > 0) $broken_array['tomorrow'] += $tomorrow;

            } else {
                // echo "<br /> Duration $duration_id ND end safe";
                if ($start >= $this->_midnight) {
                    // echo "<br />Start after MN";
                    $tomorrow_nd = $end - $start;
                    if ($tomorrow_nd > 0) $broken_array['tomorrow_nd'] += $tomorrow_nd;
                } else {
                    // echo "<br />Start before MN - ";

                    $tomorrow_nd = $end - $this->_midnight;
                    if ($tomorrow_nd > 0) $broken_array['tomorrow_nd'] += $tomorrow_nd;

                    if ($start >= $this->_night_diff_start) {
                        // echo " after ND";
                        $today_nd = $this->_midnight - $start;
                        if ($today_nd > 0) $broken_array['today_nd'] += $today_nd;


                    } else { // $start less than nd
                        // echo " before ND";
                        $today = $this->_night_diff_start - $start;
                        if ($today > 0) $broken_array['today'] += $today;

                        $today_nd = $this->_midnight - $this->_night_diff_start;
                        if ($today_nd > 0) $broken_array['today_nd'] += $today_nd;
                    }
                }

            }
        }

        /*
        foreach($broken_array as $key => $value) {
            // if(!$value > 0) unset($broken_array[$key]);
        }
        */

        $broken_array = array_map(function ($x) {
            return $x / 3600;
        }, $broken_array);

        // if($Attendance['id'] == '241297' && $duration_id == 2) { preprint($Attendance) ;preprint($broken_array,1); }


        return array($duration_id => $broken_array);
    }

    protected function _get_monthly_fuel_purchase(
        Messerve_Model_Attendance $Attendance
        , $monthly_work_duration, $date_start, $date_end
    )
    {
        $employee_id = $Attendance->getEmployeeId();

        // $date_start = $Attendance->getDatetimeStart();
        // $date_end = date('Y-m-d', strtotime('last day of this month', strtotime($date_start)));

        $FuelMap = new Messerve_Model_Mapper_Fuelpurchase();

        $fuel_purchases = $FuelMap->fetchList("invoice_date >= '{$date_start}' AND invoice_date <= '{$date_end} 23:59' AND employee_id = {$employee_id}"
        );

        $fuel_purchased = 0;
        $fuel_consumption = 0;

        foreach ($fuel_purchases as $ftvalue) {

            $fuel_purchased += $ftvalue->getProductQuantity();
        }

        return $fuel_purchased;

    }

    public function GetEmployeePayroll($employee_id, $group_id, $period_start)
    {
        $PayrollMap = new Messerve_Model_Mapper_AttendancePayroll();

        $payroll_raw = $PayrollMap->fetchList("employee_id = $employee_id
            AND group_id = $group_id
            AND period_start LIKE '$period_start'
            AND attendance_id > 0
        ");


        $payroll = array();

        foreach ($payroll_raw as $pvalue) { // TODO:  Construct array properly
            if ($employee_id == 893) {
                preprint($pvalue->toArray());

            }

            if ($pvalue->getRateData() != '') {
                @$payroll[$pvalue->getRateId()]['meta'] = json_decode($pvalue->getRateData());
            }

            if ($pvalue->getRegHours() > 0) {
                @$payroll[$pvalue->getRateId()][$pvalue->getHolidayType()]['reg']['hours'] += $pvalue->getRegHours();
                @$payroll[$pvalue->getRateId()][$pvalue->getHolidayType()]['reg']['pay'] += $pvalue->getRegPay();
            }


            if ($pvalue->getOtHours() > 0) {
                @$payroll[$pvalue->getRateId()][$pvalue->getHolidayType()]['ot']['hours'] += $pvalue->getOtHours();
                @$payroll[$pvalue->getRateId()][$pvalue->getHolidayType()]['ot']['pay'] += $pvalue->getOtPay();
            }

            if ($pvalue->getNdHours() > 0) {
                @$payroll[$pvalue->getRateId()][$pvalue->getHolidayType()]['nd']['hours'] += $pvalue->getNdHours();
                @$payroll[$pvalue->getRateId()][$pvalue->getHolidayType()]['nd']['pay'] += $pvalue->getNdPay();
            }

            if ($pvalue->getNdOtHours() > 0) {
                @$payroll[$pvalue->getRateId()][$pvalue->getHolidayType()]['nd_ot']['hours'] += $pvalue->getNdOtHours();
                @$payroll[$pvalue->getRateId()][$pvalue->getHolidayType()]['nd_ot']['pay'] += $pvalue->getNdOtPay();
            }

        }


        return $payroll;
    }

    protected function _get_date($start, $end, $date)
    {

    }

    protected function _break_the_day($date, $data)
    {
        $data['start_1'] = str_pad($data['start_1'], 4, '0', STR_PAD_LEFT);
        $data['start_2'] = str_pad($data['start_2'], 4, '0', STR_PAD_LEFT);
        $data['start_3'] = str_pad($data['start_3'], 4, '0', STR_PAD_LEFT);

        $data['end_1'] = str_pad($data['end_1'], 4, '0', STR_PAD_LEFT);
        $data['end_2'] = str_pad($data['end_2'], 4, '0', STR_PAD_LEFT);
        $data['end_3'] = str_pad($data['end_3'], 4, '0', STR_PAD_LEFT);

        $date_start_1 = date('Y-m-d H:i', strtotime("$date {$data['start_1']}"));


        if ($data['start_1'] > $data['end_1']) { // Period ends the next day
            $date = date('Y-m-d', strtotime('+1 day', strtotime($date))); // current date is now tomorrow's
            $date_end_1 = date('Y-m-d H:i', strtotime("$date {$data['end_1']}"));
        } else {
            $date_end_1 = date('Y-m-d H:i', strtotime("$date {$data['end_1']}"));
        }


        $date_start_2 = date('Y-m-d H:i', strtotime("$date {$data['start_2']}"));

        if ($data['start_2'] > $data['end_2']) { // Period ends the next day
            $date = date('Y-m-d', strtotime('+1 day', strtotime($date))); // current date is now tomorrow's
            $data['end_2'] = str_pad($data['end_2'], 4, '0', STR_PAD_LEFT);
            $date_end_2 = date('Y-m-d H:i', strtotime("$date {$data['end_2']}"));
        } else {
            $date_end_2 = date('Y-m-d H:i', strtotime("$date {$data['end_2']}"));
        }

        $date_start_3 = date('Y-m-d H:i', strtotime("$date {$data['start_3']}"));

        if ($data['start_3'] > $data['end_3']) { // Period ends the next day
            $date = date('Y-m-d', strtotime('+1 day', strtotime($date))); // current date is now tomorrow's
            $data['end_3'] = str_pad($data['end_3'], 4, '0', STR_PAD_LEFT);
            $date_end_3 = date('Y-m-d H:i', strtotime("$date {$data['end_3']}"));
        } else {
            $date_end_3 = date('Y-m-d H:i', strtotime("$date {$data['end_3']}"));
        }

    }

    protected function _get_daily_fuel_purchase(Messerve_Model_Attendance $Attendance, $daily_total_duration)
    { // Retired
        $employee_id = $Attendance->getEmployeeId();
        $date_start = $Attendance->getDatetimeStart();

        $date_end = date('Y-m-d', strtotime('next day', strtotime($date_start)));

        $FuelMap = new Messerve_Model_Mapper_Fuelpurchase();

        $fuel_today = $FuelMap->fetchList(
            "invoice_date >= '{$date_start}'
			AND invoice_date < '{$date_end}'
			AND employee_id = {$employee_id}");

        $fuel_purchased = 0;
        $fuel_consumption = 0;

        foreach ($fuel_today as $ftvalue) {
            $fuel_purchased += $ftvalue->getProductQuantity();
        }

        return $fuel_purchased;
    }

    protected function _get_cutoff_fuel_consumption(Messerve_Model_Attendance $Attendance, $cutoff_total_duration, $total_fuel_purchase_l)
    {
        $fuel_per_hour = $this->_config->messerve->fuelperhour;

        $fuel_consumption = $total_fuel_purchase_l - ($fuel_per_hour * $cutoff_total_duration);

        return $fuel_consumption;

    }

    protected function _get_fuel_consumption(Messerve_Model_Attendance $Attendance, $cutoff_total_duration)
    {
        $employee_id = $Attendance->getEmployeeId();
        $date_start = $Attendance->getDatetimeStart();

        $date_end = date('Y-m-d', strtotime('last day of this month', strtotime($date_start)));

        $FuelMap = new Messerve_Model_Mapper_Fuelpurchase();

        $fuel_today = $FuelMap->fetchList(
            "invoice_date >= '{$date_start}'
				AND invoice_date < '{$date_end}'
				AND employee_id = {$employee_id}");

        $fuel_purchased = 0;
        $fuel_consumption = 0;

        foreach ($fuel_today as $ftvalue) {
            $fuel_purchased += $ftvalue->getProductQuantity();
        }

        $fuel_per_hour = $this->_config->messerve->fuelperhour;

        $fuel_consumption = $fuel_purchased - ($fuel_per_hour * $cutoff_total_duration);

        return $fuel_consumption;

    }

    protected function _get_monthly_fuel_consumption(
        Messerve_Model_Attendance $Attendance
        , $monthly_work_duration
        , $fuel_purchased = 0
    )
    {
        $employee_id = $Attendance->getEmployeeId();

        $date_start = $Attendance->getDatetimeStart();
        $date_end = date('Y-m-d', strtotime('last day of this month', strtotime($date_start)));

        $FuelMap = new Messerve_Model_Mapper_Fuelpurchase();

        $fuel_purchases = $FuelMap->fetchList(
            "invoice_date >= '{$date_start}'
				AND invoice_date < '{$date_end}'
				AND employee_id = {$employee_id}");

        $fuel_purchased = 0;
        $fuel_consumption = 0;

        foreach ($fuel_purchases as $ftvalue) {
            $fuel_purchased += $ftvalue->getProductQuantity();
        }

        $fuel_per_hour = $this->_config->messerve->fuelperhour;

        $fuel_consumption = $fuel_purchased - ($fuel_per_hour * $monthly_work_duration);

        return $fuel_consumption;

    }
}

