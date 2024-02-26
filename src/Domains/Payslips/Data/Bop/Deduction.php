<?php

namespace Domains\Payslips\Data\Bop;


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
    /**
     * @var Metadata
     */
    public $metadata;

    public function __construct(
        string $name,
        float  $amount,
        Metadata $metadata
    ) {
        $this->name = $name;
        $this->amount = $amount;
        $this->metadata = $metadata;
    }
}