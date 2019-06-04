<?php
use Illuminate\Database\Eloquent\Model as Eloquent;

class Messerve_Model_Eloquent_Fuelpurchase extends Eloquent
{
    protected $table = 'fuelpurchase';

    protected $fillable = [
    ];

    public function employee()
    {
        return $this->belongsTo(Messerve_Model_Eloquent_Employee::class, 'employee_id', 'id');
    }

}
