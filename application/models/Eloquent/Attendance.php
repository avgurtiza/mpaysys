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

    public function group()
    {
        return $this->belongsTo(Messerve_Model_Eloquent_Group::class, 'group_id', 'id');
    }

    public function attendancePayroll()
    {
        return $this->hasMany(Messerve_Model_Eloquent_AttendancePayroll::class, 'attendance_id', 'id');
    }

    public function getLegalAttendance($employee_id, $group_id, $date_start, $date_end)
    {
        return $this->where('employee_id', $employee_id)
            ->where('group_id', $group_id)
            ->where('datetime_start', '>=', '{$date_start} 00:00')
            ->where('datetime_start', '<=', '{$date_end} 23:59')
            ->where(function ($query) {
                return $query->where('legal', '>', 0)->orWhere('legal_ot', '>', 0);
            })->get();
    }

    /*
     $legal_attendance = $LegalAttendanceMap->fetchListToArray("(attendance.employee_id = '{$Employee->getId()}')
                    AND (attendance.group_id = {$group_id})
                    AND datetime_start >= '{$date_start} 00:00'
                    AND datetime_start <= '{$date_end} 23:59'
                    AND (
                        legal > 0  OR legal_ot > 0
                        -- OR legal_nd_ot > 0 OR legal_nd > 0 // TODO:  This may break legal UA calcs.   Check this if it happens
                    )");

     */
}
