# 今日热榜 - 多平台实时热点聚合

<p align="center"><b>50+ 平台热榜数据一个页面看完 · 纯原生 PHP + JS · 三层缓存 · 夜间模式 · 对外 JSON API</b></p>

<p align="center">
  <img src="assets/images/og-image.png" alt="预览图">
</p>

---

## 🚀 快速开始

### 环境要求

- PHP **7.4+**（推荐 8.0+），扩展：`curl`、`json`
- Nginx（推荐）或 Apache

### 三步上线

**1. 上传**

phpStudy / 宝塔 / 任何 LNMP 环境，把整个项目丢进 Web 根目录。

**2. Nginx 配置**

- 宝塔 / 云服务器：打开 [nginx.conf](nginx.conf)，复制里面的 server{} 内容粘贴到面板生成的站点配置里
- 本地 phpStudy：打开 [nginx.htaccess](nginx.htaccess)，顶部注释有完整三步操作

> gzip 必须在全局 http{} 块开启。宝塔面板里打勾就行；phpStudy 打开 `Extensions/Nginx版本号/conf/nginx.conf` 把注释的 `#gzip on;` 换成完整配置。

**3. 改 config.php + robots.txt**

config.php 里改 `site_name`、`seo_domain`、`opcache_clear_key` 三处；robots.txt 最后一行 `Sitemap:` 后面的域名改成你的。

搞定！访问域名即可看到首页。

---

## 📦 项目结构

```
# —— 访客页面 ——
index.php              ← 首页（卡片网格）
platform.php           ← 单平台详情 / 短 URL /baidu
archive.php            ← 历史归档浏览
stats.php              ← API 调用统计
sitemap.php            ← 动态 Sitemap（XML）
404.php                ← 404 错误页

# —— 站长工具（建议加 IP 白名单）——
setting.php            ← 用户偏好设置
status.php             ← 平台缓存状态面板
debug.php              ← 调试 & CURL 测试
apidoc.php             ← API 文档

# —— 系统 ——
config.php             ← 唯一配置文件
function.php           ← 公共入口（require 所有模块）
robots.txt             ← 爬虫规则
nginx.conf            ← 宝塔 / 云服务器专用
nginx.htaccess         ← phpStudy 本地专用

home/                  ← 页面组件（header / footer / notice）
core/                  ← 核心逻辑（fetcher / curl_helper / cache）
api/                   ← API 系统
  ├── V1/              ← 全量采集（53 个平台，原样抓取）
  ├── V2/              ← 精选展示（24 个平台，精简字段）
  └── utils/config.php ← API 白名单
assets/                ← 静态资源（CSS / JS / iconfont / 图片）
cache/ logs/           ← 自动生成，无需手动创建
```

> V1 和 V2 是两套独立的处理器目录，config.php 里 `api_version` 切换用哪套。V1 全量覆盖、字段多；V2 精选质量、字段统一。

---

## 🗂️ 页面路由

| URL | 文件 | 说明 |
|---|---|---|
| `/` | index.php | 首页，热榜卡片网格 |
| `/platform/baidu` | platform.php | 单平台详情页 |
| `/baidu` | platform.php | 短 URL，同上 |
| `/archive/baidu` | archive.php | 某平台的历史归档 |
| `/stats` | stats.php | API 调用统计（7 日 Top） |
| `/sitemap` / `/sitemap.xml` | sitemap.php | 动态 XML 网站地图 |
| `/status` | status.php | 各平台缓存状态面板 |
| `/debug` | debug.php | 平台 CURL 测试 + 缓存诊断 |
| `/apidoc` | apidoc.php | 可用平台列表 + API 文档 |
| `/setting` | setting.php | 浏览器端偏好（主题等） |

> Nginx rewrite 让 `/platform/baidu`、`/baidu`、`/archive/baidu` 都不用加 `.php`；独立页面通过精确匹配 `location = /xxx` 实现。具体规则见 nginx.conf 或 nginx.htaccess。

---

## 🎨 特性

- 50+ 平台（百度 / 微博 / 知乎 / Bilibili / 抖音 / 虎扑 / GitHub …）
- 三层缓存（页面静态 + 平台 JSON + OPcache）
- 夜间模式 / 响应式 / iconfont 图标字体
- 动态 Sitemap + 安全加固

---

## 🔌 对外 API（默认开启）

API 默认开启，开关在 `api/index.php` 顶部。如需关闭，改 `ENABLE_API_ROUTER` 为 `false`。

- ✅ 支持 CORS 跨域，前端直接调用
- ✅ 支持 PATH_INFO 方式，零配置
- ✅ 支持 `force=1` 跳过缓存强制刷新

**请求格式**
```
GET /api/index.php/{platform}?limit=10&force=1
```

**请求示例**
```
GET /api/index.php/baidu?limit=10
GET /api/index.php/weibo?limit=20
GET /api/index.php/zhihu?limit=5&force=1
```

**返回格式**
```json
{
    "success": true,
    "title": "百度热搜",
    "subtitle": "热搜",
    "total": 50,
    "update_time": "2026-11-20 10:00:00",
    "data": [
        {"index": 1, "title": "热搜标题", "hot": "4,992,109", "url": "...", "desc": "", "pic": ""}
    ]
}
```

**可用平台**

api/V1/ 下有 53 个，api/V2/ 下有 24 个，实际可用平台取决于 config.php 里的 `api_version` 配置。查看完整列表：`/apidoc.php`。

---

## ⚙️ 配置速查

所有配置都在 `config.php` 里，按分组注释，打开即懂。核心项：

| 项 | 作用 | 默认 |
|---|---|---|
| `api_version` | V1 全量采集 / V2 精选展示 | `V2` |
| `cache_ttl` | 平台 JSON 缓存时长 | 43200s（12h） |
| `page_cache_ttl` | 页面 HTML 缓存时长 | 1800s（30min） |
| `default_theme` | 默认主题 | `light` |
| `display_limit` | 卡片默认显示条数 | `10` |

---

## 🛠️ 添加新平台

1. `api/V1/` 和 `api/V2/` 各创建一个处理器类（类名规则：`xxx_hot_search` → `XxxHotSearch`）
2. `api/utils/config.php` 的 `platforms[]` 注册 id
3. `config.php` 的 `$sources[]` 加一条显示配置
4. `assets/css/icon-color.css` 加颜色类

---

## 🐛 常见问题

| 问题 | 解决 |
|---|---|
| 改了代码页面没变化 | 清 OPcache + 页面缓存（顶部按钮），或 Ctrl+F5 |
| 某平台返回空 | 访问 `/debug.php` 单独测试，看具体错误 |
| 正则失效 | 打开处理器文件，F12 Network 看目标站接口结构 |
| API 不支持某平台 | 检查白名单 + V1/V2 下有没有对应处理器文件 |

---

## 📄 License

MIT
