<?php

namespace Domains\Payslips\Data;

use Domains\Payslips\Data\Payslip\Addition;
use Domains\Payslips\Data\Payslip\Deduction;
use Domains\Payslips\Data\Payslip\Salary;
use Domains\Payslips\Data\Payslip\Rate;
use Domains\Payslips\Data\Payslip\Totals;
use Illuminate\Support\Collection;

class Payslip
{
    /** @var Rate */
    public $rate;

    /** @var Salary[] */
    public $salary;

    /** @var Addition[] */
    public $additions;

    /** @var Deduction[] */
    public $deductions;

    /** @var Totals */
    public $totals;

    /** @var Bop */
    public $bop;

    public function __construct()
    {
        $this->rate = new Rate(0, 0, 0);
        $this->totals = new Totals(0, 0, 0, 0, 0);

        $this->salary = new Collection();
        $this->additions = new Collection();
        $this->deductions = new Collection();
        $this->bop = new Bop();
    }
}