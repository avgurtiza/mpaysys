<?php

namespace Domains\Payslips\Data\Payslip;

class Rate
{
    /**
     * @var float
     */
    public $daily;
    /**
     * @var float
     */
    public $ecola;
    /**
     * @var float
     */
    public $minimum;

    public function __construct(
         float $daily,
         float $ecola,
         float $minimum
    ) {
        $this->daily = $daily;
        $this->ecola = $ecola;
        $this->minimum = $minimum;
    }
}