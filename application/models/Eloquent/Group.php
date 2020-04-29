<?php
use Illuminate\Database\Eloquent\Model as Eloquent;

class Messerve_Model_Eloquent_Group extends Eloquent
{
    protected $table = 'group';

    protected $fillable = [
    ];

    public function rate()
    {
        return $this->belongsTo(Messerve_Model_Eloquent_Rate::class, 'rate_id', 'id');
    }

    public function client()
    {
        return $this->belongsTo(Messerve_Model_Eloquent_Client::class, 'client_id', 'id');
    }

    public function clientRate()
    {
        return $this->hasOne(Messerve_Model_Eloquent_ClientRate::class, 'id', 'rate_client_id');
    }

    public function getFullNameAttribute() {
        return $this->client->name . ' - ' . $this->name;
    }
}