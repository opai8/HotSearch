<?php
/**
 * ============================================================
 * 归档页 - 浏览某平台的全部历史日期
 * ============================================================
 *
 * 作用：
 *   扫 cache/YYYYMMDD/{source}.json 存在的日期，按月份分组展示
 *   点击某一天 → 跳转到已有的历史详情页 /{source}/{date}
 *
 * URL 格式（两种都能用）：
 *   短 URL（需 rewrite）：  /archive/baidu
 *   原生 URL（零 rewrite）： /archive.php?source=baidu
 *
 * ============================================================
 */

require_once __DIR__ . '/function.php';

$cfg = $GLOBALS['site_config'];

// ============================================================
// 1. 解析 source 参数（和 platform.php 同样的多轨兼容）
// ============================================================
$sourceId = '';

if (!empty($_GET['source'])) {
    $sourceId = $_GET['source'];
}
if (empty($sourceId) && !empty($_SERVER['PATH_INFO'])) {
    $parts = explode('/', trim($_SERVER['PATH_INFO'], '/'));
    if (!empty($parts[0])) $sourceId = $parts[0];
}
if (empty($sourceId) && !empty($_SERVER['REQUEST_URI'])) {
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (preg_match('#/archive/([a-z0-9_]+)#', $uri, $m)) {
        $sourceId = $m[1];
    }
}
$sourceId = preg_replace('/[^a-z0-9_]/', '', strtolower($sourceId));

// 没传 source → 首页
if (empty($sourceId)) {
    header('Location: /', true, 302);
    exit;
}

// ============================================================
// 2. 验证 source 在白名单（按 api_version 过滤，和 index.php 一致）
// ============================================================
$apiVersion = 'V1';
if (!empty($cfg['api_version'])) {
    $apiVersion = preg_replace('/[^V0-9]/', '', strtoupper($cfg['api_version'])) ?: 'V1';
}
$versionDir = __DIR__ . '/api/' . $apiVersion;
$availableIds = [];
if (is_dir($versionDir)) {
    foreach (glob($versionDir . '/*.php') as $f) {
        $availableIds[] = strtolower(pathinfo($f, PATHINFO_FILENAME));
    }
}

$sourceConfig = null;
foreach ($GLOBALS['sources'] as $s) {
    if ($s['id'] === $sourceId && (empty($availableIds) || in_array($sourceId, $availableIds, true))) {
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
// 3. 扫历史缓存：按月份分组
// ============================================================
$cacheDir = isset($cfg['archive_dir']) ? $cfg['archive_dir'] : (__DIR__ . '/cache');
$cacheDir = rtrim($cacheDir, '/\\');

// 扫所有 YYYYMMDD 子目录，找到里面有 {sourceId}.json 的日期
$allDates = [];
if (is_dir($cacheDir)) {
    foreach (glob($cacheDir . '/*', GLOB_ONLYDIR) as $dayDir) {
        $dayName = basename($dayDir);
        if (!preg_match('/^\d{8}$/', $dayName)) continue;  // 只认 YYYYMMDD
        $jsonFile = $dayDir . '/' . $sourceId . '.json';
        if (file_exists($jsonFile)) {
            // YYYYMMDD → YYYY-MM-DD
            $dateObj = DateTime::createFromFormat('Ymd', $dayName);
            if ($dateObj) {
                $allDates[] = $dateObj->format('Y-m-d');
            }
        }
    }
}

// 倒序：最新的在前
rsort($allDates);

// 按月份分组：['2026-11' => ['2026-11-16', ...], '2026-10' => [...]]
$months = [];
foreach ($allDates as $d) {
    $month = substr($d, 0, 7);  // 2026-11
    $months[$month][] = $d;
}

// ============================================================
// 4. 准备 SEO 变量
// ============================================================
$platformName = $sourceConfig['name'];
$siteName     = $cfg['site_name'];
$countLabel   = count($allDates) . ' 天历史存档';

$pageTitle   = $platformName . ' 历史存档归档 | ' . $siteName;
$pageDesc    = $siteName . ' - ' . $platformName . ' 历史数据归档，共 ' . $countLabel . '，完整保留每日热榜，可按日期浏览对照。';
$pageKeywords = $platformName . ', ' . $platformName . '历史存档, ' . $platformName . '历史榜单, ' . $siteName;
$pageRobots   = 'index, follow';
$canonical    = $cfg['seo_domain'] . '/archive/' . $sourceId;

// 自定义 3 层 BreadcrumbList JSON-LD（比 header.php 默认 2 层模板更精确）
$breadcrumbJsonLd = '
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "BreadcrumbList",
        "itemListElement": [
            {"@type":"ListItem","position":1,"name":"首页","item":"' . $cfg['seo_domain'] . '/"},
            {"@type":"ListItem","position":2,"name":"' . escapeHtml($platformName) . '","item":"' . $cfg['seo_domain'] . '/' . $sourceId . '"},
            {"@type":"ListItem","position":3,"name":"历史存档","item":"' . $canonical . '"}
        ]
    }
    </script>';

include __DIR__ . '/home/header.php';
?>
    <link rel="stylesheet" href="/assets/css/platform.css">
    <link rel="stylesheet" href="/assets/css/archive.css">

    <div class="archive-page">
        <!-- 面包屑 -->
        <nav class="platform-breadcrumb" aria-label="面包屑导航">
            <a href="/">首页</a>
            <span class="breadcrumb-sep">›</span>
            <a href="/<?php echo $sourceId; ?>"><?php echo escapeHtml($platformName); ?></a>
            <span class="breadcrumb-sep">›</span>
            <span>历史存档</span>
        </nav>

        <!-- Hero -->
        <header class="archive-hero">
            <div class="hero-icon">
                <span class="iconfont <?php echo escapeHtml($sourceConfig['icon']); ?> platform-icon <?php echo escapeHtml($sourceConfig['colorClass']); ?>"></span>
            </div>
            <div class="hero-info">
                <h1 class="hero-title"><?php echo escapeHtml($platformName); ?> · 历史存档</h1>
                <div class="hero-meta">
                    <span class="iconfont icon-shijian hero-info-icon"></span>
                    共 <?php echo count($allDates); ?> 天历史记录
                    <?php if (!empty($allDates)): ?>
                    &nbsp;·&nbsp; 最早 <?php echo $allDates[count($allDates)-1]; ?> · 最新 <?php echo $allDates[0]; ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="hero-actions">
                <a href="/<?php echo $sourceId; ?>" class="archive-today-btn">
                    <span class="iconfont icon-shouye hero-info-icon"></span>
                    返回今天
                </a>
            </div>
        </header>

        <?php if (empty($allDates)): ?>
            <!-- 暂无历史 -->
            <section class="archive-empty">
                <span class="iconfont icon-wushuju archive-empty-icon"></span>
                <p>还没有历史存档</p>
                <p class="archive-empty-tip">首次访问后每天会自动保存数据，明天再来看看吧。</p>
                <a href="/<?php echo $sourceId; ?>" class="detail-btn detail-btn-primary archive-empty-btn">
                    <span class="iconfont icon-shouye"></span>查看今天榜单
                </a>
            </section>
        <?php else: ?>
            <!-- 月份分组 -->
            <?php foreach ($months as $month => $dates):
                $monthLabel = date('Y年n月', strtotime($month . '-01'));
            ?>
            <section class="archive-month-block">
                <h2 class="archive-month-title">
                    <span class="iconfont icon-riqi archive-month-icon"></span>
                    <?php echo $monthLabel; ?>
                    <span class="archive-month-count"><?php echo count($dates); ?> 天</span>
                </h2>
                <div class="archive-days-grid">
                    <?php foreach ($dates as $date):
                        $isToday = ($date === date('Y-m-d'));
                        $dayNum  = ltrim(substr($date, 8, 2), '0');  // 1 → 9，10+ 不变
                    ?>
                    <a href="/<?php echo $sourceId; ?>/<?php echo $date; ?>"
                       class="archive-day-cell<?php echo $isToday ? ' is-today' : ''; ?>"
                       title="<?php echo $date; ?><?php echo $isToday ? '（今天）' : ''; ?>">
                        <span class="day-num"><?php echo $dayNum; ?></span>
                        <span class="day-label"><?php echo $isToday ? '今天' : '查看'; ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

<?php include __DIR__ . '/home/footer.php'; ?>
