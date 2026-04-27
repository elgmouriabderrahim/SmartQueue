<?php

namespace App\Services;

use App\Models\User;

class UserManagementService
{
    public function create(array $data): User
    {
        $data = $this->normalizeInstitutionFields($data);
        $user = User::query()->create($data);
        $user->syncInstitutionMembership();

        return $user;
    }

    public function update(User $user, array $data): User
    {
        if (array_key_exists('password', $data) && $data['password'] === null) {
            unset($data['password']);
        }

        $data = $this->normalizeInstitutionFields($data, $user);
        $user->update($data);
        $user->syncInstitutionMembership();

        return $user->fresh();
    }

    public function delete(User $user): void
    {
        $user->delete();
    }

    private function normalizeInstitutionFields(array $data, ?User $currentUser = null): array
    {
        $role = (string) ($data['role'] ?? $currentUser?->role ?? 'citizen');

        if (in_array($role, ['citizen', 'admin'], true)) {
            $data['institution_id'] = null;
            $data['department_id'] = null;

            return $data;
        }

        if (! array_key_exists('institution_id', $data) && $currentUser) {
            $data['institution_id'] = $currentUser->institution_id;
        }

        if (! array_key_exists('department_id', $data) && $currentUser) {
            $data['department_id'] = $currentUser->department_id;
        }

        return $data;
    }
}
