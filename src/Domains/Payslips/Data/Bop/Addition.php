<?php

namespace Domains\Payslips\Data\Bop;


use Illuminate\Support\Collection;

class Addition
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
     * @var Metadata[]|null
     */
    public $metadata;

    public function __construct(
        string   $name,
        float    $amount,
        Collection $metadata = null
    )
    {
        $this->name = $name;
        $this->amount = $amount;
        $this->metadata = $metadata;
    }
}