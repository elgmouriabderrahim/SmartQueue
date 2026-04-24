<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Analytics extends Model
{
    use HasFactory;

    protected $fillable = [
        'institution_id',
        'service_id',
        'total_appointments',
        'completed_appointments',
        'cancelled_appointments',
        'total_visitors',
        'average_rating',
        'average_wait_time',
    ];

    protected $casts = [
        'average_rating' => 'decimal:2',
        'average_wait_time' => 'decimal:2',
    ];

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
