<?php
use Illuminate\Database\Eloquent\Model as Eloquent;

class Messerve_Model_Eloquent_Employee extends Eloquent
{
    protected $table = 'employee';

    protected $fillable = [
    ];

    public function group()
    {
        return $this->belongsTo(Messerve_Model_Eloquent_Group::class, 'group_id', 'id');
    }

    public function rate()
    {
        return $this->belongsTo(Messerve_Model_Eloquent_Rate::class, 'rate_id', 'id');
    }

    public function basic_pay()
    {
        $rate = $this->rate->reg;
    }

    public function attendance()
    {
        return $this->hasMany(Messerve_Model_Eloquent_Attendance::class, 'employee_id', 'id');
    }

    public function motherGroupAttendanceByDate($date)
    {
        return $this->attendance()
            ->where('datetime_start', $date . ' 00:00:00')
            ->where('group_id', $this->group_id)
            ->first();
    }

    public function bop_payments()
    {
        return $this->hasManyThrough(Messerve_Model_Eloquent_BopAttendance::class, Messerve_Model_Eloquent_Attendance::class, 'employee_id', 'attendance_id');
    }

    public function getNameAttribute()
    {
        return $this->firstname . ' ' . $this->lastname;
    }

    public static function findByEmployeeNumber($employee_number)
    {
        return self::where('employee_number', $employee_number)->first();
    }

    public function bop()
    {
        $this->hasOne(Messerve_Model_Eloquent_Bop::class, 'bop_id', 'id');
    }

    public function hasBop() {
        return $this->bop_id > 0;
    }

    public function restDays() {
        return $this->hasMany(Messerve_Model_Eloquent_RestDay::class, 'employee_id', 'id');
    }

    public function restDaysByRange(DateTime $from, DateTime $to) {
        return $this->restDays()->whereBetween('date', [$from, $to]);
    }
}
