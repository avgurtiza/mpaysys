<?php
use Illuminate\Database\Eloquent\Model as Eloquent;

class Messerve_Model_Eloquent_RateSchedule extends Eloquent
{
    protected $table = 'employee_rate_schedule';

    protected $fillable = [];

    public function groups()
    {
        return $this->hasMany(Messerve_Model_Eloquent_Group::class, 'rate_id', 'id');
    }

    public static function byGroupAndDate($group_id, $date)
    {
        return Messerve_Model_Eloquent_RateSchedule::where('group_id', $group_id)
            // ->where('rate', $date)
            ->get()
            ;
    }

    public static function splits($group_id, $date_start, $date_end)
    {
        return Messerve_Model_Eloquent_RateSchedule::where('group_id', $group_id)
            ->where('date_active', '>=', $date_start)
            ->where('date_active', '<=', $date_end)
            ->get()
            ;
    }

    public function rate()
    {
        return $this->belongsTo(Messerve_Model_Eloquent_Rate::class, 'rate_id', 'id');
    }
}
