<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PlanSeeder::class,
            CreditPackageSeeder::class,
            RoleKeywordSeeder::class,
            FreeEmailProviderSeeder::class,
            AdminUserSeeder::class,
        ]);
    }
}
