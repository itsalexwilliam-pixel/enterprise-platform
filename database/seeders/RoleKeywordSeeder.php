<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleKeywordSeeder extends Seeder
{
    public function run(): void
    {
        $roleKeywords = [
            // Administrative
            'admin', 'administrator', 'administrador', 'administration',
            // Support
            'support', 'helpdesk', 'help', 'desk', 'ticket', 'tickets',
            // Sales
            'sales', 'marketing', 'promo', 'promotions', 'offers',
            // Billing
            'billing', 'payments', 'invoice', 'invoices', 'accounts', 'accounting',
            // Information
            'info', 'information', 'contact', 'contacts', 'hello', 'hi',
            // Technical
            'webmaster', 'postmaster', 'hostmaster', 'mailer', 'noreply', 'no-reply',
            'donotreply', 'do-not-reply', 'notification', 'notifications',
            'alert', 'alerts', 'newsletter', 'newsletters', 'subscribe', 'unsubscribe',
            // System
            'root', 'sysadmin', 'system', 'server', 'daemon', 'mailer-daemon',
            'bounce', 'bounces', 'bounce-admin',
            // Business functions
            'hr', 'humanresources', 'careers', 'jobs', 'recruitment',
            'press', 'media', 'pr', 'legal', 'compliance',
            'security', 'privacy', 'gdpr', 'dpo',
            'finance', 'cfo', 'ceo', 'cto', 'coo',
            // Service
            'service', 'services', 'customer', 'customerservice',
            'feedback', 'reviews', 'survey',
            // Abuse
            'abuse', 'spam', 'phishing', 'report',
            // Generic
            'team', 'general', 'office', 'company',
        ];

        $data = array_map(fn ($kw) => [
            'keyword'    => strtolower($kw),
            'type'       => 'role',
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ], $roleKeywords);

        DB::table('role_keywords')->insertOrIgnore($data);
    }
}
