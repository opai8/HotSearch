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
    <!-- 浏览器标签页标题（从配置读取） -->
    <title><?php echo escapeHtml($cfg['site_name']); ?> - <?php echo escapeHtml($cfg['site_desc']); ?></title>
    <!-- SEO：网站描述（搜索引擎显示在标题下面，建议 120-150 字） -->
    <meta name="description" content="<?php echo escapeHtml($cfg['seo_description']); ?>">
    <!-- SEO：网站关键词（用逗号分隔） -->
    <meta name="keywords" content="<?php echo escapeHtml($cfg['seo_keywords']); ?>">
    <!-- SEO：作者 -->
    <meta name="author" content="<?php echo escapeHtml($cfg['site_name']); ?>">
    <!-- SEO：生成日期 -->
    <meta name="robots" content="index, follow">
    <!-- SEO：Canonical 标签（规范化链接，防止重复内容） -->
    <link rel="canonical" href="<?php echo $cfg['seo_domain']; ?>/">
    <!-- favicon（从配置读取） -->
    <link rel="icon" type="image/png" href="<?php echo escapeHtml($cfg['site_favicon']); ?>">

    <!-- ============================================================
         Open Graph 社交分享标签
         用于微信/QQ/微博/Twitter 等分享时显示预览卡片
         ============================================================ -->
    <meta property="og:type" content="<?php echo $cfg['seo_type']; ?>">
    <meta property="og:title" content="<?php echo escapeHtml($cfg['site_name']); ?> - <?php echo escapeHtml($cfg['site_desc']); ?>">
    <meta property="og:description" content="<?php echo escapeHtml($cfg['seo_description']); ?>">
    <meta property="og:url" content="<?php echo $cfg['seo_domain']; ?>/">
    <meta property="og:site_name" content="<?php echo escapeHtml($cfg['site_name']); ?>">
    <meta property="og:image" content="<?php echo $cfg['seo_domain']; ?><?php echo escapeHtml($cfg['seo_og_image']); ?>">
    <meta property="og:locale" content="zh_CN">

    <!-- ============================================================
         Twitter Card 标签
         用于 Twitter 分享时显示预览卡片
         ============================================================ -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo escapeHtml($cfg['site_name']); ?> - <?php echo escapeHtml($cfg['site_desc']); ?>">
    <meta name="twitter:description" content="<?php echo escapeHtml($cfg['seo_description']); ?>">
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
            <a href="/index.php" class="header-brand-link">
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
            <button class="header-icon-btn" title="设置" onclick="location.href='/home/test.html'">
                <span class="iconfont icon-shezhi"></span>
            </button>
            <?php endif; ?>
        </div>
    </header>
