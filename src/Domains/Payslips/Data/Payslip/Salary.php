<?php

namespace Domains\Payslips\Data\Payslip;

class Salary
{
    /**
     * @var string
     */
    public $holiday;
    /**
     * @var string
     */
    public $name;
    /**
     * @var int
     */
    public $hours;
    /**
     * @var float
     */
    public $amount;

    public function __construct(
        string $name,
        string $holiday,
        int $hours,
        float $amount
    )
    {
        $this->holiday = $holiday;
        $this->name = $name;
        $this->hours = $hours;
        $this->amount = $amount;
    }
}