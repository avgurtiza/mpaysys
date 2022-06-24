<?php


namespace Domains\Payroll\Actions;


use Carbon\Carbon;

class GetWageIncreaseScheduleAction
{
    public function __invoke(int $group_id, Carbon $date)
    {
        if($date->day == 1) {
            $endDate = $date->copy()->addDays(14);
        } else {
            $endDate = $date->copy()->endOfMonth();
        }

        return \Messerve_Model_Eloquent_RateSchedule::query()
            ->where("group_id", $group_id)
            ->where("date_active", ">=", $date->toDateString())
            ->where("date_active", "<=", $endDate->toDateString())
            ->first()
        ;

    }

}