<?php


namespace Domains\Attendance\Collections;

use Domains\Attendance\Data\AdditionalIncome;
use Illuminate\Support\Collection;

class DTRChanges extends Collection
{
    public static function fromAdditionalPropertiesObject(object $object): Collection
    {
        $array = [];

        $has_additional_income = false;

        foreach ($object->old as $date => $old_item) {
            if(property_exists($object->new, $date)) {
                $array[$date] =  DTRPropertyChanges::fromObject($old_item, $object->new->$date);
            } else {
                $has_additional_income = true;
            }
        }


        if($has_additional_income) {
            $array['additional_income'][] =  AdditionalIncome::fromObject($object->new);
        }

        return collect($array);

    }
}