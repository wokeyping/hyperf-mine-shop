# MineShop 接口文档索引

> 根据 `app/Interface` 控制器注解自动生成（`php bin/generate-api-doc.php` 可重新生成总览表）。

接口文档已按端拆分：

| 文档 | 说明 | 路径前缀 |
|------|------|----------|
| [C 端 Api 接口文档](./API接口文档-Api.md) | 小程序 / H5 商城接口 | `/api/v1/*` |
| [Admin 后台接口文档](./API接口文档-Admin.md) | 管理后台接口 | `/admin/*` |

**本地基础地址**：`http://127.0.0.1:9501`

两份文档均包含：统一响应结构、鉴权说明、**接口总览表**（含「响应 data」列）及核心业务接口说明。精细字段以各 `Request`、`Transformer`、`Dto` 为准。

---

## 如何查更细的字段

| 需求 | 查看位置 |
|------|----------|
| 路由与方法 | `app/Interface/{Admin\|Api}/Controller/**/*.php` 注解 |
| 入参校验 | `app/Interface/{Admin\|Api}/Request/**/*.php` 的 `*Rules()` |
| 出参结构 | `app/Interface/Api/Transformer/**`、`app/Interface/Admin/Dto/**` |
| 业务逻辑 | `app/Application/**`、`app/Domain/**` |

重新生成 **接口总览表**：

```bash
php bin/generate-api-doc.php
```

---

*文档版本：与仓库代码同步整理，如有接口变更请以代码注解为准。*
