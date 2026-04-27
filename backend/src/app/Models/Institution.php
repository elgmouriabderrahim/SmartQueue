<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Institution extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'city',
        'adress',
        'description',
        'opening_time',
        'closing_time',
        'working_days',
        'max_appointments_per_day',
        'status',
    ];

    protected $casts = [
        'working_days' => 'array',
        'opening_time' => 'datetime',
        'closing_time' => 'datetime',
    ];

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'institution_user')
            ->withTimestamps();
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function analytics(): HasMany
    {
        return $this->hasMany(Analytics::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(InstitutionInvitation::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }
}