<?php
use Illuminate\Database\Eloquent\Model as Eloquent;

class Messerve_Model_Eloquent_AttendancePayroll extends Eloquent
{
    protected $table = 'attendance_payroll';

    protected $fillable = [];

    public function attendance()
    {
        return $this->belongsTo(Messerve_Model_Eloquent_Attendance::class, 'attendance_id', 'id');
    }

}
