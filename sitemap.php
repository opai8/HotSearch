<?php
/**
 * sitemap.xml - 动态生成
 *
 * 为什么用 PHP 生成？
 *   1. 平台列表从 config.php 的 $sources + api_version 过滤自动同步
 *   2. 路径走短 URL（需 .htaccess rewrite 支持）
 *   3. lastmod 动态读取各平台缓存的最新更新时间
 *
 * 使用方式：
 *   - 直接访问 /sitemap.php 输出 XML
 *   - 或部署时 php sitemap.php > sitemap.xml
 *   - robots.txt 里 Sitemap 指向 sitemap.php 或 sitemap.xml 均可
 */

require_once __DIR__ . '/function.php';

$cfg = $GLOBALS['site_config'];
$domain = rtrim($cfg['seo_domain'], '/');

// ============================================================
// 1. 按 api_version 过滤（和 index.php 完全一致的逻辑）
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
$sources = $GLOBALS['sources'];
if (!empty($availableIds)) {
    $sources = array_values(array_filter($sources, function($s) use ($availableIds) {
        return in_array(strtolower($s['id']), $availableIds, true);
    }));
}

// ============================================================
// 2. 读各平台缓存的最新更新时间（做 lastmod）
// ============================================================
$today = date('Y-m-d');
$cacheDir = isset($cfg['cache_dir']) ? $cfg['cache_dir'] : (__DIR__ . '/cache');

function getPlatformLastmod($sourceId, $cacheDir, $defaultToday) {
    // 优先读今天的缓存 JSON 里的 captured_at（最精准，原平台真正的抓取时间）
    $todayFile = $cacheDir . '/' . date('Ymd') . '/' . $sourceId . '.json';
    if (file_exists($todayFile)) {
        $content = @file_get_contents($todayFile);
        if ($content !== false) {
            $json = json_decode($content, true);
            if (is_array($json) && !empty($json['captured_at'])) {
                return date('Y-m-d', intval($json['captured_at']));
            }
        }
    }
    // 备选：文件修改时间（兜底）
    if (file_exists($todayFile)) {
        $ts = filemtime($todayFile);
        if ($ts) return date('Y-m-d', $ts);
    }
    // 再备选：_locks 下的刷新时间
    $lockFile = $cacheDir . '/_locks/source_' . $sourceId . '.lock';
    if (file_exists($lockFile)) {
        $ts = intval(@file_get_contents($lockFile));
        if ($ts > 0) return date('Y-m-d', $ts);
    }
    return $defaultToday;
}

// ============================================================
// 3. 收集 URL
// ============================================================
$urls = [];

// 3.1 首页 - 每次刷新都变，用今天
$urls[] = [
    'loc'        => $domain . '/',
    'lastmod'    => $today,
    'changefreq' => 'hourly',
    'priority'   => '1.0',
];

// 3.2 各平台详情页（只包含 api_version 里实际存在的）
foreach ($sources as $source) {
    $urls[] = [
        'loc'        => $domain . '/' . $source['id'],   // 短 URL，.htaccess rewrite 支持
        'lastmod'    => getPlatformLastmod($source['id'], $cacheDir, $today),
        'changefreq' => 'hourly',
        'priority'   => '0.8',
    ];
    // 3.2b 归档页（每个平台一个）
    $urls[] = [
        'loc'        => $domain . '/archive/' . $source['id'],
        'lastmod'    => $today,
        'changefreq' => 'daily',
        'priority'   => '0.4',
    ];
}

// 3.3 状态页
$urls[] = [
    'loc'        => $domain . '/status.php',
    'lastmod'    => $today,
    'changefreq' => 'daily',
    'priority'   => '0.3',
];

// ============================================================
// 4. 生成 XML
// ============================================================
$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

foreach ($urls as $u) {
    $xml .= "    <url>\n";
    $xml .= "        <loc>" . htmlspecialchars($u['loc'], ENT_XML1, 'UTF-8') . "</loc>\n";
    $xml .= "        <lastmod>{$u['lastmod']}</lastmod>\n";
    $xml .= "        <changefreq>{$u['changefreq']}</changefreq>\n";
    $xml .= "        <priority>{$u['priority']}</priority>\n";
    $xml .= "    </url>\n";
}

$xml .= '</urlset>' . "\n";

// 直接访问 sitemap.php 输出 XML；CLI 模式也输出（用于重定向到 sitemap.xml）
$isCli = php_sapi_name() === 'cli';
if (!$isCli) {
    header('Content-Type: application/xml; charset=utf-8');
    header('Cache-Control: no-cache, must-revalidate');
}
echo $xml;
exit;
