<?php
use Illuminate\Database\Eloquent\Model as Eloquent;

class Messerve_Model_Eloquent_RestDay extends Eloquent
{
    protected $table = 'rest_day';

    protected $fillable = [
        'employee_id', 'date'
    ];

}
