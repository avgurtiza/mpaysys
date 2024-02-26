<?php

namespace Domains\Payslips\Data\Payslip;

class Totals
{
    /** @var float */
    public $hours;

    /** @var float */
    public $pay;

    /**
     * @var float
     */
    public $additions;
    /**
     * @var float
     */
    public $deductions;
    /**
     * @var float
     */
    public $net_pay;

    public function __construct(
        float $hours,
        float $pay,
        float $additions,
        float $deductions,
        float $net_pay
    )
    {
        $this->hours = $hours;
        $this->pay = $pay;
        $this->additions = $additions;
        $this->deductions = $deductions;
        $this->net_pay = $net_pay;
    }
}