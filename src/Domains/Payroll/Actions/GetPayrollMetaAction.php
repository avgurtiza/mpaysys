<?php


namespace Domains\Payroll\Actions;


use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class GetPayrollMetaAction
{
    public function __invoke(\Messerve_Model_PayrollTemp $data, bool $return_array = true)
    {
        // Is there a scheduled wage increase for this period?
        $schedule = (new GetWageIncreaseScheduleAction())($data->getGroupId(), Carbon::parse($data->getPeriodCovered()));

        if (!$schedule) {
            // No wage increase, return as-is
            return json_decode($data->getPayrollMeta(), $return_array);
        }

        $employee = \Messerve_Model_Eloquent_Employee::find($data->getEmployeeId());

        $hours_meta = $this->buildHoursMeta($employee->attendancePayroll);

        return (["rate_data" => json_encode("")] + $hours_meta);

    }

    private function buildHoursMeta(Collection $collection): array
    {
        $payroll = [];

        foreach ($collection as  $pvalue) {
            if ($pvalue->reg_hours > 0) {
                @$payroll[$pvalue->holiday_type]['reg']['hours'] += $pvalue->reg_hours;
                @$payroll[$pvalue->holiday_type]['reg']['pay'] += $pvalue->reg_pay;
            }

            if ($pvalue->ot_hours> 0) {
                @$payroll[$pvalue->holiday_type]['ot']['hours'] += $pvalue->ot_hours;
                @$payroll[$pvalue->holiday_type]['ot']['pay'] += $pvalue->ot_pay;
            }

            if ($pvalue->nd_hours > 0) {
                @$payroll[$pvalue->holiday_type]['nd']['hours'] += $pvalue->nd_hours;
                @$payroll[$pvalue->holiday_type]['nd']['pay'] += $pvalue->nd_pay;
            }

            if ($pvalue->nd_ot_hours > 0) {
                @$payroll[$pvalue->holiday_type]['nd_ot']['hours'] += $pvalue->nd_ot_hours;
                @$payroll[$pvalue->holiday_type]['nd_ot']['pay'] += $pvalue->nd_ot_pay;
            }
        }

        return $payroll;

    }

}