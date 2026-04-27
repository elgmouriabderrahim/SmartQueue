<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'institution_id',
        'department_id',
        'name',
        'description',
        'duration',
        'capacity',
        'opening_time',
        'closing_time',
        'working_days',
        'status',
    ];

    protected $casts = [
        'working_days' => 'array',
    ];

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function counters(): BelongsToMany
    {
        return $this->belongsToMany(ServiceCounter::class, 'service_counter_service')
            ->withTimestamps();
    }

    public function queues(): HasMany
    {
        return $this->hasMany(Queue::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    public function analytics(): HasMany
    {
        return $this->hasMany(Analytics::class);
    }
}
