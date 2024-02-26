<?php

namespace Domains\Payslips\Data;

use Domains\Payslips\Data\Bop\Addition;
use Domains\Payslips\Data\Bop\Deduction;
use Domains\Payslips\Data\Bop\Totals;
use Illuminate\Support\Collection;

class Bop
{
    /** @var Addition[] */
    public $additions;

    /** @var Deduction[] */
    public $deductions;
    /**
     * @var Totals
     */
    public $totals;

    public function __construct()
    {
        $this->additions = new Collection();
        $this->deductions = new Collection();
        $this->totals = new Totals(0, 0, 0);
    }
}