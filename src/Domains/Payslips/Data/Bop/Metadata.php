<?php

namespace Domains\Payslips\Data\Bop;

class Metadata
{
    /**
     * @var string
     */
    public $name;
    /**
     * @var float
     */
    public $value;

    public function __construct(string $name, float $value)
    {
        $this->name = $name;
        $this->value = $value;
    }
}