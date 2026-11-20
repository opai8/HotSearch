# 今日热榜 - 多平台实时热点聚合

---

<p><b>50+ 平台热榜数据一个页面看完 · 纯原生 PHP + JS · 三层缓存 · 夜间模式 · 对外 JSON API</b></p>

---

<p align="center">
  <img src="assets/images/og-image.png" alt="预览图">
</p>

---

## 🚀 快速开始

**环境要求**：PHP 7.4+（推荐 8.0+），扩展 `curl` `json`，Nginx（推荐，需配置）或 Apache（开箱即用）

### 三步上线

**1. 上传**

phpStudy / 宝塔 / 任何 LNMP 环境，把整个项目丢进 Web 根目录。

**2. Nginx 配置**

- 宝塔：打开 [nginx.conf](nginx.conf)，复制 server{} 内容粘到面板站点配置里
- phpStudy：打开 [nginx.htaccess](nginx.htaccess)，顶部注释有完整三步操作

**3. 改配置**

- `config.php` → `site_name`、`seo_domain`、`opcache_clear_key` 三处
- `robots.txt` → 最后一行 `Sitemap:` 后面的域名

访问域名，搞定。

---

## 📦 项目结构

```
config.php             ← 唯一配置文件
function.php           ← 公共入口（require 所有模块）
robots.txt

home/                  ← 页面组件（header / footer / notice）
core/                  ← 核心逻辑（fetcher / curl_helper / cache）
api/                   ← API 系统
  ├── V1/              ← 全量采集（53 平台，原样抓取）
  ├── V2/              ← 精选展示（24 平台，精简字段）
  └── utils/config.php ← API 白名单
assets/                ← 静态资源（CSS / JS / iconfont / 图片）
cache/ logs/           ← 自动生成，无需手动创建
```

> V1/V2 两套独立处理器，config.php 的 `api_version` 切换。

---

## 🗂️ 页面路由

| URL | 文件 | 说明 |
|---|---|---|
| `/` | index.php | 首页，热榜卡片网格 |
| `/platform/baidu` · `/baidu` | platform.php | 单平台详情 + 短 URL |
| `/archive/baidu` | archive.php | 某平台的历史归档 |
| `/stats` | stats.php | API 调用统计（7 日 Top） |
| `/sitemap` / `/sitemap.xml` | sitemap.php | 动态 XML 网站地图 |
| `/status` | status.php | 各平台缓存状态面板 |
| `/debug` | debug.php | 平台 CURL 测试 + 缓存诊断 |
| `/apidoc` | apidoc.php | 可用平台列表 + API 文档 |
| `/setting` | setting.php | 浏览器端偏好（主题等） |

---

## 🎨 特性

- 50+ 平台（百度 / 微博 / 知乎 / Bilibili / 抖音 / 虎扑 / GitHub …）
- 三层缓存（页面静态 + 平台 JSON + OPcache）
- 夜间模式 / 响应式 / iconfont 图标字体
- 动态 Sitemap + 安全加固

---

## 🔌 对外 API（默认开启）

开关在 `api/index.php` 顶部，改 `ENABLE_API_ROUTER` 为 `false` 可关闭。

- ✅ CORS 跨域，前端直接调用
- ✅ PATH_INFO 零配置
- ✅ `force=1` 跳过缓存强制刷新

```
GET /api/index.php/baidu?limit=10
GET /api/index.php/weibo?limit=20
GET /api/index.php/zhihu?limit=5&force=1
```

返回：
```json
{
    "success": true,
    "title": "百度热搜",
    "total": 50,
    "update_time": "2026-11-20 10:00:00",
    "data": [{"index": 1, "title": "热搜标题", "hot": "4,992,109", "url": "..."}]
}
```

可用平台取决于 config.php 的 `api_version`，查看完整列表访问 `/apidoc.php`。

---

## ⚙️ 配置速查

所有配置都在 `config.php` 里，按分组注释，打开即懂。核心项：

| 项 | 作用 | 默认 |
|---|---|---|
| `api_version` | V1 全量 / V2 精选 | `V2` |
| `cache_ttl` | 平台 JSON 缓存 | 12h |
| `page_cache_ttl` | 页面 HTML 缓存 | 30min |
| `default_theme` | 默认主题 | `light` |
| `display_limit` | 卡片默认条数 | `10` |

---

## 🛠️ 添加新平台

1. `api/V1/` + `api/V2/` 各创建处理器类（`xxx_hot_search` → `XxxHotSearch`）
2. `api/utils/config.php` 的 `platforms[]` 注册 id
3. `config.php` 的 `$sources[]` 加一条
4. `assets/css/icon-color.css` 加颜色类

---

## 🐛 常见问题

| 问题 | 解决 |
|---|---|
| 改了没变化 | 清 OPcache + 页面缓存（顶部按钮），或 Ctrl+F5 |
| 某平台返回空 | 访问 `/debug.php` 单独测试 |
| 正则失效 | 打开处理器，F12 Network 看目标站接口 |
| API 不支持 | 检查白名单 + V1/V2 下有没有处理器 |

---

## 📄 License

MIT
