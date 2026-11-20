<?php
/**
 * ============================================================
 * Debug 调试面板 - 站长运维工具
 * ============================================================
 *
 * 功能：
 *   - 左侧平台列表（按 config.php api_version 扫描）
 *   - 点击哪个平台 → 右侧展示详情 + 缓存状态 + 测试按钮
 *   - 测试按钮触发 /api/index.php/{source} 实时 curl 请求
 *   - 支持 force=1 强制刷新跳过缓存
 *   - 版本切换即时响应（改 config.php 刷新页面即生效）
 *
 * 入口：从 status.php 底部进入
 * 安全：noindex + API handler 自身防刷
 * ============================================================
 */

require_once __DIR__ . '/function.php';
$cfg = $GLOBALS['site_config'];

// ---------- 按 api_version 过滤平台列表 ----------
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
$sources = $GLOBALS['sources'];
if (!empty($availableIds)) {
    $sources = array_values(array_filter($sources, function($s) use ($availableIds) {
        return isset($s['id']) && in_array(strtolower($s['id']), $availableIds, true);
    }));
}

// ---------- 读取今天的缓存状态（供首开展示） ----------
$todayDir = date('Ymd');
$cacheDir = rtrim(isset($cfg['archive_dir']) ? $cfg['archive_dir'] : $cfg['cache_dir'], '/') . '/' . $todayDir;
$now = time();
$cacheMap = [];
if (is_dir($cacheDir)) {
    foreach ($sources as $s) {
        $file = $cacheDir . '/' . $s['id'] . '.json';
        if (file_exists($file)) {
            $cacheMap[$s['id']] = [
                'size' => filesize($file),
                'mtime' => filemtime($file),
                'age' => $now - filemtime($file),
            ];
        }
    }
}

// ---------- SEO ----------
$pageTitle   = '调试工具 - ' . $cfg['site_name'];
$pageDesc    = '站长内部调试工具，测试各平台 API 爬虫是否正常。';
$pageKeywords = '调试工具,API测试,爬虫调试';
$pageRobots  = 'noindex, nofollow';
$canonical   = $cfg['seo_domain'] . '/debug';
$breadcrumbJsonLd = '<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "BreadcrumbList",
    "itemListElement": [
        {"@type":"ListItem","position":1,"name":"首页","item":"' . $cfg['seo_domain'] . '/"},
        {"@type":"ListItem","position":2,"name":"调试工具","item":"' . $cfg['seo_domain'] . '/debug"}
    ]
}
</script>';

include __DIR__ . '/home/header.php';

// 平台 JSON（给 JS 用，含缓存状态）
$platformsJson = json_encode(array_map(function($s) use ($cacheMap) {
    $cache = isset($cacheMap[$s['id']]) ? $cacheMap[$s['id']] : null;
    return [
        'id'    => $s['id'],
        'name'  => $s['name'],
        'icon'  => $s['icon'],
        'color' => $s['colorClass'],
        'url'   => $s['url'],
        'cache' => $cache,
    ];
}, $sources), JSON_UNESCAPED_UNICODE);
?>
    <link rel="stylesheet" href="/assets/css/debug.css">

    <!-- 引导数据 -->
    <div id="debugBootstrap"
         data-version="<?php echo escapeHtml($apiVersion); ?>"
         data-cache-dir="<?php echo escapeHtml($cacheDir); ?>"
         data-cache-ttl="<?php echo intval($cfg['cache_ttl']); ?>"
         data-platforms='<?php echo escapeHtml($platformsJson); ?>'
         style="display:none"></div>

    <div class="debug-wrap">
        <!-- 顶部栏 -->
        <div class="debug-topbar">
            <div class="debug-title-row">
                <a href="/" class="debug-logo">← 首页</a>
                <span class="debug-title">调试工具</span>
                <a href="/status" class="debug-link">状态面板</a>
            </div>
            <div class="debug-meta-row">
                <span class="debug-version-badge">当前 API 版本：<b><?php echo escapeHtml($apiVersion); ?></b></span>
                <span class="debug-tip">提示：在 config.php 改 api_version 后刷新本页面即可切版本</span>
            </div>
        </div>

        <div class="debug-body">
            <!-- 左侧：平台列表 -->
            <aside class="debug-sidebar">
                <div class="debug-sidebar-head">
                    平台列表 <span class="debug-count">(<?php echo count($sources); ?>)</span>
                </div>
                <input type="text" id="debugSearch" class="debug-search" placeholder="🔍 搜索平台...">
                <div class="debug-list" id="debugList">
                    <!-- JS 渲染 -->
                </div>
                <div class="debug-sidebar-foot">
                    <button class="debug-btn-mini" id="btnRunAll">▶ 批量测试</button>
                    <button class="debug-btn-mini debug-btn-danger" id="btnClearAll">清缓存</button>
                </div>
            </aside>

            <!-- 右侧：详情 + 测试 -->
            <main class="debug-main">
                <div class="debug-empty" id="debugEmpty">
                    <div class="debug-empty-icon">👈</div>
                    <div class="debug-empty-text">点击左侧平台查看详情</div>
                </div>
                <div class="debug-detail" id="debugDetail" style="display:none">
                    <!-- JS 渲染 -->
                </div>
            </main>
        </div>
    </div>

    <script src="/assets/js/debug.js"></script>

<?php include __DIR__ . '/home/footer.php'; ?>
