<?php

namespace Domains\Attendance\Collections;

use Carbon\Carbon;
use Domains\Attendance\Data\DTRFormRow;
use Domains\Attendance\Data\DTRValidationError;
use Illuminate\Support\Collection;

class DTRSubmission extends Collection
{
    public function push($value): DTRSubmission
    {
        if(!$value instanceof DTRFormRow ) {
            throw new \RuntimeException("Value is not an instance of DTRFormRow!");
        }
        $this->offsetSet(null, $value);

        return $this;
    }

    public static function fromFormArray(array $formArray): DTRSubmission
    {
        $submission = new static();

        foreach ($formArray as $key=>$row) {
            $submission->push(new DTRFormRow(
                $row['id'],
                Carbon::parse($key),
                (int) $row['start_1'],
                (int) $row['end_1'],
                (int) $row['start_2'],
                (int) $row['end_2'],
                (int) $row['start_3'],
                (int) $row['end_3'],
                (int) $row['ot_approved_hours'],
                $row['type']
            ));
        }

        return $submission;
    }
}