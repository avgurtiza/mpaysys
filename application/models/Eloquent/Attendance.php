<?php
use Illuminate\Database\Eloquent\Model as Eloquent;

class Messerve_Model_Eloquent_Attendance extends Eloquent
{
    protected $table = 'attendance';

    protected $dates = [
        'datetime_start',
    ];


    protected $fillable = [];

    public function employee()
    {
        return $this->belongsTo(Messerve_Model_Eloquent_Employee::class, 'employee_id', 'id');
    }

    public function attendancePayroll()
    {
        return $this->hasMany(Messerve_Model_Eloquent_AttendancePayroll::class, 'attendance_id', 'id');
    }
}
