<?php

namespace Database\Seeders;

use App\Models\CreditPackage;
use Illuminate\Database\Seeder;

class CreditPackageSeeder extends Seeder
{
    public function run(): void
    {
        $packages = [
            ['name' => 'Starter',    'credits' => 5000,   'price' => 19.00,  'bonus_credits' => 0,      'is_popular' => false, 'sort_order' => 1],
            ['name' => 'Growth',     'credits' => 25000,  'price' => 79.00,  'bonus_credits' => 5000,   'is_popular' => true,  'sort_order' => 2],
            ['name' => 'Business',   'credits' => 100000, 'price' => 249.00, 'bonus_credits' => 25000,  'is_popular' => false, 'sort_order' => 3],
            ['name' => 'Enterprise', 'credits' => 500000, 'price' => 999.00, 'bonus_credits' => 100000, 'is_popular' => false, 'sort_order' => 4],
        ];

        foreach ($packages as $pkg) {
            $pkg['price_per_credit'] = round($pkg['price'] / $pkg['credits'], 6);
            CreditPackage::firstOrCreate(
                ['name' => $pkg['name']],
                array_merge($pkg, ['is_active' => true])
            );
        }
    }
}
