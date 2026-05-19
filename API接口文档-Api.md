# MineShop C 端 Api 接口文档

> 根据 `app/Interface/Api` 控制器注解自动生成（`php bin/generate-api-doc.php` 可重新生成）。
> **§2.1 总览表含「响应 data」列**（由控制器 `return` 推断）；精细字段以各 `Request`、`Transformer` 为准。
> 下文对核心接口补充了请求校验与典型响应说明。

[← 返回文档索引](./API接口文档.md)

**基础地址**

| 环境 | 地址 | 路径前缀 |
|------|------|----------|
| 本地 | `http://127.0.0.1:9501` | `/api/v1/*` |

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
### 1.3 鉴权

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
### 1.4 参数命名

- HTTP JSON 建议使用 **snake_case**（如 `order_no`）；小程序客户端会自动做驼峰 ↔ 蛇形转换。
- GET 查询参数、POST JSON Body 均可能使用；路径参数见各接口路径中的 `{id}`、`{orderNo}` 等。

---
## 二、接口列表（`/api/v1`）

### 2.1 接口总览

| 方法 | 路径 | 控制器方法 | 请求类/参数 | 响应 `data` |
|------|------|------------|-------------|-------------|
| GET | `/api/v1/after-sales` | index | — | list[] + pagination |
| POST | `/api/v1/after-sales` | apply | AfterSaleApplyRequest $request | 业务对象（Transformer） |
| GET | `/api/v1/after-sales/eligibility` | eligibility | — | 业务数据（见 Service/Transformer） |
| GET | `/api/v1/after-sales/{id}` | detail | int $id | 业务对象（Transformer） |
| POST | `/api/v1/after-sales/{id}/cancel` | cancel | int $id | 空对象 {} |
| POST | `/api/v1/after-sales/{id}/confirm-exchange-received` | confirmExchangeReceived | int $id | 空对象 {} |
| GET | `/api/v1/after-sales/{id}/reship-logistics` | reshipLogistics | int $id | 业务数据（见 Service/Transformer） |
| GET | `/api/v1/after-sales/{id}/return-logistics` | returnLogistics | int $id | 业务数据（见 Service/Transformer） |
| POST | `/api/v1/after-sales/{id}/return-shipment` | submitReturnShipment | int $id, AfterSaleReturnShipmentRequest $request | 空对象 {} |
| POST | `/api/v1/auth/captcha` | captcha | SendVerificationCodeRequest $request | phone, scene, code?(调试) |
| POST | `/api/v1/auth/forgotPassword` | forgotPassword | ForgotPasswordRequest $request | 空对象 {} |
| POST | `/api/v1/auth/register` | register | RegisterRequest $request | token, refresh_token, expires_in, member |
| GET | `/api/v1/auth/register/protocols` | registerProtocols | — | userAgreement, privacyPolicy |
| GET | `/api/v1/cart` | index | — | 业务数据（见 Service/Transformer） |
| POST | `/api/v1/cart/clear-invalid` | clearInvalid | — | 业务数据（见 Service/Transformer） |
| POST | `/api/v1/cart/items` | store | CartItemStoreRequest $request | 业务数据（见 Service/Transformer） |
| DELETE | `/api/v1/cart/items/{skuId}` | destroy | int $skuId | 业务数据（见 Service/Transformer） |
| PUT | `/api/v1/cart/items/{skuId}` | update | CartItemUpdateRequest $request, int $skuId | 业务数据（见 Service/Transformer） |
| GET | `/api/v1/categories` | index | — | 对象（见控制器内联数组） |
| GET | `/api/v1/coupons/available` | available | CouponAvailableRequest $request | 对象（见控制器内联数组） |
| GET | `/api/v1/coupons/{id}` | show | int $id | 对象（见控制器） |
| GET | `/api/v1/geo/regions` | index | RequestInterface $request | 对象（见控制器） |
| GET | `/api/v1/group-buy/products` | index | — | 业务数据（见 Service/Transformer） |
| GET | `/api/v1/group-buy/products/{activityId}/groups` | groups | int $activityId | 对象（见控制器内联数组） |
| GET | `/api/v1/group-buy/products/{activityId}/{spuId}` | show | int $activityId, int $spuId | 业务数据（见 Service/Transformer） |
| GET | `/api/v1/home` | show | — | 业务数据（见 Service/Transformer） |
| POST | `/api/v1/login/h5Password` | h5Password | — | token, refresh_token, expires_in, member |
| POST | `/api/v1/login/miniApp` | miniApp | — | token, refresh_token, expires_in, member |
| GET | `/api/v1/member/addresses` | index | — | list[]（+ 可选 total） |
| POST | `/api/v1/member/addresses` | store | MemberAddressRequest $request | 业务对象（Transformer） |
| DELETE | `/api/v1/member/addresses/{id}` | destroy | int $id | 空对象 {} |
| GET | `/api/v1/member/addresses/{id}` | show | int $id | 业务对象（Transformer） |
| PUT | `/api/v1/member/addresses/{id}` | update | MemberAddressRequest $request, int $id | 业务对象（Transformer） |
| POST | `/api/v1/member/addresses/{id}/default` | markDefault | int $id | 空对象 {} |
| GET | `/api/v1/member/center` | center | — | userInfo, countsData, orderTagInfos… |
| GET | `/api/v1/member/coupons` | index | — | 对象（见控制器内联数组） |
| POST | `/api/v1/member/coupons/receive` | receive | CouponReceiveRequest $request | 对象（见控制器内联数组） |
| GET | `/api/v1/member/invite/qrcode` | inviteQrCode | — | 业务数据（见 Service/Transformer） |
| POST | `/api/v1/member/phone/bind` | bindPhone | PhoneAuthorizeRequest $request | phone_number, pure_phone_number, country_code |
| GET | `/api/v1/member/profile` | profile | — | member（MemberProfileTransformer） |
| POST | `/api/v1/member/profile/authorize` | authorizeProfile | ProfileAuthorizeRequest $request | 空对象 {} |
| POST | `/api/v1/member/profile/update` | updateProfile | ProfileUpdateRequest $request | 空对象 {} |
| GET | `/api/v1/member/wallet/transactions` | transactions | WalletTransactionRequest $request | list[], total |
| POST | `/api/v1/order/cancel` | cancel | OrderCancelRequest $request | 空对象 {} |
| POST | `/api/v1/order/confirm-receipt` | confirmReceipt | OrderConfirmReceiptRequest $request | 空对象 {} |
| GET | `/api/v1/order/detail/{orderNo}` | detail | string $orderNo | orderTransformer 对象 |
| GET | `/api/v1/order/list` | list | OrderListRequest $request | list[] + pagination |
| GET | `/api/v1/order/logistics/{orderNo}` | logistics | string $orderNo | 业务数据（见 Service/Transformer） |
| GET | `/api/v1/order/pay-info/{orderNo}` | payInfo | string $orderNo | 对象（见控制器） |
| POST | `/api/v1/order/payment` | payment | OrderPaymentRequest $request | 业务数据（见 Service/Transformer） |
| POST | `/api/v1/order/preview` | preview | OrderPreviewRequest $request | 见 checkoutTransformer |
| GET | `/api/v1/order/statistics` | statistics | — | 见 orderTransformer |
| POST | `/api/v1/order/submit` | submit | OrderCommitRequest $request | 业务数据（见 Service/Transformer） |
| GET | `/api/v1/order/submit-result/{tradeNo}` | submitResult | string $tradeNo | 业务数据（见 Service/Transformer） |
| POST | `/api/v1/payment/wechat/pay-notify` | payNotify | — | 业务数据（见 Service/Transformer） |
| POST | `/api/v1/payment/wechat/refund-notify` | refundNotify | — | 业务数据（见 Service/Transformer） |
| GET | `/api/v1/products` | index | ProductListRequest $request | list[]（+ 可选 total） |
| GET | `/api/v1/products/{id}` | show | int $id | 业务对象（Transformer） |
| POST | `/api/v1/review` | store | CreateReviewRequest $request | { id } 或含 id 的对象 |
| GET | `/api/v1/review/product/{id}` | productReviews | int $id | 业务数据（见 Service/Transformer） |
| GET | `/api/v1/review/product/{id}/stats` | productStats | int $id | 业务数据（见 Service/Transformer） |
| GET | `/api/v1/review/product/{id}/summary` | productSummary | int $id | 业务数据（见 Service/Transformer） |
| GET | `/api/v1/seckill/products` | index | — | 业务数据（见 Service/Transformer） |
| GET | `/api/v1/seckill/products/{sessionId}/{spuId}` | show | int $sessionId, int $spuId | 业务对象（Transformer） |
| GET | `/api/v1/seckill/sessions` | index | — | 业务数据（见 Service/Transformer） |
| POST | `/api/v1/upload/image` | image | RequestInterface $request | 对象（见控制器） |


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

**响应 `data`**（同小程序登录）

| 字段 | 说明 |
|------|------|
| token | 访问令牌 |
| refresh_token | 刷新令牌 |
| expires_in | 过期秒数 |
| member | id、phone、nickname、avatar、source |

#### POST `/api/v1/auth/captcha` — 发送验证码

| 字段 | 类型 | 必填 | 说明 |
|------|------|------|------|
| phone | string | 是 | 手机号 |
| scene | string | 是 | `register` \| `forgot_password` |

**响应 `data`**

| 字段 | 说明 |
|------|------|
| phone | 手机号 |
| scene | 场景 |
| code | 仅调试环境可能返回验证码明文 |

#### POST `/api/v1/auth/register` — 注册

| 字段 | 类型 | 必填 | 说明 |
|------|------|------|------|
| phone | string | 是 | 未注册手机号 |
| password | string | 是 | 最少 6 位 |
| password_confirmation | string | 是 | 确认密码 |
| code | string | 是 | 6 位验证码 |

**响应 `data`**（同登录）：`token`、`refresh_token`、`expires_in`、`member`

#### POST `/api/v1/auth/forgotPassword` — 忘记密码

同注册字段（phone、password、password_confirmation、code）。

**响应 `data`**：空对象 `{}`

#### GET `/api/v1/auth/register/protocols` — 注册协议文案

无请求体。

**响应 `data`**

| 字段 | 说明 |
|------|------|
| userAgreement | 用户协议 HTML/文本 |
| privacyPolicy | 隐私政策 HTML/文本 |

---

### 2.3 会员

#### GET `/api/v1/member/profile` — 个人资料

需 Token。

**响应 `data`**

| 字段 | 说明 |
|------|------|
| member | 见 `MemberProfileTransformer`：id、avatar、nickname、phone、gender、level_name、level、balance、points、authorized_profile、invite_code |

#### GET `/api/v1/member/center` — 个人中心聚合数据

需 Token。

**响应 `data`**（见 `MemberCenterTransformer`）

| 字段 | 说明 |
|------|------|
| userInfo | 头像、昵称、手机、等级、余额、积分、邀请码等 |
| countsData | 余额/积分/优惠券数量卡片 |
| orderTagInfos | 待付款、待发货、待收货、待评价、售后角标 |
| customerServiceInfo | 客服电话、服务时间 |
| referralCount | 推荐人数 |

#### POST `/api/v1/member/profile/update` — 更新资料

| 字段 | 类型 | 必填 | 说明 |
|------|------|------|------|
| avatar_url | string | 否 | 头像 URL |
| nick_name | string | 否 | 昵称 |
| gender | int | 否 | 0/1/2 |
| phone | string | 否 | 手机号 |

**响应 `data`**：空对象 `{}`（成功后建议再调 `profile` 拉最新资料）

#### POST `/api/v1/member/profile/authorize` — 授权头像昵称

**响应 `data`**：空对象 `{}`

#### POST `/api/v1/member/phone/bind` — 绑定手机号

见 `PhoneAuthorizeRequest`（微信手机号组件 `code`）。

**响应 `data`**

| 字段 | 说明 |
|------|------|
| phone_number | 带区号手机号 |
| pure_phone_number | 纯手机号 |
| country_code | 国家码 |

#### GET `/api/v1/member/invite/qrcode` — 邀请二维码

需 Token。Query 可选 `page`（小程序页面路径）。

**响应 `data`**：含小程序码 URL/Base64 等（见 `AppApiMemberReferralQueryService`）

#### GET `/api/v1/member/wallet/transactions` — 钱包流水

| 查询参数 | 类型 | 必填 | 说明 |
|----------|------|------|------|
| wallet_type | string | 是 | `balance` \| `points` |
| page | int | 否 | 页码，默认 1 |
| page_size | int | 否 | 每页条数，最大 100 |

**响应 `data`**

| 字段 | 说明 |
|------|------|
| list | 流水记录数组 |
| total | 总条数 |

---

### 2.4 收货地址 `/api/v1/member/addresses`

| 方法 | 路径 | 说明 | 响应 `data` |
|------|------|------|-------------|
| GET | `` | 地址列表 | `list[]`，见 `MemberAddressTransformer` |
| GET | `/{id}` | 地址详情 | 单条地址对象 |
| POST | `` | 新增 | 单条地址对象 |
| PUT | `/{id}` | 修改 | 单条地址对象 |
| DELETE | `/{id}` | 删除 | 空对象 `{}` |
| POST | `/{id}/default` | 设为默认 | 空对象 `{}` |

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

**响应 `data`**：分页 `list[]` + `pagination`（见 `ProductTransformer::transformListItem`）

#### GET `/api/v1/products/{id}` — 商品详情

**响应 `data`**：商品详情对象（见 `ProductTransformer::transformDetail`）

#### GET `/api/v1/categories` — 分类树/列表

**响应 `data`**：`list` 为分类树（见 `CategoryTransformer::transformTree`）

---

### 2.6 购物车 `/api/v1/cart`

| 方法 | 路径 | Body / 说明 | 响应 `data` |
|------|------|-------------|-------------|
| GET | `` | 购物车列表 | 见 `CartTransformer`（商品行、金额汇总等） |
| POST | `items` | `sku_id`(必填), `quantity`(1-999) | 同 GET 结构 |
| PUT | `items/{skuId}` | `quantity` | 同 GET 结构 |
| DELETE | `items/{skuId}` | 删除单项 | 同 GET 结构 |
| POST | `clear-invalid` | 清理失效商品 | 同 GET 结构 |

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

**响应 `data`**：微信返回调起支付参数（timeStamp、nonceStr、package 等）；余额支付返回扣款结果对象。

#### GET `list` — 订单列表

| 查询参数 | 说明 |
|----------|------|
| status | `all` \| `pending` \| `paid` \| `shipped` \| `completed` \| `after_sale` |
| page / page_size | 分页 |

**响应 `data`**：分页 `list[]` + `pagination`（见 `OrderTransformer::transform`）

#### GET `detail/{orderNo}` — 订单详情

**响应 `data`**：见 `OrderTransformer::transformDetail`

#### GET `logistics/{orderNo}` — 物流轨迹

**响应 `data`**：物流公司、运单号、轨迹节点列表等

#### GET `statistics` — 各状态订单数量

**响应 `data`**：各状态订单数量统计对象

#### GET `pay-info/{orderNo}` — 待支付订单支付信息

**响应 `data`**：应付金额、可用支付方式等

#### POST `cancel` — 取消订单

Body: `{ "order_no": "..." }`

**响应 `data`**：空对象 `{}`

#### POST `confirm-receipt` — 确认收货

Body: `{ "order_no": "..." }`

**响应 `data`**：空对象 `{}`

---

### 2.8 优惠券

| 方法 | 路径 | 说明 | 响应 `data` |
|------|------|------|-------------|
| GET | `/api/v1/coupons/available` | 可领/可用券，`spu_id`、`limit` | `list[]`、`total` |
| GET | `/api/v1/coupons/{id}` | 券详情 | 券对象 |
| GET | `/api/v1/member/coupons` | 我的优惠券 | `list[]` |
| POST | `/api/v1/member/coupons/receive` | 领取，Body: `coupon_id` | `{ message: "领取成功" }` |

---

### 2.9 秒杀 / 拼团

| 方法 | 路径 | 说明 | 响应 `data` |
|------|------|------|-------------|
| GET | `/api/v1/seckill/sessions` | 秒杀场次列表 | 场次列表（Transformer） |
| GET | `/api/v1/seckill/products` | 场次商品列表 | 商品列表 |
| GET | `/api/v1/seckill/products/{sessionId}/{spuId}` | 秒杀商品详情 | 商品详情 |
| GET | `/api/v1/group-buy/products` | 拼团活动商品 | 活动商品列表 |
| GET | `/api/v1/group-buy/products/{activityId}/groups` | 进行中的团 | `{ list: 团列表 }` |
| GET | `/api/v1/group-buy/products/{activityId}/{spuId}` | 拼团商品详情 | 商品+活动详情 |

下单时 `order_type` 分别为 `seckill`、`group_buy`。

---

### 2.10 售后 `/api/v1/after-sales`

| 方法 | 路径 | 说明 | 响应 `data` |
|------|------|------|-------------|
| GET | `eligibility` | 可申请售后的订单项 | 可申请项列表 |
| POST | `` | 申请售后 | 见 `AfterSaleTransformer` |
| GET | `` | 售后列表 | 分页 list + pagination |
| GET | `{id}` | 详情 | 售后单详情 |
| GET | `{id}/return-logistics` | 退货物流 | 物流信息 |
| GET | `{id}/reship-logistics` | 换货发货物流 | 物流信息 |
| POST | `{id}/cancel` | 取消申请 | 空对象 `{}` |
| POST | `{id}/return-shipment` | 提交退货物流 | 空对象 `{}` |
| POST | `{id}/confirm-exchange-received` | 确认换货收货 | 空对象 `{}` |

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

| 方法 | 路径 | 说明 | 响应 `data` |
|------|------|------|-------------|
| POST | `` | 提交评价（需 Token） | `{ id: 评价ID }` |
| GET | `product/{id}` | 商品评价列表 | 见 `ReviewTransformer::transformListResult` |
| GET | `product/{id}/stats` | 评价统计 | 各星级数量等 |
| GET | `product/{id}/summary` | 评价摘要 | 见 `ReviewTransformer::transformSummaryResult` |

**提交评价 Body**: `rating`(1-5), `content`, `images[]`, `order_id`, `order_item_id`, `is_anonymous`

---

### 2.12 其他 C 端接口

| 方法 | 路径 | 说明 | 响应 `data` |
|------|------|------|-------------|
| GET | `/api/v1/home` | 首页数据（Banner、推荐等） | 见 `HomeTransformer` |
| POST | `/api/v1/upload/image` | 图片上传（multipart） | `url` 等附件信息 |
| GET | `/api/v1/geo/regions` | 省市区，Query: `parent_code` 等 | `list[]` 区划节点 |
| POST | `/api/v1/payment/wechat/pay-notify` | 微信回调 | 非标准 Result，返回微信要求格式 |
| POST | `/api/v1/payment/wechat/refund-notify` | 退款回调 | 同上 |

---

## 三、如何查更细的字段

| 需求 | 查看位置 |
|------|----------|
| 路由与方法 | `app/Interface/Api/Controller/**/*.php` 注解 |
| 入参校验 | `app/Interface/Api/Request/**/*.php` 的 `*Rules()` |
| 出参结构 | `app/Interface/Api/Transformer/**` |
| 业务逻辑 | `app/Application/**`、`app/Domain/**` |

重新生成本文档 **§2.1 接口总览表**：

```bash
php bin/generate-api-doc.php
```

---

*文档版本：与仓库代码同步整理，如有接口变更请以代码注解为准。*
