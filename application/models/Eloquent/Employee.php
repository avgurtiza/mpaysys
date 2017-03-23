<?php
use Illuminate\Database\Eloquent\Model as Eloquent;

class Messerve_Model_Eloquent_Employee extends Eloquent
{
    protected $table = 'employee';

    protected $fillable = [
    ];

    public function group() {
        return $this->belongsTo(Messerve_Model_Eloquent_Group::class, 'group_id','id');
    }

    public function rate()
    {
        return $this->belongsTo(Messerve_Model_Eloquent_Rate::class, 'rate_id', 'id');
    }
}