<?php

namespace App\Http\Controllers;

use App\Models\EegDailyStat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EegStatsController extends Controller
{
    /**
     * Dnevna statistika - npr. poslednjih N dana
     */
    public function dailyStats(Request $request)
    {
        $userId = Auth::id();
        // Koliko dana unazad prikazujemo (ili dobijemo iz query param)
        $days = $request->query('days', 7);

        $sinceDate = Carbon::now()->subDays($days)->startOfDay();

        $stats = EegDailyStat::where('user_id', $userId)
            ->where('stat_date', '>=', $sinceDate)
            ->orderBy('stat_date', 'asc')
            ->get();

        // Svaki EegDailyStat ima getAvgFocusAttribute za prosek
        return response()->json([
            'daily_stats' => $stats->map(function($stat) {
                return [
                    'date' => $stat->stat_date->toDateString(),
                    'avg_focus' => $stat->avg_focus,    // sum_focus / count_records
                    'min_focus' => $stat->min_focus,
                    'max_focus' => $stat->max_focus,
                    'count' => $stat->count_records,
                ];
            })
        ]);
    }

    /**
     * Nedeljna statistika - možemo je izvući grupisanjem dnevne statistike
     * umesto sirovih zapisa. 
     * 
     * Za primer, prikupi EegDailyStat i grupiši po "nedelji"
     * (u PostgreSQL stilu se može koristiti date_trunc('week', ...),
     *  ali ovde ću pokazati u PHP)
     */
    public function weeklyStats(Request $request)
    {
        $userId = Auth::id();
        // kolika je vremenska pokrivenost npr. 8 nedelja
        $weeks = $request->query('weeks', 8);

        // Uzimamo poslednjih X nedelja
        $sinceDate = Carbon::now()->subWeeks($weeks)->startOfWeek();

        // Dohvati dnevne statistike, pa grupiši ručno
        $dailyStats = EegDailyStat::where('user_id', $userId)
            ->where('stat_date', '>=', $sinceDate)
            ->get();

        // Grupisanje ručno u PHP po ISO week
        $grouped = [];
        foreach ($dailyStats as $stat) {
            // Koja nedelja? (ISOWeekYear-ISOWeek)
            $weekKey = $stat->stat_date->format('o-\WW'); 
            // primer: "2025-W06"
            if (!isset($grouped[$weekKey])) {
                $grouped[$weekKey] = [
                    'week_start' => $stat->stat_date->startOfWeek()->toDateString(),
                    'sum_focus' => 0,
                    'count_records' => 0,
                    'min_focus' => null,
                    'max_focus' => null,
                ];
            }
            $grouped[$weekKey]['sum_focus'] += $stat->sum_focus;
            $grouped[$weekKey]['count_records'] += $stat->count_records;

            // min/max fokus
            if (is_null($grouped[$weekKey]['min_focus']) || $stat->min_focus < $grouped[$weekKey]['min_focus']) {
                $grouped[$weekKey]['min_focus'] = $stat->min_focus;
            }
            if (is_null($grouped[$weekKey]['max_focus']) || $stat->max_focus > $grouped[$weekKey]['max_focus']) {
                $grouped[$weekKey]['max_focus'] = $stat->max_focus;
            }
        }

        // Transformiši rezultat u niz
        $weeklyStats = [];
        foreach ($grouped as $weekKey => $data) {
            $avgFocus = $data['count_records'] > 0
                ? $data['sum_focus'] / $data['count_records']
                : 0;

            $weeklyStats[] = [
                'week_key'   => $weekKey, 
                'week_start' => $data['week_start'],
                'avg_focus'  => $avgFocus,
                'min_focus'  => $data['min_focus'],
                'max_focus'  => $data['max_focus'],
                'count'      => $data['count_records'],
            ];
        }

        // Sortiraj po datumu pocetka nedelje
        usort($weeklyStats, function($a, $b){
            return strtotime($a['week_start']) - strtotime($b['week_start']);
        });

        return response()->json([
            'weekly_stats' => $weeklyStats
        ]);
    }
}
