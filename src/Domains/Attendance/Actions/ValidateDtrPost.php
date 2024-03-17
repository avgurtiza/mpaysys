<?php

namespace Domains\Attendance\Actions;

use Carbon\Carbon;
use Domains\Attendance\Collections\DTRSubmission;
use Domains\Attendance\Collections\DTRValidationErrors;
use Domains\Attendance\Data\DTRFormRow;
use Domains\Attendance\Data\DTRValidationError;
use Exception;
use Messerve_Model_Eloquent_Attendance as Attendance;

class ValidateDtrPost
{

    /**
     * @var DTRValidationErrors
     */
    private $errors;
    /**
     * @var int
     */
    private $employee_id;
    /**
     * @var int
     */
    private $group_id;

    public function __construct()
    {
    }

    public function __invoke(int $employee_id, int $group_id, DTRSubmission $submission): ValidateDtrPost
    {
        $this->employee_id = $employee_id;
        $this->group_id = $group_id;

        $errors = new DTRValidationErrors();

        $submission->each(function (DTRFormRow $item) use ($errors) {
            /** @var Attendance $overlap */
            try {
                if ($overlap = $this->hasOverlappingAttendance($item)) {
                    $errors->push(new DTRValidationError(
                        'Overlap',
                        'Attendance has overlapping time with another attendance.',
                        Attendance::query()->find($item->id),
                        $overlap
                    ));
                }
            } catch (Exception $e) {
                $errors->push(new DTRValidationError(
                    'Exception',
                    $e->getMessage(),
                    Attendance::query()->find($item->id)
                ));
            }
        });

        $this->errors = $errors;

        return $this;
    }

    public function errors(): DTRValidationErrors
    {
        return $this->errors;
    }

    public function fails(): bool
    {
        return $this->errors->isNotEmpty();
    }

    /**
     * @throws Exception
     */
    private function hasOverlappingAttendance(DTRFormRow $item) : ?Attendance
    {
        $date = Carbon::parse($item->date);

        [$start, $end] = $this->getStartAndEnd($item, $date);

        $periodAttendance = (new Attendance())->newQuery()
            ->where('employee_id', $this->employee_id)
            ->where('datetime_start', '>=', $date->startOfDay())
            ->where('datetime_start', '<=', $date->endOfDay())
            ->where('id', '!=', $item->id)
            // ->where('group_id', '!=', $this->group_id)
            ->get();

        foreach ($periodAttendance as $attendance) {
            [$attendanceStart, $attendanceEnd] = $this->getStartAndEnd($attendance, $date);

            if ($start->between($attendanceStart, $attendanceEnd) || $end->between($attendanceStart, $attendanceEnd)) {
                return $attendance;
            }
        }

        return null;
    }

    /**
     * @throws Exception
     */
    private function getStartAndEnd(DTRFormRow $item, Carbon $date): array
    {
        $start = 0;
        $end = 0;

        if ($item->start_3 > 0) {
            $start = $item->start_3;
            if (!$item->end_3) {
                throw new Exception("Start 3 has no end time");
            }

            $end = $item->end_3;
        }

        if ($item->start_2 > 0) {
            $start = $item->start_2;

            if (!$item->end_2) {
                throw new Exception("Start 2 has no end time");
            }

            if (!$end || $end < $item->end_2) {
                $end = $item->end_2;
            }
        }

        if ($item->start_1 > 0) {
            $start = $item->start_1;

            if (!$item->end_1) {
                throw new Exception("Start 1 has no end time");
            }

            if (!$end || $end < $item->end_1) {
                $end = $item->end_1;
            }
        }

        return [
            'start' => $this->militaryToCarbon($start, $date),
            'end' => $this->militaryToCarbon($end, $date)
        ];
    }

    private function militaryToCarbon(int $time, Carbon $date): Carbon
    {
        return $date->setTime($time / 100, $time % 100);
    }
}