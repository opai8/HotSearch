<?php
/**
 * ============================================================
 * 热搜榜 - 单平台详情页入口
 * ============================================================
 *
 * URL 格式（三种方式，全兼容）：
 *   1. 零路由（所有环境自动可用）：/platform.php/baidu
 *                                   /platform.php/weibo/2026-11-10
 *   2. Apache 自动（.htaccess rewrite）：/baidu
 *                                        /weibo/2026-11-10
 *   3. 通用 GET 参数：/platform.php?source=baidu&date=2026-11-10
 *
 * 两种页面模式：
 *   - 今天页（无日期参数）：读今天缓存 → 过期刷新 → 写回今天目录
 *   - 历史页（有日期参数）：只读 cache/YYYYMMDD/{source}.json，不存在则 404
 *     历史页**永远不会触发新抓取**（保护历史快照不被污染）
 *
 * 页面缓存：独立的缓存 key（page_platform_{source}_{date}_*），30 分钟有效期
 * CSS 文件：/assets/css/platform.css + platform-dark.css
 * ============================================================
 */

$startTime = microtime(true);

require_once __DIR__ . '/function.php';
$cfg = $GLOBALS['site_config'];


// ============================================================
// 第 1 步：解析 source 参数（PATH_INFO / REQUEST_URI / GET 三轨兼容）
// ============================================================
$sourceId = '';
$dateParam = '';  // 可选，"2026-11-10" 格式

// 方式 1：PATH_INFO（Apache/Nginx 都支持）
// /platform.php/baidu           → source=baidu
// /platform.php/weibo/2026-11-10 → source=weibo, date=2026-11-10
if (!empty($_SERVER['PATH_INFO'])) {
    $parts = explode('/', trim($_SERVER['PATH_INFO'], '/'));
    if (!empty($parts[0])) $sourceId = $parts[0];
    if (!empty($parts[1])) $dateParam = $parts[1];
}
// 方式 2：REQUEST_URI 正则匹配
if (empty($sourceId) && !empty($_SERVER['REQUEST_URI'])) {
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (preg_match('#/([a-z0-9_]+)(?:/(\d{4}-\d{2}-\d{2}))?(?:/platform\.php.*)?#', $uri, $m)) {
        // 排除真实文件
        if (!file_exists(__DIR__ . '/' . ltrim($uri, '/'))) {
            $sourceId = $m[1];
            if (!empty($m[2])) $dateParam = $m[2];
        }
    }
    // /platform.php/xxx 格式
    if (preg_match('#/platform\.php/([a-z0-9_]+)(?:/(\d{4}-\d{2}-\d{2}))?#', $uri, $m)) {
        $sourceId = $m[1];
        if (!empty($m[2])) $dateParam = $m[2];
    }
}
// 方式 3：$_GET
if (empty($sourceId) && !empty($_GET['source'])) {
    $sourceId = $_GET['source'];
}
if (empty($dateParam) && !empty($_GET['date'])) {
    $dateParam = $_GET['date'];
}

// 清理 sourceId
$sourceId = preg_replace('/[^a-z0-9_]/', '', strtolower($sourceId));

// 没传 source → 首页
if (empty($sourceId)) {
    header('Location: /', true, 302);
    exit;
}

// 清理 dateParam（只允许 YYYY-MM-DD 格式）
$isHistory = false;
if (!empty($dateParam)) {
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $dateParam, $m)) {
        $year = intval($m[1]); $month = intval($m[2]); $day = intval($m[3]);
        if (checkdate($month, $day, $year)) {
            $isHistory = true;
        }
    }
}


// ============================================================
// 第 2 步：验证 source 是否在白名单里
// ============================================================
$sourceConfig = null;
foreach ($GLOBALS['sources'] as $s) {
    if ($s['id'] === $sourceId) {
        $sourceConfig = $s;
        break;
    }
}
if (!$sourceConfig) {
    http_response_code(404);
    include __DIR__ . '/404.php';
    exit;
}


// ============================================================
// 第 3 步：页面级静态缓存（key 里包含日期参数）
// ============================================================
$cacheKey = 'page_platform_' . $sourceId . ($isHistory ? '_' . $dateParam : '_today') . '_' . md5(serialize([
    $cfg['site_name'],
    $cfg['show_heat'],
    $cfg['seo_domain'],
]));
$cacheFile = $cfg['cache_dir'] . '/' . $cacheKey . '.html';
$cacheTtl = isset($cfg['page_cache_ttl']) ? intval($cfg['page_cache_ttl']) : 1800;

if (!empty($cfg['page_cache_enable'])) {
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTtl) {
        $content = file_get_contents($cacheFile);
        $loadTime = microtime(true) - $startTime;
        $timeStr = $loadTime < 1 ? round($loadTime * 1000, 0) . ' ms' : round($loadTime, 2) . ' 秒';
        $timeHtml = '<span class="iconfont icon-shandian" style="font-size:12px; vertical-align:middle; margin-right:2px;"></span>页面加载耗时: ' . $timeStr . ' (来自缓存)';
        $content = str_replace('<!--GEN_TIME_PLACEHOLDER-->', $timeHtml, $content);
        echo $content;
        exit;
    }
    ob_start();
}


// ============================================================
// 第 4 步：获取数据（历史页只读缓存，今天页可触发刷新）
// ============================================================
$data  = array();
$error = '';
$capturedAt = null;
$force = isset($_GET['force']) ? true : false;

if ($isHistory) {
    // --- 历史页：只读缓存，不触发新抓取 ---
    $cache = readCacheByDate($sourceId, $dateParam);
    if ($cache === null) {
        // 那天没有数据
        http_response_code(404);
        include __DIR__ . '/404.php';
        exit;
    }
    $data = isset($cache['data']['data']) ? $cache['data']['data'] : (isset($cache['data']) && is_array($cache['data']) ? $cache['data'] : array());
    $capturedAt = isset($cache['captured_at']) ? intval($cache['captured_at']) : null;
} else {
    // --- 今天页：正常缓存/刷新流程 ---
    $result = fetchApiData($sourceId, $cfg['api_limit'], $force);
    $data  = isset($result['data']) ? $result['data'] : array();
    $error = isset($result['error']) ? $result['error'] : '';
    $capturedAt = isset($result['time']) ? intval($result['time']) : time();
}

// 用于显示日期的时间戳：优先用抓取时间，否则用当前时间
$dateTs = $capturedAt ?: time();


// ============================================================
// 第 5 步：准备 SEO 变量（必须在 include header.php 之前设置）
// ============================================================
$platformName  = $sourceConfig['name'];
$platformIcon  = $sourceConfig['icon'];
$platformColor = $sourceConfig['colorClass'];
$platformUrl   = $sourceConfig['url'];
$siteName      = $cfg['site_name'];

// 日期格式（用数据的 captured_at，不是当前时间）
$dateText = date('n月j日', $dateTs);       // 11月12日
$dateFull = date('Y年n月j日', $dateTs);    // 2026年11月12日
$dateISO  = date('Y-m-d', $dateTs);        // 2026-11-12

// SEO Title（历史页 vs 今天页）
if ($isHistory) {
    $pageTitle = $dateText . $platformName . '（历史存档） | ' . $siteName;
} else {
    $pageTitle = $dateText . $platformName . ' - 实时更新 | ' . $siteName;
}

// SEO Description
$countText = !empty($data) ? count($data) . '条' : '暂无';
if ($isHistory) {
    $pageDesc = $siteName . '历史存档：' . $dateText . $platformName . '完整榜单，' . $countText . '数据永久保留。';
} else {
    $pageDesc = $siteName . '聚合最新' . $dateText . $platformName . '，共' . $countText . '真实榜单，实时更新中。';
}

// SEO Keywords
$pageKeywords = $platformName . ', ' . $dateText . $platformName . ', ' . $platformName . '历史榜单, ' . $siteName;

// Canonical（历史页带日期，今天页不带）
$canonical = $cfg['seo_domain'] . '/' . $sourceId . ($isHistory ? '/' . $dateISO : '');

include __DIR__ . '/home/header.php';
?>
    <!-- 详情页样式 -->
    <link rel="stylesheet" href="/assets/css/platform.css">
    <link rel="stylesheet" href="/assets/css/platform-dark.css">
    <style>
.detail-card .rank.top1 { background: #ff4d4f; color: #fff; }
.detail-card .rank.top2 { background: #ff7a45; color: #fff; }
.detail-card .rank.top3 { background: #ffa940; color: #fff; }
.detail-card .rank:not(.top1):not(.top2):not(.top3) { background: #e8e8e8; color: #444; }
    </style>

    <!-- Schema.org 结构化数据 -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "CollectionPage",
        "name": "<?php echo escapeHtml($dateText . $platformName); ?>",
        "url": "<?php echo $canonical; ?>",
        "description": "<?php echo escapeHtml($pageDesc); ?>",
        "datePublished": "<?php echo $dateISO; ?>",
        "isPartOf": {
            "@type": "WebSite",
            "name": "<?php echo escapeHtml($siteName); ?>",
            "url": "<?php echo $cfg['seo_domain']; ?>/"
        }
    }
    </script>

    <div class="platform-detail">
        <!-- 面包屑 -->
        <nav class="platform-breadcrumb" aria-label="面包屑导航">
            <a href="/">首页</a>
            <span style="margin:0 6px;">›</span>
            <?php if ($isHistory): ?>
                <a href="/<?php echo $sourceId; ?>"><?php echo escapeHtml($platformName); ?></a>
                <span style="margin:0 6px;">›</span>
                <span><?php echo escapeHtml($dateText); ?>（历史存档）</span>
            <?php else: ?>
                <span><?php echo escapeHtml($platformName); ?></span>
            <?php endif; ?>
        </nav>

        <?php if ($isHistory): ?>
            <!-- 历史页专属提示条 -->
            <div class="platform-breadcrumb" style="background:#fff8e6; border-radius:6px; padding:8px 14px; color:#a86c00; border:1px solid #ffe58f; margin-bottom:14px;">
                <span class="iconfont icon-tishi" style="font-size:14px; margin-right:6px;"></span>
                这是 <?php echo $dateFull; ?> 的历史存档，数据永久保留不更新。
                <a href="/<?php echo $sourceId; ?>" style="margin-left:8px;">查看今天 →</a>
            </div>
        <?php endif; ?>

        <?php if ($error && !$isHistory): ?>
            <!-- 今天页错误 -->
            <section class="detail-card">
                <div class="detail-error">
                    <span class="iconfont icon-cuowu" style="font-size:24px;"></span>
                    <p style="margin-top:10px;"><?php echo escapeHtml($error); ?></p>
                    <p style="margin-top:8px; font-size:12px;">
                        <a href="/">返回首页</a> &nbsp;·&nbsp;
                        <a href="?force=1">强制刷新</a>
                    </p>
                </div>
            </section>
        <?php else: ?>

        <!-- Hero -->
        <header class="platform-hero">
            <div class="hero-icon">
                <span class="iconfont <?php echo escapeHtml($platformIcon); ?> platform-icon <?php echo escapeHtml($platformColor); ?>"></span>
            </div>
            <div class="hero-info">
                <h1 class="hero-title">
                    <time class="hero-date" datetime="<?php echo $dateISO; ?>" title="<?php echo $dateFull; ?>"><?php echo $dateText; ?></time>
                    <?php echo escapeHtml($platformName); ?>
                    <?php if ($isHistory): ?>
                        <span style="font-size:11px; color:#a86c00; background:#fff8e6; padding:1px 8px; border-radius:10px; font-weight:normal;">历史存档</span>
                    <?php endif; ?>
                </h1>
                <div class="hero-meta">
                    <span class="iconfont icon-shijian" style="font-size:12px; margin-right:3px;"></span>
                    <?php
                    if ($capturedAt) {
                        // 历史页/今天页都显示精确的抓取时间
                        echo date('Y-m-d H:i:s', $capturedAt) . ' 抓取';
                    } else {
                        echo getUpdateTime(time()) . ' 更新';
                    }
                    ?>
                    <?php if (!empty($data)): ?>
                    &nbsp;·&nbsp; 共 <?php echo count($data); ?> 条
                    <?php endif; ?>
                </div>
            </div>
            <div class="hero-actions">
                <a href="<?php echo escapeHtml($platformUrl); ?>" target="<?php echo getLinkTarget(); ?>" rel="noopener">原始来源 ↗</a>
                <?php if (!$isHistory): ?>
                <button onclick="location.search='?force=1'" title="强制刷新数据">
                    <span class="iconfont icon-shanchuanshuaxin" style="font-size:12px; margin-right:3px;"></span>强制刷新
                </button>
                <?php endif; ?>
            </div>
        </header>

        <?php if (empty($data)): ?>
            <section class="detail-card">
                <div class="detail-empty">
                    <span class="iconfont icon-wushuju" style="font-size:32px;"></span>
                    <p style="margin-top:10px;">暂无数据</p>
                    <?php if (!$isHistory): ?>
                    <p style="margin-top:8px; font-size:12px;"><a href="?force=1">点击强制刷新</a></p>
                    <?php endif; ?>
                </div>
            </section>
        <?php else: ?>

        <!-- 完整榜单 -->
        <section class="detail-card" aria-label="<?php echo escapeHtml($platformName); ?>完整榜单" itemscope itemtype="https://schema.org/ItemList">
            <meta itemprop="name" content="<?php echo escapeHtml($dateText . $platformName); ?>">
            <meta itemprop="numberOfItems" content="<?php echo count($data); ?>">
            <ol class="hot-list">
                <?php foreach ($data as $i => $item):
                    $rankClass = '';
                    if      ($i === 0) $rankClass = ' top1';
                    elseif  ($i === 1) $rankClass = ' top2';
                    elseif  ($i === 2) $rankClass = ' top3';
                    $title = isset($item['title']) ? $item['title'] : '';
                    $url   = isset($item['url'])   ? $item['url']   : '#';
                    $hot   = isset($item['hot'])   ? $item['hot']   : '';
                ?>
                <li class="hot-item" onclick="openHotLink('<?php echo escapeHtml($url); ?>')"
                    itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                    <meta itemprop="position" content="<?php echo $i + 1; ?>">
                    <span class="rank<?php echo $rankClass; ?>"><?php echo $i + 1; ?></span>
                    <span class="hot-title" itemprop="name"><?php echo escapeHtml($title); ?></span>
                    <?php if ($cfg['show_heat'] && $hot): ?>
                    <span class="hot-heat"><span class="iconfont icon-xiaohuomiao" style="font-size:12px; margin-right:2px;"></span><?php echo escapeHtml($hot); ?></span>
                    <?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ol>
        </section>

        <?php endif; ?>
        <?php endif; ?>
    </div>

<?php
include __DIR__ . '/home/footer.php';

// 写入页面缓存
$genTime = microtime(true) - $startTime;
$timeStr = $genTime < 1 ? round($genTime * 1000, 0) . ' ms' : round($genTime, 2) . ' 秒';
$timeHtml = '<span class="iconfont icon-shandian" style="font-size:12px; vertical-align:middle; margin-right:2px;"></span>页面生成耗时: ' . $timeStr;

if (!empty($cfg['page_cache_enable']) && ob_get_level() > 0) {
    $content = ob_get_contents();
    if ($content !== false) {
        if (is_dir($cfg['cache_dir']) && is_writable($cfg['cache_dir'])) {
            @file_put_contents($cacheFile, $content);
        }
        $content = str_replace('<!--GEN_TIME_PLACEHOLDER-->', $timeHtml, $content);
    }
    ob_end_clean();
    echo $content;
}
?>
