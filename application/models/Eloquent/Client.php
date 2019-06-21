<?php
use Illuminate\Database\Eloquent\Model as Eloquent;

class Messerve_Model_Eloquent_Client extends Eloquent
{
    protected $table = 'client';

    protected $fillable = [
    ];

    public function group()
    {
        return $this->hasMany(Messerve_Model_Eloquent_Group::class, 'client_id', 'id');
    }

    public function usesBiometrics() {
        return $this->uses_biometrics == 1;
    }
}