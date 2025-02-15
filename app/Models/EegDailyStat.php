<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EegDailyStat extends Model
{
    use HasFactory;

    protected $table = 'eeg_daily_stats';

    protected $fillable = [
        'user_id',
        'stat_date',
        'sum_focus',
        'count_records',
        'min_focus',
        'max_focus'
    ];

    protected $casts = [
        'stat_date' => 'date',
        'sum_focus' => 'double',
        'count_records' => 'integer',
        'min_focus' => 'float',
        'max_focus' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Helper za racunanje prosecnog fokusa
    public function getAvgFocusAttribute()
    {
        if ($this->count_records == 0) {
            return 0;
        }
        return $this->sum_focus / $this->count_records;
    }
}
