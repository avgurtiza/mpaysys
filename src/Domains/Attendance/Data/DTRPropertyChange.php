<?php


namespace Domains\Attendance\Data;


class DTRPropertyChange
{
    /**
     * @var string
     */
    public $name;
    /**
     * @var string
     */
    public $old_value;
    /**
     * @var string
     */
    public $new_value;

    public function __construct(string $name, $old_value = "", $new_value = "")
    {
        $this->name = $name;
        $this->old_value = $old_value;
        $this->new_value = $new_value;
    }
}