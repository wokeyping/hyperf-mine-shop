<?php

declare(strict_types=1);

use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

/**
 * H5 / 手机号验证码注册无微信 openid，需允许该列为空（唯一索引下允许多个 NULL）.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', static function (Blueprint $table) {
            $table->string('openid', 100)->nullable()->change()->comment('微信OpenID，H5 手机注册等可为空');
        });
    }

    public function down(): void
    {
        Schema::table('members', static function (Blueprint $table) {
            $table->string('openid', 100)->nullable(false)->change()->comment('微信OpenID');
        });
    }
};
