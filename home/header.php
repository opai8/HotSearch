<?php
/**
 * 页面头部模板
 * ============================================================
 * 包含：DOCTYPE、CSS 引用、顶部导航栏
 * 由 index.php、setting.php 等页面引入
 *
 * 新手提示：
 *   - 网站名称、描述、Logo 都从 config.php 的 $site_config 读取
 *   - 想改这些？直接改 config.php 就行，不用改这里
 * ============================================================
 */

// 确保配置变量存在（防止直接访问此文件报错）
if (!isset($GLOBALS['site_config'])) {
    $GLOBALS['site_config'] = [
        'site_name' => '今日热榜',
        'site_desc' => '多平台实时热点聚合',
        'site_logo' => '/assets/images/favicon.png',
        'site_favicon' => '/assets/images/favicon.png',
        'default_theme' => 'light',
    ];
}
$cfg = $GLOBALS['site_config'];

// 准备注入到 JS 的卡片数据（用于 "展开全部" 的客户端切换）
$cardDataList = [];
if (isset($cardsData) && is_array($cardsData)) {
    foreach ($cardsData as $card) {
        if (isset($card['source']['id']) && isset($card['data'])) {
            $cardDataList[$card['source']['id']] = array_values($card['data']);
        }
    }
}
$cardDataJson = json_encode($cardDataList, JSON_UNESCAPED_UNICODE);

// 当前日期时间
$nowDate = date('Y-m-d H:i:s');
$weekDay = ['星期日', '星期一', '星期二', '星期三', '星期四', '星期五', '星期六'][date('w')];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
    // 允许页面（如 platform.php、status.php）提前设置 SEO 变量覆盖默认值
    $seoTitle     = isset($pageTitle)     ? $pageTitle     : ($cfg['site_name'] . ' - ' . $cfg['site_desc']);
    $seoDesc      = isset($pageDesc)      ? $pageDesc      : $cfg['seo_description'];
    $seoKeywords  = isset($pageKeywords)  ? $pageKeywords  : $cfg['seo_keywords'];
    $seoRobots    = isset($pageRobots)    ? $pageRobots    : 'index, follow';
    $seoCanonical = isset($canonical)     ? $canonical     : ($cfg['seo_domain'] . '/');
    $seoOgType    = isset($pageOgType)    ? $pageOgType    : $cfg['seo_type'];
    $seoOgUrl     = isset($canonical)     ? $canonical     : ($cfg['seo_domain'] . '/');
    ?>
    <!-- 浏览器标签页标题 -->
    <title><?php echo escapeHtml($seoTitle); ?></title>
    <!-- SEO：网站描述 -->
    <meta name="description" content="<?php echo escapeHtml($seoDesc); ?>">
    <!-- SEO：网站关键词 -->
    <meta name="keywords" content="<?php echo escapeHtml($seoKeywords); ?>">
    <!-- SEO：作者 -->
    <meta name="author" content="<?php echo escapeHtml($cfg['site_name']); ?>">
    <!-- SEO：robots（运维页面如 status.php 会覆盖为 noindex, nofollow） -->
    <meta name="robots" content="<?php echo $seoRobots; ?>">
    <!-- SEO：Canonical 标签 -->
    <link rel="canonical" href="<?php echo $seoCanonical; ?>">
    <!-- favicon -->
    <link rel="icon" type="image/png" href="<?php echo escapeHtml($cfg['site_favicon']); ?>">

    <!-- DNS 预解析：提前解析热门平台域名，减少点击跳转时的等待 -->
    <link rel="dns-prefetch" href="//top.baidu.com">
    <link rel="dns-prefetch" href="//s.weibo.com">
    <link rel="dns-prefetch" href="//www.zhihu.com">
    <link rel="dns-prefetch" href="//www.bilibili.com">
    <link rel="dns-prefetch" href="//www.douyin.com">
    <link rel="dns-prefetch" href="//bbs.hupu.com">
    <link rel="dns-prefetch" href="//tieba.baidu.com">
    <link rel="dns-prefetch" href="//36kr.com">
    <link rel="dns-prefetch" href="//www.acfun.cn">
    <link rel="dns-prefetch" href="//www.miyoushe.com">
    <link rel="dns-prefetch" href="//lol.qq.com">

    <!-- ============================================================
         Open Graph 社交分享标签
         ============================================================ -->
    <meta property="og:type" content="<?php echo $seoOgType; ?>">
    <meta property="og:title" content="<?php echo escapeHtml($seoTitle); ?>">
    <meta property="og:description" content="<?php echo escapeHtml($seoDesc); ?>">
    <meta property="og:url" content="<?php echo $seoOgUrl; ?>">
    <meta property="og:site_name" content="<?php echo escapeHtml($cfg['site_name']); ?>">
    <meta property="og:image" content="<?php echo $cfg['seo_domain']; ?><?php echo escapeHtml($cfg['seo_og_image']); ?>">
    <meta property="og:locale" content="zh_CN">

    <!-- ============================================================
         Twitter Card 标签
         ============================================================ -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo escapeHtml($seoTitle); ?>">
    <meta name="twitter:description" content="<?php echo escapeHtml($seoDesc); ?>">
    <meta name="twitter:image" content="<?php echo $cfg['seo_domain']; ?><?php echo escapeHtml($cfg['seo_og_image']); ?>">

    <!-- ============================================================
         JSON-LD 结构化数据
         给搜索引擎喂结构化数据，可能获得"富媒体搜索结果"
         ============================================================
         1. WebSite：告诉搜索引擎这是一个网站
         2. BreadcrumbList：面包屑导航（不显示，仅给搜索引擎看）
         新手提示：
         - 为什么用 JSON-LD？因为 Google 推荐这种方式，比 microdata 更清晰
         - 放在 <head> 或 <body> 里都行，放 head 里比较集中
         ============================================================ -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebSite",
        "name": "<?php echo escapeHtml($cfg['site_name']); ?>",
        "url": "<?php echo $cfg['seo_domain']; ?>/",
        "description": "<?php echo escapeHtml($cfg['seo_description']); ?>",
        "inLanguage": "zh-CN",
        "publisher": {
            "@type": "Organization",
            "name": "<?php echo escapeHtml($cfg['site_name']); ?>",
            "logo": {
                "@type": "ImageObject",
                "url": "<?php echo $cfg['seo_domain']; ?><?php echo escapeHtml($cfg['site_logo']); ?>"
            }
        }
    }
    </script>
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "BreadcrumbList",
        "itemListElement": [
            {
                "@type": "ListItem",
                "position": 1,
                "name": "首页",
                "item": "<?php echo $cfg['seo_domain']; ?>/"
            },
            {
                "@type": "ListItem",
                "position": 2,
                "name": "<?php echo escapeHtml($cfg['site_name']); ?>",
                "item": "<?php echo $cfg['seo_domain']; ?>/"
            }
        ]
    }
    </script>

    <!-- 图标字体 -->
    <link rel="stylesheet" href="/assets/iconfont/iconfont.css">
    <!-- 主样式 -->
    <link rel="stylesheet" href="/assets/css/style.css">
    <!-- 夜间模式样式 -->
    <link rel="stylesheet" href="/assets/css/theme-dark.css">
    <!-- 响应式布局 -->
    <link rel="stylesheet" href="/assets/css/responsive.css">
    <!-- 平台图标颜色 -->
    <link rel="stylesheet" href="/assets/css/icon-color.css">
    <!-- Toast 工具（最先加载，其他 JS 可能用到） -->
    <script src="/assets/js/toast.js"></script>
    <script>
        // 新手提示：默认主题从配置读取，在 CSS 加载前就设置，避免页面闪烁
        var defaultTheme = '<?php echo $cfg['default_theme']; ?>';
        var savedTheme = localStorage.getItem('theme');
        var theme = savedTheme || defaultTheme;
        if (theme === 'auto') {
            theme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        }
        document.documentElement.setAttribute('data-theme', theme);
        if (theme === 'dark') {
            document.body.setAttribute('data-theme', 'dark');
        }

        // 热搜链接打开方式（从 config 读取）
        var LINK_TARGET = '<?php echo getLinkTarget(); ?>';
    </script>
</head>
<body>

    <!-- ============================================================
         顶部导航栏
         ============================================================
         结构：左侧 Logo+标题 | 中间 时间日期 | 右侧 功能按钮
         ============================================================ -->
    <header class="header">
        <div class="header-left">
            <!-- 整个 Logo+标题 是一个链接，点击跳首页（从配置读取路径） -->
            <a href="/" class="header-brand-link">
                <!-- Logo 图片（从配置读取） -->
                <img src="<?php echo escapeHtml($cfg['site_logo']); ?>" alt="<?php echo escapeHtml($cfg['site_name']); ?> Logo" class="header-logo">
                <div class="header-brand">
                    <!-- 网站名称（从配置读取） -->
                    <h1 class="site-title"><?php echo escapeHtml($cfg['site_name']); ?></h1>
                    <!-- 网站描述（从配置读取） -->
                    <p class="site-desc"><?php echo escapeHtml($cfg['site_desc']); ?></p>
                </div>
            </a>
        </div>
        <div class="header-center">
            <div class="header-time"><?php echo $nowDate; ?></div>
            <div class="header-date"><?php echo $weekDay; ?></div>
        </div>
        <div class="header-right">
            <?php if ($cfg['show_header_refresh']): ?>
            <button class="header-icon-btn" id="headerRefreshBtn" title="刷新全部" onclick="location.reload()">
                <span class="iconfont icon-yuanxunhuan"></span>
            </button>
            <?php endif; ?>
            <button class="header-icon-btn" title="夜间模式" id="themeToggle">
                <span class="iconfont icon-yejian"></span>
            </button>
            <?php if ($cfg['show_header_setting']): ?>
            <button class="header-icon-btn" title="清理缓存" onclick="clearOpcache()">
                <span class="iconfont icon-shezhi"></span>
            </button>
            <?php endif; ?>
        </div>
    </header>
