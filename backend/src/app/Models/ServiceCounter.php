<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceCounter extends Model
{
    use HasFactory;

    protected $fillable = [
        'counter_number',
        'name',
        'status',
    ];

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'service_counter_service')
            ->withTimestamps();
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'service_counter_id');
    }
}
