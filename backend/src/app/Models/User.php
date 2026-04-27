<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'identity_number',
        'role',
        'institution_id',
        'department_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $appends = [
        'api_role',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (self $user): void {
            if (! $user->wasRecentlyCreated && ! $user->wasChanged('institution_id')) {
                return;
            }

            $user->syncInstitutionMembership();
        });
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function institutions(): BelongsToMany
    {
        return $this->belongsToMany(Institution::class, 'institution_user')
            ->withTimestamps();
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    public function messagesSent(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function messagesReceived(): HasMany
    {
        return $this->hasMany(Message::class, 'recipient_id');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function getApiRoleAttribute(): string
    {
        return in_array($this->role, ['manager', 'employee'], true)
            ? 'institution'
            : $this->role;
    }

    public function currentInstitutionId(): ?int
    {
        if ($this->institution_id) {
            return (int) $this->institution_id;
        }

        $pivotInstitutionId = $this->institutions()->value('institutions.id');

        return $pivotInstitutionId ? (int) $pivotInstitutionId : null;
    }

    public function syncInstitutionMembership(): void
    {
        if (! Schema::hasTable('institution_user')) {
            return;
        }

        $this->institutions()->detach();

        if ($this->institution_id) {
            $this->institutions()->attach((int) $this->institution_id);
        }
    }

}
