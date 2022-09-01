<?php


namespace Domains\Attendance\Data;


class AdditionalIncome
{
    /**
     * @var int
     */
    public $attendance_id;
    /**
     * @var float
     */
    public $thirteenth_month_pay;
    /**
     * @var float
     */
    public $incentives;
    /**
     * @var float
     */
    public $paternity;
    /**
     * @var float
     */
    public $misc_income;

    public function __construct(
        int $attendance_id,
        float $thirteenth_month_pay,
        float $incentives,
        float $paternity,
        float $misc_income
    ) {
        $this->attendance_id = $attendance_id;
        $this->thirteenth_month_pay = $thirteenth_month_pay;
        $this->incentives = $incentives;
        $this->paternity = $paternity;
        $this->misc_income = $misc_income;
    }

    public static function fromObject(object $object): AdditionalIncome
    {
        return new AdditionalIncome(
            $object->attendance_id,
            $object->thirteenth_month_pay,
            $object->incentives,
            $object->paternity,
            $object->misc_income
        );
    }
}

