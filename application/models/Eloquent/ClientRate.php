<?php
use Illuminate\Database\Eloquent\Model as Eloquent;

class Messerve_Model_Eloquent_ClientRate extends Eloquent
{
    protected $table = 'rate_client';

    protected $fillable = [
        'name',
        'code',
        'reg',
        'reg_ot',
        'reg_nd',
        'reg_nd_ot',
        'spec',
        'spec_ot',
        'spec_nd',
        'spec_nd_ot',
        'legal',
        'legal_ot',
        'legal_nd',
        'legal_nd_ot',
        'legal_unattend'
    ];

}
