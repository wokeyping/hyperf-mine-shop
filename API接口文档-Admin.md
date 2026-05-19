# MineShop Admin 后台接口文档

> 根据 `app/Interface/Admin` 控制器注解自动生成（`php bin/generate-api-doc.php` 可重新生成 **§2.1 接口总览表**）。
> 总览表仅列方法、路径与用途说明；入参、出参详见 §2.2 起各模块说明及 `Request`/`Dto`。

[← 返回文档索引](./API接口文档.md)

**基础地址**

| 环境 | 地址 | 路径前缀 |
|------|------|----------|
| 本地 | `http://127.0.0.1:9501` | `/admin/*` |

---

## 一、通用约定
### 1.1 统一响应结构

除微信支付回调等少数接口外，JSON 接口均返回：

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
| 401 | 未授权（Token/签名无效） |
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

- HTTP JSON 建议使用 **snake_case**（如 `order_no`）；小程序客户端会自动做驼峰 ↔ 蛇形转换。
- GET 查询参数、POST JSON Body 均可能使用；路径参数见各接口路径中的 `{id}`、`{orderNo}` 等。

---
## 二、接口列表（`/admin`）

### 2.1 接口总览

下列接口均需管理员登录（除 `passport/login`）。具体筛选项、表单字段见 `app/Interface/Admin/Request/**` 与 `app/Interface/Admin/Dto/**`。

| 方法 | 路径 | 说明 | 控制器方法 |
|------|------|------|------------|
| GET | `/admin/attachment/list` | 列表 | list |
| POST | `/admin/attachment/upload` | upload | upload |
| DELETE | `/admin/attachment/{id}` | delete | delete |
| POST | `/admin/coupon` | 新增 | store |
| POST | `/admin/coupon/export` | export | export |
| GET | `/admin/coupon/list` | 列表 | list |
| GET | `/admin/coupon/stats` | stats | stats |
| GET | `/admin/coupon/user/list` | 列表 | list |
| PUT | `/admin/coupon/user/{id:\d+}/mark-expired` | 标记过期 | markExpired |
| PUT | `/admin/coupon/user/{id:\d+}/mark-used` | 标记已使用 | markUsed |
| DELETE | `/admin/coupon/{id:\d+}` | 删除优惠券 | delete |
| GET | `/admin/coupon/{id:\d+}` | 详情 | show |
| PUT | `/admin/coupon/{id:\d+}` | 更新优惠券 | update |
| POST | `/admin/coupon/{id:\d+}/issue` | issue | issue |
| PUT | `/admin/coupon/{id:\d+}/toggle-status` | 切换状态 | toggleStatus |
| GET | `/admin/dashboard/analysis` | 数据分析页 | analysis |
| GET | `/admin/dashboard/report` | 多维度统计报表 | report |
| GET | `/admin/dashboard/welcome` | 商城首页 — 欢迎页 | welcome |
| DELETE | `/admin/department` | delete | delete |
| POST | `/admin/department` | create | create |
| GET | `/admin/department/list` | pageList | pageList |
| PUT | `/admin/department/{id}` | save | save |
| POST | `/admin/group-buy` | 新增 | store |
| POST | `/admin/group-buy-order/export` | export | export |
| GET | `/admin/group-buy-order/list` | 列表 | list |
| GET | `/admin/group-buy-order/{activityId:\d+}/orders` | orders | orders |
| POST | `/admin/group-buy/export` | export | export |
| GET | `/admin/group-buy/list` | 列表 | list |
| GET | `/admin/group-buy/stats` | stats | stats |
| DELETE | `/admin/group-buy/{id:\d+}` | 删除团购活动 | delete |
| GET | `/admin/group-buy/{id:\d+}` | 详情 | show |
| PUT | `/admin/group-buy/{id:\d+}` | 更新团购活动 | update |
| PUT | `/admin/group-buy/{id:\d+}/toggle-status` | 切换状态 | toggleStatus |
| DELETE | `/admin/leader` | delete | delete |
| POST | `/admin/leader` | create | create |
| GET | `/admin/leader/list` | pageList | pageList |
| POST | `/admin/member/account/wallet/adjust` | 钱包调整 | adjust |
| GET | `/admin/member/account/wallet/logs` | walletLogs | walletLogs |
| POST | `/admin/member/level` | 新增 | store |
| GET | `/admin/member/level/list` | 列表 | list |
| DELETE | `/admin/member/level/{id:\d+}` | 删除会员等级 | delete |
| GET | `/admin/member/level/{id:\d+}` | 详情 | show |
| PUT | `/admin/member/level/{id:\d+}` | 更新会员等级 | update |
| POST | `/admin/member/member` | 新增 | store |
| POST | `/admin/member/member/export` | export | export |
| GET | `/admin/member/member/list` | 列表 | list |
| GET | `/admin/member/member/overview` | overview | overview |
| GET | `/admin/member/member/stats` | stats | stats |
| GET | `/admin/member/member/{id:\d+}` | 详情 | show |
| PUT | `/admin/member/member/{id:\d+}` | 会员资料已更新 | update |
| PUT | `/admin/member/member/{id:\d+}/status` | 会员状态已更新 | updateStatus |
| PUT | `/admin/member/member/{id:\d+}/tags` | 会员标签已更新 | syncTags |
| POST | `/admin/member/tag` | 新增 | store |
| GET | `/admin/member/tag/list` | 列表 | list |
| GET | `/admin/member/tag/options` | options | options |
| DELETE | `/admin/member/tag/{id:\d+}` | 标签已删除 | delete |
| PUT | `/admin/member/tag/{id:\d+}` | 标签更新 | update |
| DELETE | `/admin/menu` | delete | delete |
| POST | `/admin/menu` | create | create |
| GET | `/admin/menu/list` | pageList | pageList |
| PUT | `/admin/menu/{id}` | save | save |
| GET | `/admin/order/after-sale/list` | 列表 | list |
| GET | `/admin/order/after-sale/{id:\d+}` | 详情 | show |
| PUT | `/admin/order/after-sale/{id:\d+}/approve` | approve | approve |
| PUT | `/admin/order/after-sale/{id:\d+}/complete-exchange` | completeExchange | completeExchange |
| PUT | `/admin/order/after-sale/{id:\d+}/receive` | 领取 | receive |
| PUT | `/admin/order/after-sale/{id:\d+}/refund` | refund | refund |
| PUT | `/admin/order/after-sale/{id:\d+}/reject` | reject | reject |
| PUT | `/admin/order/after-sale/{id:\d+}/reship` | reship | reship |
| POST | `/admin/order/order/export` | export | export |
| GET | `/admin/order/order/list` | 列表 | list |
| GET | `/admin/order/order/stats` | stats | stats |
| GET | `/admin/order/order/{id:\d+}` | 详情 | show |
| PUT | `/admin/order/order/{id:\d+}/cancel` | 订单已取消 | cancel |
| PUT | `/admin/order/order/{id:\d+}/ship` | 发货 | ship |
| GET | `/admin/passport/getInfo` | 当前登录用户信息 | getInfo |
| POST | `/admin/passport/login` | 管理员登录 | login |
| POST | `/admin/passport/logout` | 退出登录 | logout |
| POST | `/admin/passport/refresh` | 刷新 Token | refresh |
| GET | `/admin/permission/menus` | menus | menus |
| GET | `/admin/permission/roles` | roles | roles |
| POST | `/admin/permission/update` | 修改 | update |
| DELETE | `/admin/position` | delete | delete |
| POST | `/admin/position` | create | create |
| GET | `/admin/position/list` | pageList | pageList |
| PUT | `/admin/position/{id}` | save | save |
| PUT | `/admin/position/{id}/data_permission` | batchDataPermission | batchDataPermission |
| POST | `/admin/product/brand` | 新增 | store |
| GET | `/admin/product/brand/list` | 列表 | list |
| GET | `/admin/product/brand/options` | options | options |
| PUT | `/admin/product/brand/sort` | 更新排序 | sort |
| GET | `/admin/product/brand/statistics` | 统计 | statistics |
| DELETE | `/admin/product/brand/{id:\d+}` | 删除品牌 | delete |
| GET | `/admin/product/brand/{id:\d+}` | 详情 | show |
| PUT | `/admin/product/brand/{id:\d+}` | 更新品牌 | update |
| POST | `/admin/product/category` | 新增 | store |
| GET | `/admin/product/category/list` | 列表 | list |
| PUT | `/admin/product/category/move` | 移动分类 | move |
| GET | `/admin/product/category/options` | options | options |
| PUT | `/admin/product/category/sort` | 更新排序 | sort |
| GET | `/admin/product/category/statistics` | 统计 | statistics |
| GET | `/admin/product/category/tree` | tree | tree |
| DELETE | `/admin/product/category/{id:\d+}` | 删除分类 | delete |
| GET | `/admin/product/category/{id:\d+}` | 详情 | show |
| PUT | `/admin/product/category/{id:\d+}` | 更新分类 | update |
| GET | `/admin/product/category/{id:\d+}/breadcrumb` | breadcrumb | breadcrumb |
| POST | `/admin/product/product` | 新增 | store |
| POST | `/admin/product/product/export` | export | export |
| GET | `/admin/product/product/list` | 列表 | list |
| PUT | `/admin/product/product/sort` | 更新排序 | updateSort |
| GET | `/admin/product/product/stats` | stats | stats |
| DELETE | `/admin/product/product/{id:\d+}` | 删除商品 | delete |
| GET | `/admin/product/product/{id:\d+}` | 详情 | show |
| PUT | `/admin/product/product/{id:\d+}` | 修改 | update |
| PUT | `/admin/product/product/{id:\d+}/status` | 更新状态 | updateStatus |
| GET | `/admin/review/by-order/{orderId:\d+}` | byOrder | byOrder |
| GET | `/admin/review/list` | 列表 | list |
| GET | `/admin/review/stats` | stats | stats |
| GET | `/admin/review/{id:\d+}` | 详情 | show |
| PUT | `/admin/review/{id:\d+}/approve` | 审核通过 | approve |
| PUT | `/admin/review/{id:\d+}/reject` | 审核拒绝 | reject |
| PUT | `/admin/review/{id:\d+}/reply` | 回复 | reply |
| DELETE | `/admin/role` | delete | delete |
| POST | `/admin/role` | create | create |
| GET | `/admin/role/list` | pageList | pageList |
| PUT | `/admin/role/{id}` | save | save |
| GET | `/admin/role/{id}/permissions` | getRolePermissionForRole | getRolePermissionForRole |
| PUT | `/admin/role/{id}/permissions` | batchGrantPermissionsForRole | batchGrantPermissionsForRole |
| POST | `/admin/seckill-order/export` | export | export |
| GET | `/admin/seckill-order/list` | 列表 | list |
| GET | `/admin/seckill-order/{activityId:\d+}/orders` | orders | orders |
| POST | `/admin/seckill/activity` | 新增 | store |
| POST | `/admin/seckill/activity/export` | export | export |
| GET | `/admin/seckill/activity/list` | 列表 | list |
| GET | `/admin/seckill/activity/stats` | stats | stats |
| DELETE | `/admin/seckill/activity/{id:\d+}` | 删除活动 | delete |
| GET | `/admin/seckill/activity/{id:\d+}` | 详情 | show |
| PUT | `/admin/seckill/activity/{id:\d+}` | 更新活动 | update |
| PUT | `/admin/seckill/activity/{id:\d+}/toggle-status` | 切换状态 | toggleStatus |
| POST | `/admin/seckill/product` | 新增 | store |
| POST | `/admin/seckill/product/batch` | batchStore | batchStore |
| GET | `/admin/seckill/product/by-session/{sessionId:\d+}` | bySession | bySession |
| GET | `/admin/seckill/product/list` | 列表 | list |
| DELETE | `/admin/seckill/product/{id:\d+}` | 删除商品 | delete |
| GET | `/admin/seckill/product/{id:\d+}` | 详情 | show |
| PUT | `/admin/seckill/product/{id:\d+}` | 更新商品 | update |
| PUT | `/admin/seckill/product/{id:\d+}/toggle-status` | 切换状态 | toggleStatus |
| POST | `/admin/seckill/session` | 新增 | store |
| GET | `/admin/seckill/session/by-activity/{activityId:\d+}` | byActivity | byActivity |
| GET | `/admin/seckill/session/list` | 列表 | list |
| DELETE | `/admin/seckill/session/{id:\d+}` | 删除场次 | delete |
| GET | `/admin/seckill/session/{id:\d+}` | 详情 | show |
| PUT | `/admin/seckill/session/{id:\d+}` | 更新场次 | update |
| PUT | `/admin/seckill/session/{id:\d+}/toggle-status` | 切换状态 | toggleStatus |
| POST | `/admin/shipping/templates` | 新增 | store |
| GET | `/admin/shipping/templates/list` | 列表 | list |
| DELETE | `/admin/shipping/templates/{id:\d+}` | 删除运费模板 | destroy |
| GET | `/admin/shipping/templates/{id:\d+}` | 详情 | show |
| PUT | `/admin/shipping/templates/{id:\d+}` | 更新运费模板 | update |
| POST | `/admin/system-message/batchSend` | 批量发送完成 | batchSend |
| DELETE | `/admin/system-message/delete` | 删除操作完成 | delete |
| GET | `/admin/system-message/index` | 列表 | index |
| GET | `/admin/system-message/popular` | popular | popular |
| GET | `/admin/system-message/preference/checkDoNotDisturb` | checkDoNotDisturb | checkDoNotDisturb |
| GET | `/admin/system-message/preference/defaults` | getDefaults | getDefaults |
| GET | `/admin/system-message/preference/index` | 列表 | index |
| POST | `/admin/system-message/preference/reset` | reset | reset |
| PUT | `/admin/system-message/preference/setDoNotDisturbTime` | setDoNotDisturbTime | setDoNotDisturbTime |
| PUT | `/admin/system-message/preference/setMinPriority` | setMinPriority | setMinPriority |
| PUT | `/admin/system-message/preference/toggleDoNotDisturb` | toggleDoNotDisturb | toggleDoNotDisturb |
| PUT | `/admin/system-message/preference/update` | 修改 | update |
| PUT | `/admin/system-message/preference/updateChannels` | updateChannelPreferences | updateChannelPreferences |
| PUT | `/admin/system-message/preference/updateTypes` | updateTypePreferences | updateTypePreferences |
| GET | `/admin/system-message/read/{id}` | read | read |
| GET | `/admin/system-message/recent` | recent | recent |
| POST | `/admin/system-message/save` | 消息创建 | save |
| POST | `/admin/system-message/schedule` | 消息调度 | schedule |
| GET | `/admin/system-message/search` | search | search |
| POST | `/admin/system-message/send` | 消息发送 | send |
| GET | `/admin/system-message/statistics` | 统计 | statistics |
| GET | `/admin/system-message/template/active` | getActiveTemplates | getActiveTemplates |
| GET | `/admin/system-message/template/categories` | getCategories | getCategories |
| PUT | `/admin/system-message/template/changeStatus` | changeStatus | changeStatus |
| POST | `/admin/system-message/template/copy` | copy | copy |
| DELETE | `/admin/system-message/template/delete` | delete | delete |
| POST | `/admin/system-message/template/export` | export | export |
| GET | `/admin/system-message/template/getVariables/{id}` | getVariables | getVariables |
| POST | `/admin/system-message/template/import` | import | import |
| GET | `/admin/system-message/template/index` | 列表 | index |
| POST | `/admin/system-message/template/preview` | 预览 | preview |
| GET | `/admin/system-message/template/read/{id}` | read | read |
| POST | `/admin/system-message/template/render` | render | render |
| POST | `/admin/system-message/template/save` | save | save |
| GET | `/admin/system-message/template/search` | search | search |
| PUT | `/admin/system-message/template/update/{id}` | 修改 | update |
| POST | `/admin/system-message/template/validateVariables` | validateVariables | validateVariables |
| PUT | `/admin/system-message/update/{id}` | 消息更新 | update |
| DELETE | `/admin/system-message/user/batchDelete` | batchDelete | batchDelete |
| PUT | `/admin/system-message/user/batchMarkRead` | batchMarkAsRead | batchMarkAsRead |
| DELETE | `/admin/system-message/user/delete/{messageId}` | 消息删除 | delete |
| GET | `/admin/system-message/user/index` | 列表 | index |
| PUT | `/admin/system-message/user/markAllRead` | markAllAsRead | markAllAsRead |
| PUT | `/admin/system-message/user/markRead/{messageId}` | 消息已标记为已读 | markAsRead |
| GET | `/admin/system-message/user/read/{messageId}` | read | read |
| GET | `/admin/system-message/user/search` | search | search |
| GET | `/admin/system-message/user/typeStats` | getTypeStats | getTypeStats |
| GET | `/admin/system-message/user/unreadCount` | getUnreadCount | getUnreadCount |
| GET | `/admin/system/setting/group/{group}` | group | group |
| GET | `/admin/system/setting/groups` | 进行中的团列表 | groups |
| GET | `/admin/system/setting/values` | values | values |
| PUT | `/admin/system/setting/{key}` | 配置已更新 | update |
| DELETE | `/admin/user` | delete | delete |
| POST | `/admin/user` | create | create |
| PUT | `/admin/user` | updateInfo | updateInfo |
| DELETE | `/admin/user-login-log` | delete | delete |
| GET | `/admin/user-login-log/list` | page | page |
| DELETE | `/admin/user-operation-log` | delete | delete |
| GET | `/admin/user-operation-log/list` | page | page |
| GET | `/admin/user/list` | pageList | pageList |
| PUT | `/admin/user/password` | resetPassword | resetPassword |
| PUT | `/admin/user/{userId}` | save | save |
| GET | `/admin/user/{userId}/roles` | getUserRole | getUserRole |
| PUT | `/admin/user/{userId}/roles` | batchGrantRolesForUser | batchGrantRolesForUser |


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

1. **列表接口**：多数为 `GET .../list` 或 `page`，`data` 为分页结构（`list`/`items` + 分页字段），详见各模块说明。
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
