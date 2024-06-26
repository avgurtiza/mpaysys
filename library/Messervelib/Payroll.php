<?php

use Carbon\Carbon;
use Domains\Holiday\Actions\GetHolidayFromService;
use Messerve_Model_Eloquent_FloatingAttendance as Floating;

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

    private function legalHolidayViability($EloquentEmployee, $holiday_date, $restday_date)
    {
        $legal_unattended_group = $this->groupWithAttendanceOnDay($EloquentEmployee->id, $holiday_date);

        $legal_unattended_viable = false;

        if (!($legal_unattended_group > 0)) {
            // No duty on the holiday?  Let's check if it's a rest day
            /** @var $EloquentEmployee Messerve_Model_Eloquent_Employee */

            if ($attendance_group = $this->groupWithAttendanceOnDay($EloquentEmployee->id, $restday_date)) { // Has duty on the restday date
                $legal_unattended_group = $attendance_group;

                logger(sprintf("%s qualified for %s on group %s because of duty on %s (rest day date)", $EloquentEmployee->name, $holiday_date, $legal_unattended_group, $restday_date));
            } elseif ($EloquentEmployee->restDays()->where('date', $restday_date)->first()) { // Has restday on the restday date
                logger(sprintf("Found rest day on %s for %s", $restday_date, $EloquentEmployee->name));

                $before_restday = (Carbon::parse($restday_date))->subDay(1)->toDateString();

                // Let's check if they had duty on the day before the rest day to finally qualify
                if ($attendance_group = $this->groupWithAttendanceOnDay($EloquentEmployee->id, $before_restday)) {
                    $legal_unattended_group = $attendance_group;

                    logger(sprintf("%s qualified for legal unattended for %s because of duty in group  %s on %s and rest day on %s", $EloquentEmployee->name, $holiday_date, $legal_unattended_group, $before_restday, $restday_date));
                }
            } else {
                logger(sprintf("No rest day on the %s for %s; not viable for unattended legal holiday pay.", $restday_date, $EloquentEmployee->name));
            }
        }

        if ($legal_unattended_group > 0) {
            $legal_unattended_viable = true;
            // $legal_unattended_group = $EloquentEmployee->group_id; // !!!!!!! This resets the group to the parent group!
            // Disabled as per Sally's instructions -- Slide 2021-05-20

            logger(sprintf("%s qualified for legal unattended %s, setting to group  %s", $EloquentEmployee->name, $holiday_date, $legal_unattended_group));
        } else {
            logger(sprintf("%s did not qualify for %s", $EloquentEmployee->name, $holiday_date));
        }

        return (object)[
            'legal_unattended_viable' => $legal_unattended_viable,
            'legal_unattended_group' => $legal_unattended_group,
        ];
    }

    /**
     * @throws Zend_Exception
     * @throws Zend_Config_Exception
     * @throws Zend_Http_Client_Exception
     */
    public function save_the_day($employee_id, $group_id, $data)
    {
        $Employee = new Messerve_Model_Employee();

        $Employee->find($employee_id);

        $EloquentEmployee = $Employee->eloquent();

        $Group = new Messerve_Model_Group();
        $Group->find($group_id);


        if ($Group->getRoundOff10() === 'yes') {
            $this->_round_to_ten_minutes = true;
        }

        $cutoff_total_duration = 0;

        foreach ($data as $date => $attendance) {
            if (!isset($attendance['id']) || !($attendance['id'] > 0)) continue;

            if (isset($attendance['model'])) {
                $Attendance = $attendance['model'];
            } else {
                $Attendance = new Messerve_Model_Attendance();
                $Attendance->find($attendance['id']);
            }

            $holiday_today = false;
            $holiday_tomorrow = false; // TODO check this, might be deprecated.

            $holidays = $this->_fetch_holidays($group_id, $date);

            if ($holidays) {
                if ($holidays['today']) $holiday_today = $holidays['today'];
                if ($holidays['tomorrow']) $holiday_tomorrow = $holidays['tomorrow'];
            }

            $this->_date = $date;

            $today_d = date('d', strtotime($date));

            $unix_date = strtotime($date);

            if (!isset($rate_date_start)) {
                /*
                $rate_date_start is used to set the pay period.  Salary periods start at either  the 1st or the 16th and
                the lines after this block set the proper one.  Defaulting to the first period of month so code editors
                won't complain of it being unset.
                */
                $rate_date_start = date('Y-m-01', $unix_date);
                $rate_date_end = date('Y-m-15', $unix_date);
            }

            if ($today_d == '1' || $today_d == '16') {
                $first_day = $Attendance;

                // Day 1 or 16? process deductions

                if (date('d', $unix_date) == '1') {
                    $cutoff = '1';
                    $rate_date_start = date('Y-m-01', $unix_date);
                    $rate_date_end = date('Y-m-15', $unix_date);
                } else {
                    $cutoff = '2';
                    $rate_date_start = date('Y-m-16', $unix_date);
                    $rate_date_end = date('Y-m-d', strtotime("last day of this month", $unix_date));
                }

                $this->_get_rates($group_id, $rate_date_start, $rate_date_end);

                $DeductAttendMap = new Messerve_Model_Mapper_DeductionAttendance();

                // RESET! Delete all employee deductions for this payroll period
                $DeductAttendMap->getDbTable()->delete('attendance_id = ' . $attendance['id']);

                // Is this the employee's home group? If it is, process deductions and BOP
                if ($Employee->getGroupId() == $group_id) {
                    $this->_process_deductions($attendance['id'], $employee_id, $cutoff);
                    $BOP = $this->_process_bop($attendance['id'], $Employee, $cutoff, $date);
                }
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
                ->setEmployeeId($employee_id)
                ->setEmployeeNumber($Employee->getEmployeeNumber());


            if (isset($attendance['ot_approved']) && $attendance['ot_approved'] === 'yes') {
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

            $time_array = [];

            // Determine employee and client rates for today and tomorrow
            $rates_today = $this->_get_rate_today($date);

            $date_tomorrow = date('Y-m-d 00:00:00', strtotime('+1 day', strtotime($date)));

            $rates_tomorrow = $this->_get_rate_today($date_tomorrow);

            $stacked_holiday_multiplier = 1; // For stacked holidays

            if (!($attendance['start_1'] > 0)) { // Work on records with no start_1 times (no duty day)
                // TODO: Mod to accommodate 12MN start dates

                if ($holiday_today && $holiday_today->getType() === 'legal') { // Unattended legal holiday
                    $legal_unattended_viable = false;


                    // Check Magistrate for holiday handling
                    if($holiday_data = (new GetHolidayFromService($this->_config->magistrate->api->base_url))($date)) {
                        $legal_holiday_viability = $this->legalHolidayViability($EloquentEmployee, $date, $holiday_data->rest_day);
                        $legal_unattended_group = $legal_holiday_viability->legal_unattended_group;
                        $legal_unattended_viable = $legal_holiday_viability->legal_unattended_viable;
                    }


                    logger("######## Holiday data");
                    logger(print_r($holiday_data, true));

//                    if ($date === '2024-03-28') { // MTH 2024
//
//                        $legal_holiday_viability = $this->legalHolidayViability($EloquentEmployee, $date, '2024-03-27');
//                        $legal_unattended_group = $legal_holiday_viability->legal_unattended_group;
//                        $legal_unattended_viable = $legal_holiday_viability->legal_unattended_viable;
//
//                    }
//
//                    if ($date === '2024-03-29') { // GF 2024
//
//                        $legal_holiday_viability = $this->legalHolidayViability($EloquentEmployee, $date, '2024-03-27');
//                        $legal_unattended_group = $legal_holiday_viability->legal_unattended_group;
//                        $legal_unattended_viable = $legal_holiday_viability->legal_unattended_viable;
//
//                        if (!$legal_unattended_viable) { // Not viable? Check if there was duty yesterday (MTH)
//                            if ($attendance_group = $this->groupWithAttendanceOnDay($EloquentEmployee->id, '2024-03-28')) { // Has duty on MTH
//                                $legal_unattended_group = $attendance_group;
//                                $legal_unattended_viable = true;
//                                logger(sprintf("%s qualified for %s on group %s because of duty on %s (MTH)", $EloquentEmployee->name, "2024-03-29", $legal_unattended_group, "2024-03-28"));
//                            }
//                        }
//                    }

                    if ($date === '2024-04-09') { // ANK 2024

                        $legal_holiday_viability = $this->legalHolidayViability($EloquentEmployee, $date, '2024-04-08');
                        $legal_unattended_group = $legal_holiday_viability->legal_unattended_group;
                        $legal_unattended_viable = $legal_holiday_viability->legal_unattended_viable;

                    }

                    if ($date === '2024-04-10') { // EIDL FIT'R 2024

                        $legal_holiday_viability = $this->legalHolidayViability($EloquentEmployee, $date, '2024-04-08');
                        $legal_unattended_group = $legal_holiday_viability->legal_unattended_group;
                        $legal_unattended_viable = $legal_holiday_viability->legal_unattended_viable;

                        if (!$legal_unattended_viable) { // Not viable? Check if there was duty yesterday
                            if ($attendance_group = $this->groupWithAttendanceOnDay($EloquentEmployee->id, '2024-04-09')) {
                                $legal_unattended_group = $attendance_group;
                                $legal_unattended_viable = true;
                                logger(sprintf("%s qualified for %s on group %s because of duty on %s", $EloquentEmployee->name, "2024-04-10", $legal_unattended_group, "2024-04-09"));
                            }
                        }
                    }

                    if (!$legal_unattended_viable) {
                        // Had attendance yesterday
                        $date_yesterday = date('Y-m-d 00:00:00', strtotime('-1 day', strtotime($date)));

                        $AttendanceY = (new Messerve_Model_Attendance())->getMapper()->findByField(
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
                    }

                    // Check if there is legal holiday attendance on other groups
                    $AttendanceN = (new Messerve_Model_Attendance())->getMapper()->findByField(
                        array('employee_id', 'datetime_start'),
                        array($employee_id, $date)
                    );

                    foreach ($AttendanceN as $avalue) {
                        if ($avalue->getStart1() > 0) {
                            // Employee has legal holiday attendance, no longer qualified for unattended legal holiday pay
                            logger(sprintf('%s not LU viable, has attendance: %s', $EloquentEmployee->name, json_encode($avalue->toArray())));
                            $legal_unattended_viable = false;
                            break;
                        }
                    }

                    $PayrollToday = new Messerve_Model_AttendancePayroll();

                    // TODO replace with eloquent firstOrCreate
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

                    if ($legal_unattended_viable) {
                        // Look for attendance on this day on non-mother groups and reset it
                        $PayrollEloquent = Messerve_Model_Eloquent_AttendancePayroll::where('date', $date)
                            ->where('group_id', '<>', $group_id)
                            ->where('employee_id', $employee_id);

                        if ($PayrollEloquent->count() > 0) {
                            logger("Found payroll records for $date on non-mother groups.  Resetting it now...");

                            $PayrollEloquent->update([
                                'rate_id' => $rates_today['employee']['rate']['id'],
                                'client_rate_id' => $rates_today['client']['rate']['id'],
                                'holiday_type' => '',
                                'period_start' => $rate_date_start,

                                'reg_hours' => 0,
                                'reg_pay' => 0,
                                'ot_hours' => 0,
                                'ot_pay' => 0,
                                'nd_hours' => 0,
                                'nd_pay' => 0,
                                'nd_ot_hours' => 0,
                                'nd_ot_pay' => 0,
                                'date_processed' => \Carbon\Carbon::now()->toDateTimeString()
                            ]);
                        }

                        if ($legal_unattended_group == $group_id) {
                            logger(sprintf('Writing payroll record for %s on %s', $EloquentEmployee->name, $date));
                            // TODO:  Moon prism power clean up.
                            // Apply legal unattended pay

                            $PayrollToday
                                ->setRateData(json_encode($rates_today))
                                ->setGroupId($legal_unattended_group)
                                ->setHolidayType("Legal unattended")
                                ->setRegHours($this->_max_regular_hours)
                                ->setRegPay(
                                    $this->_max_regular_hours *
                                    $rates_today['employee']['rate']['legal_unattend'] *
                                    $stacked_holiday_multiplier
                                ); // TODO move to its own class or method

                            $time_array['legal_unattend'] = $this->_max_regular_hours;
                        } else {
                            $time_array['legal_unattend'] = 0;
                        }

                        logger(sprintf('Done dealing with legal holidays for %s on %s', $EloquentEmployee->name, $date));
                    }

                    if ($employee_id > 0 && $attendance['id'] > 0) { // TODO: Fix hacky hack
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
                }

                continue; // Today has no attendance and I'm done processing unattended legal holiday. Move to the next day.
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

            if ($end_1 > $start_2) { // First shift crossed midnight, assuming the rest of the attendance is on the second day
                $date = date('Y-m-d', strtotime('+1 day', strtotime($date)));
                $start_2 = strtotime($date . ' T' . str_pad($attendance['start_2'], 4, 0, STR_PAD_LEFT));
                $end_2 = isset($attendance['end_2']) && $attendance['end_2'] != '' ? strtotime($date . ' T' . str_pad($attendance['end_2'], 4, 0, STR_PAD_LEFT)) : 0;
            } elseif ($end_2 < $start_2) { // Second shift crossed midnight
                $date = date('Y-m-d', strtotime('+1 day', strtotime($date)));
                $end_2 = isset($attendance['end_2']) && $attendance['end_2'] != '' ? strtotime($date . ' T' . str_pad($attendance['end_2'], 4, 0, STR_PAD_LEFT)) : 0;
            }

            $start_3 = isset($attendance['start_3']) && $attendance['start_3'] != '' ? strtotime($date . ' T' . str_pad($attendance['start_3'], 4, 0, STR_PAD_LEFT)) : 0;
            $end_3 = isset($attendance['end_3']) && $attendance['end_3'] != '' ? strtotime($date . ' T' . str_pad($attendance['end_3'], 4, 0, STR_PAD_LEFT)) : 0;

            if ($end_1 > $start_2) { // First shift crossed midnight, assuming the rest of the attendance is on the second day
                $date = date('Y-m-d', strtotime('+1 day', strtotime($date)));
                $start_3 = strtotime($date . ' T' . str_pad($attendance['start_3'], 4, 0, STR_PAD_LEFT));
                $end_3 = isset($attendance['end_3']) && $attendance['end_3'] != '' ? strtotime($date . ' T' . str_pad($attendance['end_3'], 4, 0, STR_PAD_LEFT)) : 0;
            } elseif ($end_3 < $start_3) { // Third shift crossed midnight
                $date = date('Y-m-d', strtotime('+1 day', strtotime($date)));
                $end_3 = isset($attendance['end_3']) && $attendance['end_3'] != '' ? strtotime($date . ' T' . str_pad($attendance['end_3'], 4, 0, STR_PAD_LEFT)) : 0;
            }

            // $weekday = date('D', $start_1);
            // $weekday_midnight = date('D', $midnight);

            // $total_duration = 0;

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

            if (isset($attendance['ot_approved']) && $attendance['ot_approved'] === 'yes') {
                $ot_duration = $work_duration - $this->_max_regular_hours;
                $ot_approved = true;
            }

            if (isset($attendance['extended_shift']) && $attendance['extended_shift'] === 'yes') {
                $ot_duration = $work_duration - $this->_max_regular_hours;
                $has_extended_shift = true;
            }

            if (!$ot_approved && !$has_extended_shift && $work_duration > $this->_max_regular_hours) {
                $work_duration = $this->_max_regular_hours;
                $ot_duration = 0;
            }

            $ot_start = 0;
            $ot_start_period = 0;

            if ($ot_duration > 0) {
                /* When does OT start? */
                if ($work_duration >= $this->_max_regular_hours) {

                    if ($duration_1 >= $this->_max_regular_hours) { // OT starts in D1

                        $ot_start = $start_1 + ($this->_max_regular_hours * 3600);
                        $ot_start_period = 0;

                    } elseif (($duration_1 + $duration_2) >= $this->_max_regular_hours) { // OT starts in D1

                        $ot_start = $start_1 + $break_duration_1 + ($this->_max_regular_hours * 3600);
                        $ot_start_period = 1;

                    } elseif (($duration_1 + $duration_2 + $duration_3) >= $this->_max_regular_hours) { // OT starts in D3

                        $ot_start = $end_3 - ($work_duration - ($this->_max_regular_hours * 3600));
                        $ot_start_period = 2;

                    }
                }
            } else { // Just in case negatives turn up
                $ot_duration = 0;
            }


            $this->_ot_start = $ot_start;

            if ($duration_1 > 0) {
                $d1_attendance = $this->_break_it_down($start_1, $end_1, 1, $attendance);
                $time_array = array_merge($time_array, $d1_attendance);
            }

            if ($duration_2 > 0) {
                $d2_attendance = $this->_break_it_down($start_2, $end_2, 2, $attendance);
                $time_array = array_merge($time_array, $d2_attendance);
            }


            if ($duration_3 > 0) {
                $d3_attendance = $this->_break_it_down($start_3, $end_3, 3, $attendance);
                $time_array = array_merge($time_array, $d3_attendance);
            }


            $reg = 0;
            $nd = 0;
            $ot = 0;
            $nd_ot = 0;

            $tomorrow = 0;
            $tomorrow_nd = 0;
            $tomorrow_ot = 0;
            $tomorrow_nd_ot = 0;

            if ($ot_duration > 0) {
                $ot_balance = round($ot_duration, 2);

                for ($i = 2; $i >= 0; $i--) {

                    if (isset($time_array[$i])) {

                        /*
                        * TODO:  For OT with shifts starting before 6AM
                        if ((int)date('H', strtotime($start_1)) < 6) { // If shift starts before 6AM (ND cutoff), do today's ND first
                        } else {
                        }
                        */

                        if (isset($time_array[$i]['tomorrow']) && $time_array[$i]['tomorrow'] > 0) {
                            if ($ot_start_period <= $i) {
                                //// echo "<br>{$Attendance->id} $ot_start_period : $i  Doing T OTBAL $ot_balance<br>";

                                if (($time_array[$i]['tomorrow'] - $ot_balance) >= 0) {
                                    // Can split reg and ot;

                                    $tomorrow += bcsub($time_array[$i]['tomorrow'], $ot_balance, 2);
                                    $tomorrow_ot += $ot_balance;
                                    $ot_balance = 0;
                                } elseif ($ot_balance > 0) {
                                    // Can't split reg and ot

                                    $ot_balance -= $time_array[$i]['tomorrow'];
                                    $tomorrow_ot += $time_array[$i]['tomorrow'];
                                }
                            } else {
                                $tomorrow += $time_array[$i]['tomorrow'];
                            }

                        }


                        if (isset($time_array[$i]['tomorrow_nd']) && $time_array[$i]['tomorrow_nd'] > 0) {
                            if ($ot_start_period <= $i) {
                                // Doing TND OTBAL
                                if (($time_array[$i]['tomorrow_nd'] - $ot_balance) > 0) {

                                    $tomorrow_nd += bcsub($time_array[$i]['tomorrow_nd'], $ot_balance, 2);
                                    $tomorrow_nd_ot += $ot_balance;

                                    //  Can split reg and ot
                                    $ot_balance = 0;

                                } elseif ($ot_balance > 0) {
                                    $ot_balance -= $time_array[$i]['tomorrow_nd'];
                                    $tomorrow_nd_ot += $time_array[$i]['tomorrow_nd'];

                                    // Can't split reg and ot TND
                                }
                            } else {
                                $tomorrow_nd += $time_array[$i]['tomorrow_nd'];
                            }

                        }


                        if (isset($time_array[$i]['today_nd']) && $time_array[$i]['today_nd'] > 0) {

                            if ($ot_start_period <= $i) {
                                // Doing ND OTBAL ;

                                if (($time_array[$i]['today_nd'] - $ot_balance) >= 0) {
                                    // Can split reg and ot

                                    $nd += bcsub($time_array[$i]['today_nd'], $ot_balance, 2);

                                    $nd_ot += $ot_balance;
                                    $ot_balance = 0;
                                } elseif ($ot_balance > 0) {
                                    $ot_balance -= $time_array[$i]['today_nd'];
                                    $nd_ot += $time_array[$i]['today_nd'];
                                }
                            } else {
                                $nd += $time_array[$i]['today_nd'];
                            }

                        }

                        if (isset($time_array[$i]['today']) && $time_array[$i]['today'] > 0) {

                            if ($ot_start_period <= $i) {
                                // echo "<br>{$Attendance->id}  $ot_start_period : $i Doing REG OTBAL $ot_balance<br>";

                                if (($time_array[$i]['today'] - $ot_balance) >= 0) {

                                    $reg += bcsub($time_array[$i]['today'], $ot_balance, 2);
                                    // echo "Can split reg and ot {$time_array[$i]['today']} <br>";

                                    // echo ($time_array[$i]['today'] - $ot_balance) . "<br>";

                                    $ot += $ot_balance;
                                    // echo "REG $reg OT $ot $ot_balance<br>";

                                    $ot_balance = 0;


                                } elseif ($ot_balance > 0) {
                                    $ot_balance -= $time_array[$i]['today'];
                                    $ot += $time_array[$i]['today'];
                                }
                            } else {
                                $reg += $time_array[$i]['today'];
                            }

                        }

                    }
                }
            } else {
                // echo "<br>L " . __LINE__ . " NO OT <br>";

                $reg_balance = $this->_max_regular_hours;

                // echo "<br> Start1 : {$attendance['start_1']} $reg";

                for ($i = 2; $i >= 0; $i--) {


                    if ((int)$attendance['start_1'] < 600) { // If shift starts before 6AM (ND cutoff), do today's ND first
                        // echo "<br> Shift $i Pre-6AM";
                        if (isset($time_array[$i]['today_nd']) && $reg_balance > 0) {
                            if (($reg_balance - $time_array[$i]['today_nd']) >= 0) {
                                $nd += $time_array[$i]['today_nd'];
                                $reg_balance -= $time_array[$i]['today_nd'];
                            } else {
                                $nd += $reg_balance;
                                $reg_balance = 0;
                            }

                            if (isset($time_array[$i]['today'])) {
                                if (($reg_balance - $time_array[$i]['today']) >= 0) {
                                    $reg += $time_array[$i]['today'];
                                    $reg_balance -= $time_array[$i]['today'];
                                } else {
                                    $reg += $reg_balance;
                                    $reg_balance = 0;
                                }

                            }
                        }
                    } else {
                        // echo "<br> Shift $i Post-6am";
                        // echo "<br>REG bal $reg_balance : ";

                        if (isset($time_array[$i]['today'])) {
                            if (($reg_balance - $time_array[$i]['today']) >= 0) {
                                // echo "( adding value to today)";
                                $reg += $time_array[$i]['today'];
                                $reg_balance -= $time_array[$i]['today'];
                            } else {
                                // echo "( adding balance $reg_balance to reg $reg)";
                                $reg += $reg_balance;
                                $reg_balance = 0;
                            }


                            // echo " [ Today : ";
                        }


                        if (isset($time_array[$i]['today_nd']) && $reg_balance > 0) {
                            if (($reg_balance - $time_array[$i]['today_nd']) >= 0) {
                                $nd += $time_array[$i]['today_nd'];
                                $reg_balance -= $time_array[$i]['today_nd'];

                            } else {
                                $nd += $reg_balance;
                                $reg_balance = 0;
                            }

                        }

                    }


                    if (isset($time_array[$i]['tomorrow_nd']) && $reg_balance > 0) {
                        if (($reg_balance - $time_array[$i]['tomorrow_nd']) >= 0) {
                            $tomorrow_nd += $time_array[$i]['tomorrow_nd'];
                            $reg_balance -= $time_array[$i]['tomorrow_nd'];
                        } else {
                            $tomorrow_nd += $reg_balance;
                            $reg_balance = 0;
                        }


                    }

                    if (isset($time_array[$i]['tomorrow']) && $reg_balance > 0) {
                        if (($reg_balance - $time_array[$i]['tomorrow']) >= 0) {
                            $tomorrow += $time_array[$i]['tomorrow'];
                            $reg_balance -= $time_array[$i]['tomorrow'];
                        } else {
                            $tomorrow += $reg_balance;
                            $reg_balance = 0;
                        }


                    }

                    // if (isset($time_array[$i]['today'])) echo "Total reg $reg : Duration reg {$time_array[$i]['today']} <br>";

                }
            }


            $time_array['ot_actual_hours'] = $tomorrow_ot + $tomorrow_nd_ot + $nd_ot + $ot;

            if (!$has_extended_shift && $ot_duration > $Attendance->getOtApprovedHours()) {
                $excess_ot = bcsub($ot_duration, $Attendance->getOtApprovedHours(), 2);

                if ($tomorrow_ot > 0 && $excess_ot > 0) {
                    $ot_check = $tomorrow_ot - $excess_ot;

                    if ($ot_check > 0) {
                        $excess_ot = bcsub($excess_ot, $tomorrow_ot, 2);
                        $tomorrow_ot = $ot_check;
                    } else {
                        $tomorrow_ot = 0;
                        $excess_ot = $ot_check * -1;
                    }
                }

                if ($tomorrow_nd_ot > 0 && $excess_ot > 0) {
                    $ot_check = bcsub($tomorrow_nd_ot, $excess_ot, 2);

                    if ($ot_check > 0) {
                        $excess_ot = bcsub($excess_ot, $tomorrow_nd_ot, 2);
                        $tomorrow_nd_ot = $ot_check;
                    } else {
                        $tomorrow_nd_ot = 0;
                        $excess_ot = $ot_check * -1;
                    }
                }

                if ($nd_ot > 0 && $excess_ot > 0) {
                    $ot_check = bcsub($nd_ot, $excess_ot, 2);

                    if ($ot_check > 0) {
                        $excess_ot = bcsub($excess_ot, $nd_ot, 2);
                        $nd_ot = $ot_check;
                    } else {
                        $nd_ot = 0;
                        $excess_ot = $ot_check * -1;
                    }
                }

                if ($ot > 0 && $excess_ot > 0) {
                    $ot_check = bcsub($ot, $excess_ot, 2);

                    if ($ot_check > 0) {
                        $ot = $ot_check;
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

            if ($attendance['type'] === 'rest') {
                $time_array['rest'] = $reg + $tomorrow;
                $time_array['rest_ot'] = $ot + $tomorrow_ot;
                $time_array['rest_nd'] = $nd + $tomorrow_nd;
                $time_array['rest_nd_ot'] = $nd_ot + $tomorrow_nd_ot;
            } else { // Regular
                // echo "<br>Regular today<br>";
                $time_array['reg'] = $reg + $tomorrow;
                $time_array['reg_ot'] = $ot + $tomorrow_ot;
                $time_array['reg_nd'] = $nd + $tomorrow_nd;
                $time_array['reg_nd_ot'] = $nd_ot + $tomorrow_nd_ot;

            }

            if ($holiday_today) {
                // echo "<br>Holiday today<br>";
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
                        if (!($work_duration > 0)) { // Do unattended OT calcs
                            // echo "Doing unattended <br>";
                            $time_array['legal_unattend'] = $this->_max_regular_hours;
                        } else {
                            // echo "Doing attended $ot <br>";
                            $time_array['legal'] = $reg;
                            $time_array['legal_nd'] = $nd;
                            $time_array['legal_ot'] = $ot;
                            $time_array['legal_nd_ot'] = $nd_ot;

                            $time_array['legal'] += $tomorrow;
                            $time_array['legal_ot'] += $tomorrow_ot;
                            $time_array['legal_nd'] += $tomorrow_nd;
                            $time_array['legal_nd_ot'] += $tomorrow_nd_ot;

                            // preprint($time_array);

                            // echo "<br> Not the end  $ot ";
                        }

                        break;
                    case 'special':
                        $time_array['spec'] = $reg;
                        $time_array['spec_nd'] = $nd;
                        $time_array['spec_ot'] = $ot;
                        $time_array['spec_nd_ot'] = $nd_ot;

                        $time_array['spec'] += $tomorrow;
                        $time_array['spec_nd'] += $tomorrow_nd;
                        $time_array['spec_ot'] += $tomorrow_ot;
                        $time_array['spec_nd_ot'] += $tomorrow_nd_ot;
                        break;
                    default:
                        throw new Exception('Holiday type is invalid: ' . $holiday_today->getType());
                        break;
                }
            }


            if ($Attendance->getOtApproved() !== 'yes' && !$has_extended_shift) {

                // echo __LINE__ . " Resetting OT <br>";

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
                // echo __LINE__ . " Not resetting OT <br>";
            }

            $options = $time_array;

            if ($has_extended_shift) { // TODO:  Fix hack
                $options['extended_shift'] = 'yes';
            } else {
                $options['extended_shift'] = 'no';
            }


            if ($ot_duration > 0) {

                if (!isset($attendance['approved_extended_shift']) || $attendance['approved_extended_shift'] == '') {

                    $options['approved_extended_shift'] = 'no';

                    $floating = Floating::updateOrCreate(['attendance_id' => $Attendance->getId()],
                        [
                            'reg_ot' => $time_array['reg_ot'],
                            'reg_nd_ot' => $time_array['reg_nd_ot'],

                            'spec_ot' => $time_array['spec_ot'],
                            'spec_nd_ot' => $time_array['spec_nd_ot'],

                            'rest_ot' => $time_array['rest_ot'],
                            'rest_nd_ot' => $time_array['rest_nd_ot'],

                            'legal_ot' => $time_array['legal_ot'],
                            'legal_nd_ot' => $time_array['legal_nd_ot'],
                        ]
                    );

                    /*
                    $floating->update([
                        'reg_ot' => $time_array['reg_ot'],
                        'reg_nd_ot' => $time_array['reg_nd_ot'],

                        'spec_ot' => $time_array['spec_ot'],
                        'spec_nd_ot' => $time_array['spec_nd_ot'],

                        'rest_ot' => $time_array['rest_ot'],
                        'rest_nd_ot' => $time_array['rest_nd_ot'],

                        'legal_ot' => $time_array['legal_ot'],
                        'legal_nd_ot' => $time_array['legal_nd_ot'],
                    ]);
                    */
                } else {
                    $floating = Floating::where('attendance_id', $Attendance->getId())->first();

                    if ($floating) {
                        $floating->delete();
                    }
                }
            }


            if (is_array($attendance)) {
                $options = array_merge($options, $attendance);

                $Attendance
                    ->setGroupId($group_id)
                    ->setOptions($options)
                    ->setEmployeeId($employee_id);

                $Attendance->save();

                $holiday_type_today = "Regular";

                $pay_rate_prefix = "reg";

                if ($attendance['type'] === "rest") {
                    $holiday_type_today = "Rest";
                    $pay_rate_prefix = "sun";
                }

                if ($holiday_today) {
                    $holiday_type_today = ucfirst($holiday_today->getType());
                    $pay_rate_prefix = strtolower($holiday_today->getType());
                }

                if ($pay_rate_prefix === 'special') $pay_rate_prefix = "spec";

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
                        ->setDateProcessed(date("Y-m-d H:i:s"));

                    if ($holiday_today) {
                        if ($new_date_today === '2020-04-09') { // Maundy Thursday + National Heroes day 2020
                            $stacked_holiday_multiplier = 3; // Defaulting to triple pay, assuming that rider rendered duty today
                            $pay_rate_prefix = 'reg';
                        }

                        $PayrollToday
                            ->setRegHours($options['today'] + $options['tomorrow'])
                            ->setOtHours($options['today_ot'] + $options['tomorrow_ot'])
                            ->setRegPay(($options['today'] + $options['tomorrow'])
                                * $rates_today['employee']['rate'][$pay_rate_prefix]
                                * $stacked_holiday_multiplier)
                            ->setOtPay(($options['today_ot'] + $options['tomorrow_ot'])
                                * $rates_today['employee']['rate'][$pay_rate_prefix . "_ot"]
                                * $stacked_holiday_multiplier)
                            ->setNdPay(($options['today_nd'] + $options['tomorrow_nd'])
                                * $rates_today['employee']['rate'][$pay_rate_prefix . "_nd"]
                                * $stacked_holiday_multiplier)
                            ->setNdOtPay(($options['today_nd_ot'] + $options['tomorrow_nd_ot'])
                                * $rates_today['employee']['rate'][$pay_rate_prefix . "_nd_ot"]
                                * $stacked_holiday_multiplier)
                            ->setNdHours($options['today_nd'] + $options['tomorrow_nd'] * $stacked_holiday_multiplier)
                            ->setNdOtHours($options['today_nd_ot'] + $options['tomorrow_nd_ot'] * $stacked_holiday_multiplier);
                    } else {
                        $PayrollToday
                            ->setRegHours($options['today'])
                            ->setOtHours($options['today_ot'])
                            ->setRegPay($options['today'] * $rates_today['employee']['rate'][$pay_rate_prefix])
                            ->setOtPay($options['today_ot'] * $rates_today['employee']['rate'][$pay_rate_prefix . "_ot"])
                            ->setNdPay($options['today_nd'] * $rates_today['employee']['rate'][$pay_rate_prefix . "_nd"])
                            ->setNdOtPay($options['today_nd_ot'] * $rates_today['employee']['rate'][$pay_rate_prefix . "_nd_ot"])
                            ->setNdHours($options['today_nd'])
                            ->setNdOtHours($options['today_nd_ot']);
                    }

                    $PayrollToday->save();
                }

                $holiday_type_tomorrow = "Regular";
                $pay_rate_prefix = "reg";


                if ($attendance['type'] === "rest") {
                    $holiday_type_tomorrow = $holiday_type_today;
                    $holiday_type_today = "Rest";
                    $pay_rate_prefix = "sun";
                }

                if ($holiday_today) {
                    $holiday_type_tomorrow = $holiday_type_today;
                    $pay_rate_prefix = strtolower($holiday_today->getType());
                    // echo "Holiday today <br>";
                }


                $new_date_tomorrow = date("Y-m-d", strtotime('tomorrow', strtotime($Attendance->getDatetimeStart())));

                $PayrollTomorrow = new Messerve_Model_AttendancePayroll();

                $PayrollTomorrow->getMapper()->findOneByField(
                    array("employee_id", "attendance_id", "date")
                    , array($employee_id, $Attendance->getId(), $new_date_tomorrow)
                    , $PayrollTomorrow
                );

                if ($pay_rate_prefix === 'special') $pay_rate_prefix = "spec";

                $rates_tomorrow['_prefix'] = $pay_rate_prefix;

                if ($employee_id > 0 && $Attendance->getId() > 0) {
                    // echo "Setting payroll tomorrow <br>";

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
                        ->setDateProcessed(date("Y-m-d H:i:s"));

                    if (!$holiday_today) {
                        $PayrollTomorrow
                            ->setRegHours($options['tomorrow'])
                            ->setOtHours($options['tomorrow_ot'])
                            ->setRegPay($options['tomorrow'] * $rates_tomorrow['employee']['rate'][$pay_rate_prefix])
                            ->setOtPay($options['tomorrow_ot'] * $rates_tomorrow['employee']['rate'][$pay_rate_prefix . "_ot"])
                            ->setNdPay($options['tomorrow_nd'] * $rates_tomorrow['employee']['rate'][$pay_rate_prefix . "_nd"])
                            ->setNdOtPay($options['tomorrow_nd_ot'] * $rates_tomorrow['employee']['rate'][$pay_rate_prefix . "_nd_ot"])
                            ->setNdHours($options['tomorrow_nd'])
                            ->setNdOtHours($options['tomorrow_nd_ot']);
                    } else {
                        $PayrollTomorrow
                            ->setRegHours(0)
                            ->setOtHours(0)
                            ->setRegPay(0)
                            ->setOtPay(0)
                            ->setNdPay(0)
                            ->setNdOtPay(0)
                            ->setNdHours(0)
                            ->setNdOtHours(0);
                    }

                    $PayrollTomorrow->save();
                }
            }
        }

        logger("Doing final things for {$EloquentEmployee->name}");

        if (!($cutoff_total_duration > 0)) {
            logger("{$EloquentEmployee->name} has no cutoff total duration, resetting and not doing fuel! : ");

            $reset = array('today' => 0, 'today_nd' => 0, 'today_ot' => 0, 'today_nd_ot' => 0, 'tomorrow_nd_ot' => 0
            , 'reg' => 0, 'reg_nd' => 0, 'reg_ot' => 0, 'reg_nd_ot' => 0
            , 'spec' => 0, 'spec_nd' => 0, 'spec_ot' => 0, 'spec_nd_ot' => 0
            , 'legal' => 0, 'legal_nd' => 0, 'legal_ot' => 0, 'legal_nd_ot' => 0, 'legal_unattend' => 0
            , 'rest' => 0, 'rest_nd' => 0, 'rest_ot' => 0, 'rest_nd_ot' => 0);

        } else {
            logger("Has cutoff total duration, doing stuff! : {$EloquentEmployee->name}");

            if ($cutoff === '2') {
                logger('Cut-off is 2.');

                if ($Employee->getGroupId() == $group_id) { // Parent group?  Process fuel calcs
                    $first_day
                        ->setFuelHours(0)
                        ->setFuelAlloted(0)
                        ->setFuelConsumed(0)
                        ->setFuelOverage(0)
                        ->save();  // Reset fuel consumption

                    if ($Employee->getGascard() > 0 || $Employee->getGascard2() > 0 || $Employee->getGascard3() > 0) {
                        // DO OIL EMPIRE SPLIT?

                        // Get previous cutoff duration!
                        $prev_date_start = date('Y-m-28', strtotime('last month', strtotime($first_day->getDatetimeStart())));
                        $prev_date_end = date('Y-m-27', strtotime($first_day->getDatetimeStart()));

                        if (strtotime($Employee->getBopStart()) > strtotime($prev_date_start)) {
                            $prev_date_start = $Employee->getBopStart();
                        }

                        $monthly_work_duration = $this->_get_work_duration($employee_id, 0, $prev_date_start, $prev_date_end);

                        $fuel_allotted = $Group->getFuelperhour() * $monthly_work_duration;

                        $fuel_purchased = $this->_get_monthly_fuel_purchase($first_day, $monthly_work_duration, $prev_date_start, $prev_date_end);

                        $fuel_consumption = $fuel_purchased - $fuel_allotted;

                        $first_day
                            ->setFuelHours($monthly_work_duration)
                            ->setFuelAlloted($fuel_allotted)
                            ->setFuelConsumed($fuel_purchased)
                            ->setFuelOverage($fuel_consumption)
                            ->save();

                        logger("Fuel for: {$Employee->getFirstname()} {$Employee->getLastname()}");
                    } else {
                        logger("NO GASCARD FOR: " . $Employee->getId());
                    }
                } else {
                    logger("Not in parent group! {$Employee->getFirstname()} {$Employee->getLastname()}");
                }
            } else {
                logger("Un2 cuttoff! not doing fuel... {$Employee->getFirstname()} {$Employee->getLastname()}");
            }
        }

        $this->_clean_up_holidays($Employee, $rate_date_start, $group_id);
    }

    protected function groupWithAttendanceOnDay($employee_id, $date)
    {
        $attendance = (new Messerve_Model_Attendance())->getMapper()->findByField(
            array('employee_id', 'datetime_start')
            , array($employee_id, $date)
        );

        foreach ($attendance as $avalue) {
            if ($avalue->getStart1() > 0) {
                // Day before attendance gets priority for billing group
                return $avalue->getGroupId();
            }
        }

        return 0;

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
            //// echo "<br /> $attendance_id HAS DEDUCTIONS";
            foreach ($deductions as $dvalue) {
                // Total all deductions,  balance matches max deduction value + this deduction?

                $select = $DeductAttendMap->getDbTable()->select();

                $select
                    ->from('deduction_attendance', array('mysum' => 'SUM(amount)'))
                    ->where('deduction_schedule_id = ?', $dvalue->getId());

                $this_sum = $DeductAttendMap->getDbTable()->fetchRow($select)->mysum;
                //// echo "BALANCE:  $this_sum";

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

            //// echo "<br />Duration: $duration_id ";

            if ($start <= $this->_night_diff_start) {
                $today = $this->_night_diff_start - $start;
                if ($today > 0) $broken_array['today'] += $today;

                $today_nd = $end - $this->_night_diff_start;

                if ($today_nd > 0) $broken_array['today_nd'] += $today_nd;

            } else {
                //// echo "<br /> ND :B ";
                $today = $start - $this->_night_diff_start;
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
            //// echo "<br> Early ND";
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
            //// echo "<br />End beyond MN";
            if ($end > $this->_night_diff_end) { // Breached next day's 6AM
                //// echo "<br /> Duration $duration_id ND end breach";

                if ($start > $this->_midnight) {
                    //// echo "<br />Start after MN";

                    $tomorrow_nd = $this->_night_diff_end - $start;
                    $broken_array['tomorrow_nd'] += $tomorrow_nd;
                } else {
                    //// echo "<br />Start before MN";
                    $today_nd = $this->_midnight - $start;
                    if ($today_nd > 0) $broken_array['today_nd'] += $today_nd;

                    $tomorrow_nd = $this->_night_diff_end - $this->_midnight;
                    $broken_array['tomorrow_nd'] += $tomorrow_nd;
                }

                $tomorrow = $end - $this->_night_diff_end;
                if ($tomorrow > 0) $broken_array['tomorrow'] += $tomorrow;

            } else {
                //// echo "<br /> Duration $duration_id ND end safe";
                if ($start >= $this->_midnight) {
                    //// echo "<br />Start after MN";
                    $tomorrow_nd = $end - $start;
                    if ($tomorrow_nd > 0) $broken_array['tomorrow_nd'] += $tomorrow_nd;
                } else {
                    //// echo "<br />Start before MN - ";

                    $tomorrow_nd = $end - $this->_midnight;
                    if ($tomorrow_nd > 0) $broken_array['tomorrow_nd'] += $tomorrow_nd;

                    if ($start >= $this->_night_diff_start) {
                        //// echo " after ND";
                        $today_nd = $this->_midnight - $start;
                        if ($today_nd > 0) $broken_array['today_nd'] += $today_nd;


                    } else { // $start less than nd
                        //// echo " before ND";
                        $today = $this->_night_diff_start - $start;
                        if ($today > 0) $broken_array['today'] += $today;

                        $today_nd = $this->_midnight - $this->_night_diff_start;
                        if ($today_nd > 0) $broken_array['today_nd'] += $today_nd;
                    }
                }
            }
        }


        $broken_array = array_map(function ($x) {
            return round($x / 3600, 2);
        }, $broken_array);


        return array($duration_id => $broken_array);
    }

    protected function _get_monthly_fuel_purchase(
        Messerve_Model_Attendance $Attendance
        ,                         $monthly_work_duration, $date_start, $date_end
    )
    {
        $employee_id = $Attendance->getEmployeeId();

        // $date_start = $Attendance->getDatetimeStart();
        // $date_end = date('Y-m-d', strtotime('last day of this month', strtotime($date_start)));

        $FuelMap = new Messerve_Model_Mapper_Fuelpurchase();

        $fuel_purchases = $FuelMap->fetchList("invoice_date >= '{$date_start}' 
            AND invoice_date <= '{$date_end} 23:59' 
            AND employee_id = {$employee_id}
            AND gascard_type IS NOT NULL"
        ); // Added 2019-06-05 to fix duplicate fuel records

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
            AND period_start = '$period_start'
            AND attendance_id > 0
        ");

        $Employee = Messerve_Model_Eloquent_Employee::find($employee_id);
        $Group = Messerve_Model_Eloquent_Group::find($group_id);

        $rate_meta = [];

        if ($Group && $Group->rate) {
            $rate_meta = ['employee' => ['rate' => $Group->rate->toArray()]];
        }

        $payroll = [];

        foreach ($payroll_raw as $pvalue) { // TODO:  Construct array properly

            if (!isset($payroll[$pvalue->getRateId()])) {
                $payroll[$pvalue->getRateId()] = [];
            }
            if ($pvalue->getRateData() != '') {
                $payroll[$pvalue->getRateId()]['meta'] = json_decode($pvalue->getRateData());
            } else {

                if (!isset($payroll[$pvalue->getRateId()]['meta'])) {
                    logger(sprintf("Did not find rate metadata from AttendancePayroll record for %s (%s).  Defaulted to group rate for %s",
                        $Employee->name, $Employee->id, $Group->full_name
                    ), 'warn');

                    $payroll[$pvalue->getRateId()]['meta'] = json_decode(json_encode($rate_meta));
                }

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
        ,                         $monthly_work_duration
        ,                         $fuel_purchased = 0
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

