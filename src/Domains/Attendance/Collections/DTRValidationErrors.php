<?php

namespace Domains\Attendance\Collections;

use Domains\Attendance\Data\DTRValidationError;
use Illuminate\Support\Collection;

class DTRValidationErrors extends Collection
{

    public function push($value): DTRValidationErrors
    {
        if(!$value instanceof DTRValidationError ) {
            throw new \RuntimeException("Value is not an instance of DTRValidationError!");
        }
        $this->offsetSet(null, $value);

        return $this;
    }
}