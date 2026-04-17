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
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'identity_number' => $data['identity_number'] ?? null,
            'role' => $this->normalizeRole($data['role'] ?? 'citizen'),
            'institution_id' => $data['institution_id'] ?? null,
            'department_id' => $data['department_id'] ?? null,
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
