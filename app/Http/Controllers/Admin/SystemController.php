<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class SystemController extends Controller
{
    public function index()
    {
        // Queue sizes
        $queues = [];
        try {
            $queueNames = ['default', 'smtp', 'dns', 'bulk', 'webhooks'];
            foreach ($queueNames as $q) {
                $queues[$q] = Redis::llen("queues:{$q}") ?? 0;
            }
        } catch (\Throwable) {}

        // Redis info
        $redisInfo = [];
        try {
            $info = Redis::info();
            $redisInfo = [
                'used_memory_human' => $info['used_memory_human'] ?? 'N/A',
                'connected_clients' => $info['connected_clients'] ?? 'N/A',
                'uptime_in_days'    => $info['uptime_in_days'] ?? 'N/A',
            ];
        } catch (\Throwable) {}

        // MySQL connections
        $mysqlConnections = 0;
        try {
            $mysqlConnections = DB::select("SHOW STATUS WHERE Variable_name = 'Threads_connected'")[0]->Value ?? 0;
        } catch (\Throwable) {}

        // PHP info
        $phpInfo = [
            'version'       => PHP_VERSION,
            'memory_limit'  => ini_get('memory_limit'),
            'max_execution' => ini_get('max_execution_time'),
            'extensions'    => ['pdo_mysql', 'redis', 'mbstring', 'bcmath', 'gd', 'zip'],
        ];

        $workers = \App\Models\Worker::orderBy('queue')->get();

        return view('admin.system', compact('queues', 'redisInfo', 'mysqlConnections', 'phpInfo', 'workers'));
    }
}
