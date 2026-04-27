<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Arr;

class UserManagementService
{
    public function create(array $data): User
    {
        $institutionId = $this->resolveInstitutionId($data);
        $user = User::query()->create($data);
        $user->syncInstitutionMembership($institutionId);

        return $user;
    }

    public function update(User $user, array $data): User
    {
        if (array_key_exists('password', $data) && $data['password'] === null) {
            unset($data['password']);
        }

        $institutionId = $this->resolveInstitutionId($data, $user);
        $user->update($data);
        $user->syncInstitutionMembership($institutionId);

        return $user->fresh();
    }

    public function delete(User $user): void
    {
        $user->delete();
    }

    private function resolveInstitutionId(array &$data, ?User $currentUser = null): ?int
    {
        $role = (string) ($data['role'] ?? $currentUser?->role ?? 'citizen');
        $institutionId = array_key_exists('institution_id', $data)
            ? (int) $data['institution_id']
            : $currentUser?->currentInstitutionId();

        Arr::forget($data, ['institution_id']);

        if (in_array($role, ['citizen', 'admin'], true)) {
            return null;
        }

        return $institutionId > 0 ? $institutionId : null;
    }
}
