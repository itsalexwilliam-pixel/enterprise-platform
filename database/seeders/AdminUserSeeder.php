<?php

namespace Database\Seeders;

use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name'                     => 'Admin User',
                'password'                 => Hash::make('password'),
                'role'                     => 'admin',
                'status'                   => 'active',
                'credit_balance'           => 999999,
                'email_verified_at'        => now(),
                'email_verification_token' => null,
            ]
        );

        if (!$admin->apiKeys()->exists()) {
            $key = 'ev_' . Str::random(56);
            ApiKey::create([
                'user_id'               => $admin->id,
                'name'                  => 'Admin API Key',
                'key'                   => $key,
                'key_prefix'            => substr($key, 0, 8),
                'status'                => 'active',
                'rate_limit_per_minute' => 10000,
                'rate_limit_per_day'    => 1000000,
            ]);
        }

        // Regular demo user
        $user = User::firstOrCreate(
            ['email' => 'user@example.com'],
            [
                'name'                     => 'Demo User',
                'password'                 => Hash::make('password'),
                'role'                     => 'user',
                'status'                   => 'active',
                'credit_balance'           => 500,
                'email_verified_at'        => now(),
                'email_verification_token' => null,
            ]
        );

        if (!$user->apiKeys()->exists()) {
            $key2 = 'ev_' . Str::random(56);
            ApiKey::create([
                'user_id'               => $user->id,
                'name'                  => 'My Default Key',
                'key'                   => $key2,
                'key_prefix'            => substr($key2, 0, 8),
                'status'                => 'active',
                'rate_limit_per_minute' => 60,
                'rate_limit_per_day'    => 10000,
            ]);
        }

        $this->command->info('Admin: admin@example.com / password');
        $this->command->info('User:  user@example.com  / password');
    }
}
