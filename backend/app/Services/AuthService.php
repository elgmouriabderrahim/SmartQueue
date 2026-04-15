<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function register(array $data): array
    {
        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'phone' => $data['phone'] ?? null,
            'identity_number' => $data['identity_number'] ?? null,
            'role' => $this->normalizeRole($data['role'] ?? 'citizen'),
            'institution_id' => $data['institution_id'] ?? null,
            'department_id' => $data['department_id'] ?? null,
            'status' => $data['status'] ?? 'active',
        ]);

        $token = $user->createToken('api-token')->plainTextToken;

        if (in_array($user->role, ['manager', 'employee'], true)) {
            $user->setAttribute('api_role', 'institution');
        }

        return compact('user', 'token');
    }

    public function login(array $credentials): array
    {
        $user = User::query()->where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials.'],
            ]);
        }

        if ($user->status !== 'active') {
            throw ValidationException::withMessages([
                'email' => ['User account is not active.'],
            ]);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        if (in_array($user->role, ['manager', 'employee'], true)) {
            $user->setAttribute('api_role', 'institution');
        }

        return compact('user', 'token');
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()?->delete();
    }

    private function normalizeRole(string $role): string
    {
        return match ($role) {
            'institution' => 'manager',
            default => $role,
        };
    }
}
