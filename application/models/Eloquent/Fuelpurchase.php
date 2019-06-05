<?php
use Illuminate\Database\Eloquent\Model as Eloquent;

class Messerve_Model_Eloquent_Fuelpurchase extends Eloquent
{
    protected $table = 'fuelpurchase';

    protected $fillable = [
        'gascard'
        , 'raw_invoice_date'
        , 'statement_date'
        , 'invoice_date'
        , 'product_quantity'
        , 'invoice_number'
        , 'station_name'
        , 'product'
        , 'fuel_cost'
        , 'gascard_type'
        , 'employee_id'
    ];

    public function employee()
    {
        return $this->belongsTo(Messerve_Model_Eloquent_Employee::class, 'employee_id', 'id');
    }

}
