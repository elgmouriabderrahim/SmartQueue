<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'service_id',
        'queue_id',
        'service_counter_id',
        'reference_code',
        'appointment_date',
        'status',
    ];

    protected $casts = [
        'appointment_date' => 'datetime',
    ];

    protected $appends = [
        'queue_position',
        'estimated_waiting_minutes',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function queue(): BelongsTo
    {
        return $this->belongsTo(Queue::class);
    }


    public function counter(): BelongsTo
    {
        return $this->belongsTo(ServiceCounter::class, 'service_counter_id');
    }

    public function queueEntry(): HasOne
    {
        return $this->hasOne(QueueEntry::class);
    }

    public function rating(): HasOne
    {
        return $this->hasOne(Rating::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function getQueuePositionAttribute(): ?int
    {
        if ($this->relationLoaded('queueEntry')) {
            return $this->queueEntry?->position;
        }

        return $this->queueEntry()->value('position');
    }

    public function getEstimatedWaitingMinutesAttribute(): int
    {
        $position = $this->queue_position;
        if (! $position) {
            return 0;
        }

        $estimatedDuration = $this->relationLoaded('service')
            ? (int) ($this->service?->estimated_duration ?? 0)
            : (int) $this->service()->value('estimated_duration');

        $currentPosition = $this->relationLoaded('queue')
            ? (int) ($this->queue?->current_position ?? 0)
            : (int) $this->queue()->value('current_position');

        $remainingAhead = max(0, $position - max(1, $currentPosition + 1));

        return max(0, $remainingAhead * $estimatedDuration);
    }
}
