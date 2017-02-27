<?php
use Illuminate\Database\Eloquent\Model as Eloquent;

class Messerve_Model_EloquentFloatingAttendance extends Eloquent
{
    protected $table = 'floating_attendance';
    protected $fillable = [
        'attendance_id',
        'reg_ot', 'reg_nd_ot',
        'spec_ot', 'spec_nd_ot',
        'rest_ot', 'rest_nd_ot',
        'legal_ot', 'legal_nd_ot',
    ];
}
