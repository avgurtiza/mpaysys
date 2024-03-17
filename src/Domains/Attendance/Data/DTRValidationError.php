<?php

namespace Domains\Attendance\Data;

use Messerve_Model_Eloquent_Attendance as Attendance;

class DTRValidationError
{
    /**
     * @var string
     */
    public $name;
    /**
     * @var string
     */
    public $message;

    /**
     * @var Attendance
     */
    public $attendance;

    /**
     * @var Attendance|null
     */
    public $otherAttendance;

    public function __construct(string $name, string $message, Attendance $attendance, Attendance $otherAttendance = null)
    {
        $this->name = $name;
        $this->message = $message;
        $this->attendance = $attendance;
        $this->otherAttendance = $otherAttendance;
    }
}