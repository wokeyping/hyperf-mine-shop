# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## 项目概述

MineShop 是基于 MineAdmin 框架开发的高性能电商商城系统，采用 DDD（领域驱动设计）架构。
后端为 PHP 8.2 + Hyperf 3.1 + Swoole 5.0 协程框架，前端管理端为 Vue 3 + Element Plus，小程序端为 Taro。
项目处于开发阶段，暂不建议用于生产环境。

## 常用命令

### 后端
```bash
composer install                    # 安装依赖
composer dev                        # 启动开发服务器（热重载）
composer start                      # 启动生产服务器
composer test                       # 运行测试（co-phpunit，协程感知）
composer coverage                   # 运行测试并生成覆盖率
composer cs-fix                     # 代码格式化（PHP CS Fixer）
composer analyse                    # 静态分析（PHPStan level 5）

php bin/hyperf.php migrate          # 运行数据库迁移
php bin/hyperf.php db:seed          # 运行数据库填充
```

### 前端 (web/ 目录)
```bash
cd web && npm install               # 安装依赖
npm run dev                         # 启动 Vite 开发服务器
npm run build                       # 生产构建
npm run lint                        # 类型检查 + ESLint + Stylelint
npm run test:e2e                    # Playwright E2E 测试
```

### Docker
```bash
docker compose up                   # 启动 MySQL + Redis + Hyperf（开发模式热重载）
```

## DDD 架构

项目严格遵循 4 层 DDD 架构，调用链如下：

**后台管理端：**
```
Controller → App*CommandService / App*QueryService → Domain*Service → Repository
```

**小程序端（Api）：**
```
Controller → AppApi*CommandService → DomainApi*CommandService → Repository
```

### 目录结构

- `app/Interface/` — 接口层：Controller、Request 验证、DTO、Middleware。分为 `Admin/` 和 `Api/`（V1 版本化）
- `app/Application/` — 应用层：CommandService（写操作）、QueryService（读操作）。负责事务管理、缓存清理、事件发布，**不含业务逻辑**
- `app/Domain/` — 领域层：DomainService、Entity、Mapper、Repository、ValueObject、Contract 接口
- `app/Infrastructure/` — 基础设施层：Model（Hyperf Database）、外部服务集成（微信、支付）、命令、定时任务、监听器
- `config/` — 配置文件
- `databases/migrations/` — 数据库迁移
- `databases/seeders/` — 数据填充
- `plugins/` — 插件（export-center、express、sms、wechat）
- `web/` — 后台管理前端（Vue 3）
- `miniprogram/` — 微信小程序端（Taro）

### Service 命名规范

| 层级 | 前缀 | 示例 |
|------|------|------|
| Application 后台 Command | `App` | `AppOrderCommandService` |
| Application 后台 Query | `App` | `AppOrderQueryService` |
| Application 小程序 | `AppApi` | `AppApiOrderCommandService` |
| Domain Service | `Domain` | `DomainOrderService` |
| Domain Api Command | `DomainApi` | `DomainApiOrderCommandService` |

### 关键设计规则

1. **DTO 通过 Contract 接口解耦**：DTO 实现 Domain 层定义的 Contract 接口（如 `UserInput`），Controller 通过 `Request::toDto()` 转换
2. **Entity 使用 dirty 追踪**：Entity 内部通过 `markDirty()` 标记修改字段，`toArray()` 只返回已修改的字段
3. **简单 CRUD 不需要 Entity**：直接在 Domain Service 中使用 `DTO::toArray()`，Contract 接口声明 `toArray()` 方法
4. **复杂业务需要 Entity**：Domain Service 提供 `getEntity(int $id)` 方法，通过 `Mapper::fromModel()` 转换
5. **异常使用标准 PHP 异常**：`\DomainException`、`\RuntimeException`，不创建自定义领域异常
6. **值对象命名以 `Vo` 结尾**：如 `GrantRolesVo`，不用 `Result`

完整 DDD 规范文档见 `docs/DDD-ARCHITECTURE.md`。

## 路由

使用 Hyperf PHP 属性注解定义路由：

```php
#[Controller(prefix: '/admin/user')]
#[PostMapping(path: '')]           // POST /admin/user
#[PutMapping(path: '{id}')]       // PUT /admin/user/{id}
#[DeleteMapping(path: '{id}')]    // DELETE /admin/user/{id}
```

API 路由版本化：`#[AutoController(prefix: '/api/v1/login')]`
中间件通过 `#[Middleware]` 属性按 priority 排序应用。

## 业务模块

- **Catalog**：商品分类、品牌、商品、SKU、规格
- **Trade**：订单、售后、支付（微信支付 + 支付宝）
- **Member**：会员、等级、标签、钱包、积分
- **Marketing**：优惠券、秒杀、拼团
- **Permission**：用户、角色、菜单、部门（RBAC 通过 Casbin）

## 数据库

- MySQL 8.0（Docker 端口 3309→3306），Redis 7.2（端口 6380→6379）
- Model 缓存通过 `RedisHandler`，TTL 7 天
- RBAC 使用 Casbin 规则表

## 开发工作流规则

1. **数据库变更必须写迁移**：任何涉及数据库 schema 的变更（新增表、新增/修改字段、索引、外键等），必须同步在 `databases/migrations/` 下创建迁移文件，不得直接手动修改数据库，确保环境间的一致性和可追溯性。
2. **代码修改后的操作命令顺序**：
   - 后端 PHP 代码修改后：`composer cs-fix`（格式化）→ `composer analyse`（静态分析）→ 重启服务（`docker compose restart hyperf` 或 `composer dev`）
   - 前端 Vue 代码修改后：`cd web && npm run lint`（检查）→ `npm run build`（构建验证）
   - 数据库迁移变更后：`php bin/hyperf.php migrate`（执行迁移）
3. **新增 API 接口必须写文档**：新增接口时必须同步更新对应的接口文档（`API接口文档.md`、`API接口文档-Api.md`、`API接口文档-Admin.md`），文档需包含：请求路径与方法、请求参数（含类型和说明）、返回数据结构。

## 代码规范

- 所有 PHP 文件必须 `declare(strict_types=1)`
- PHP CS Fixer 强制 PSR-12 / PER-CS2.0
- 导入按字母排序，单引号字符串，无未使用导入
- PHPStan level 5 静态分析
- 前端使用 Antfu ESLint 配置 + vue-tsc 类型检查
