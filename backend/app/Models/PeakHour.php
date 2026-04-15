<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PeakHour extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_id',
        'date',
        'hour',
        'appointments_count',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
