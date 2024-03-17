<?php

namespace Domains\Attendance\Data;

class DTRFormRow
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var \DateTime
     */
    public $date;

    /**
     * @var int
     */
    public $start_1;

    /**
     * @var int
     */
    public $end_1;

    /**
     * @var int
     */

    public $start_2;

    /**
     * @var int
     */
    public $end_2;

    /**
     * @var int
     */
    public $start_3;

    /**
     * @var int
     */
    public $end_3;

    /**
     * @var int
     */
    public $ot_approved_hours;

    /**
     * @var string
     */
    public $type;

    public function __construct(int $id, \DateTime $date, int $start_1, int $end_1, int $start_2, int $end_2, int $start_3, int $end_3, int $ot_approved_hours, string $type)
    {
        $this->id = $id;
        $this->date = $date;
        $this->start_1 = $start_1;
        $this->end_1 = $end_1;
        $this->start_2 = $start_2;
        $this->end_2 = $end_2;
        $this->start_3 = $start_3;
        $this->end_3 = $end_3;
        $this->ot_approved_hours = $ot_approved_hours;
        $this->type = $type;
    }
}