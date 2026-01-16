<?php

namespace Database\Seeders\Concerns;

use App\Models\User;
use RuntimeException;

trait InteractsWithSeedUser
{
    protected function seedUserId(): int
    {
        $userId = User::query()->value('id');

        if (! $userId) {
            throw new RuntimeException('Seed data requires at least one user.');
        }

        return (int) $userId;
    }
}
