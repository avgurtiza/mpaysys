<?php
use Illuminate\Database\Eloquent\Model as Eloquent;

class Messerve_Model_Eloquent_AttendancePayroll extends Eloquent
{
    protected $table = 'attendance_payroll';

    protected $dates = [
        'period_start',
    ];

    protected $fillable = [];

    public function attendance()
    {
        return $this->belongsTo(Messerve_Model_Eloquent_Attendance::class, 'attendance_id', 'id');
    }

    public static function groupAttendanceByClientRate($group_id, $cutoff_date, $client_rate_id) {
        return Messerve_Model_Eloquent_AttendancePayroll::where('group_id', $group_id)
            ->where('period_start', $cutoff_date)
            ->where('client_rate_id', $client_rate_id)
            ->get();
    }

    public static function cutOffRates($group_id, $cutoff_date = null)
    {
        if(is_null($cutoff_date)) {
            if(date('d') > 15) {
                $cutoff_date = date('Y-m-16');
            } else {
                $cutoff_date = date('Y-m-01');
            }
        }

        $rates = ['rider' => [], 'client' => []];

        // die($cutoff_date);

        $rider_rates = Messerve_Model_Eloquent_AttendancePayroll::where('group_id', $group_id)
            ->where('period_start', $cutoff_date)
            ->groupBy('rate_id')->get(['rate_id']);

        $rates['rider'] = $rider_rates->pluck('rate_id')->toArray();

        $client_rates = Messerve_Model_Eloquent_AttendancePayroll::where('group_id', $group_id)
            ->where('period_start', $cutoff_date)
            ->groupBy('client_rate_id')->get(['client_rate_id']);

        $rates['client'] = $client_rates->pluck('client_rate_id')->toArray();

        return $rates;
    }
}
