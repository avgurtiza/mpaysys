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
    /**
     * @var float
     */
    public $solo_parent_leave;
    /**
     * @var float
     */
    public $tl_allowance;

    public function __construct(
        int $attendance_id,
        float $thirteenth_month_pay,
        float $incentives,
        float $paternity,
        float $misc_income,
        float $solo_parent_leave,
        float $tl_allowance
    ) {
        $this->attendance_id = $attendance_id;
        $this->thirteenth_month_pay = $thirteenth_month_pay;
        $this->incentives = $incentives;
        $this->paternity = $paternity;
        $this->misc_income = $misc_income;
        $this->solo_parent_leave = $solo_parent_leave;
        $this->tl_allowance = $tl_allowance;
    }

    public static function fromObject(object $object): AdditionalIncome
    {
        return new AdditionalIncome(
            (int) $object->attendance_id,
            (float) $object->thirteenth_month_pay,
            (float) $object->incentives,
            (float) $object->paternity,
            (float) $object->misc_income,
            (float) $object->solo_parent_leave,
            (float) $object->tl_allowance
        );
    }
}

