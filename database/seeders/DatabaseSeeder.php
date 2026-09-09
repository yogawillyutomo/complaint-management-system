<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Reference Data Only
        |--------------------------------------------------------------------------
        |
        | Normal database seeding intentionally creates no login accounts and
        | no complaint fixtures. Controlled UAT accounts/scenarios live in the
        | dedicated UatScenarioSeeder and require a non-production environment
        | plus an explicit UAT_DEMO_PASSWORD.
        |
        */
        $this->call([
            RoleSeeder::class,
            CategorySeeder::class,
            PrioritySeeder::class,
        ]);
    }
}
