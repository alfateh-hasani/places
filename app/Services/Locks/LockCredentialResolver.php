<?php

namespace App\Services\Locks;

use App\Models\Apartment;
use RuntimeException;

class LockCredentialResolver
{
    /**
     * Single canonical source of smart-lock credentials for an apartment:
     * Apartment -> SmartLock -> Building.ttlock_username/ttlock_password.
     */
    public function forApartment(Apartment $apartment): LockCredentials
    {
        $lock = $apartment->smartLock;

        if (! $lock) {
            throw new RuntimeException("Apartment {$apartment->id} has no smart lock configured.");
        }

        $building = $lock->building;

        if (! $building) {
            throw new RuntimeException("Smart lock {$lock->id} has no building configured.");
        }

        return new LockCredentials(
            lockId: $lock->lock_id,
            username: $building->ttlock_username,
            password: $building->ttlock_password,
        );
    }
}
