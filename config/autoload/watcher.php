<?php

declare(strict_types=1);
/**
 * Hyperf 开发热重载（server:watch）.
 * docker compose 的 hyperf 服务在开发环境下可由此自动重启，无需手动 restart.
 */
use Hyperf\Watcher\Driver\ScanFileDriver;

return [
    'driver' => ScanFileDriver::class,
    'bin' => PHP_BINARY,
    'watch' => [
        'dir' => ['app', 'config', 'plugins'],
        'file' => ['.env'],
        // Windows + Docker 卷挂载下 inotify 不可用，ScanFileDriver 轮询间隔（毫秒）
        'scan_interval' => 2000,
    ],
    'ext' => ['.php', '.env'],
];
