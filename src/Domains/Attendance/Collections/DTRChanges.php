<?php


namespace Domains\Attendance\Collections;


use Domains\Attendance\Data\DTRPropertyChange;
use Illuminate\Support\Collection;

class DTRChanges extends Collection
{
    public static function fromAdditionalPropertiesObject(object $object): Collection
    {
        $array = [];

        foreach ($object->old as $date => $old_item) {
            $array[$date] =  DTRPropertyChanges::fromObject($old_item, $object->new->$date);
        }

        return collect($array);

    }
}