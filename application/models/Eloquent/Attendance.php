<?php
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @property mixed id
 */
class Messerve_Model_Eloquent_Attendance extends Eloquent
{
    protected $table = 'attendance';

    protected $dates = [
        'datetime_start',
    ];

    protected $fillable = ['datetime_start', 'employee_id'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Messerve_Model_Eloquent_Employee::class, 'employee_id', 'id');
    }

    public function group()
    {
        return $this->belongsTo(Messerve_Model_Eloquent_Group::class, 'group_id', 'id');
    }

    public function attendancePayroll(): HasMany
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

    public function bopAttendance(): HasMany
    {
        return $this->hasMany(Messerve_Model_Eloquent_BopAttendance::class, 'attendance_id', 'id');
    }



    public function history(): MorphMany
    {
        return $this->morphMany(Messerve_Model_Eloquent_Activity::class, 'subject');
    }
}
