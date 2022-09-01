<?php


namespace Domains\Attendance\Collections;


use Domains\Attendance\Data\DTRPropertyChange;
use Illuminate\Support\Collection;

class DTRPropertyChanges extends Collection
{
    public function push($value): DTRPropertyChanges
    {
        if(!$value instanceof DTRPropertyChange ) {
            throw new \RuntimeException("Value is not an instance of DTRPropertyChange!");
        }
        $this->offsetSet(null, $value);

        return $this;
    }

    public static function fromObject(object $old, object $new): DTRPropertyChanges
    {
        $collection = new DTRPropertyChanges();


        foreach($old as $key=>$old_item) {
            $collection->push(new DTRPropertyChange($key, $old_item, $new->$key));
        }

        return $collection;
    }

}