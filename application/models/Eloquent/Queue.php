<?php
use Illuminate\Database\Eloquent\Model as Eloquent;

class Messerve_Model_Eloquent_Queue extends Eloquent
{
    protected $table = 'queue', $primaryKey = 'queue_id';

    protected $fillable = [
    ];

    public function messages()
    {
        return $this->hasMany(Messerve_Model_Eloquent_Message::class, 'queue_id', 'queue_id');
    }

}