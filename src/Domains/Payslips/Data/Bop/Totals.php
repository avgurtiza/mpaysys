<?php

namespace Domains\Payslips\Data\Bop;

class Totals
{
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
    public $total;

    public function __construct(
        float $additions,
        float $deductions,
        float $total
    )
    {
        $this->additions = $additions;
        $this->deductions = $deductions;
        $this->total = $total;
    }
}