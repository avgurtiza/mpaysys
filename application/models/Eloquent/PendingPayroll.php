<?php
use Illuminate\Database\Eloquent\Model as Eloquent;

class Messerve_Model_Eloquent_PendingPayroll extends Eloquent
{
    protected $table = 'pending_payroll';

    protected $fillable = [
        'message_id',
        'group_id',
        'date_start',
        'date_end',
        'pay_period',
        'is_done'
    ];

    public function queueMessage()
    {
        return $this->hasOne(Messerve_Model_Eloquent_Message::class, 'message_id', 'message_id');
    }

    public function group()
    {
        return $this->belongsTo(Messerve_Model_Eloquent_Group::class, 'group_id', 'id');
    }

}