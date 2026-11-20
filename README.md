# 今日热榜 - 多平台实时热点聚合

<p align="center">
  <img src="https://img.shields.io/badge/PHP-7.4+-blue" alt="PHP">
  <img src="https://img.shields.io/badge/license-MIT-green" alt="MIT">
</p>

<p align="center">
  <img src="assets/images/og-image.png" alt="预览图">
</p>

<p align="center">50+ 平台热榜数据一个页面看完 · 纯原生 PHP + JS · 三层缓存 · 夜间模式 · 对外 API</p>

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

**3. 改 config.php + robots.txt**

config.php 里改 `site_name`、`seo_domain`、`opcache_clear_key` 三处；robots.txt 最后一行 `Sitemap:` 后面的域名改成你的。

搞定！访问域名即可看到首页。

---

## 📦 项目结构

```
index.php / platform.php / stats.php / sitemap.php / robots.txt
config.php              ← 唯一配置文件
nginx.conf             ← 宝塔 / 云服务器专用
nginx.htaccess          ← phpStudy 本地专用

home/                   ← 页面组件（header / footer / notice）
core/                   ← 核心逻辑（fetcher / curl_helper / cache）
api/                    ← API 系统
  ├── V1/               ← 全量采集（53 个平台，原样抓取）
  ├── V2/               ← 精选展示（24 个平台，精简字段）
  └── utils/config.php  ← API 白名单
assets/                 ← 静态资源（CSS / JS / iconfont / 图片）
cache/ logs/            ← 自动生成，无需手动创建
```

> V1 和 V2 是两套独立的处理器目录，config.php 里 `api_version` 切换用哪套。V1 采集多平台数据；V2 部分精选。

---

## 🎨 特性

- 50+ 平台（百度 / 微博 / 知乎 / Bilibili / 抖音 / 虎扑 / GitHub …）
- 三层缓存（页面静态 + 平台 JSON + OPcache）
- 夜间模式 / 响应式 / iconfont 图标字体
- 对外 JSON API（可选开关） + 动态 Sitemap + 安全加固

---

## 🔌 API

开关在 `api/index.php` 顶部：
```php
define('ENABLE_API_ROUTER', true);
```

请求示例：
```
GET /api/index.php/baidu?limit=10
GET /api/index.php/zhihu?limit=5&force=1    ← force=1 跳过缓存
```

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
