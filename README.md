# 热搜榜 - 多平台实时热点聚合

一个轻量级的热搜聚合站，抓取百度、微博、知乎、虎扑、抖音、贴吧、头条、澎湃、微信读书、V2EX、GitHub、掘金、少数派、果壳、虎嗅、爱范儿、HelloGitHub、52破解、纽约时报中文、游研社、B站、历史上的今天、知乎日报、JavBus 等平台的热榜数据，在一个页面上以卡片网格展示。

---

## 目录结构

```
项目根目录/
├── index.php             ← 首页入口（渲染卡片网格）
├── config.php            ← 全局配置（平台列表 / 缓存 TTL / API 路径）
├── function.php          ← 核心功能统一入口（集中 require）
├── .htaccess             ← Apache 安全规则（可选）
├── nginx.htaccess        ← Nginx 伪静态参考（可选）
│
├── home/                 ← 页面模板组件
│   ├── header.php        ← 页头（DOCTYPE / CSS / 顶部导航）
│   ├── notice-bar.php    ← 通知栏（数据来源提示 / 测试页入口）
│   ├── footer.php        ← 页脚（回到顶部 / 页脚 / JS 注入）
│   ├── setting.php       ← 设置页面（显示/主题/缓存设置）
│   └── test.html         ← API 接口测试页
│
├── core/                 ← 核心逻辑（首页专用）
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
│   │   ├── zhihu_daily.php
│   │   ├── douyin.php
│   │   ├── toutiao.php
│   │   ├── thepaper.php
│   │   ├── weread.php
│   │   ├── v2ex.php
│   │   ├── nytimes.php
│   │   ├── bilibili.php
│   │   ├── hupu.php
│   │   ├── tieba.php
│   │   ├── yystv.php
│   │   ├── ifanr.php
│   │   ├── github.php
│   │   ├── hellogithub.php
│   │   ├── guokr.php
│   │   ├── huxiu.php
│   │   ├── juejin.php
│   │   ├── pojie52.php
│   │   ├── sspai.php
│   │   ├── history.php
│   │   └── javbus.php
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
│   │   ├── notice-bar-responsive.css  ← 通知栏响应式
│   │   ├── set.css               ← 设置页日间样式
│   │   ├── set-dark.css          ← 设置页夜间样式
│   │   └── set-responsive.css    ← 设置页响应式
│   ├── js/
│   │   ├── toast.js      ← Toast 轻提示（通用工具）
│   │   ├── script.js     ← 首页交互（展开/折叠/刷新/回到顶部）
│   │   ├── header.js     ← 头部按钮（夜间模式切换/刷新）
│   │   └── set.js        ← 设置页交互
│   ├── iconfont/         ← 图标字体
│   └── images/           ← 图片资源（favicon 等）
│
└── cache/                ← JSON 缓存文件（带 .htaccess 保护）
```

---

## 快速开始

1. 将整个项目放到 phpStudy（或其他 Apache/Nginx + PHP 环境）的站点根目录。
2. 访问 `http://你的域名/index.php` 即可看到首页。
3. 访问 `http://你的域名/home/test.html` 可以逐个测试各平台的 API 返回。
4. 点击首页右上角「⚙️ 设置」可进入设置页面。

---

## 主要功能一览

| 功能 | 说明 | 相关文件 |
|------|------|---------|
| 多平台热搜聚合 | 30+ 平台，卡片网格展示 | `index.php` + `config.php` |
| 夜间模式 | 一键切换，localStorage 记忆 | `header.js` + `theme-dark.css` |
| 设置页面 | 显示条数 / 主题模式 / 缓存时间 | `home/setting.php` + `set.js` |
| 文件缓存 | 带时间戳命名，6 小时有效期 | `core/fetcher.php` |
| 单卡刷新 | 点击卡片底部「刷新」按钮 | `core/refresh.php` |
| 展开全部 | 每卡默认 10 条，可展开查看全部 | `script.js` |
| 通知栏 | 数据来源提示 + 测试页入口 | `home/notice-bar.php` |
| 对外 API | 可选启用，统一 JSON 格式 | `api/index.php` |

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

### config.php（根目录）

首页 **唯一配置文件**。修改后立即生效。

| 变量 | 作用 | 默认 |
|------|------|------|
| `$GLOBALS['cache_dir']` | 缓存目录路径 | `__DIR__ . '/cache'` |
| `$GLOBALS['cache_ttl']` | 缓存有效期（秒） | `21600`（6 小时） |
| `$sources` | 平台显示配置数组（id/name/icon/tag/colorClass/url） | 30+ 平台 |
| `$apiLimit` | 每个平台默认抓取条数 | `100` |
| `$displayLimit` | 卡片默认显示条数（超出可"展开全部"） | `10` |
| `$apiBaseUrl` | API 路径前缀（用于 AJAX 调用） | `/api/index.php/` |

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

## 首页结构（PHP 文件）

| 文件 | 作用 | 输出 |
|------|------|------|
| `index.php` | 数据拉取 + 卡片网格渲染 | HTML 主体 |
| `home/header.php` | DOCTYPE / CSS / 顶部导航 / toast.js | 页面头部 HTML |
| `home/notice-bar.php` | 通知栏（数据来源提示 / 测试页入口） | 通知栏 HTML |
| `home/footer.php` | 回到顶部按钮 / 页脚 / JS 注入 | 页面底部 HTML |
| `home/setting.php` | 设置页面 | 完整设置页 HTML |
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

home/setting.php
  └── function.php
        ├── home/header.php
        ├── 专属 CSS (set.css / set-dark.css / set-responsive.css)
        ├── set.js
        └── home/footer.php

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
| `set.css` | 设置页日间样式（页面独立） |
| `set-dark.css` | 设置页夜间样式 |
| `set-responsive.css` | 设置页响应式 |
| `icon-color.css` | 平台图标颜色（集中管理，新增/修改平台颜色只改这里） |

### JS 组织

| 文件 | 作用 | 依赖 |
|------|------|------|
| `toast.js` | Toast 轻提示工具函数 | 无（最先加载） |
| `script.js` | 首页卡片交互（展开/折叠/刷新） | `toast.js` |
| `header.js` | 顶部按钮（夜间模式切换/刷新） | `toast.js` |
| `set.js` | 设置页交互 | `toast.js` |

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

**Q: 设置页面点不进去？**
A: 确认 `home/setting.php` 文件存在。设置按钮链接为相对路径
`home/setting.php`（从首页点击）。如果从其他目录的页面跳转，
需要调整路径。

**Q: 夜间模式切换时页面元素大小在变化？**
A: 日间和夜间模式的滚动条、字体大小等已经统一为相同尺寸。
如果发现差异，检查 `style.css` 和 `theme-dark.css` 中对应元素的样式是否一致。
