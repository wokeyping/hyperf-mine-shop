# MineShop API 接口文档

> 根据 `app/Interface` 控制器注解自动生成（`php bin/generate-api-doc.php` 可重新生成路由清单）。
> 详细请求/响应字段以各 `Request` 类与 `Transformer` 为准；下文对 C 端核心接口补充了校验规则说明。

**基础地址**

| 环境 | Admin 后台 | C 端 Api |
|------|------------|----------|
| 本地 | `http://127.0.0.1:9501` | 同左 |
| 说明 | 前缀 `/admin/*` | 前缀 `/api/v1/*` |

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

部分 Admin 列表使用 MineAdmin 约定，也可能在 `data` 中直接返回 `items` + `pageInfo`，以实际接口为准。

### 1.3 Admin 鉴权

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

### 1.4 C 端 Api 鉴权

| 项 | 说明 |
|----|------|
| API 签名 | 所有 `/api/v1/*` 请求需签名（`API_SIGNATURE_ENABLED=true` 时） |
| 会员 Token | 需登录接口：`Authorization: Bearer {token}` |

**签名请求头**

| Header | 说明 |
|--------|------|
| X-Client-Id | `h5` 或 `miniapp`（与 `.env` 中密钥对应） |
| X-Timestamp | Unix 秒级时间戳，与服务器差值 ≤ `API_SIGNATURE_TTL`（默认 300s） |
| X-Nonce | 随机串，防重放 |
| X-Body-Sha256 | 请求体原始字节的 SHA256 十六进制 |
| X-Signature | HMAC-SHA256 签名 |

签名原文（换行拼接）：`METHOD\nPATH\nQUERY\nTIMESTAMP\nNONCE\nBODY_SHA256\nCLIENT_ID`

小程序/H5 实现可参考：`miniprogram/src/services/_utils/signature.ts`。

### 1.5 参数命名

- HTTP JSON 建议使用 **snake_case**（如 `order_no`）；小程序客户端会自动做驼峰 ↔ 蛇形转换。
- GET 查询参数、POST JSON Body 均可能使用；路径参数见各接口路径中的 `{id}`、`{orderNo}` 等。

---

## 二、C 端 Api 接口（`/api/v1`）

### 2.1 接口总览
| 方法 | 路径 | 控制器方法 | 请求类/参数 |
|------|------|------------|-------------|
| GET | `/api/v1/after-sales` | index | — |
| POST | `/api/v1/after-sales` | apply | AfterSaleApplyRequest $request |
| GET | `/api/v1/after-sales/eligibility` | eligibility | — |
| GET | `/api/v1/after-sales/{id}` | detail | int $id |
| POST | `/api/v1/after-sales/{id}/cancel` | cancel | int $id |
| POST | `/api/v1/after-sales/{id}/confirm-exchange-received` | confirmExchangeReceived | int $id |
| GET | `/api/v1/after-sales/{id}/reship-logistics` | reshipLogistics | int $id |
| GET | `/api/v1/after-sales/{id}/return-logistics` | returnLogistics | int $id |
| POST | `/api/v1/after-sales/{id}/return-shipment` | submitReturnShipment | int $id, AfterSaleReturnShipmentRequest $request |
| POST | `/api/v1/auth/captcha` | captcha | SendVerificationCodeRequest $request |
| POST | `/api/v1/auth/forgotPassword` | forgotPassword | ForgotPasswordRequest $request |
| POST | `/api/v1/auth/register` | register | RegisterRequest $request |
| GET | `/api/v1/auth/register/protocols` | registerProtocols | — |
| GET | `/api/v1/cart` | index | — |
| POST | `/api/v1/cart/clear-invalid` | clearInvalid | — |
| POST | `/api/v1/cart/items` | store | CartItemStoreRequest $request |
| DELETE | `/api/v1/cart/items/{skuId}` | destroy | int $skuId |
| PUT | `/api/v1/cart/items/{skuId}` | update | CartItemUpdateRequest $request, int $skuId |
| GET | `/api/v1/categories` | index | — |
| GET | `/api/v1/coupons/available` | available | CouponAvailableRequest $request |
| GET | `/api/v1/coupons/{id}` | show | int $id |
| GET | `/api/v1/geo/regions` | index | RequestInterface $request |
| GET | `/api/v1/group-buy/products` | index | — |
| GET | `/api/v1/group-buy/products/{activityId}/groups` | groups | int $activityId |
| GET | `/api/v1/group-buy/products/{activityId}/{spuId}` | show | int $activityId, int $spuId |
| GET | `/api/v1/home` | show | — |
| POST | `/api/v1/login/h5Password` | h5Password | — |
| POST | `/api/v1/login/miniApp` | miniApp | — |
| GET | `/api/v1/member/addresses` | index | — |
| POST | `/api/v1/member/addresses` | store | MemberAddressRequest $request |
| DELETE | `/api/v1/member/addresses/{id}` | destroy | int $id |
| GET | `/api/v1/member/addresses/{id}` | show | int $id |
| PUT | `/api/v1/member/addresses/{id}` | update | MemberAddressRequest $request, int $id |
| POST | `/api/v1/member/addresses/{id}/default` | markDefault | int $id |
| GET | `/api/v1/member/center` | center | — |
| GET | `/api/v1/member/coupons` | index | — |
| POST | `/api/v1/member/coupons/receive` | receive | CouponReceiveRequest $request |
| GET | `/api/v1/member/invite/qrcode` | inviteQrCode | — |
| POST | `/api/v1/member/phone/bind` | bindPhone | PhoneAuthorizeRequest $request |
| GET | `/api/v1/member/profile` | profile | — |
| POST | `/api/v1/member/profile/authorize` | authorizeProfile | ProfileAuthorizeRequest $request |
| POST | `/api/v1/member/profile/update` | updateProfile | ProfileUpdateRequest $request |
| GET | `/api/v1/member/wallet/transactions` | transactions | WalletTransactionRequest $request |
| POST | `/api/v1/order/cancel` | cancel | OrderCancelRequest $request |
| POST | `/api/v1/order/confirm-receipt` | confirmReceipt | OrderConfirmReceiptRequest $request |
| GET | `/api/v1/order/detail/{orderNo}` | detail | string $orderNo |
| GET | `/api/v1/order/list` | list | OrderListRequest $request |
| GET | `/api/v1/order/logistics/{orderNo}` | logistics | string $orderNo |
| GET | `/api/v1/order/pay-info/{orderNo}` | payInfo | string $orderNo |
| POST | `/api/v1/order/payment` | payment | OrderPaymentRequest $request |
| POST | `/api/v1/order/preview` | preview | OrderPreviewRequest $request |
| GET | `/api/v1/order/statistics` | statistics | — |
| POST | `/api/v1/order/submit` | submit | OrderCommitRequest $request |
| GET | `/api/v1/order/submit-result/{tradeNo}` | submitResult | string $tradeNo |
| POST | `/api/v1/payment/wechat/pay-notify` | payNotify | — |
| POST | `/api/v1/payment/wechat/refund-notify` | refundNotify | — |
| GET | `/api/v1/products` | index | ProductListRequest $request |
| GET | `/api/v1/products/{id}` | show | int $id |
| POST | `/api/v1/review` | store | CreateReviewRequest $request |
| GET | `/api/v1/review/product/{id}` | productReviews | int $id |
| GET | `/api/v1/review/product/{id}/stats` | productStats | int $id |
| GET | `/api/v1/review/product/{id}/summary` | productSummary | int $id |
| GET | `/api/v1/seckill/products` | index | — |
| GET | `/api/v1/seckill/products/{sessionId}/{spuId}` | show | int $sessionId, int $spuId |
| GET | `/api/v1/seckill/sessions` | index | — |
| POST | `/api/v1/upload/image` | image | RequestInterface $request |


### 2.2 认证 / 登录

#### POST `/api/v1/login/miniApp` — 小程序授权登录

| 字段 | 类型 | 必填 | 说明 |
|------|------|------|------|
| code | string | 是 | `wx.login` 返回的 code |
| encrypted_data | string | 否 | 加密用户信息 |
| iv | string | 否 | 加密算法初始向量 |
| openid | string | 否 | 可选，调试或特殊场景 |

**响应 `data`**

| 字段 | 说明 |
|------|------|
| token | 访问令牌 |
| refresh_token | 刷新令牌 |
| expires_in | 过期秒数 |
| member | 会员基本信息（id、nickname、avatar 等） |

#### POST `/api/v1/login/h5Password` — H5 密码登录

| 字段 | 类型 | 必填 | 说明 |
|------|------|------|------|
| phone | string | 是 | 手机号 `1[3-9]xxxxxxxxx` |
| password | string | 是 | 最少 6 位 |

#### POST `/api/v1/auth/captcha` — 发送验证码

| 字段 | 类型 | 必填 | 说明 |
|------|------|------|------|
| phone | string | 是 | 手机号 |
| scene | string | 是 | `register` \| `forgot_password` |

#### POST `/api/v1/auth/register` — 注册

| 字段 | 类型 | 必填 | 说明 |
|------|------|------|------|
| phone | string | 是 | 未注册手机号 |
| password | string | 是 | 最少 6 位 |
| password_confirmation | string | 是 | 确认密码 |
| code | string | 是 | 6 位验证码 |

#### POST `/api/v1/auth/forgotPassword` — 忘记密码

同注册字段（phone、password、password_confirmation、code）。

#### GET `/api/v1/auth/register/protocols` — 注册协议文案

无请求体。

---

### 2.3 会员

#### GET `/api/v1/member/profile` — 个人资料

需 Token。

#### GET `/api/v1/member/center` — 个人中心聚合数据

需 Token。

#### POST `/api/v1/member/profile/update` — 更新资料

| 字段 | 类型 | 必填 | 说明 |
|------|------|------|------|
| avatar_url | string | 否 | 头像 URL |
| nick_name | string | 否 | 昵称 |
| gender | int | 否 | 0/1/2 |
| phone | string | 否 | 手机号 |

#### POST `/api/v1/member/phone/bind` — 绑定手机号

见 `PhoneAuthorizeRequest`（code 等微信手机号组件参数）。

#### GET `/api/v1/member/invite/qrcode` — 邀请二维码

需 Token。

#### GET `/api/v1/member/wallet/transactions` — 钱包流水

| 查询参数 | 类型 | 必填 | 说明 |
|----------|------|------|------|
| wallet_type | string | 是 | `balance` \| `points` |
| page | int | 否 | 页码，默认 1 |
| page_size | int | 否 | 每页条数，最大 100 |

---

### 2.4 收货地址 `/api/v1/member/addresses`

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `` | 地址列表 |
| GET | `/{id}` | 地址详情 |
| POST | `` | 新增 |
| PUT | `/{id}` | 修改 |
| DELETE | `/{id}` | 删除 |
| POST | `/{id}/default` | 设为默认 |

**新增/修改 Body**

| 字段 | 类型 | 必填 | 说明 |
|------|------|------|------|
| name | string | 是 | 收货人 |
| phone | string | 是 | 电话 |
| province / city / district | string | 是 | 省市区名称 |
| province_code / city_code / district_code | string | 否 | 区划编码 |
| detail | string | 是 | 详细地址 |
| is_default | bool | 否 | 是否默认 |

---

### 2.5 商品 / 分类

#### GET `/api/v1/products` — 商品列表

| 查询参数 | 类型 | 说明 |
|----------|------|------|
| category_id | int | 分类 ID |
| keyword | string | 关键词 |
| is_recommend / is_hot / is_new | bool | 筛选 |
| page / page_size | int | 分页 |

#### GET `/api/v1/products/{id}` — 商品详情

#### GET `/api/v1/categories` — 分类树/列表

---

### 2.6 购物车 `/api/v1/cart`

| 方法 | 路径 | Body / 说明 |
|------|------|-------------|
| GET | `` | 购物车列表 |
| POST | `items` | `sku_id`(必填), `quantity`(1-999) |
| PUT | `items/{skuId}` | `quantity` |
| DELETE | `items/{skuId}` | 删除单项 |
| POST | `clear-invalid` | 清理失效商品 |

---

### 2.7 订单 `/api/v1/order`（需 Token + 签名）

#### POST `preview` — 订单预览（结算页）

| 字段 | 类型 | 必填 | 说明 |
|------|------|------|------|
| goods_request_list | array | 是 | 商品项列表 |
| goods_request_list.*.sku_id | int | 是 | SKU ID |
| goods_request_list.*.quantity | int | 是 | 数量 1-999 |
| order_type | string | 否 | `normal` \| `seckill` \| `group_buy`，默认 normal |
| address_id | int | 否 | 已有地址 ID |
| user_address | object | 否 | 临时地址（name/phone/province/city/district/detail） |
| coupon_id | int | 否 | 用户优惠券 ID |
| activity_id / session_id | int | 秒杀必填 | 活动/场次 |
| group_buy_id / group_no | int/string | 拼团 | 活动 ID、团号 |
| buy_original_price | bool | 否 | 拼团原价购买 |
| from_cart | bool | 否 | 是否来自购物车 |

**响应**：结算明细（金额、运费、优惠、商品行等），见 `OrderCheckoutTransformer`。

#### POST `submit` — 提交订单（异步）

在 `preview` 基础上增加：

| 字段 | 类型 | 必填 | 说明 |
|------|------|------|------|
| total_amount | int | 是 | 前端展示的应付总额（分），用于校验 |
| user_name | string | 否 | 下单人昵称 |
| invoice_request | object | 否 | 发票需求 |

**响应 `data`**

```json
{
  "trade_no": "订单号",
  "status": "processing",
  "pay_methods": [
    { "channel": "wechat", "name": "微信支付", "enabled": true },
    { "channel": "balance", "name": "钱包", "enabled": true }
  ]
}
```

#### GET `submit-result/{tradeNo}` — 轮询下单结果

| status | 说明 |
|--------|------|
| processing | 处理中 |
| created | 已创建，可支付 |
| failed | 失败，见 error |
| not_found | 无此 trade_no |

#### POST `payment` — 发起支付

| 字段 | 类型 | 必填 | 说明 |
|------|------|------|------|
| order_no | string | 是 | 订单号 |
| pay_method | string | 是 | `wechat` \| `balance` |

微信返回调起支付参数；余额直接扣款。

#### GET `list` — 订单列表

| 查询参数 | 说明 |
|----------|------|
| status | `all` \| `pending` \| `paid` \| `shipped` \| `completed` \| `after_sale` |
| page / page_size | 分页 |

#### GET `detail/{orderNo}` — 订单详情

#### GET `logistics/{orderNo}` — 物流轨迹

#### GET `statistics` — 各状态订单数量

#### GET `pay-info/{orderNo}` — 待支付订单支付信息

#### POST `cancel` — 取消订单

Body: `{ "order_no": "..." }`

#### POST `confirm-receipt` — 确认收货

Body: `{ "order_no": "..." }`

---

### 2.8 优惠券

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/api/v1/coupons/available` | 可领/可用券，`spu_id`、`limit` |
| GET | `/api/v1/coupons/{id}` | 券详情 |
| GET | `/api/v1/member/coupons` | 我的优惠券 |
| POST | `/api/v1/member/coupons/receive` | 领取，Body: `coupon_id` |

---

### 2.9 秒杀 / 拼团

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/api/v1/seckill/sessions` | 秒杀场次列表 |
| GET | `/api/v1/seckill/products` | 场次商品列表 |
| GET | `/api/v1/seckill/products/{sessionId}/{spuId}` | 秒杀商品详情 |
| GET | `/api/v1/group-buy/products` | 拼团活动商品 |
| GET | `/api/v1/group-buy/products/{activityId}/groups` | 进行中的团 |
| GET | `/api/v1/group-buy/products/{activityId}/{spuId}` | 拼团商品详情 |

下单时 `order_type` 分别为 `seckill`、`group_buy`。

---

### 2.10 售后 `/api/v1/after-sales`

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `eligibility` | 可申请售后的订单项 |
| POST | `` | 申请售后 |
| GET | `` | 售后列表 |
| GET | `{id}` | 详情 |
| GET | `{id}/return-logistics` | 退货物流 |
| GET | `{id}/reship-logistics` | 换货发货物流 |
| POST | `{id}/cancel` | 取消申请 |
| POST | `{id}/return-shipment` | 提交退货物流 |
| POST | `{id}/confirm-exchange-received` | 确认换货收货 |

**申请售后 Body**

| 字段 | 类型 | 说明 |
|------|------|------|
| order_id / order_item_id | int | 订单/明细 |
| type | string | `refund_only` \| `return_refund` \| `exchange` |
| reason | string | 原因 |
| description | string | 描述 |
| apply_amount | int | 申请金额（分） |
| quantity | int | 数量 |
| images | string[] | 凭证图，最多 9 张 |

**退货物流 Body**: `logistics_company`, `logistics_no`

---

### 2.11 评价 `/api/v1/review`

| 方法 | 路径 | 说明 |
|------|------|------|
| POST | `` | 提交评价（需 Token） |
| GET | `product/{id}` | 商品评价列表 |
| GET | `product/{id}/stats` | 评价统计 |
| GET | `product/{id}/summary` | 评价摘要 |

**提交评价 Body**: `rating`(1-5), `content`, `images[]`, `order_id`, `order_item_id`, `is_anonymous`

---

### 2.12 其他 C 端接口

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/api/v1/home` | 首页数据（Banner、推荐等） |
| POST | `/api/v1/upload/image` | 图片上传（multipart） |
| GET | `/api/v1/geo/regions` | 省市区数据，支持 parent_code 等查询参数 |
| POST | `/api/v1/payment/wechat/pay-notify` | 微信支付回调（微信服务器调用，非 JSON Result） |
| POST | `/api/v1/payment/wechat/refund-notify` | 退款回调 |

---

## 三、Admin 后台接口（`/admin`）

### 3.1 接口总览

> 下列接口均需管理员登录（除 `passport/login`）。具体筛选项、表单字段见 `app/Interface/Admin/Request/**` 与 `app/Interface/Admin/Dto/**`。
| 方法 | 路径 | 控制器方法 | 请求类/参数 |
|------|------|------------|-------------|
| GET | `/admin/attachment/list` | list | UploadRequest $request |
| POST | `/admin/attachment/upload` | upload | UploadRequest $request |
| DELETE | `/admin/attachment/{id}` | delete | int $id |
| POST | `/admin/coupon` | store | CouponRequest $request |
| POST | `/admin/coupon/export` | export | CouponRequest $request |
| GET | `/admin/coupon/list` | list | CouponRequest $request |
| GET | `/admin/coupon/stats` | stats | — |
| GET | `/admin/coupon/user/list` | list | CouponUserRequest $request |
| PUT | `/admin/coupon/user/{id:\d+}/mark-expired` | markExpired | int $id, CouponUserRequest $request |
| PUT | `/admin/coupon/user/{id:\d+}/mark-used` | markUsed | int $id, CouponUserRequest $request |
| DELETE | `/admin/coupon/{id:\d+}` | delete | int $id |
| GET | `/admin/coupon/{id:\d+}` | show | int $id |
| PUT | `/admin/coupon/{id:\d+}` | update | int $id, CouponRequest $request |
| POST | `/admin/coupon/{id:\d+}/issue` | issue | int $id, CouponIssueRequest $request |
| PUT | `/admin/coupon/{id:\d+}/toggle-status` | toggleStatus | int $id |
| GET | `/admin/dashboard/analysis` | analysis | — |
| GET | `/admin/dashboard/report` | report | — |
| GET | `/admin/dashboard/welcome` | welcome | — |
| DELETE | `/admin/department` | delete | — |
| POST | `/admin/department` | create | DepartmentRequest $request |
| GET | `/admin/department/list` | pageList | — |
| PUT | `/admin/department/{id}` | save | int $id, DepartmentRequest $request |
| POST | `/admin/group-buy` | store | GroupBuyRequest $request |
| POST | `/admin/group-buy-order/export` | export | — |
| GET | `/admin/group-buy-order/list` | list | — |
| GET | `/admin/group-buy-order/{activityId:\d+}/orders` | orders | int $activityId |
| POST | `/admin/group-buy/export` | export | GroupBuyRequest $request |
| GET | `/admin/group-buy/list` | list | GroupBuyRequest $request |
| GET | `/admin/group-buy/stats` | stats | — |
| DELETE | `/admin/group-buy/{id:\d+}` | delete | int $id |
| GET | `/admin/group-buy/{id:\d+}` | show | int $id |
| PUT | `/admin/group-buy/{id:\d+}` | update | int $id, GroupBuyRequest $request |
| PUT | `/admin/group-buy/{id:\d+}/toggle-status` | toggleStatus | int $id |
| DELETE | `/admin/leader` | delete | — |
| POST | `/admin/leader` | create | LeaderRequest $request |
| GET | `/admin/leader/list` | pageList | — |
| POST | `/admin/member/account/wallet/adjust` | adjust | MemberAccountRequest $request |
| GET | `/admin/member/account/wallet/logs` | walletLogs | MemberAccountRequest $request |
| POST | `/admin/member/level` | store | MemberLevelRequest $request |
| GET | `/admin/member/level/list` | list | MemberLevelRequest $request |
| DELETE | `/admin/member/level/{id:\d+}` | delete | int $id |
| GET | `/admin/member/level/{id:\d+}` | show | int $id |
| PUT | `/admin/member/level/{id:\d+}` | update | int $id, MemberLevelRequest $request |
| POST | `/admin/member/member` | store | MemberRequest $request |
| POST | `/admin/member/member/export` | export | MemberRequest $request |
| GET | `/admin/member/member/list` | list | MemberRequest $request |
| GET | `/admin/member/member/overview` | overview | MemberRequest $request |
| GET | `/admin/member/member/stats` | stats | MemberRequest $request |
| GET | `/admin/member/member/{id:\d+}` | show | int $id |
| PUT | `/admin/member/member/{id:\d+}` | update | int $id, MemberRequest $request |
| PUT | `/admin/member/member/{id:\d+}/status` | updateStatus | int $id, MemberRequest $request |
| PUT | `/admin/member/member/{id:\d+}/tags` | syncTags | int $id, MemberRequest $request |
| POST | `/admin/member/tag` | store | MemberTagRequest $request |
| GET | `/admin/member/tag/list` | list | MemberTagRequest $request |
| GET | `/admin/member/tag/options` | options | — |
| DELETE | `/admin/member/tag/{id:\d+}` | delete | int $id |
| PUT | `/admin/member/tag/{id:\d+}` | update | int $id, MemberTagRequest $request |
| DELETE | `/admin/menu` | delete | — |
| POST | `/admin/menu` | create | MenuRequest $request |
| GET | `/admin/menu/list` | pageList | RequestInterface $request |
| PUT | `/admin/menu/{id}` | save | int $id, MenuRequest $request |
| GET | `/admin/order/after-sale/list` | list | AfterSaleReviewRequest $request |
| GET | `/admin/order/after-sale/{id:\d+}` | show | int $id |
| PUT | `/admin/order/after-sale/{id:\d+}/approve` | approve | int $id, AfterSaleReviewRequest $request |
| PUT | `/admin/order/after-sale/{id:\d+}/complete-exchange` | completeExchange | int $id, AfterSaleReviewRequest $request |
| PUT | `/admin/order/after-sale/{id:\d+}/receive` | receive | int $id, AfterSaleReviewRequest $request |
| PUT | `/admin/order/after-sale/{id:\d+}/refund` | refund | int $id, AfterSaleReviewRequest $request |
| PUT | `/admin/order/after-sale/{id:\d+}/reject` | reject | int $id, AfterSaleReviewRequest $request |
| PUT | `/admin/order/after-sale/{id:\d+}/reship` | reship | int $id, AfterSaleReviewRequest $request |
| POST | `/admin/order/order/export` | export | OrderRequest $request |
| GET | `/admin/order/order/list` | list | OrderRequest $request |
| GET | `/admin/order/order/stats` | stats | OrderRequest $request |
| GET | `/admin/order/order/{id:\d+}` | show | int $id |
| PUT | `/admin/order/order/{id:\d+}/cancel` | cancel | int $id, OrderRequest $request |
| PUT | `/admin/order/order/{id:\d+}/ship` | ship | int $id, OrderRequest $request |
| GET | `/admin/passport/getInfo` | getInfo | — |
| POST | `/admin/passport/login` | login | PassportLoginRequest $request |
| POST | `/admin/passport/logout` | logout | — |
| POST | `/admin/passport/refresh` | refresh | — |
| GET | `/admin/permission/menus` | menus | — |
| GET | `/admin/permission/roles` | roles | — |
| POST | `/admin/permission/update` | update | PermissionRequest $request |
| DELETE | `/admin/position` | delete | — |
| POST | `/admin/position` | create | PositionRequest $request |
| GET | `/admin/position/list` | pageList | — |
| PUT | `/admin/position/{id}` | save | int $id, PositionRequest $request |
| PUT | `/admin/position/{id}/data_permission` | batchDataPermission | int $id, BatchGrantDataPermissionForPositionRequest $request |
| POST | `/admin/product/brand` | store | BrandRequest $request |
| GET | `/admin/product/brand/list` | list | BrandRequest $request |
| GET | `/admin/product/brand/options` | options | — |
| PUT | `/admin/product/brand/sort` | sort | BrandRequest $request |
| GET | `/admin/product/brand/statistics` | statistics | — |
| DELETE | `/admin/product/brand/{id:\d+}` | delete | int $id |
| GET | `/admin/product/brand/{id:\d+}` | show | int $id |
| PUT | `/admin/product/brand/{id:\d+}` | update | int $id, BrandRequest $request |
| POST | `/admin/product/category` | store | CategoryRequest $request |
| GET | `/admin/product/category/list` | list | CategoryRequest $request |
| PUT | `/admin/product/category/move` | move | CategoryRequest $request |
| GET | `/admin/product/category/options` | options | CategoryRequest $request |
| PUT | `/admin/product/category/sort` | sort | CategoryRequest $request |
| GET | `/admin/product/category/statistics` | statistics | — |
| GET | `/admin/product/category/tree` | tree | CategoryRequest $request |
| DELETE | `/admin/product/category/{id:\d+}` | delete | int $id |
| GET | `/admin/product/category/{id:\d+}` | show | int $id |
| PUT | `/admin/product/category/{id:\d+}` | update | int $id, CategoryRequest $request |
| GET | `/admin/product/category/{id:\d+}/breadcrumb` | breadcrumb | int $id |
| POST | `/admin/product/product` | store | ProductRequest $request |
| POST | `/admin/product/product/export` | export | — |
| GET | `/admin/product/product/list` | list | — |
| PUT | `/admin/product/product/sort` | updateSort | RequestInterface $request |
| GET | `/admin/product/product/stats` | stats | — |
| DELETE | `/admin/product/product/{id:\d+}` | delete | int $id |
| GET | `/admin/product/product/{id:\d+}` | show | int $id |
| PUT | `/admin/product/product/{id:\d+}` | update | int $id, ProductRequest $request |
| PUT | `/admin/product/product/{id:\d+}/status` | updateStatus | int $id, RequestInterface $request |
| GET | `/admin/review/by-order/{orderId:\d+}` | byOrder | int $orderId |
| GET | `/admin/review/list` | list | ReviewRequest $request |
| GET | `/admin/review/stats` | stats | — |
| GET | `/admin/review/{id:\d+}` | show | int $id |
| PUT | `/admin/review/{id:\d+}/approve` | approve | int $id |
| PUT | `/admin/review/{id:\d+}/reject` | reject | int $id |
| PUT | `/admin/review/{id:\d+}/reply` | reply | int $id, ReviewReplyRequest $request |
| DELETE | `/admin/role` | delete | — |
| POST | `/admin/role` | create | RoleRequest $request |
| GET | `/admin/role/list` | pageList | — |
| PUT | `/admin/role/{id}` | save | int $id, RoleRequest $request |
| GET | `/admin/role/{id}/permissions` | getRolePermissionForRole | int $id |
| PUT | `/admin/role/{id}/permissions` | batchGrantPermissionsForRole | int $id, BatchGrantPermissionsForRoleRequest $request |
| POST | `/admin/seckill-order/export` | export | — |
| GET | `/admin/seckill-order/list` | list | — |
| GET | `/admin/seckill-order/{activityId:\d+}/orders` | orders | int $activityId |
| POST | `/admin/seckill/activity` | store | SeckillActivityRequest $request |
| POST | `/admin/seckill/activity/export` | export | SeckillActivityRequest $request |
| GET | `/admin/seckill/activity/list` | list | SeckillActivityRequest $request |
| GET | `/admin/seckill/activity/stats` | stats | — |
| DELETE | `/admin/seckill/activity/{id:\d+}` | delete | int $id |
| GET | `/admin/seckill/activity/{id:\d+}` | show | int $id |
| PUT | `/admin/seckill/activity/{id:\d+}` | update | int $id, SeckillActivityRequest $request |
| PUT | `/admin/seckill/activity/{id:\d+}/toggle-status` | toggleStatus | int $id |
| POST | `/admin/seckill/product` | store | SeckillProductRequest $request |
| POST | `/admin/seckill/product/batch` | batchStore | SeckillProductRequest $request |
| GET | `/admin/seckill/product/by-session/{sessionId:\d+}` | bySession | int $sessionId |
| GET | `/admin/seckill/product/list` | list | SeckillProductRequest $request |
| DELETE | `/admin/seckill/product/{id:\d+}` | delete | int $id |
| GET | `/admin/seckill/product/{id:\d+}` | show | int $id |
| PUT | `/admin/seckill/product/{id:\d+}` | update | int $id, SeckillProductRequest $request |
| PUT | `/admin/seckill/product/{id:\d+}/toggle-status` | toggleStatus | int $id |
| POST | `/admin/seckill/session` | store | SeckillSessionRequest $request |
| GET | `/admin/seckill/session/by-activity/{activityId:\d+}` | byActivity | int $activityId |
| GET | `/admin/seckill/session/list` | list | SeckillSessionRequest $request |
| DELETE | `/admin/seckill/session/{id:\d+}` | delete | int $id |
| GET | `/admin/seckill/session/{id:\d+}` | show | int $id |
| PUT | `/admin/seckill/session/{id:\d+}` | update | int $id, SeckillSessionRequest $request |
| PUT | `/admin/seckill/session/{id:\d+}/toggle-status` | toggleStatus | int $id |
| POST | `/admin/shipping/templates` | store | ShippingTemplateRequest $request |
| GET | `/admin/shipping/templates/list` | list | ShippingTemplateRequest $request |
| DELETE | `/admin/shipping/templates/{id:\d+}` | destroy | int $id |
| GET | `/admin/shipping/templates/{id:\d+}` | show | int $id |
| PUT | `/admin/shipping/templates/{id:\d+}` | update | int $id, ShippingTemplateRequest $request |
| POST | `/admin/system-message/batchSend` | batchSend | — |
| DELETE | `/admin/system-message/delete` | delete | — |
| GET | `/admin/system-message/index` | index | — |
| GET | `/admin/system-message/popular` | popular | — |
| GET | `/admin/system-message/preference/checkDoNotDisturb` | checkDoNotDisturb | — |
| GET | `/admin/system-message/preference/defaults` | getDefaults | — |
| GET | `/admin/system-message/preference/index` | index | — |
| POST | `/admin/system-message/preference/reset` | reset | — |
| PUT | `/admin/system-message/preference/setDoNotDisturbTime` | setDoNotDisturbTime | — |
| PUT | `/admin/system-message/preference/setMinPriority` | setMinPriority | — |
| PUT | `/admin/system-message/preference/toggleDoNotDisturb` | toggleDoNotDisturb | — |
| PUT | `/admin/system-message/preference/update` | update | UpdatePreferenceRequest $request |
| PUT | `/admin/system-message/preference/updateChannels` | updateChannelPreferences | — |
| PUT | `/admin/system-message/preference/updateTypes` | updateTypePreferences | — |
| GET | `/admin/system-message/read/{id}` | read | int $id |
| GET | `/admin/system-message/recent` | recent | — |
| POST | `/admin/system-message/save` | save | CreateMessageRequest $request |
| POST | `/admin/system-message/schedule` | schedule | — |
| GET | `/admin/system-message/search` | search | — |
| POST | `/admin/system-message/send` | send | — |
| GET | `/admin/system-message/statistics` | statistics | — |
| GET | `/admin/system-message/template/active` | getActiveTemplates | — |
| GET | `/admin/system-message/template/categories` | getCategories | — |
| PUT | `/admin/system-message/template/changeStatus` | changeStatus | — |
| POST | `/admin/system-message/template/copy` | copy | — |
| DELETE | `/admin/system-message/template/delete` | delete | — |
| POST | `/admin/system-message/template/export` | export | — |
| GET | `/admin/system-message/template/getVariables/{id}` | getVariables | int $id |
| POST | `/admin/system-message/template/import` | import | — |
| GET | `/admin/system-message/template/index` | index | — |
| POST | `/admin/system-message/template/preview` | preview | — |
| GET | `/admin/system-message/template/read/{id}` | read | int $id |
| POST | `/admin/system-message/template/render` | render | — |
| POST | `/admin/system-message/template/save` | save | CreateTemplateRequest $request |
| GET | `/admin/system-message/template/search` | search | — |
| PUT | `/admin/system-message/template/update/{id}` | update | int $id, UpdateTemplateRequest $request |
| POST | `/admin/system-message/template/validateVariables` | validateVariables | — |
| PUT | `/admin/system-message/update/{id}` | update | int $id, UpdateMessageRequest $request |
| DELETE | `/admin/system-message/user/batchDelete` | batchDelete | — |
| PUT | `/admin/system-message/user/batchMarkRead` | batchMarkAsRead | — |
| DELETE | `/admin/system-message/user/delete/{messageId}` | delete | int $messageId |
| GET | `/admin/system-message/user/index` | index | — |
| PUT | `/admin/system-message/user/markAllRead` | markAllAsRead | — |
| PUT | `/admin/system-message/user/markRead/{messageId}` | markAsRead | int $messageId |
| GET | `/admin/system-message/user/read/{messageId}` | read | int $messageId |
| GET | `/admin/system-message/user/search` | search | — |
| GET | `/admin/system-message/user/typeStats` | getTypeStats | — |
| GET | `/admin/system-message/user/unreadCount` | getUnreadCount | — |
| GET | `/admin/system/setting/group/{group}` | group | string $group |
| GET | `/admin/system/setting/groups` | groups | — |
| GET | `/admin/system/setting/values` | values | ?string $keys = null |
| PUT | `/admin/system/setting/{key}` | update | string $key, SystemSettingRequest $request |
| DELETE | `/admin/user` | delete | — |
| POST | `/admin/user` | create | UserRequest $request |
| PUT | `/admin/user` | updateInfo | UserRequest $request |
| DELETE | `/admin/user-login-log` | delete | RequestInterface $request |
| GET | `/admin/user-login-log/list` | page | — |
| DELETE | `/admin/user-operation-log` | delete | RequestInterface $request |
| GET | `/admin/user-operation-log/list` | page | — |
| GET | `/admin/user/list` | pageList | — |
| PUT | `/admin/user/password` | resetPassword | ResetPasswordRequest $request |
| PUT | `/admin/user/{userId}` | save | int $userId, UserRequest $request |
| GET | `/admin/user/{userId}/roles` | getUserRole | int $userId |
| PUT | `/admin/user/{userId}/roles` | batchGrantRolesForUser | int $userId, BatchGrantRolesForUserRequest $request |


### 3.2 登录与权限

| 方法 | 路径 | 说明 |
|------|------|------|
| POST | `/admin/passport/login` | 登录 |
| POST | `/admin/passport/logout` | 退出 |
| GET | `/admin/passport/getInfo` | 当前用户信息与菜单 |
| POST | `/admin/passport/refresh` | 刷新 Token |
| GET | `/admin/permission/menus` | 当前用户菜单树 |
| GET | `/admin/permission/roles` | 角色选项 |
| POST | `/admin/permission/update` | 更新权限相关 |

### 3.3 用户 / 角色 / 菜单 / 部门 / 岗位

| 模块 | 前缀 | 主要能力 |
|------|------|----------|
| 用户 | `/admin/user` | list、增删改、改密、分配角色 |
| 角色 | `/admin/role` | list、CRUD、分配菜单权限 |
| 菜单 | `/admin/menu` | list、CRUD |
| 部门 | `/admin/department` | list、CRUD |
| 岗位 | `/admin/position` | list、CRUD、数据权限 |
| 领导 | `/admin/leader` | list、设置、删除 |

Request 参考：`app/Interface/Admin/Request/Permission/*`

### 3.4 商品中心

| 模块 | 前缀 | 主要能力 |
|------|------|----------|
| 商品 | `/admin/product/product` | list、stats、详情、CRUD、上下架、排序、导出 |
| 分类 | `/admin/product/category` | list、tree、CRUD、options、统计、排序、移动、面包屑 |
| 品牌 | `/admin/product/brand` | list、CRUD、options、统计、排序 |

Request 参考：`app/Interface/Admin/Request/Product/*`

### 3.5 订单与售后

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

### 3.6 会员

| 模块 | 前缀 | 主要能力 |
|------|------|----------|
| 会员 | `/admin/member/member` | list、stats、overview、CRUD、状态、标签、导出 |
| 等级 | `/admin/member/level` | CRUD |
| 标签 | `/admin/member/tag` | list、options、CRUD |
| 账户 | `/admin/member/account` | 钱包流水、余额调整 |

### 3.7 营销

| 模块 | 前缀 | 主要能力 |
|------|------|----------|
| 优惠券 | `/admin/coupon` | list、stats、CRUD、启停、发放、导出 |
| 领券记录 | `/admin/coupon/user` | list、标记已用/过期 |
| 秒杀活动 | `/admin/seckill/activity` | list、stats、CRUD、启停、导出 |
| 秒杀场次 | `/admin/seckill/session` | list、按活动查、CRUD、启停 |
| 秒杀商品 | `/admin/seckill/product` | list、按场次查、CRUD、批量、启停 |
| 拼团 | `/admin/group-buy` | list、stats、CRUD、启停、导出 |

### 3.8 运营与系统

| 模块 | 前缀 | 主要能力 |
|------|------|----------|
| 评价 | `/admin/review` | list、详情、审核、回复、统计、按订单查 |
| 运费模板 | `/admin/shipping/templates` | CRUD |
| 系统设置 | `/admin/system/setting` | 分组读取、按键更新、批量 values |
| 附件 | `/admin/attachment` | list、upload、delete |
| 仪表盘 | `/admin/dashboard` | welcome、analysis、report |
| 登录/操作日志 | `/admin/user-login-log`、`/admin/user-operation-log` | list、删除 |
| 站内信 | `admin/system-message/*` | 消息、模板、用户消息、偏好设置 |

### 3.9 Admin 通用说明

1. **列表接口**：多数为 `GET .../list`，查询参数定义在对应 `*Request::listRules()` 中；常含 `page`、`page_size` 或 MineAdmin 分页字段。
2. **创建/更新**：`POST`/`PUT` 使用 JSON Body，规则见 `*Request::saveRules()` / `updateRules()` 或 DTO。
3. **删除**：部分为 `DELETE` + Body 传 `ids` 数组（见 `DeleteRequest`）。
4. **导出**：`POST .../export` 通常触发异步导出任务（export-center 插件），返回任务 ID。
5. **权限码**：控制器上 `#[Permission(code: '...')]`，前端按钮需与之后端一致。

---

## 四、如何查更细的字段

| 需求 | 查看位置 |
|------|----------|
| 路由与方法 | `app/Interface/{Admin\|Api}/Controller/**/*.php` 注解 |
| 入参校验 | `app/Interface/{Admin\|Api}/Request/**/*.php` 的 `*Rules()` |
| 出参结构 | `app/Interface/Api/Transformer/**`、`app/Interface/Admin/Dto/**` |
| 业务逻辑 | `app/Application/**`、`app/Domain/**` |

重新生成本文档第二节/第三节中的 **接口总览表**：

```bash
php bin/generate-api-doc.php
```

---

*文档版本：与仓库代码同步整理，如有接口变更请以代码注解为准。*
