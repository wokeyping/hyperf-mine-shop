<?php

declare(strict_types=1);
/**
 * 本地磁盘存储模式下，附件 URL 形如 /uploads/日期/文件名，需提供 HTTP 可读入口.
 */

namespace App\Interface\Common\Controller;

use Hyperf\HttpServer\Contract\ResponseInterface;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;

final class LocalUploadFileController
{
    public function serve(string $path, ResponseInterface $response): PsrResponseInterface
    {
        $storageRoot = BASE_PATH . '/storage/uploads';
        $relative = rawurldecode($path);
        $relative = str_replace(['\\', "\0"], '/', $relative);
        if (str_contains($relative, '..')) {
            return $response->raw('Not Found')->withStatus(404);
        }

        $candidate = $storageRoot . '/' . $relative;
        $fullPath = realpath($candidate);
        $rootReal = realpath($storageRoot);

        if ($rootReal === false || $fullPath === false) {
            return $response->raw('Not Found')->withStatus(404);
        }

        if ($fullPath !== $rootReal && ! str_starts_with($fullPath, $rootReal . DIRECTORY_SEPARATOR)) {
            return $response->raw('Not Found')->withStatus(404);
        }

        if (! is_file($fullPath) || ! is_readable($fullPath)) {
            return $response->raw('Not Found')->withStatus(404);
        }

        return $response->download($fullPath);
    }
}
