<?php

namespace Domains\Holiday\Data;

class HolidayData
{
    /**
     * @var mixed
     */
    public $date;
    /**
     * @var mixed
     */
    public $rest_day;

    public function __construct($date, $rest_day)
    {
        $this->date = $date;
        $this->rest_day = $rest_day;
    }

    public function toArray(): array
    {
        return [
            'date' => $this->date,
            'rest_day' => $this->rest_day
        ];
    }
}