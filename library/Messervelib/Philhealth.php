<?php

class Messervelib_Philhealth
{
    static public function resetDeductionsForCutoff($date_start, $employee_id)
    {
        $date_start = \Carbon\Carbon::parse($date_start);

        if ($date_start->day <= 15) { // First cutoff
            $period_covered = $date_start->day(1)->toDateString();
            logger("Resetting philhealth for employee $employee_id for $period_covered ($date_start) -- First cutoff");


        } else { // Second cutoff
            $period_covered = $date_start->day(16)->toDateString();
            logger("Resetting philhealth for employee $employee_id for $period_covered ($date_start) -- Second cutoff");
        }

        return Messerve_Model_Eloquent_PayrollTemp::where('period_covered', $period_covered)
            ->where('employee_id', $employee_id)->update(['philhealth' => 0]);

    }

    static public function getPhilhealthDeductionByRiderRate(Messerve_Model_Eloquent_Employee $employee)
    {
        $notes = '';
        $group_rate = $employee->group->rate;

        return [
            'employee' => $group_rate->philhealth_employee / 2,
            'employer' => $group_rate->philhealth_employeer / 2,
            'basepay' => $group_rate->reg * 22,
            'notes' => $notes
        ];
    }

    static public function getPhilhealthDeduction($basic_pay, $date_start, $employee_id)
    {
        // Retired
        throw new Exception("The payroll system tried to use old Philhealth calculation method.");
        $date_start = \Carbon\Carbon::parse($date_start);

        $minimum_monthly_deduction = 137.50; // TODO: config this.  Or make user-editable
        $minimum_deduction = $minimum_monthly_deduction / 2;

        $notes = 'OK';

        if ($date_start->day <= 15) { // First cutoff
            $period_covered = $date_start->day(1)->toDateString();

            logger("Processing employee $employee_id for $period_covered ($date_start) -- First cutoff");

            $employee_share = $minimum_deduction;
            $employer_share = $employee_share;

        } else { // Second cutoff
            $period_covered = $date_start->day(16)->toDateString();

            logger("Processing employee $employee_id for $period_covered ($date_start) -- Second cutoff, getting previous deduction");

            $previous_period = $date_start->day(1)->toDateString();

            $philhealth = Messerve_Model_Eloquent_PayrollTemp::where('period_covered', $previous_period)
                //->where('group_id', $group_id) // Because riders are shuffling groups/branches
                ->where('employee_id', $employee_id)
                ->get();

            $previous_basic = 0;

            if ($philhealth->count() > 0) { // TODO: Fix this, maybe.
                logger("-- found previous deduction/s");

                foreach ($philhealth as $value) {
                    logger("-- found previous deduction (deduction/basic) {$value["philhealth"]} / {$value["philhealth_basic"]}");
                    $previous_basic += $value["philhealth_basic"];
                }

                $monthly_pay = $basic_pay + $previous_basic; // TODO:  make sure you get ALL the basic pay for current cutoff

                if ($monthly_pay >= 14006.75) { // TODO: config these threshold
                    logger("-- Monthly pay >= 537/day $monthly_pay");
                    $total_monthly_share = 192.59;
                } elseif ($monthly_pay >= 10433.33) {
                    logger("-- Monthly pay >= 400/day $monthly_pay");
                    $total_monthly_share = 143.46;
                } elseif ($monthly_pay >= 10329.00) {
                    logger("-- Monthly pay >= 396/day $monthly_pay");
                    $total_monthly_share = 142.02;
                } else {
                    logger("-- Monthly pay below threshold; assuming minimum $monthly_pay");
                    $total_monthly_share = $minimum_monthly_deduction;
                }

                $previous_philhealth = $minimum_deduction;

                logger("-- Monthly basic $monthly_pay, Current basic $basic_pay, prev basic $previous_basic, total monthly share $total_monthly_share");

                if ($total_monthly_share > $minimum_monthly_deduction) { // TODO:  simplify, maybe move to the >= 10k control
                    $employee_share = $total_monthly_share - $previous_philhealth;
                    logger("-- Share - $total_monthly_share - $previous_philhealth; Share is $employee_share");
                } else {
                    $employee_share = $minimum_monthly_deduction - $previous_philhealth;
                    logger("-- EE/ER below threshold $total_monthly_share; doing  minimum $minimum_monthly_deduction - previous $previous_philhealth.  Share is $employee_share");
                }

            } else {
                logger("--did not find previous deduction/s, setting to minimum deduction.");

                $employee_share = $minimum_monthly_deduction;
            }

            if ($employee_share < 0) {
                $employee_share = 0;
            }

            $employer_share = $employee_share;

        }

        return ['employee' => $employee_share, 'employer' => $employer_share, 'basepay' => $basic_pay, 'notes' => $notes];

    }
}