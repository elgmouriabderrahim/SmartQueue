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
}
