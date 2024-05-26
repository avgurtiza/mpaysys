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
    private function hasOverlappingAttendance(DTRFormRow $item): ?Attendance
    {
        $date = Carbon::parse($item->date);

        [$start, $end] = $this->getStartAndEnd($item, $date);

        if ($start == null || $end == null) {
            return null;
        }

        $periodAttendance = (new Attendance())->newQuery()
            ->where('employee_id', $this->employee_id)
            ->where('datetime_start', $date->startOfDay())
            ->where('id', '!=', $item->id)
            // ->where('group_id', '!=', $this->group_id)
            ->get();

        foreach ($periodAttendance as $attendance) {

            $form = new DTRFormRow(
                $attendance->id,
                Carbon::parse($attendance->datetime_start),
                $attendance->start_1,
                $attendance->end_1,
                $attendance->start_2,
                $attendance->end_2,
                $attendance->start_3,
                $attendance->end_3,
                $attendance->ot_approved_hours,
                $attendance->type);

            [$attendanceStart, $attendanceEnd] = $this->getStartAndEnd($form, $date);

            if ($attendanceStart == null || $attendanceEnd == null) {
                continue;
            }

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
        $timePairs = [];

        if ($item->start_1 > 0) {
            $timePairs[] = [$item->start_1, $item->end_1];
        }

        if ($item->start_2 > 0) {
            $timePairs[] = [$item->start_2, $item->end_2];
        }

        if ($item->start_3 > 0) {
            $timePairs[] = [$item->start_3, $item->end_3];
        }

        // Initialize the earliest and latest times
        $earliest = null;
        $latest = null;

        foreach ($timePairs as $pair) {
            // Convert the start and end times to Carbon instances
            $start = Carbon::createFromFormat('Y-m-d Hi', $date->format("Y-m-d" . str_pad($pair['start'], 4, '0', STR_PAD_LEFT)));
            $end = Carbon::createFromFormat('Y-m-d Hi', $date->format("Y-m-d" . str_pad($pair['end'], 4, '0', STR_PAD_LEFT)));

            // If the end time is earlier than the start time, it means it's on the next day
            if ($end->lt($start)) {
                $end->addDay();
            }

            // Update the earliest and latest times
            if (!$earliest || $start->lt($earliest)) {
                $earliest = $start;
            }
            if (!$latest || $end->gt($latest)) {
                $latest = $end;
            }
        }

        return [$earliest, $latest];
    }
}