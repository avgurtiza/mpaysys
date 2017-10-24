<?php
use Illuminate\Database\Eloquent\Model as Eloquent;

class Messerve_Model_Eloquent_ClientRateSchedule extends Eloquent
{
    protected $table = 'client_rate_schedule';

    protected $fillable = [];

    protected $dates = ['date_active'];

    public function rate()
    {
        return $this->belongsTo(Messerve_Model_Eloquent_ClientRate::class, 'client_rate_id', 'id');
    }

    public static function cutoffRates($group_id, $cutoff_date) {

        $date = \Carbon\Carbon::parse($cutoff_date);

        if($date->day > 15) {
            $date->day = 16;
            $from = $date->toDateString();
            $to = $date->endOfMonth()->toDateString();
        } else {
            $from = $date->startOfMonth()->toDateString();
            $date->day = 15;
            $to = $date->toDateString();
        }

        return Messerve_Model_Eloquent_ClientRateSchedule::where('group_id', $group_id)
            ->where('date_active','>=', $from)
            ->where('date_active','<=', $to)->get();
    }
}
