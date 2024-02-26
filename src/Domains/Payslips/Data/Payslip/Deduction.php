<?php

namespace Domains\Payslips\Data\Payslip;


class Deduction
{
    /**
     * @var string
     */
    public $name;
    /**
     * @var float
     */
    public $amount;

    public function __construct(
        string $name,
        float  $amount
    ) {
        $this->name = $name;
        $this->amount = $amount;
    }
}