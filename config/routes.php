<?php

declare(strict_types=1);
/**
 * This file is part of MineAdmin.
 *
 * @link     https://www.mineadmin.com
 * @document https://doc.mineadmin.com
 * @contact  root@imoi.cn
 * @license  https://github.com/mineadmin/MineAdmin/blob/master/LICENSE
 */
use App\Interface\Common\Controller\LocalUploadFileController;
use Hyperf\HttpServer\Router\Router;

Router::get('/', static function () {
    return 'welcome use mineAdmin';
});

Router::get('/favicon.ico', static function () {
    return '';
});

// 本地存储（file.storage.local）：通过 /uploads 访问 storage/uploads 下文件，与 public_url 一致
Router::get('/uploads/{path:.+}', [LocalUploadFileController::class, 'serve']);
