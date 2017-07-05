<?php
use Illuminate\Database\Eloquent\Model as Eloquent;

class Messerve_Model_Eloquent_BopAttendance extends Eloquent
{
    protected $table = 'bop_attendance';

    protected $fillable = [];

    public function attendance()
    {
        return $this->belongsTo(Messerve_Model_Eloquent_Attendance::class, 'attendance_id', 'id');
    }

}
