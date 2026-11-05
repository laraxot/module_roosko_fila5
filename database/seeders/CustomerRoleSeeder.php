<?php

declare(strict_types=1);

namespace Modules\Roosko\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\User\Models\Role;

/**
 * Ruolo dell'area clienti: il nome coincide con l'id del pannello (`BaseUser::canAccessPanel`).
 * Idempotente.
 */
class CustomerRoleSeeder extends Seeder
{
    public const string ROLE = 'roosko::customer';

    public function run(): void
    {
        Role::findOrCreate(self::ROLE);
    }
}
