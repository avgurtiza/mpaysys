<?php
use Illuminate\Database\Eloquent\Model as Eloquent;

class Messerve_Model_Eloquent_Attendance extends Eloquent
{
    protected $table = 'attendance';

    protected $fillable = [];

    public function employee()
    {
        return $this->belongsTo(Messerve_Model_Eloquent_Employee::class, 'employee_id', 'id');
    }
}
