<?php
use Illuminate\Database\Eloquent\Model as Eloquent;

class Messerve_Model_Eloquent_DtrAnomaly extends Eloquent
{
    protected $table = 'dtr_anomaly';

    protected $fillable = ['employee_id', 'group_id', 'pay_period', 'date_start', 'date_end', 'is_approved'];

    static public function employeeGroupPeriod(Messerve_Model_Eloquent_Attendance $attendance, $period)
    {
        return self::firstOrCreate([
                'employee_id' => $attendance->employee_id,
                'group_id' => $attendance->group_id,
                'pay_period' => $period,
                'date_start' => $attendance->datetime_start,
                'date_end' => $attendance->datetime_start,
            ]
        );
    }

    public function employee()
    {
        return $this->belongsTo(Messerve_Model_Eloquent_Employee::class, 'employee_id', 'id');
    }

    public function group()
    {
        return $this->belongsTo(Messerve_Model_Eloquent_Group::class, 'group_id', 'id');
    }

    static public function payPeriod($period) {
        return self::where('pay_period', $period)->orderBy('group_id')->orderBy('employee_id')->get();
    }
}