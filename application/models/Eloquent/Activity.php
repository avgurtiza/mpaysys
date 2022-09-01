<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;


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

    public function causer() {
        return $this->morphTo();
    }

    public function diff() {
        $data = json_decode($this->properties);

        $additional_properties = $data->additional_properties;

        $diff = array_diff($additional_properties->old, $additional_properties->new);
        return $additional_properties;
    }

}