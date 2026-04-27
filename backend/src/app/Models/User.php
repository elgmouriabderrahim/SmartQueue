<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
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
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $appends = [
        'api_role',
        'institution_id',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function institutions()
    {
        return $this->belongsToMany(Institution::class, 'institution_user')
            ->withTimestamps();
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

    public function getInstitutionIdAttribute(): ?int
    {
        return $this->currentInstitutionId();
    }

    public function currentInstitutionId(): ?int
    {
        if ($this->relationLoaded('institutions')) {
            $institution = $this->institutions->first();

            return $institution?->id ? (int) $institution->id : null;
        }

        $pivotInstitutionId = $this->institutions()->value('institutions.id');

        return $pivotInstitutionId ? (int) $pivotInstitutionId : null;
    }

    public function syncInstitutionMembership(?int $institutionId = null): void
    {
        $institutionId ??= $this->currentInstitutionId();

        $this->institutions()->sync($institutionId ? [$institutionId] : []);
    }

}
