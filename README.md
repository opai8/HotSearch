# 热搜榜 - 多平台实时热点聚合

一个轻量级的热搜聚合站，抓取百度、微博、知乎、虎扑、抖音、贴吧、头条、澎湃、微信读书、V2EX、GitHub、掘金、少数派、果壳、虎嗅、爱范儿、HelloGitHub、52破解、纽约时报中文、游研社、B站、历史上的今天、知乎日报、 等平台的热榜数据，在一个页面上以卡片网格展示。

---

## 目录结构

```
项目根目录/
├── index.php             ← 首页入口（渲染卡片网格）
├── config.php            ← 全局配置（所有配置都在这里）
├── function.php          ← 核心功能统一入口（集中 require）
├── 404.php               ← 404 错误页面
├── .htaccess             ← Apache 配置（404 / 重写 / 缓存 / 安全）
├── robots.txt            ← 搜索引擎爬虫规则
├── sitemap.xml           ← 网站地图
│
├── home/                 ← 页面模板组件
│   ├── header.php        ← 页头（DOCTYPE / CSS / 顶部导航）
│   ├── notice-bar.php    ← 通知栏（数据来源提示 / 测试页入口）
│   ├── footer.php        ← 页脚（回到顶部 / 页脚 / JS 注入）
│   └── test.html         ← API 接口测试页
│
├── core/                 ← 核心逻辑（首页专用）
│   ├── .htaccess         ← core 目录访问保护
│   ├── fetcher.php       ← 数据抓取 + 文件缓存读写
│   ├── helper.php        ← 工具函数（时间格式化 / HTML 转义）
│   └── refresh.php       ← AJAX 刷新接口（前端刷新按钮调用）
│
├── api/                  ← API 系统
│   ├── index.php         ← API 路由入口（开关 ENABLE_API_ROUTER）
│   ├── V1/               ← 平台处理器（30+ 个平台）
│   │   ├── baidu.php
│   │   ├── weibo.php
│   │   ├── zhihu.php
│   │   ├── xxxxxxxxxx
│   │   ├── hupu.php
│   │   ├── tieba.php
│   │   ├── sspai.php
│   │   ├── history.php
│   └── utils/
│       ├── config.php    ← API 白名单（平台 id 列表）
│       ├── error.php     ← 错误响应
│       └── response.php  ← 响应封装
│
├── assets/
│   ├── css/
│   │   ├── style.css             ← 全站主样式（日间模式）
│   │   ├── theme-dark.css        ← 夜间模式样式
│   │   ├── responsive.css        ← 响应式布局
│   │   ├── icon-color.css        ← 平台图标颜色（集中管理）
│   │   ├── notice-bar.css        ← 通知栏日间样式
│   │   ├── notice-bar-dark.css   ← 通知栏夜间样式
│   │   └── notice-bar-responsive.css  ← 通知栏响应式
│   ├── js/
│   │   ├── toast.js      ← Toast 轻提示（通用工具）
│   │   ├── script.js     ← 首页交互（展开/折叠/刷新/回到顶部）
│   │   └── header.js     ← 头部按钮（夜间模式切换/刷新）
│   ├── iconfont/         ← 图标字体
│   └── images/           ← 图片资源（favicon 等）
│
└── cache/                ← JSON 缓存文件（带 .htaccess 保护）
```

---

## 快速开始

1. 将整个项目放到 phpStudy（或其他 Apache/Nginx + PHP 环境）的站点根目录。
2. 访问 `http://你的域名/` 即可看到首页（配置好 URL 重写后不需要加 index.php）。
3. 访问 `http://你的域名/home/test.html` 可以逐个测试各平台的 API 返回。
4. 想改配置？直接编辑根目录的 `config.php`，保存后刷新立即生效。

---

## 服务器配置（404 页面 / 安全 / 性能优化）

### 先看这张表

| 环境 | 配置文件 | 怎么配 |
|------|---------|--------|
| **Apache**（phpStudy 或服务器） | `.htaccess` | 放到根目录，**自动生效** |
| **Nginx + phpStudy**（本地） | 见下方完整配置 | 手动改站点配置文件 |
| **Nginx + 宝塔面板**（服务器） | 见下方完整配置 | 宝塔面板里改站点配置 |

> **重要**：`.htaccess` 只对 Apache 生效，Nginx 完全不认。
> Nginx 配置直接看下方的完整配置代码，复制过去改 3 处就行。

---

### 一、Apache 用户（最简单）

什么都不用做，`.htaccess` 放到网站根目录，Apache 自动读取，里面包含：
- 404 页面跳转
- **隐藏 index.php**（访问 `/index.php` 自动 301 跳转到 `/`）
- 静态资源缓存（图片 7 天、CSS/JS 7 天、字体 30 天）
- Gzip 压缩
- 安全规则

---

### 二、Nginx + phpStudy（本地开发）

#### 完整配置（直接复制改 3 处就行）

找到你的站点配置文件，一般在：
```
phpStudy安装目录/Extensions/Nginx/conf/vhosts/你的域名_80.conf
```

把下面内容**整个复制**过去，然后改 3 处标了 `⭐` 的地方：

```nginx
server {
    listen        80;
    # ⭐ 改成你的域名
    server_name  hot.cc;
    # ⭐ 改成你的网站根目录
    root   "C:/phpstudy_pro/WWW/hot.cc";

    # ---- 编码 ----
    charset utf-8;

    # ---- 404 页面 ----
    error_page 404 /404.php;

    # ---- 隐藏 index.php（必须放在 server 块下，不能放在 location / 里）----
    # 原因：访问 /index.php 时会被 location ~ \.php$ 优先匹配，不走 location /
    # 所以重写规则必须放在 server 级别，所有请求都能生效
    # ⚠️ 注意：正则开头必须有 ^，只匹配根目录的 /index.php，不能匹配 /api/index.php
    #     否则会把 /api/index.php/xxx 错误重定向，导致 API 全部失效
    if ($request_uri ~* "^/index\.php(/?)(.*)") {
        return 301 /$2;
    }

    # ---- 默认首页 ----
    location / {
        index index.php index.html;
        autoindex  off;
    }

    # ========================================================
    # 禁止访问敏感目录
    # ========================================================
    location ~* ^/cache/ {
        deny all;
    }

    location ~* ^/core/ {
        # refresh.php 是 AJAX 刷新接口，允许访问
        location ~* /core/refresh\.php$ {
            # ⭐ 改成你的 PHP-FPM 端口（phpStudy 里看）
            fastcgi_pass   127.0.0.1:9004;
            fastcgi_index  index.php;
            fastcgi_param  SCRIPT_FILENAME  $document_root$fastcgi_script_name;
            include        fastcgi_params;
        }
        deny all;
    }

    location ~ /\. {
        deny all;
    }

    # ========================================================
    # 静态资源缓存
    # ========================================================
    location ~* \.(jpg|jpeg|png|gif|webp|svg|ico)$ {
        expires 7d;
        add_header Cache-Control "public, no-transform";
    }

    location ~* \.(css|js)$ {
        expires 7d;
        add_header Cache-Control "public, no-transform";
    }

    location ~* \.(ttf|otf|woff|woff2|font\.css)$ {
        expires 30d;
        add_header Cache-Control "public, no-transform";
    }

    # HTML 不缓存，确保每次都是最新的
    location ~* \.html?$ {
        expires -1;
        add_header Cache-Control "no-store, no-cache, must-revalidate";
    }

    # ========================================================
    # Gzip 压缩
    # ========================================================
    gzip on;
    gzip_min_length 1k;
    gzip_comp_level 6;
    gzip_types
        text/plain
        text/css
        application/json
        application/javascript
        text/javascript
        text/xml
        application/xml
        application/xml+rss;
    gzip_vary on;

    # ========================================================
    # PHP 处理
    # ========================================================
    location ~ \.php(.*)$ {
        # 关键：先检查文件是否存在，不存在走 404
        try_files $uri =404;

        # ⭐ 改成你的 PHP-FPM 端口
        fastcgi_pass   127.0.0.1:9004;
        fastcgi_index  index.php;
        fastcgi_split_path_info  ^((?U).+\.php)(/?.+)$;
        fastcgi_param  SCRIPT_FILENAME  $document_root$fastcgi_script_name;
        fastcgi_param  PATH_INFO  $fastcgi_path_info;
        fastcgi_param  PATH_TRANSLATED  $document_root$fastcgi_path_info;
        include        fastcgi_params;
    }
}
```

#### 需要改的 3 处

| 位置 | 改什么 | 在哪看 |
|------|--------|--------|
| `server_name` | 你的域名 | phpStudy 网站管理里看 |
| `root` | 网站根目录路径 | phpStudy 网站管理里看 |
| `fastcgi_pass` | PHP-FPM 端口 | phpStudy → 软件管理 → PHP → 设置 → 看端口 |

#### 操作步骤

1. 打开站点配置文件（`你的域名_80.conf`）
2. **全选删除**原有内容
3. 把上面的配置粘贴进去
4. 改 3 处 `⭐` 标记的地方
5. 保存
6. phpStudy 里**重启 Nginx**

#### 验证

- 访问 `你的域名/abc.php` → 显示 404 页面 ✅（不是 "No input file specified"）
- 访问 `你的域名/index.php` → 自动跳转到 `你的域名/` ✅（地址栏没有 index.php）
- 访问首页 → 正常显示 ✅
- 访问 `你的域名/api/index.php/baidu?limit=3` → 返回 JSON 数据 ✅（不是 404 页面）
  - 如果返回 404 或 HTML，说明 PATH_INFO 没配置对，检查 PHP location 里有没有 `fastcgi_split_path_info`
  - 如果 API 全部失败但首页正常，检查隐藏 index.php 的正则开头有没有 `^`

> 💡 **快速排查测试页 JSON 解析失败**
> 打开测试页 → 点"发起请求" → 如果显示"原始响应"，看内容是什么：
> - 是 HTML 页面（有 `<html>` 标签） → 被重定向到 404 了，检查上面第 4 条
> - 是 JSON 但 `success: false` → API 本身返回错误，看 `message` 字段

---

### 三、Nginx + 宝塔面板（服务器上线）

#### 方法：在宝塔面板里改

1. 登录宝塔面板
2. 点击左侧 **网站**
3. 找到你的站点 → 点击右侧 **设置**
4. 点击 **配置文件** 标签
5. 用下面的配置**替换**原来的 server 块内容
6. 改 3 处 `⭐` 标记的地方
7. 点击 **保存**

```nginx
server
{
    listen 80;
    # ⭐ 改成你的域名
    server_name hot.cc www.hot.cc;
    # ⭐ 改成你的网站根目录
    root /www/wwwroot/hot.cc;
    index index.php index.html;

    # ---- 编码 ----
    charset utf-8;

    # ---- 404 页面 ----
    error_page 404 /404.php;

    # ---- 隐藏 index.php（必须放在 server 块下，不能放在 location / 里）----
    # 原因：访问 /index.php 时会被 location ~ \.php$ 优先匹配，不走 location /
    # 所以重写规则必须放在 server 级别，所有请求都能生效
    # ⚠️ 注意：正则开头必须有 ^，只匹配根目录的 /index.php，不能匹配 /api/index.php
    #     否则会把 /api/index.php/xxx 错误重定向，导致 API 全部失效
    if ($request_uri ~* "^/index\.php(/?)(.*)") {
        return 301 /$2;
    }

    # ---- 默认首页 ----
    location / {
        index index.php index.html;
    }

    # ========================================================
    # 禁止访问敏感目录
    # ========================================================
    location ~* ^/cache/ {
        deny all;
    }

    location ~* ^/core/ {
        location ~* /core/refresh\.php$ {
            # ⭐ 改成你的 PHP-FPM 地址（宝塔里看，一般是 unix socket）
            fastcgi_pass unix:/tmp/php-cgi-74.sock;
            fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
            include fastcgi_params;
        }
        deny all;
    }

    location ~ /\. {
        deny all;
    }

    # ========================================================
    # 静态资源缓存
    # ========================================================
    location ~* \.(jpg|jpeg|png|gif|webp|svg|ico)$ {
        expires 7d;
        add_header Cache-Control "public, no-transform";
    }

    location ~* \.(css|js)$ {
        expires 7d;
        add_header Cache-Control "public, no-transform";
    }

    location ~* \.(ttf|otf|woff|woff2|font\.css)$ {
        expires 30d;
        add_header Cache-Control "public, no-transform";
    }

    location ~* \.html?$ {
        expires -1;
        add_header Cache-Control "no-store, no-cache, must-revalidate";
    }

    # ========================================================
    # Gzip 压缩
    # ========================================================
    gzip on;
    gzip_min_length 1k;
    gzip_comp_level 6;
    gzip_types
        text/plain
        text/css
        application/json
        application/javascript
        text/javascript
        text/xml
        application/xml
        application/xml+rss;
    gzip_vary on;

    # ========================================================
    # PHP 处理
    # ========================================================
    location ~ \.php(.*)$ {
        # 关键：先检查文件是否存在，不存在走 404
        try_files $uri =404;

        # ⭐ 改成你的 PHP-FPM 地址
        fastcgi_pass unix:/tmp/php-cgi-74.sock;
        fastcgi_index index.php;
        fastcgi_split_path_info ^((?U).+\.php)(/?.+)$;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param PATH_INFO $fastcgi_path_info;
        fastcgi_param PATH_TRANSLATED $document_root$fastcgi_path_info;
        include fastcgi_params;
    }
}
```

#### 需要改的 3 处

| 位置 | 改什么 | 在哪看 |
|------|--------|--------|
| `server_name` | 你的域名 | 宝塔网站管理里看 |
| `root` | 网站根目录路径 | 宝塔网站管理里看 |
| `fastcgi_pass` | PHP-FPM 地址 | 宝塔 → 软件商店 → PHP 设置 → 看 socket 路径 |

---

### 四、OPcache 开启方法（PHP 代码缓存）

OPcache 是 PHP 环境配置，**必须去 php.ini 或面板里开**，PHP 代码控制不了。

#### phpStudy 本地环境

1. 打开 phpStudy → **软件管理**
2. 找到你用的 PHP 版本 → 点击 **设置**
3. 找 **配置文件**（php.ini）
4. 搜索 `opcache`
5. 找到 `;opcache.enable=1` → 去掉前面的分号 `;`
6. 找到 `;opcache.memory_consumption=128` → 去掉前面的分号
7. 保存 → **重启 PHP 服务**

#### 宝塔面板

1. 登录宝塔 → **软件商店**
2. 找到你用的 PHP 版本 → 点击 **设置**
3. 点击 **配置修改** 标签
4. 搜索 `opcache`
5. 把 `opcache.enable` 改成 `1`
6. 保存 → **重启 PHP**

#### 验证有没有开

创建一个文件 `info.php`：
```php
<?php phpinfo();
```

访问 `你的域名/info.php`，搜索 **OPcache**：
- 看到 "Zend OPcache" + "Enabled: On" → 开了 ✅
- 找不到 → 没开

---

### 五、性能功能开关速查

| 功能 | 在哪改 | 开 | 关 |
|------|--------|----|----|
| **静态资源缓存** | Nginx/Apache 配置 | `expires 7d;` | `expires -1;` 或删除 |
| **Gzip 压缩** | Nginx/Apache 配置 | `gzip on;` | `gzip off;` |
| **OPcache** | php.ini | `opcache.enable=1` | `opcache.enable=0` |

---

### 六、开发阶段建议

| 功能 | 开发时 | 上线后 |
|------|--------|--------|
| 静态资源缓存 | 关了（改了立即生效） | **必开** |
| Gzip 压缩 | 无所谓 | **必开** |
| OPcache | 无所谓 | **必开** |

**调试技巧**：
- 改了 CSS/JS 没变化？按 `Ctrl + F5` 强制刷新
- Chrome 按 `F12` → **Network** → 勾选 **Disable cache**（打开 DevTools 时生效）

---

## 上线前必做检查清单 ⭐

上线到生产环境前，务必逐项检查以下内容，避免遗漏导致 SEO 或功能问题。

### 一、域名配置（3 处必须改）

| 文件 | 改什么 | 说明 |
|------|--------|------|
| `config.php` | `seo_domain` | 改成你的实际域名，如 `'https://www.example.com'` |
| `sitemap.xml` | 所有 `<loc>` 标签里的域名 | 把 `https://hot.cc/` 全部替换成你的实际域名 |
| `robots.txt` | 最后一行 `Sitemap:` 后面的 URL | 把 `https://hot.cc/sitemap.xml` 改成你的域名 |

> **为什么要改？**
> - `seo_domain` 用于生成 Canonical 标签和 Open Graph 链接，告诉搜索引擎正确的网址
> - `sitemap.xml` 里的网址必须是真实可访问的，否则搜索引擎不会收录
> - `robots.txt` 里的 Sitemap 地址要指向真实的 sitemap 文件

### 二、SEO 基础信息（建议改）

打开 `config.php`，找到「第 1 部分：站点基本信息」和「第 6 部分：SEO 优化配置」：

| 配置项 | 建议 |
|--------|------|
| `site_name` | 改成你的网站名称 |
| `site_desc` | 改成你的网站描述（一句话说明网站是做什么的） |
| `seo_description` | 120-150 字的详细描述，会显示在搜索引擎结果页 |
| `seo_keywords` | 用逗号分隔的关键词，帮助搜索引擎理解网站内容 |

### 三、性能优化（上线必开）

| 功能 | 怎么开 | 效果 |
|------|--------|------|
| 静态资源缓存 | Nginx/Apache 配置里的 `expires` | 浏览器缓存 CSS/JS/图片，加载速度提升 50%+ |
| Gzip 压缩 | Nginx/Apache 配置里的 `gzip on` | 传输体积减少 60-80% |
| OPcache | php.ini 里 `opcache.enable=1` | PHP 执行速度提升 2-5 倍 |

> 具体配置方法见上方「服务器配置」章节。

### 四、安全检查

- [ ] `cache/` 目录禁止直接访问（Apache 已通过 `.htaccess` 保护，Nginx 配置里也有 `deny all`）
- [ ] `core/` 目录禁止直接访问（同上）
- [ ] 确认无法通过 URL 直接访问 `.htaccess` 等隐藏文件
- [ ] 网站后台（如果有）改默认账号密码

### 五、功能验证

上线后，用浏览器访问以下地址确认正常：

- [ ] 首页 `https://你的域名/` → 正常显示
- [ ] 访问 `https://你的域名/index.php` → 自动跳转到 `/`（地址栏没有 index.php）
- [ ] 访问 `https://你的域名/随便输.php` → 显示 404 页面（不是 "No input file specified"）
- [ ] `https://你的域名/robots.txt` → 能正常打开，内容正确
- [ ] `https://你的域名/sitemap.xml` → 能正常打开，里面的网址都是你的域名

---

## 主要功能一览

| 功能 | 说明 | 相关文件 |
|------|------|---------|
| 多平台热搜聚合 | 30+ 平台，卡片网格展示 | `index.php` + `config.php` |
| 夜间模式 | 一键切换，localStorage 记忆 | `header.js` + `theme-dark.css` |
| 文件缓存 | 带时间戳命名，6 小时有效期 | `core/fetcher.php` |
| 单卡刷新 | 点击卡片底部「刷新」按钮 | `core/refresh.php` |
| 展开全部 | 每卡默认 10 条，可展开查看全部 | `script.js` |
| 通知栏 | 数据来源提示 + 测试页入口 | `home/notice-bar.php` |
| 对外 API | 可选启用，统一 JSON 格式 | `api/index.php` |
| 404 页面 | 美观的错误页面，支持返回首页 | `404.php` |

---

## 缓存机制（两层清理）

### 数据缓存

- 每个平台首次访问时，会抓取数据并写入 JSON 文件：
  `cache/baidu_20260621_194950.json`（平台名 + 写入时间戳）
- 同一平台在 `cache_ttl` 秒内再次访问时直接读取缓存。
- 点击卡片底部「🔄 刷新」按钮会：清除旧缓存 → 强制抓取最新 → 返回新数据。
- `cache/` 目录受 `.htaccess` 保护，无法通过 HTTP 直接访问。

**调整缓存时长**：编辑 `config.php`：

```php
$GLOBALS['cache_ttl'] = 6 * 3600;  // 默认 6 小时（秒为单位）
```

### 缓存文件清理（A + B 双层策略）

为了防止缓存文件越积越多，实现了两层清理机制：

#### 方案 A：写入时顺手清理（主要保障，始终生效）

每次成功写入新缓存后，**自动删除同一平台的所有旧缓存文件**，只留最新的 1 份。

- 位置：`core/fetcher.php` → `writeCache()` 函数
- 效果：每个平台在 cache 目录里永远只有 1 份最新缓存
- 零配置，自动生效

#### 方案 B：概率触发全局清理（兜底保障，默认关闭）

每次访问首页时，有 1% 的概率触发一次全量扫描，把所有超过 24 小时没更新过的缓存文件都删掉。

- 位置：`function.php` 第 33 行（默认已注释）
- 作用：防止某些不再被访问的平台留下永久残留文件
- 启用方式：取消 `function.php` 中 `maybeCleanExpiredCache(1, 86400);` 的注释
- 可调参数：
  - 第一个参数：触发概率（百分比），默认 `1`（1%）
  - 第二个参数：过期时间（秒），默认 `86400`（24 小时）

---

## 添加新平台（完整 4 步）

假设要添加 "掘金"（id: `juejin`）：

### 步骤 1：注册到 API 白名单

打开 `api/utils/config.php`，在 `platforms` 数组里加一个 id：

```php
'platforms' => array('baidu', 'weibo', 'zhihu', ... , 'juejin'),
```

### 步骤 2：创建平台处理器

在 `api/V1/` 目录下创建 `juejin.php`，类名 **必须** 是 `JuejinHotSearch`（下划线分隔的每段首字母大写 + HotSearch）。

> 命名规则示例：`zhihu_daily` → `ZhihuDailyHotSearch`

```php
<?php
class JuejinHotSearch {
    public function handle($limit = 50) {
        // 1. 用 CURL 抓取目标页面 HTML / JSON
        // 2. 解析出热搜条目
        // 3. 返回固定格式：
        return array(
            'success' => true,
            'title'   => '掘金',
            'subtitle'=> '热榜',
            'total'   => count($tempArr),
            'update_time' => date('Y-m-d H:i:s'),
            'data'    => $tempArr  // 每个条目是 [title, url, hot, ...]
        );
    }
}
?>
```

> `$tempArr` 每个元素建议包含：`title`（标题，必填）、`url`（跳转链接）、`hot`（热度值/描述，可选）。

### 步骤 3：在首页配置中注册显示信息

打开 `config.php`，在 `$sources` 数组里加一条：

```php
$sources = [
    // ... 其他已有平台 ...
    [
        'id'          => 'juejin',
        'name'        => '掘金',
        'icon'        => 'iconfont icon-hot',
        'tag'         => '热榜',
        'colorClass'  => 'juejin-icon',
        'url'         => 'https://juejin.cn/hot'
    ]
];
```

> `iconfont` 图标类名参考 `assets/iconfont/` 已有的图标（可用浏览器打开 `assets/iconfont/demo_index.html` 查看所有图标）；
> `colorClass` 控制图标的颜色主题，定义在 `assets/css/icon-color.css` 中。

### 步骤 4：定义平台图标和颜色

打开 `assets/css/icon-color.css`，添加一行颜色定义：

```css
.juejin-icon { color: #1e80ff; }
```

> 类名必须与 config.php 中的 `colorClass` 完全一致，格式为 `{platform}-icon`。

完成后：
- 首页自动出现新平台卡片
- `home/test.html` 会自动多出该平台的测试按钮
- `api/index.php/juejin?limit=10` 可以独立访问

---

## 平台图标管理

所有平台的图标统一使用 `assets/iconfont/` 下的图标字体，方便日后自己配置和管理。

### 图标字体文件

| 文件 | 作用 |
|------|------|
| `assets/iconfont/iconfont.css` | 图标字体样式定义（图标类名映射） |
| `assets/iconfont/iconfont.ttf` | TTF 字体文件 |
| `assets/iconfont/iconfont.woff` | WOFF 字体文件 |
| `assets/iconfont/iconfont.woff2` | WOFF2 字体文件（推荐） |
| `assets/iconfont/demo_index.html` | 图标预览页（用浏览器打开查看所有可用图标） |

### 颜色配置文件

所有平台图标的颜色集中在 **`assets/css/icon-color.css`** 中管理。

**为什么独立成文件？**
- 新增/修改平台颜色，只改这一个文件，不用去 style.css 里翻
- 方便后期批量调整主题
- 新手接手一目了然

**colorClass 什么时候需要？**
- **彩色 iconfont 不需要 colorClass**：如果你从 iconfont.cn 下载的是**彩色图标**（多色图标），图标本身已经带有颜色，不需要再通过 CSS 的 `color` 属性控制，此时 `colorClass` 可以留空或不设置。
- **单色 iconfont 需要 colorClass**：如果你下载的是**单色图标**（默认黑色），需要通过 `colorClass` 对应的 CSS 类来设置 `color` 属性，让图标显示品牌色。
- 本项目保留了 colorClass 机制，方便灵活切换单色/彩色图标。

**使用方式：**
1. 在 `config.php` 里配置 `icon`（图标类名，如 `iconfont icon-juejin`）和 `colorClass`（颜色类名，如 `juejin-icon`）
2. 在 `icon-color.css` 里写对应颜色类的 CSS
3. 首页卡片会自动应用图标和颜色

### 新增/替换图标

如果 iconfont 里没有你想要的图标，可以去 iconfont.cn 添加入库后重新下载，替换 `assets/iconfont/` 目录下的文件即可，代码无需改动。

---

## API 认证方式说明

根据目标网站的 API 特性，处理器分为两类：

### 无需登录（公开 API）

这类平台提供公开的 JSON API，直接使用 curl 请求即可获取数据。**大部分主流平台属于此类**。

**已支持的平台示例：**

| 平台 | API 地址 | 处理器文件 |
|------|---------|-----------|
| 百度热搜 | `https://top.baidu.com/board?tab=realtime` | `api/V1/baidu.php` |
| B 站热搜 | `https://api.bilibili.com/x/web-interface/ranking/v2` | `api/V1/bilibili.php` |
| 微博热搜 | `https://weibo.com/ajax/side/hotSearch` | `api/V1/weibo.php` |
| 知乎热榜 | `https://api.zhihu.com/topstory/hot-lists/total?limit=50` | `api/V1/zhihu.php` |
| 百度贴吧 | `https://tieba.baidu.com/hottopic/browse/topicList` | `api/V1/tieba.php` |
| 知乎日报 | `https://daily.zhihu.com/api/4/news/latest` | `api/V1/zhihu_daily.php` |
| 游研社 | `https://www.yystv.cn/home/get_home_docs_by_page` | `api/V1/yystv.php` |
| 今日头条 | `https://www.toutiao.com/hot-event/hot-board/?origin=toutiao_pc` | `api/V1/toutiao.php` |
| 澎湃新闻 | `https://cache.thepaper.cn/contentapi/wwwIndex/rightSidebar` | `api/V1/thepaper.php` |
| 微信读书 | 页面解析 | `api/V1/weread.php` |
| GitHub | `https://api.github.com/search/repositories` | `api/V1/github.php` |
| 掘金 | `https://api.juejin.cn/content_api/v1/content/article_rank` | `api/V1/juejin.php` |
| 少数派 | RSS | `api/V1/sspai.php` |
| 果壳 / 虎嗅 / 爱范儿 / HelloGitHub | 页面或 RSS 解析 | 对应 `api/V1/*.php` |
| 52 破解 | 页面解析 | `api/V1/pojie52.php` |
| 历史上的今天 | 第三方 API | `api/V1/history.php` |
| V2EX | `https://www.v2ex.com/api/topics/hot.json` | `api/V1/v2ex.php` |
| 纽约时报中文 | 页面解析 | `api/V1/nytimes.php` |

> 注意：V2EX、纽约时报中文等境外网站，在国内服务器可能无法直连，需要部署到境外服务器或使用代理。

### 需要登录（Cookie 认证）

这类平台需要用户登录后才能访问数据，处理器需要在 curl 请求中携带用户 Cookie。

**已支持的平台示例：**

| 平台 | 数据来源 | 处理器文件 | 认证方式 |
|------|---------|-----------|---------|
| JavBus | `https://www.javbus.com/forum/` | `api/V1/javbus.php` | Cookie 认证 |

**实现方式（参考 `javbus.php`）：**

```php
<?php
// ⚠️ 必须在此处填写你的 Cookie（从浏览器开发者工具获取）
define('COOKIE', '你的Cookie字符串');

class JavbusHotSearch {
    public function handle($limit = 20) {
        $html = $this->curl('https://www.javbus.com/forum/', COOKIE);
        // 使用正则解析HTML...
    }

    private function curl($url, $cookie) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'User-Agent: Mozilla/5.0 ...',
            'Cookie: ' . $cookie  // ← 关键：携带Cookie
        ));
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
}
?>
```

**获取 Cookie 的步骤：**

1. 在浏览器中登录目标网站
2. 按 `F12` 打开开发者工具 → Network 标签
3. 刷新页面，找到任意请求
4. 在 Request Headers 中找到 `Cookie:` 字段
5. 复制完整的 Cookie 字符串（包括所有键值对）
6. 粘贴到处理器文件顶部的 `define('COOKIE', '...')` 中

**注意事项：**

- Cookie 具有有效期，过期后需要重新获取并更新
- 不同平台的 Cookie 格式可能不同，请确保复制完整
- 建议定期检查 Cookie 是否失效（通过 `home/test.html` 测试）
- 某些平台可能还需要额外的 Header（如 `X-Token`）

---

## API 访问方式

项目有两层 API：

### 1. 首页专属（已默认启用，不依赖 Apache 路由）

首页 `index.php` 不会走 HTTP 请求，而是直接通过 `core/fetcher.php` 的
`fetchFromApi($id, $limit)` 调用各平台处理器。零配置即可工作。

### 2. 对外 HTTP API（可选启用 / 关闭）

访问路径：

```
GET /api/index.php/baidu?limit=10
GET /api/index.php/weibo?limit=10
...
```

**开关**：打开 `api/index.php`，修改顶部即可：

```php
define('ENABLE_API_ROUTER', true);   // false 则返回 404
```

返回格式（所有平台统一）：

```json
{
    "success": true,
    "title": "百度热搜",
    "subtitle": "热搜",
    "total": 50,
    "update_time": "2026-06-21 19:49:50",
    "data": [
        { "index": 1, "title": "热搜标题1", "hot": "4,992,109", "url": "..." },
        { "index": 2, "title": "热搜标题2", "hot": "3,184,511", "url": "..." },
        ...
    ]
}
```

---

## 配置文件说明

### config.php（根目录）⭐ 新手必读

首页 **唯一配置文件**。所有配置都在这一个文件里，修改后立即生效，**不需要改其他任何文件**。

文件分为 6 个部分，按顺序排列：

| 部分 | 内容 | 改什么用的 |
|------|------|------------|
| 第 1 部分 | 站点基本信息 | 网站名称、描述、Logo、favicon |
| 第 2 部分 | 显示配置 | 卡片高度、每行几个、默认显示条数、主题模式 |
| 第 3 部分 | 功能开关 | 各种按钮和组件的显示/隐藏 |
| 第 4 部分 | 缓存配置 | 缓存时间、缓存目录 |
| 第 5 部分 | 平台列表 | 增减平台、调整顺序、改名称图标 |
| 第 6 部分 | SEO 优化 | 网站描述、关键词、域名等 |
| 第 7 部分 | API 基础路径 | 一般不用改 |

---

#### 第 1 部分：站点基本信息

| 配置项 | 作用 | 默认值 |
|--------|------|--------|
| `site_name` | 网站名称（左上角 + 浏览器标签页） | `'今日热榜'` |
| `site_desc` | 网站描述（标题下面的小字） | `'多平台实时热点聚合'` |
| `site_logo` | Logo 图片路径 | `'/assets/images/favicon.png'` |
| `site_favicon` | 浏览器标签页小图标 | `'/assets/images/favicon.png'` |

#### 第 2 部分：显示配置（布局和样式）

| 配置项 | 作用 | 默认值 | 可选值 / 说明 |
|--------|------|--------|---------------|
| `columns_per_row` | 每行显示几个卡片 | `'auto'` | `'auto'` = 自适应；`4`/`5`/`6` 等数字 = 固定数量 |
| `card_min_width` | 卡片最小宽度（px，仅自适应模式生效） | `280` | columns_per_row = 'auto' 时才有用 |
| `card_height` | 卡片高度（px，桌面端） | `400` | 内容超出后内部滚动 |
| `content_max_width` | 内容区最大宽度（px） | `1400` | 页面主体区域的最大宽度 |
| `display_limit` | 默认显示条数（折叠状态） | `10` | 每张卡片默认显示几条，超出可展开 |
| `api_limit` | API 拉取最大条数 | `100` | 刷新时从每个平台拉多少条 |
| `default_theme` | 默认主题模式 | `'light'` | `'light'` 日间 / `'dark'` 夜间 / `'auto'` 跟随系统 |

#### 第 3 部分：功能开关

`true` = 显示，`false` = 隐藏

| 配置项 | 作用 | 默认值 |
|--------|------|--------|
| `show_header_refresh` | 顶部导航栏：是否显示"刷新全部"按钮 | `true` |
| `show_header_setting` | 顶部导航栏：是否显示"设置"按钮 | `true` |
| `show_notice_bar` | 是否显示顶部通知栏 | `true` |
| `show_back_to_top` | 是否显示回到顶部按钮 | `true` |
| `show_refresh_btn` | 是否显示卡片右下角刷新按钮 | `true` |
| `show_heat` | 是否显示热度值（小火苗+数字） | `true` |
| `show_source_link` | 是否显示数据来源链接（卡片右上角） | `true` |
| `show_footer` | 是否显示页脚 | `true` |

#### 第 4 部分：缓存配置

| 配置项 | 作用 | 默认值 |
|--------|------|--------|
| `cache_dir` | 缓存目录路径 | `__DIR__ . '/cache'` |
| `cache_ttl` | 缓存有效期（秒） | `21600`（6 小时） |

常用缓存时间参考：
- 1 小时 = `3600`
- 3 小时 = `10800`
- 6 小时 = `21600`
- 12 小时 = `43200`
- 24 小时 = `86400`

#### 第 5 部分：平台列表（$sources）

每个平台的字段说明：

| 字段 | 说明 |
|------|------|
| `id` | 平台唯一标识，对应 `api/V1/` 下的文件名 |
| `name` | 显示在卡片上的名称 |
| `icon` | iconfont 图标类名 |
| `tag` | 标签文字（热搜/热榜/热帖 等） |
| `colorClass` | CSS 颜色类名，定义在 `assets/css/icon-color.css` |
| `url` | 数据来源链接 |

**常用操作：**
- **隐藏某个平台**：在它前面加 `//` 注释掉
- **调整顺序**：直接把数组项上下移动
- **新增平台**：参考「添加新平台」章节

#### 第 6 部分：SEO 优化配置

| 配置项 | 作用 | 默认值 |
|--------|------|--------|
| `seo_description` | 网站描述（搜索引擎显示在标题下面） | `'聚合百度、微博、知乎...'` |
| `seo_keywords` | 网站关键词（用逗号分隔） | `'热搜榜,微博热搜,...'` |
| `seo_domain` | 网站域名（末尾不要加斜杠） | `'https://hot.cc'` |
| `seo_type` | 网站类型 | `'website'`（可选 blog/news） |

> SEO 优化说明：
> - `seo_description`：建议 120-150 字，会显示在搜索引擎结果中
> - `seo_keywords`：虽然 Google 不再用这个，但百度和其他平台可能还会参考
> - `seo_domain`：用于生成 Canonical URL 和 Sitemap

#### 第 7 部分：API 基础路径

| 变量 | 作用 | 默认值 |
|------|------|--------|
| `$apiBaseUrl` | API 路径前缀（用于 AJAX 调用） | `/api/index.php/` |

> 一般不用改。

---

### api/utils/config.php（API 层）

只负责 **平台白名单**：哪些 id 能通过 `/api/index.php/xxx` 访问。

```php
return array(
    'platforms' => array('baidu', 'bilibili', 'weibo', 'zhihu', ...),
    ...
);
```

> 新增平台时，**两个 config 文件都必须注册**。

---

### 新手配置修改步骤

1. 打开 `config.php`
2. 找到对应部分（第 1~6 部分）
3. 修改等号后面的值
4. 保存文件
5. 刷新页面，立即生效

⚠️ **注意事项：**
- 字符串值要加引号，比如 `'今日热榜'`
- 数字值不加引号，比如 `400`
- 布尔值是 `true` 或 `false`，不加引号
- 数组最后一项后面可以有逗号，也可以没有，都不会报错

---

## 首页结构（PHP 文件）

| 文件 | 作用 | 输出 |
|------|------|------|
| `index.php` | 数据拉取 + 卡片网格渲染 | HTML 主体 |
| `home/header.php` | DOCTYPE / CSS / 顶部导航 / toast.js | 页面头部 HTML |
| `home/notice-bar.php` | 通知栏（数据来源提示 / 测试页入口） | 通知栏 HTML |
| `home/footer.php` | 回到顶部按钮 / 页脚 / JS 注入 | 页面底部 HTML |
| `config.php` | 全局配置（被 function.php 引用） | 无输出 |
| `function.php` | 统一 require（config + helper + fetcher） | 无输出 |
| `core/fetcher.php` | 数据抓取 + 缓存读写 | 无输出 |
| `core/helper.php` | 工具函数 | 无输出 |
| `core/refresh.php` | AJAX 刷新接口 | JSON |

调用链：

```
index.php
  └── function.php  (require config.php + helper.php + fetcher.php)
        ├── home/header.php  (HTML 开头 + toast.js)
        ├── home/notice-bar.php  (通知栏)
        └── home/footer.php  (HTML 结尾 + 脚本加载)

core/refresh.php (AJAX)
  └── function.php
```

---

## 前端资源说明

### CSS 组织

| 文件 | 作用 |
|------|------|
| `style.css` | 全站主样式（日间模式） |
| `theme-dark.css` | 全站夜间模式 |
| `responsive.css` | 全站响应式布局 |
| `notice-bar.css` | 通知栏日间样式（组件独立） |
| `notice-bar-dark.css` | 通知栏夜间样式 |
| `notice-bar-responsive.css` | 通知栏响应式 |
| `icon-color.css` | 平台图标颜色（集中管理，新增/修改平台颜色只改这里） |

### JS 组织

| 文件 | 作用 | 依赖 |
|------|------|------|
| `toast.js` | Toast 轻提示工具函数 | 无（最先加载） |
| `script.js` | 首页卡片交互（展开/折叠/刷新） | `toast.js` |
| `header.js` | 顶部按钮（夜间模式切换/刷新） | `toast.js` |

### 自定义 Tooltip

全站使用纯 CSS 实现的自定义 tooltip，替换浏览器原生的 `title` 属性提示框。

**用法：** 给任何 HTML 元素加 `data-tooltip="提示文字"` 属性即可。

```html
<div data-tooltip="这是提示文字">鼠标移上来看看</div>
```

**样式特点：**
- 深蓝黑背景（`#1a1a2e`）+ 白色文字，夜间模式自动适配更深色
- 圆角 8px，带柔和阴影
- 0.18s 淡入动画
- 最大宽度 320px，文字过长自动换行

**特殊规则：**
- 热搜列表前 3 条（`.hot-item:nth-child(-n+3)`）的 tooltip **向下弹出**，避免被顶部导航栏遮挡
- 其余条目默认**向上弹出**

**为什么挂在 `.hot-item` 而不是 `.hot-title` 上？**
因为 `.hot-title` 有 `overflow: hidden`（用于文字截断省略号），CSS 伪元素会被裁切导致 tooltip 看不见。`.hot-item` 没有 overflow 限制，伪元素可以正常显示。

**修改位置：** `assets/css/style.css` 第 14 节「自定义 Tooltip」，夜间模式在 `assets/css/theme-dark.css` 的「夜间模式 - Tooltip」节。

---

## 版本切换（v1 → v2）

如果将来要做 v2 版本，**不需要改动 v1 的文件**，按以下方式：

1. **新建目录**：`api/V2/`，放入新的处理器（`baidu.php`、`weibo.php` ...）。
2. **新建 API 入口**：复制 `api/index.php` 为 `api/index_v2.php`，修改内部路径指向 `api/V2/` 和新的白名单配置。
3. **新建首页配置模块**：复制 `config.php` 为 `config_v2.php`，在 `function.php` 顶部切换加载。
4. **访问方式**：`/api/index_v2.php/baidu?limit=10`

> 不建议在 `api/V1/` 内改动现有处理器，除非是修复错误。保持目录干净是后续维护的基础。

---

## 常见问题

**Q: 某平台数据获取失败，显示错误？**
A: 先访问 `home/test.html` 单独测试该平台，看具体错误信息。如果是
"处理器不存在" → 检查 `api/utils/config.php` 的 `platforms` 数组；
如果是"正则解析失败" → 目标网站页面结构可能变更，需要更新
对应 `api/V1/xxx.php` 内的解析逻辑。

**Q: 缓存文件越来越多？**
A: 方案 A（写入时清理）已经确保每个平台只留最新 1 份。如果还有堆积，
可以启用方案 B（概率全局清理），取消 `function.php` 中
`maybeCleanExpiredCache(1, 86400);` 的注释即可。或者手动清理：
直接删除 `cache/` 目录下所有 `.json` 文件。

**Q: 首页加载很慢？**
A: 每个平台首次访问会触发真实 HTTP 请求，约 1-3 秒。有缓存后
首页在几百毫秒内完成。可以通过调整 `cache_ttl` 控制刷新频率。

**Q: 怎么切换到 Nginx？**
A: 参考 `nginx.htaccess` 里的伪静态规则，复制到站点管理后台。
注意：无论 Apache 还是 Nginx，**首页 `index.php` 永远不需要**
路由规则，它本来就是默认文档。

**Q: 夜间模式切换时页面元素大小在变化？**
A: 日间和夜间模式的滚动条、字体大小等已经统一为相同尺寸。
如果发现差异，检查 `style.css` 和 `theme-dark.css` 中对应元素的样式是否一致。
