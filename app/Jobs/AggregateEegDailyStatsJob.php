<?php

namespace App\Jobs;

use App\Models\EegDailyStat;
use App\Models\EegRawReading;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AggregateEegDailyStatsJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    // Koji dan agregiramo
    protected $date;

    public function __construct($date = null)
    {
        // Ako nije prosledjen datum, uzimamo jučerašnji
        $this->date = $date ?: Carbon::yesterday()->toDateString();
    }

    public function handle()
    {
        // Za svaki user_id, saberi focus vrednosti za date
        // 1) Pokupimo sve groupBy user_id
        $results = EegRawReading::selectRaw("
                user_id,
                SUM(focus) as sum_focus,
                COUNT(*) as count_records,
                MIN(focus) as min_focus,
                MAX(focus) as max_focus
            ")
            ->whereDate('recorded_at', $this->date)
            ->groupBy('user_id')
            ->get();

        foreach ($results as $row) {
            // Upsert u eeg_daily_stats
            EegDailyStat::updateOrCreate(
                [
                    'user_id' => $row->user_id,
                    'stat_date' => $this->date
                ],
                [
                    'sum_focus' => $row->sum_focus,
                    'count_records' => $row->count_records,
                    'min_focus' => $row->min_focus,
                    'max_focus' => $row->max_focus
                ]
            );
        }
    }
}
