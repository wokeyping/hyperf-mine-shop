# MineShop Admin 后台接口文档

> 根据 `app/Interface/Admin` 控制器注解自动生成（`php bin/generate-api-doc.php` 可重新生成）。
> **§2.1 总览表含「响应 data」列**（由控制器 `return` 推断）；精细字段以各 `Request`、`Dto` 为准。

[← 返回文档索引](./API接口文档.md)

**基础地址**

| 环境 | 地址 | 路径前缀 |
|------|------|----------|
| 本地 | `http://127.0.0.1:9501` | `/admin/*` |

---

## 一、通用约定

### 1.1 统一响应结构

除少数回调外，JSON 接口均返回：

```json
{
  "code": 200,
  "message": "成功提示或错误说明",
  "data": {}
}
```

| code | 含义 |
|------|------|
| 200 | 成功 |
| 401 | 未授权 |
| 403 | 无权限 |
| 404 | 资源不存在 |
| 422 | 参数校验失败 |
| 500 | 业务失败或服务器错误 |

### 1.2 分页响应（列表接口常见）

`data` 结构示例：

```json
{
  "list": [],
  "pagination": {
    "current_page": 1,
    "last_page": 10,
    "per_page": 15,
    "total": 150
  }
}
```

部分列表使用 MineAdmin 约定，也可能在 `data` 中直接返回 `items` + `pageInfo`，以实际接口为准。

### 1.3 鉴权

| 项 | 说明 |
|----|------|
| Header | `Authorization: Bearer {access_token}` |
| 登录 | `POST /admin/passport/login` |
| 刷新 | `POST /admin/passport/refresh` |
| 权限 | 各接口标注 `permission:code`，需在角色中授权 |

**登录请求体**

| 字段 | 类型 | 必填 | 说明 |
|------|------|------|------|
| username | string | 是 | 后台用户名 |
| password | string | 是 | 密码 |

**登录成功 `data`（典型）**：`access_token`、`refresh_token`、`expires_in`、用户信息等（见 `PassportController`）。

### 1.4 参数命名

- HTTP JSON 建议使用 **snake_case**（如 `order_no`）。
- GET 查询参数、POST JSON Body 均可能使用；路径参数见各接口路径中的 `{id}` 等。

---

## 二、接口列表（`/admin`）


### 2.1 接口总览

> 下列接口均需管理员登录（除 `passport/login`）。具体筛选项、表单字段见 `app/Interface/Admin/Request/**` 与 `app/Interface/Admin/Dto/**`。
| 方法 | 路径 | 控制器方法 | 请求类/参数 | 响应 `data` |
|------|------|------------|-------------|-------------|
| GET | `/admin/attachment/list` | list | UploadRequest $request | 分页列表（QueryService::page） |
| POST | `/admin/attachment/upload` | upload | UploadRequest $request | 业务数据（见 Service/Transformer） |
| DELETE | `/admin/attachment/{id}` | delete | int $id | 空对象 {} |
| POST | `/admin/coupon` | store | CouponRequest $request | 空对象 {} |
| POST | `/admin/coupon/export` | export | CouponRequest $request | task_id, status（导出任务） |
| GET | `/admin/coupon/list` | list | CouponRequest $request | 分页列表（QueryService::page） |
| GET | `/admin/coupon/stats` | stats | — | 统计数据对象 |
| GET | `/admin/coupon/user/list` | list | CouponUserRequest $request | 分页列表（QueryService::page） |
| PUT | `/admin/coupon/user/{id:\d+}/mark-expired` | markExpired | int $id, CouponUserRequest $request | null 或空 |
| PUT | `/admin/coupon/user/{id:\d+}/mark-used` | markUsed | int $id, CouponUserRequest $request | null 或空 |
| DELETE | `/admin/coupon/{id:\d+}` | delete | int $id | null 或空 |
| GET | `/admin/coupon/{id:\d+}` | show | int $id | 业务数据（见 Service/Transformer） |
| PUT | `/admin/coupon/{id:\d+}` | update | int $id, CouponRequest $request | 空对象 {} |
| POST | `/admin/coupon/{id:\d+}/issue` | issue | int $id, CouponIssueRequest $request | 业务数据（见控制器） |
| PUT | `/admin/coupon/{id:\d+}/toggle-status` | toggleStatus | int $id | null 或空 |
| GET | `/admin/dashboard/analysis` | analysis | — | 业务数据（见 Service/Transformer） |
| GET | `/admin/dashboard/report` | report | — | 业务数据（见 Service/Transformer） |
| GET | `/admin/dashboard/welcome` | welcome | — | 业务数据（见 Service/Transformer） |
| DELETE | `/admin/department` | delete | — | 空对象 {} |
| POST | `/admin/department` | create | DepartmentRequest $request | 空对象 {} |
| GET | `/admin/department/list` | pageList | — | 对象（见控制器内联数组） |
| PUT | `/admin/department/{id}` | save | int $id, DepartmentRequest $request | 空对象 {} |
| POST | `/admin/group-buy` | store | GroupBuyRequest $request | 空对象 {} |
| POST | `/admin/group-buy-order/export` | export | — | task_id, status（导出任务） |
| GET | `/admin/group-buy-order/list` | list | — | 业务数据（见 Service/Transformer） |
| GET | `/admin/group-buy-order/{activityId:\d+}/orders` | orders | int $activityId | 业务数据（见 Service/Transformer） |
| POST | `/admin/group-buy/export` | export | GroupBuyRequest $request | task_id, status（导出任务） |
| GET | `/admin/group-buy/list` | list | GroupBuyRequest $request | 分页列表（QueryService::page） |
| GET | `/admin/group-buy/stats` | stats | — | 统计数据对象 |
| DELETE | `/admin/group-buy/{id:\d+}` | delete | int $id | null 或空 |
| GET | `/admin/group-buy/{id:\d+}` | show | int $id | 业务数据（见 Service/Transformer） |
| PUT | `/admin/group-buy/{id:\d+}` | update | int $id, GroupBuyRequest $request | 空对象 {} |
| PUT | `/admin/group-buy/{id:\d+}/toggle-status` | toggleStatus | int $id | null 或空 |
| DELETE | `/admin/leader` | delete | — | 空对象 {} |
| POST | `/admin/leader` | create | LeaderRequest $request | 空对象 {} |
| GET | `/admin/leader/list` | pageList | — | 分页列表（QueryService::page） |
| POST | `/admin/member/account/wallet/adjust` | adjust | MemberAccountRequest $request | 业务数据（见 Service/Transformer） |
| GET | `/admin/member/account/wallet/logs` | walletLogs | MemberAccountRequest $request | 分页列表（QueryService::page） |
| POST | `/admin/member/level` | store | MemberLevelRequest $request | 业务数据（见 Service/Transformer） |
| GET | `/admin/member/level/list` | list | MemberLevelRequest $request | 分页列表（QueryService::page） |
| DELETE | `/admin/member/level/{id:\d+}` | delete | int $id | null 或空 |
| GET | `/admin/member/level/{id:\d+}` | show | int $id | 业务数据（见 Service/Transformer） |
| PUT | `/admin/member/level/{id:\d+}` | update | int $id, MemberLevelRequest $request | 业务数据（见 Service/Transformer） |
| POST | `/admin/member/member` | store | MemberRequest $request | 业务数据（见 Service/Transformer） |
| POST | `/admin/member/member/export` | export | MemberRequest $request | task_id, status（导出任务） |
| GET | `/admin/member/member/list` | list | MemberRequest $request | 业务数据（见 Service/Transformer） |
| GET | `/admin/member/member/overview` | overview | MemberRequest $request | 业务数据（见 Service/Transformer） |
| GET | `/admin/member/member/stats` | stats | MemberRequest $request | 统计数据对象 |
| GET | `/admin/member/member/{id:\d+}` | show | int $id | 业务数据（见 Service/Transformer） |
| PUT | `/admin/member/member/{id:\d+}` | update | int $id, MemberRequest $request | 业务数据（见 Service/Transformer） |
| PUT | `/admin/member/member/{id:\d+}/status` | updateStatus | int $id, MemberRequest $request | 空对象 {} |
| PUT | `/admin/member/member/{id:\d+}/tags` | syncTags | int $id, MemberRequest $request | 空对象 {} |
| POST | `/admin/member/tag` | store | MemberTagRequest $request | 空对象 {} |
| GET | `/admin/member/tag/list` | list | MemberTagRequest $request | 业务数据（见 Service/Transformer） |
| GET | `/admin/member/tag/options` | options | — | 业务数据（见 Service/Transformer） |
| DELETE | `/admin/member/tag/{id:\d+}` | delete | int $id | null 或空 |
| PUT | `/admin/member/tag/{id:\d+}` | update | int $id, MemberTagRequest $request | 空对象 {} |
| DELETE | `/admin/menu` | delete | — | 空对象 {} |
| POST | `/admin/menu` | create | MenuRequest $request | 空对象 {} |
| GET | `/admin/menu/list` | pageList | RequestInterface $request | 业务数据（见控制器） |
| PUT | `/admin/menu/{id}` | save | int $id, MenuRequest $request | 空对象 {} |
| GET | `/admin/order/after-sale/list` | list | AfterSaleReviewRequest $request | 业务数据（见 Service/Transformer） |
| GET | `/admin/order/after-sale/{id:\d+}` | show | int $id | 业务数据（见 Service/Transformer） |
| PUT | `/admin/order/after-sale/{id:\d+}/approve` | approve | int $id, AfterSaleReviewRequest $request | 业务数据（见 Service/Transformer） |
| PUT | `/admin/order/after-sale/{id:\d+}/complete-exchange` | completeExchange | int $id, AfterSaleReviewRequest $request | 业务数据（见 Service/Transformer） |
| PUT | `/admin/order/after-sale/{id:\d+}/receive` | receive | int $id, AfterSaleReviewRequest $request | 业务数据（见 Service/Transformer） |
| PUT | `/admin/order/after-sale/{id:\d+}/refund` | refund | int $id, AfterSaleReviewRequest $request | 业务数据（见 Service/Transformer） |
| PUT | `/admin/order/after-sale/{id:\d+}/reject` | reject | int $id, AfterSaleReviewRequest $request | 业务数据（见 Service/Transformer） |
| PUT | `/admin/order/after-sale/{id:\d+}/reship` | reship | int $id, AfterSaleReviewRequest $request | 业务数据（见 Service/Transformer） |
| POST | `/admin/order/order/export` | export | OrderRequest $request | task_id, status（导出任务） |
| GET | `/admin/order/order/list` | list | OrderRequest $request | 业务数据（见 Service/Transformer） |
| GET | `/admin/order/order/stats` | stats | OrderRequest $request | 统计数据对象 |
| GET | `/admin/order/order/{id:\d+}` | show | int $id | 业务数据（见 Service/Transformer） |
| PUT | `/admin/order/order/{id:\d+}/cancel` | cancel | int $id, OrderRequest $request | 业务数据（见 Service/Transformer） |
| PUT | `/admin/order/order/{id:\d+}/ship` | ship | int $id, OrderRequest $request | 业务数据（见 Service/Transformer） |
| GET | `/admin/passport/getInfo` | getInfo | — | 用户基本信息字段 |
| POST | `/admin/passport/login` | login | PassportLoginRequest $request | access_token, refresh_token, expire_at |
| POST | `/admin/passport/logout` | logout | — | 空对象 {} |
| POST | `/admin/passport/refresh` | refresh | — | access_token, refresh_token, expire_at |
| GET | `/admin/permission/menus` | menus | — | 业务数据（见控制器） |
| GET | `/admin/permission/roles` | roles | — | 业务数据（见控制器） |
| POST | `/admin/permission/update` | update | PermissionRequest $request | 空对象 {} |
| DELETE | `/admin/position` | delete | — | 空对象 {} |
| POST | `/admin/position` | create | PositionRequest $request | 空对象 {} |
| GET | `/admin/position/list` | pageList | — | 分页列表（QueryService::page） |
| PUT | `/admin/position/{id}` | save | int $id, PositionRequest $request | 空对象 {} |
| PUT | `/admin/position/{id}/data_permission` | batchDataPermission | int $id, BatchGrantDataPermissionForPositionRequest $request | 空对象 {} |
| POST | `/admin/product/brand` | store | BrandRequest $request | 业务数据（见 Service/Transformer） |
| GET | `/admin/product/brand/list` | list | BrandRequest $request | 分页列表（QueryService::page） |
| GET | `/admin/product/brand/options` | options | — | 业务数据（见 Service/Transformer） |
| PUT | `/admin/product/brand/sort` | sort | BrandRequest $request | null 或空 |
| GET | `/admin/product/brand/statistics` | statistics | — | 业务数据（见 Service/Transformer） |
| DELETE | `/admin/product/brand/{id:\d+}` | delete | int $id | null 或空 |
| GET | `/admin/product/brand/{id:\d+}` | show | int $id | 业务数据（见 Service/Transformer） |
| PUT | `/admin/product/brand/{id:\d+}` | update | int $id, BrandRequest $request | 空对象 {} |
| POST | `/admin/product/category` | store | CategoryRequest $request | 业务数据（见 Service/Transformer） |
| GET | `/admin/product/category/list` | list | CategoryRequest $request | 分页列表（QueryService::page） |
| PUT | `/admin/product/category/move` | move | CategoryRequest $request | null 或空 |
| GET | `/admin/product/category/options` | options | CategoryRequest $request | 业务数据（见 Service/Transformer） |
| PUT | `/admin/product/category/sort` | sort | CategoryRequest $request | null 或空 |
| GET | `/admin/product/category/statistics` | statistics | — | 业务数据（见 Service/Transformer） |
| GET | `/admin/product/category/tree` | tree | CategoryRequest $request | 业务数据（见 Service/Transformer） |
| DELETE | `/admin/product/category/{id:\d+}` | delete | int $id | null 或空 |
| GET | `/admin/product/category/{id:\d+}` | show | int $id | 业务数据（见 Service/Transformer） |
| PUT | `/admin/product/category/{id:\d+}` | update | int $id, CategoryRequest $request | 空对象 {} |
| GET | `/admin/product/category/{id:\d+}/breadcrumb` | breadcrumb | int $id | 业务数据（见 Service/Transformer） |
| POST | `/admin/product/product` | store | ProductRequest $request | 业务数据（见 Service/Transformer） |
| POST | `/admin/product/product/export` | export | — | task_id, status（导出任务） |
| GET | `/admin/product/product/list` | list | — | 分页列表（QueryService::page） |
| PUT | `/admin/product/product/sort` | updateSort | RequestInterface $request | null 或空 |
| GET | `/admin/product/product/stats` | stats | — | 统计数据对象 |
| DELETE | `/admin/product/product/{id:\d+}` | delete | int $id | null 或空 |
| GET | `/admin/product/product/{id:\d+}` | show | int $id | 业务数据（见 Service/Transformer） |
| PUT | `/admin/product/product/{id:\d+}` | update | int $id, ProductRequest $request | 业务数据（见 Service/Transformer） |
| PUT | `/admin/product/product/{id:\d+}/status` | updateStatus | int $id, RequestInterface $request | null 或空 |
| GET | `/admin/review/by-order/{orderId:\d+}` | byOrder | int $orderId | 业务数据（见 Service/Transformer） |
| GET | `/admin/review/list` | list | ReviewRequest $request | 分页列表（QueryService::page） |
| GET | `/admin/review/stats` | stats | — | 统计数据对象 |
| GET | `/admin/review/{id:\d+}` | show | int $id | 业务数据（见 Service/Transformer） |
| PUT | `/admin/review/{id:\d+}/approve` | approve | int $id | 空对象 {} |
| PUT | `/admin/review/{id:\d+}/reject` | reject | int $id | 空对象 {} |
| PUT | `/admin/review/{id:\d+}/reply` | reply | int $id, ReviewReplyRequest $request | 空对象 {} |
| DELETE | `/admin/role` | delete | — | 空对象 {} |
| POST | `/admin/role` | create | RoleRequest $request | 空对象 {} |
| GET | `/admin/role/list` | pageList | — | 分页列表（QueryService::page） |
| PUT | `/admin/role/{id}` | save | int $id, RoleRequest $request | 空对象 {} |
| GET | `/admin/role/{id}/permissions` | getRolePermissionForRole | int $id | 业务数据（见 Service/Transformer） |
| PUT | `/admin/role/{id}/permissions` | batchGrantPermissionsForRole | int $id, BatchGrantPermissionsForRoleRequest $request | 空对象 {} |
| POST | `/admin/seckill-order/export` | export | — | task_id, status（导出任务） |
| GET | `/admin/seckill-order/list` | list | — | 业务数据（见 Service/Transformer） |
| GET | `/admin/seckill-order/{activityId:\d+}/orders` | orders | int $activityId | 业务数据（见 Service/Transformer） |
| POST | `/admin/seckill/activity` | store | SeckillActivityRequest $request | 空对象 {} |
| POST | `/admin/seckill/activity/export` | export | SeckillActivityRequest $request | task_id, status（导出任务） |
| GET | `/admin/seckill/activity/list` | list | SeckillActivityRequest $request | 分页列表（QueryService::page） |
| GET | `/admin/seckill/activity/stats` | stats | — | 统计数据对象 |
| DELETE | `/admin/seckill/activity/{id:\d+}` | delete | int $id | null 或空 |
| GET | `/admin/seckill/activity/{id:\d+}` | show | int $id | 业务数据（见 Service/Transformer） |
| PUT | `/admin/seckill/activity/{id:\d+}` | update | int $id, SeckillActivityRequest $request | 空对象 {} |
| PUT | `/admin/seckill/activity/{id:\d+}/toggle-status` | toggleStatus | int $id | null 或空 |
| POST | `/admin/seckill/product` | store | SeckillProductRequest $request | 空对象 {} |
| POST | `/admin/seckill/product/batch` | batchStore | SeckillProductRequest $request | 空对象 {} |
| GET | `/admin/seckill/product/by-session/{sessionId:\d+}` | bySession | int $sessionId | 业务数据（见控制器） |
| GET | `/admin/seckill/product/list` | list | SeckillProductRequest $request | 分页列表（QueryService::page） |
| DELETE | `/admin/seckill/product/{id:\d+}` | delete | int $id | null 或空 |
| GET | `/admin/seckill/product/{id:\d+}` | show | int $id | 业务数据（见 Service/Transformer） |
| PUT | `/admin/seckill/product/{id:\d+}` | update | int $id, SeckillProductRequest $request | 空对象 {} |
| PUT | `/admin/seckill/product/{id:\d+}/toggle-status` | toggleStatus | int $id | null 或空 |
| POST | `/admin/seckill/session` | store | SeckillSessionRequest $request | 空对象 {} |
| GET | `/admin/seckill/session/by-activity/{activityId:\d+}` | byActivity | int $activityId | 业务数据（见控制器） |
| GET | `/admin/seckill/session/list` | list | SeckillSessionRequest $request | 分页列表（QueryService::page） |
| DELETE | `/admin/seckill/session/{id:\d+}` | delete | int $id | null 或空 |
| GET | `/admin/seckill/session/{id:\d+}` | show | int $id | 业务数据（见 Service/Transformer） |
| PUT | `/admin/seckill/session/{id:\d+}` | update | int $id, SeckillSessionRequest $request | 空对象 {} |
| PUT | `/admin/seckill/session/{id:\d+}/toggle-status` | toggleStatus | int $id | null 或空 |
| POST | `/admin/shipping/templates` | store | ShippingTemplateRequest $request | 业务数据（见控制器） |
| GET | `/admin/shipping/templates/list` | list | ShippingTemplateRequest $request | 分页列表（QueryService::page） |
| DELETE | `/admin/shipping/templates/{id:\d+}` | destroy | int $id | null 或空 |
| GET | `/admin/shipping/templates/{id:\d+}` | show | int $id | 业务数据（见控制器） |
| PUT | `/admin/shipping/templates/{id:\d+}` | update | int $id, ShippingTemplateRequest $request | 空对象 {} |
| POST | `/admin/system-message/batchSend` | batchSend | — | 对象（见控制器） |
| DELETE | `/admin/system-message/delete` | delete | — | 对象（见控制器） |
| GET | `/admin/system-message/index` | index | — | 业务数据（见 Service/Transformer） |
| GET | `/admin/system-message/popular` | popular | — | 业务数据（见 Service/Transformer） |
| GET | `/admin/system-message/preference/checkDoNotDisturb` | checkDoNotDisturb | — | 对象（见控制器） |
| GET | `/admin/system-message/preference/defaults` | getDefaults | — | 业务数据（见 Service/Transformer） |
| GET | `/admin/system-message/preference/index` | index | — | 业务数据（见 Service/Transformer） |
| POST | `/admin/system-message/preference/reset` | reset | — | 业务数据（见 Service/Transformer） |
| PUT | `/admin/system-message/preference/setDoNotDisturbTime` | setDoNotDisturbTime | — | 业务数据（见 Service/Transformer） |
| PUT | `/admin/system-message/preference/setMinPriority` | setMinPriority | — | 业务数据（见 Service/Transformer） |
| PUT | `/admin/system-message/preference/toggleDoNotDisturb` | toggleDoNotDisturb | — | 业务数据（见 Service/Transformer） |
| PUT | `/admin/system-message/preference/update` | update | UpdatePreferenceRequest $request | 业务数据（见 Service/Transformer） |
| PUT | `/admin/system-message/preference/updateChannels` | updateChannelPreferences | — | 业务数据（见 Service/Transformer） |
| PUT | `/admin/system-message/preference/updateTypes` | updateTypePreferences | — | 业务数据（见 Service/Transformer） |
| GET | `/admin/system-message/read/{id}` | read | int $id | 业务数据（见 Service/Transformer） |
| GET | `/admin/system-message/recent` | recent | — | 业务数据（见 Service/Transformer） |
| POST | `/admin/system-message/save` | save | CreateMessageRequest $request | 业务数据（见 Service/Transformer） |
| POST | `/admin/system-message/schedule` | schedule | — | 对象（见控制器） |
| GET | `/admin/system-message/search` | search | — | 业务数据（见 Service/Transformer） |
| POST | `/admin/system-message/send` | send | — | 对象（见控制器） |
| GET | `/admin/system-message/statistics` | statistics | — | 业务数据（见 Service/Transformer） |
| GET | `/admin/system-message/template/active` | getActiveTemplates | — | 业务数据（见 Service/Transformer） |
| GET | `/admin/system-message/template/categories` | getCategories | — | 业务数据（见 Service/Transformer） |
| PUT | `/admin/system-message/template/changeStatus` | changeStatus | — | 对象（见控制器） |
| POST | `/admin/system-message/template/copy` | copy | — | 业务数据（见 Service/Transformer） |
| DELETE | `/admin/system-message/template/delete` | delete | — | 对象（见控制器） |
| POST | `/admin/system-message/template/export` | export | — | 业务数据（见 Service/Transformer） |
| GET | `/admin/system-message/template/getVariables/{id}` | getVariables | int $id | 业务数据（见 Service/Transformer） |
| POST | `/admin/system-message/template/import` | import | — | 业务数据（见 Service/Transformer） |
| GET | `/admin/system-message/template/index` | index | — | 业务数据（见 Service/Transformer） |
| POST | `/admin/system-message/template/preview` | preview | — | 业务数据（见 Service/Transformer） |
| GET | `/admin/system-message/template/read/{id}` | read | int $id | 业务数据（见 Service/Transformer） |
| POST | `/admin/system-message/template/render` | render | — | 业务数据（见 Service/Transformer） |
| POST | `/admin/system-message/template/save` | save | CreateTemplateRequest $request | 业务数据（见 Service/Transformer） |
| GET | `/admin/system-message/template/search` | search | — | 业务数据（见 Service/Transformer） |
| PUT | `/admin/system-message/template/update/{id}` | update | int $id, UpdateTemplateRequest $request | 业务数据（见 Service/Transformer） |
| POST | `/admin/system-message/template/validateVariables` | validateVariables | — | 业务数据（见 Service/Transformer） |
| PUT | `/admin/system-message/update/{id}` | update | int $id, UpdateMessageRequest $request | 业务数据（见 Service/Transformer） |
| DELETE | `/admin/system-message/user/batchDelete` | batchDelete | — | 对象（见控制器） |
| PUT | `/admin/system-message/user/batchMarkRead` | batchMarkAsRead | — | 对象（见控制器） |
| DELETE | `/admin/system-message/user/delete/{messageId}` | delete | int $messageId | null 或空 |
| GET | `/admin/system-message/user/index` | index | — | 业务数据（见 Service/Transformer） |
| PUT | `/admin/system-message/user/markAllRead` | markAllAsRead | — | 对象（见控制器） |
| PUT | `/admin/system-message/user/markRead/{messageId}` | markAsRead | int $messageId | null 或空 |
| GET | `/admin/system-message/user/read/{messageId}` | read | int $messageId | 业务数据（见 Service/Transformer） |
| GET | `/admin/system-message/user/search` | search | — | 业务数据（见 Service/Transformer） |
| GET | `/admin/system-message/user/typeStats` | getTypeStats | — | 业务数据（见 Service/Transformer） |
| GET | `/admin/system-message/user/unreadCount` | getUnreadCount | — | 对象（见控制器） |
| GET | `/admin/system/setting/group/{group}` | group | string $group | 业务数据（见 Service/Transformer） |
| GET | `/admin/system/setting/groups` | groups | — | 业务数据（见 Service/Transformer） |
| GET | `/admin/system/setting/values` | values | ?string $keys = null | 业务数据（见 Service/Transformer） |
| PUT | `/admin/system/setting/{key}` | update | string $key, SystemSettingRequest $request | 业务数据（见 Service/Transformer） |
| DELETE | `/admin/user` | delete | — | 空对象 {} |
| POST | `/admin/user` | create | UserRequest $request | 空对象 {} |
| PUT | `/admin/user` | updateInfo | UserRequest $request | 空对象 {} |
| DELETE | `/admin/user-login-log` | delete | RequestInterface $request | 空对象 {} |
| GET | `/admin/user-login-log/list` | page | — | 业务数据（见 Service/Transformer） |
| DELETE | `/admin/user-operation-log` | delete | RequestInterface $request | 空对象 {} |
| GET | `/admin/user-operation-log/list` | page | — | 业务数据（见 Service/Transformer） |
| GET | `/admin/user/list` | pageList | — | 分页列表（QueryService::page） |
| PUT | `/admin/user/password` | resetPassword | ResetPasswordRequest $request | 业务数据（见控制器） |
| PUT | `/admin/user/{userId}` | save | int $userId, UserRequest $request | 空对象 {} |
| GET | `/admin/user/{userId}/roles` | getUserRole | int $userId | 业务数据（见 Service/Transformer） |
| PUT | `/admin/user/{userId}/roles` | batchGrantRolesForUser | int $userId, BatchGrantRolesForUserRequest $request | 空对象 {} |


### 2.2 登录与权限

| 方法 | 路径 | 说明 | 响应 `data` |
|------|------|------|-------------|
| POST | `/admin/passport/login` | 登录 | `access_token`、`refresh_token`、`expire_at` |
| POST | `/admin/passport/logout` | 退出 | 空对象 `{}` |
| GET | `/admin/passport/getInfo` | 当前用户 | username、nickname、avatar、phone、email 等 |
| POST | `/admin/passport/refresh` | 刷新 Token | 同 login |
| GET | `/admin/permission/menus` | 菜单树 | 菜单数组 |
| GET | `/admin/permission/roles` | 角色选项 | 角色列表 |
| POST | `/admin/permission/update` | 更新权限 | 见控制器返回 |

### 2.3 用户 / 角色 / 菜单 / 部门 / 岗位

| 模块 | 前缀 | 主要能力 |
|------|------|----------|
| 用户 | `/admin/user` | list、增删改、改密、分配角色 |
| 角色 | `/admin/role` | list、CRUD、分配菜单权限 |
| 菜单 | `/admin/menu` | list、CRUD |
| 部门 | `/admin/department` | list、CRUD |
| 岗位 | `/admin/position` | list、CRUD、数据权限 |
| 领导 | `/admin/leader` | list、设置、删除 |

Request 参考：`app/Interface/Admin/Request/Permission/*`

### 2.4 商品中心

| 模块 | 前缀 | 主要能力 |
|------|------|----------|
| 商品 | `/admin/product/product` | list、stats、详情、CRUD、上下架、排序、导出 |
| 分类 | `/admin/product/category` | list、tree、CRUD、options、统计、排序、移动、面包屑 |
| 品牌 | `/admin/product/brand` | list、CRUD、options、统计、排序 |

Request 参考：`app/Interface/Admin/Request/Product/*`

### 2.5 订单与售后

| 模块 | 前缀 | 主要能力 |
|------|------|----------|
| 订单 | `/admin/order/order` | list、stats、详情、发货、取消、导出 |
| 售后 | `/admin/order/after-sale` | list、详情、审核、收货、退款、重发、完成换货 |
| 秒杀订单 | `/admin/seckill-order` | list、按活动查订单、导出 |
| 拼团订单 | `/admin/group-buy-order` | list、按活动查订单、导出 |

**订单列表筛选（Query）** — `OrderRequest::listRules`

| 参数 | 说明 |
|------|------|
| order_no / pay_no | 订单号/支付单号 |
| member_id / member_phone | 会员 |
| product_name | 商品名 |
| status / pay_status | 订单/支付状态 |
| start_date / end_date | 日期范围 |

**发货 Body（PUT `/{id}/ship`）**

| 字段 | 说明 |
|------|------|
| shipping_company | 物流公司 |
| shipping_no | 运单号 |
| remark | 备注 |

### 2.6 会员

| 模块 | 前缀 | 主要能力 |
|------|------|----------|
| 会员 | `/admin/member/member` | list、stats、overview、CRUD、状态、标签、导出 |
| 等级 | `/admin/member/level` | CRUD |
| 标签 | `/admin/member/tag` | list、options、CRUD |
| 账户 | `/admin/member/account` | 钱包流水、余额调整 |

### 2.7 营销

| 模块 | 前缀 | 主要能力 |
|------|------|----------|
| 优惠券 | `/admin/coupon` | list、stats、CRUD、启停、发放、导出 |
| 领券记录 | `/admin/coupon/user` | list、标记已用/过期 |
| 秒杀活动 | `/admin/seckill/activity` | list、stats、CRUD、启停、导出 |
| 秒杀场次 | `/admin/seckill/session` | list、按活动查、CRUD、启停 |
| 秒杀商品 | `/admin/seckill/product` | list、按场次查、CRUD、批量、启停 |
| 拼团 | `/admin/group-buy` | list、stats、CRUD、启停、导出 |

### 2.8 运营与系统

| 模块 | 前缀 | 主要能力 |
|------|------|----------|
| 评价 | `/admin/review` | list、详情、审核、回复、统计、按订单查 |
| 运费模板 | `/admin/shipping/templates` | CRUD |
| 系统设置 | `/admin/system/setting` | 分组读取、按键更新、批量 values |
| 附件 | `/admin/attachment` | list、upload、delete |
| 仪表盘 | `/admin/dashboard` | welcome、analysis、report |
| 登录/操作日志 | `/admin/user-login-log`、`/admin/user-operation-log` | list、删除 |
| 站内信 | `admin/system-message/*` | 消息、模板、用户消息、偏好设置 |

### 2.9 Admin 通用说明

1. **列表接口**：多数为 `GET .../list` 或 `page`，`data` 为分页结构（`list`/`items` + 分页字段），详见 **2.1 总览表「响应 data」列**。
2. **详情接口**：`GET .../{id}` 或 `read/{id}`，返回单条实体或 Dto。
3. **创建/更新**：`POST`/`PUT` 成功常返回空 `{}`、新建 `id` 或完整实体，见各控制器。
4. **删除**：部分为 `DELETE` + Body 传 `ids`，返回 `{ deleted, failed }` 等。
5. **导出**：`POST .../export` 通常返回 `{ task_id, status }`。
6. **权限码**：控制器上 `#[Permission(code: '...')]`，前端按钮需与之后端一致。

---

## 三、如何查更细的字段

| 需求 | 查看位置 |
|------|----------|
| 路由与方法 | `app/Interface/Admin/Controller/**/*.php` 注解 |
| 入参校验 | `app/Interface/Admin/Request/**/*.php` 的 `*Rules()` |
| 出参结构 | `app/Interface/Admin/Dto/**` |
| 业务逻辑 | `app/Application/**`、`app/Domain/**` |

重新生成本文档 **§2.1 接口总览表**：

```bash
php bin/generate-api-doc.php
```

---

*文档版本：与仓库代码同步整理，如有接口变更请以代码注解为准。*
