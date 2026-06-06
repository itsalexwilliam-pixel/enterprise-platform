<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FreeEmailProviderSeeder extends Seeder
{
    public function run(): void
    {
        $providers = [
            'gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com', 'live.com',
            'msn.com', 'icloud.com', 'me.com', 'mac.com', 'aol.com',
            'protonmail.com', 'pm.me', 'proton.me', 'fastmail.com', 'fastmail.fm',
            'gmx.com', 'gmx.net', 'gmx.de', 'web.de', 'yandex.com',
            'yandex.ru', 'mail.ru', 'inbox.ru', 'list.ru', 'bk.ru',
            'zoho.com', 'zohomail.com', 'tutanota.com', 'tuta.io',
            'hushmail.com', 'hush.com', 'hushmail.me', 'nym.hush.com',
            'rediffmail.com', 'lycos.com', 'excite.com', 'inbox.com',
            'aim.com', 'yahoo.co.uk', 'yahoo.com.au', 'yahoo.fr',
            'yahoo.de', 'yahoo.es', 'yahoo.it', 'yahoo.ca',
            'rocketmail.com', 'ymail.com',
        ];

        $rows = array_map(fn($d) => ['domain' => $d, 'created_at' => now()], array_unique($providers));

        DB::table('free_email_providers')->insertOrIgnore($rows);
    }
}
