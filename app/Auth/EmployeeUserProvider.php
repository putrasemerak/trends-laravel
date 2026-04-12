<?php

namespace App\Auth;

use App\Models\Employee;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;

class EmployeeUserProvider implements UserProvider
{
    public function retrieveById($identifier): ?Authenticatable
    {
        return Employee::with('details')->find($identifier);
    }

    public function retrieveByToken($identifier, $token): ?Authenticatable
    {
        return null; // Not using remember tokens
    }

    public function updateRememberToken(Authenticatable $user, $token): void
    {
        // Not using remember tokens
    }

    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        if (empty($credentials['EmpNo'])) {
            return null;
        }

        return Employee::with('details')->where('EmpNo', $credentials['EmpNo'])->first();
    }

    /**
     * Validate credentials against the SY_0050 table.
     * The existing system stores plaintext passwords — we compare as-is to maintain compatibility.
     */
    public function validateCredentials(Authenticatable $user, array $credentials): bool
    {
        /** @var \App\Models\Employee $user */
        return $user->Pass === $credentials['password'];
    }

    public function rehashPasswordIfRequired(Authenticatable $user, array $credentials, bool $force = false): void
    {
        // Not applicable — passwords are managed in the corporate MSSQL system
    }
}
