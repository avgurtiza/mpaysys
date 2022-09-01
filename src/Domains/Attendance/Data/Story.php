<?php


namespace Domains\Attendance\Data;


use Carbon\Carbon;
use Domains\Attendance\Collections\DTRChanges;
use Illuminate\Support\Collection;
use Messerve_Model_Eloquent_User as User;

class Story
{
    /**
     * @var int
     */
    public $attendance_id;
    /**
     * @var Carbon
     */
    public $changed_at;
    /**
     * @var User
     */
    public $causer;
    /**
     * @var DTRChanges
     */
    public $changes;

    public function __construct(int $attendance_id, Carbon $changed_at, User $causer, Collection  $changes)
    {
        $this->attendance_id = $attendance_id;
        $this->changed_at = $changed_at;
        $this->causer = $causer;
        $this->changes = $changes;
    }
}