<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EegSecondStat extends Model
{
    use HasFactory;

    protected $table = 'eeg_second_stats';

    protected $fillable = [
        'user_id',
        'recorded_at',
        'avg_eeg'
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'avg_eeg'     => 'double',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
