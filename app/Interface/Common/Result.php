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

namespace App\Interface\Common;

use Hyperf\Contract\Arrayable;

/**
 * @template T
 */
class Result implements Arrayable
{
    /**
     * @param T $data
     */
    public function __construct(
        public ResultCode $code = ResultCode::SUCCESS,
        public ?string $message = null,
        public mixed $data = []
    ) {
        if ($this->message === null) {
            $this->message = ResultCode::getMessage($this->code->value);
        }
    }

    public function toArray(): array
    {
        return [
            'code' => $this->code->value,
            'message' => $this->message,
            'data' => self::ensureUtf8($this->data),
        ];
    }

    /**
     * 递归确保数据为合法 UTF-8 编码.
     */
    private static function ensureUtf8(mixed $data): mixed
    {
        if (\is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = self::ensureUtf8($value);
            }
            return $data;
        }
        if (\is_string($data) && ! mb_check_encoding($data, 'UTF-8')) {
            return mb_convert_encoding($data, 'UTF-8', 'UTF-8');
        }
        return $data;
    }
}
