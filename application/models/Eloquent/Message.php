<?php
use Illuminate\Database\Eloquent\Model as Eloquent;

class Messerve_Model_Eloquent_Message extends Eloquent
{
    protected $table = 'message', $primaryKey = 'message_id';

    protected $fillable = [
    ];

    public function pendingPayroll()
    {
        return $this->hasOne(Messerve_Model_Eloquent_PendingPayroll::class, 'message_id', 'message_id');
    }

}