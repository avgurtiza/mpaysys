<?php

use Illuminate\Database\Eloquent\Model;


/**
 * @method static all()
 * @method save()
 * @property string description
 * @property mixed|string subject_type
 * @property mixed|integer subject_id
 * @property mixed|string causer_type
 * @property mixed|integer causer_id
 */

class Messerve_Model_Eloquent_Activity extends Model
{
    protected $table = 'activity_log';

}