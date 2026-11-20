<?php
/**
 * ============================================================
 * 热搜榜 - 单平台详情页入口
 * ============================================================
 *
 * URL 格式（三种方式，全兼容）：
 *   1. 零路由（所有环境自动可用）：/platform.php?source=baidu&date=2026-11-10
 *   2. PATH_INFO（PHP 原生支持）： /platform.php/baidu/2026-11-10
 *   3. 短 URL（需要 rewrite）：    /baidu/2026-11-10（当前未开启）
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

// 方式 0（最高优先级）：$_GET — rewrite 后明确指定
if (!empty($_GET['source'])) {
    $sourceId = $_GET['source'];
}
if (!empty($_GET['date'])) {
    $dateParam = $_GET['date'];
}

// 方式 1：PATH_INFO（Apache/Nginx 都支持）
if (empty($sourceId) && !empty($_SERVER['PATH_INFO'])) {
    $parts = explode('/', trim($_SERVER['PATH_INFO'], '/'));
    if (!empty($parts[0])) $sourceId = $parts[0];
    if (!empty($parts[1])) $dateParam = $parts[1];
}
// 方式 2（最低优先级）：REQUEST_URI 正则 fallback — 取最后一个路径段
if (empty($sourceId) && !empty($_SERVER['REQUEST_URI'])) {
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    // 取最后一段（$ 锚定结尾），/platform/weibo → weibo
    if (preg_match('#/([a-z0-9_]+)(?:/(\d{4}-\d{2}-\d{2}))?$#', $uri, $m)) {
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
$force = isset($_GET['force']) ? true : false;  // force=1 时跳过页面缓存

// ============================================================
// force=1 防刷：单平台 15 秒冷却（和 refresh.php / api 入口隔离）
// 已移除本地 bypass，所有 IP 统一对待
// 被冷却拦截时降级为正常请求，并在 $forceBlocked 标记，供顶部提示条显示
// ============================================================
$forceBlocked = false;
if ($force && !$isHistory) {
    $lockDir = $GLOBALS['cache_dir'] . '/_locks';
    if (!is_dir($lockDir)) { @mkdir($lockDir, 0755, true); }

    $sourceLockFile = $lockDir . '/source_' . $sourceId . '_detail.lock';
    $cooldownSec = 15;
    if (file_exists($sourceLockFile)) {
        $lastTs = intval(@file_get_contents($sourceLockFile));
        if (time() - $lastTs < $cooldownSec) {
            $force = false;
            $forceBlocked = true;  // 被冷却拦截，后续显示提示
        }
    }
    if ($force) {
        @file_put_contents($sourceLockFile, time(), LOCK_EX);
    }
}

// 读缓存（force=true 或 force被拦截时都跳过 —— force=true 要新抓取；forceBlocked 要渲染顶部提示条 + toast）
if (!empty($cfg['page_cache_enable']) && !$force && !$forceBlocked) {
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTtl) {
        // 缓存文件已包含 footer.php 写入时的生成时间，直接输出
        echo file_get_contents($cacheFile);
        exit;
    }
}

// 缓存不命中 / force=1 → 清理本平台的过期页面缓存，然后开始重新渲染
// 不管 force 与否，只要要重新渲染，就清自己的过期缓存；只碰 page_platform_{sourceId}_*.html
if (!empty($cfg['page_cache_enable'])) {
    $platformPattern = $cfg['cache_dir'] . '/page_platform_' . $sourceId . '_*.html';
    foreach (glob($platformPattern) ?: [] as $old) {
        if ($old !== $cacheFile && filemtime($old) < time() - $cacheTtl) {
            @unlink($old);
        }
    }
}

// 只要开启了页面缓存就 ob_start() 捕获完整页面
// 不管 force 与否都需要写缓存（force=true 跳过读但仍要写）
if (!empty($cfg['page_cache_enable'])) {
    ob_start();
}


// ============================================================
// 第 4 步：获取数据（历史页只读缓存，今天页可触发刷新）
// ============================================================
$data  = array();
$error = '';
$capturedAt = null;

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
    <!-- 详情页样式（正常 + 夜间 + 响应式 全在一个文件里） -->
    <link rel="stylesheet" href="/assets/css/platform.css">
    <style>
.detail-card .rank.top1 { background: #ff4d4f; color: #fff; }
.detail-card .rank.top2 { background: #ff7a45; color: #fff; }
.detail-card .rank.top3 { background: #ffa940; color: #fff; }
.detail-card .rank:not(.top1):not(.top2):not(.top3) { background: #e8e8e8; color: #444; }
    </style>

    <!-- Schema.org 结构化数据：CollectionPage（页面信息）-->
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

    <!-- Schema.org 结构化数据：ItemList（热搜排名，给搜索引擎看）-->
    <?php if (!empty($data)): ?>
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "ItemList",
        "name": "<?php echo escapeHtml($dateText . $platformName . ' 热榜 TOP ' . min(count($data), 10)); ?>",
        "url": "<?php echo $canonical; ?>",
        "itemListOrder": "Descending",
        "itemListElement": [
            <?php
            $listItems = [];
            foreach (array_slice($data, 0, 10) as $idx => $item) {
                $rank = $idx + 1;
                $title = isset($item['title']) ? $item['title'] : '';
                $url = isset($item['url']) && $item['url'] !== '#' ? $item['url'] : $canonical;
                $listItems[] = json_encode([
                    '@type'    => 'ListItem',
                    'position' => $rank,
                    'name'     => $title,
                    'url'      => $url,
                ], JSON_UNESCAPED_UNICODE);
            }
            echo implode(",\n            ", $listItems);
            ?>
        ]
    }
    </script>
    <?php endif; ?>

    <div class="platform-detail">
        <!-- 面包屑 -->
        <nav class="platform-breadcrumb" aria-label="面包屑导航">
            <a href="/">首页</a>
            <span class="breadcrumb-sep">›</span>
            <?php if ($isHistory): ?>
                <a href="/<?php echo $sourceId; ?>"><?php echo escapeHtml($platformName); ?></a>
                <span class="breadcrumb-sep">›</span>
                <span><?php echo escapeHtml($dateText); ?></span>
            <?php else: ?>
                <span><?php echo escapeHtml($platformName); ?></span>
            <?php endif; ?>
        </nav>

        <?php if ($isHistory): ?>
            <!-- 历史页专属提示条 -->
            <div class="platform-history-tip">
                <span class="iconfont icon-gonggao history-tip-icon"></span>
                这是 <?php echo $dateFull; ?> 的历史存档，数据永久保留不更新。
                <a href="/<?php echo $sourceId; ?>" class="history-tip-link">查看今天 →</a>
            </div>
        <?php endif; ?>

        <?php if ($forceBlocked): ?>
            <!-- 强制刷新冷却提示（只在今天页显示） -->
            <div class="platform-history-tip" style="background:#fff7e6;border-color:#ffd591;color:#d46b08;">
                <span class="iconfont icon-gonggao history-tip-icon" style="color:#fa8c16;"></span>
                强制刷新冷却中（60 秒内只能刷 1 次），已返回现有数据，请稍后再试。
            </div>
        <?php endif; ?>

        <?php if ($error && !$isHistory): ?>
            <!-- 今天页错误 -->
            <section class="detail-card">
                <div class="detail-error">
                    <span class="iconfont icon-cuowu detail-error-icon"></span>
                    <p class="detail-msg"><?php echo escapeHtml($error); ?></p>
                    <div class="detail-actions">
                        <a href="/" class="detail-btn detail-btn-secondary"><span class="iconfont icon-shouye"></span>返回首页</a>
                        <a href="?source=<?php echo escapeHtml($sourceId); ?>&force=1" class="detail-btn detail-btn-primary"><span class="iconfont icon-shuaxin"></span>强制刷新</a>
                    </div>
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
                        <span class="history-archive-badge">历史存档</span>
                    <?php endif; ?>
                </h1>
                <div class="hero-meta">
                    <span class="iconfont icon-shijian hero-meta-icon"></span>
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
                <a href="<?php echo escapeHtml($platformUrl); ?>" target="<?php echo getLinkTarget(); ?>" rel="noopener noreferrer">原始来源 ↗</a>
                <?php if (!$isHistory): ?>
                <button onclick="location.search='?source=<?php echo escapeHtml($sourceId); ?>&force=1'" title="强制刷新数据">
                    <span class="iconfont icon-shuaxin hero-action-icon"></span>强制刷新
                </button>
                <?php endif; ?>
            </div>
        </header>

        <?php if (empty($data)): ?>
            <section class="detail-card">
                <div class="detail-empty">
                    <span class="iconfont icon-wushuju detail-empty-icon"></span>
                    <p class="detail-msg">暂无数据</p>
                    <?php if (!$isHistory): ?>
                    <div class="detail-actions">
                        <a href="/" class="detail-btn detail-btn-secondary"><span class="iconfont icon-shouye"></span>返回首页</a>
                        <a href="?source=<?php echo escapeHtml($sourceId); ?>&force=1" class="detail-btn detail-btn-primary"><span class="iconfont icon-shuaxin"></span>点击强制刷新</a>
                    </div>
                    <?php endif; ?>
                </div>
            </section>
        <?php else: ?>

        <!-- 完整榜单 -->
        <section class="detail-card" aria-label="<?php echo escapeHtml($platformName); ?>完整榜单" itemscope itemtype="https://schema.org/ItemList">
            <h2 class="detail-section-title"><span class="iconfont icon-top" aria-hidden="true"></span><?php echo escapeHtml($dateText); ?>· <?php echo escapeHtml($platformName); ?> 完整榜单<span class="iconfont icon-top" aria-hidden="true"></span></h2>
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
                    <span class="hot-heat"><span class="iconfont icon-xiaohuomiao fire-icon"></span><?php echo escapeHtml($hot); ?></span>
                    <?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ol>
        </section>

        <?php endif; ?>

        <!-- 最近 7 天快速跳转 + 查看全部历史（archive） -->
        <?php
        // 扫 cache 目录，找这个平台最近有数据的日期（最多 7 个）
        $cacheDir = isset($cfg['archive_dir']) ? rtrim($cfg['archive_dir'], '/\\') : (__DIR__ . '/cache');
        $recentDates = [];
        if (is_dir($cacheDir)) {
            // 今天 + 往前推 14 天范围内找，避免某天没数据就断档
            for ($i = 0; $i < 14 && count($recentDates) < 7; $i++) {
                $d = date('Y-m-d', strtotime("-{$i} day"));
                $dirName = date('Ymd', strtotime($d));
                $jsonFile = $cacheDir . '/' . $dirName . '/' . $sourceId . '.json';
                if (file_exists($jsonFile)) {
                    $recentDates[] = $d;
                }
            }
        }
        $hasArchive = !empty($recentDates);
        ?>
        <?php if ($hasArchive): ?>
        <section class="platform-recent-nav">
            <div class="recent-nav-label">
                <span class="iconfont icon-riqi recent-nav-icon"></span>
                快速切换
            </div>
            <div class="recent-nav-days">
                <?php foreach ($recentDates as $d):
                    $isCurrent = ($d === $dateISO);
                    $dayNum = ltrim(substr($d, 8, 2), '0');
                ?>
                <?php if ($isCurrent): ?>
                    <span class="recent-day is-current"><?php echo $dayNum; ?>日</span>
                <?php else: ?>
                    <a class="recent-day" href="/<?php echo $sourceId; ?>/<?php echo $d; ?>"><?php echo $dayNum; ?>日</a>
                <?php endif; ?>
                <?php endforeach; ?>
            </div>
            <a class="recent-archive-link" href="/archive/<?php echo $sourceId; ?>">
                <span class="iconfont icon-gengduo archive-link-icon"></span>
                全部历史 →
            </a>
        </section>
        <?php endif; ?>
        <?php endif; ?>
    </div>

<?php
include __DIR__ . '/home/footer.php';

// ---- 强制刷新反馈（必须在 footer 之后，因为 showToast 定义在 script.js 里）----
// force=1 成功 → 绿色 toast；force=1 被冷却拦截 → 橙色 info toast
// URL 参数守卫：只有带 ?force=1 才执行 toast，避免缓存污染（force 时写入的页面缓存不应包含 toast 行为）
if ($force || $forceBlocked):
    $forceHasError = !empty($error);
    $forceHasData  = !empty($data);
    $forceErrMsg   = $forceHasError ? str_replace(["'", "\n"], ['&#39;', ' '], $error) : '';
?>
<script>
(function() {
    // 关键守卫：只有 URL 带 force 参数才显示 toast
    // 防止 force 时写入的页面缓存被正常访问读到后也触发 toast
    var url = new URL(location.href);
    if (!url.searchParams.has('force')) return;

    if (typeof showToast === 'function') {
        <?php if ($forceBlocked): ?>
            showToast('冷却中（60秒/次），已返回现有数据', 'info');
        <?php elseif ($forceHasError): ?>
            showToast('刷新失败：<?php echo $forceErrMsg; ?>', 'error');
        <?php elseif (!$forceHasData): ?>
            showToast('已刷新，但暂时没有新数据', 'info');
        <?php else: ?>
            showToast('已强制刷新最新数据', 'success');
        <?php endif; ?>
    }
    // 从 URL 去掉 force=1（保留 source 和 date），避免刷两次
    url.searchParams.delete('force');
    history.replaceState(null, '', url.pathname + url.search);
})();
</script>
<?php endif; ?>

<?php
// 写入页面缓存（footer.php 已自行计算并输出生成时间，这里直接存/输出）
if (!empty($cfg['page_cache_enable']) && ob_get_level() > 0) {
    $content = ob_get_contents();
    if ($content !== false) {
        if (is_dir($cfg['cache_dir']) && is_writable($cfg['cache_dir'])) {
            // 写页面缓存：失败打 error_log（不 @ 抑制，因为写坏会导致下次读残缺页面）
            if (file_put_contents($cacheFile, $content) === false) {
                error_log('platform.php 写页面缓存失败: ' . $cacheFile . ' (is_writable=false 或磁盘满)');
            }
        }
    }
    ob_end_clean();
    echo $content;
}
?>
