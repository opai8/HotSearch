<?php
/**
 * API 统一入口文件
 *
 * ================================================================
 * 路由开关（唯一需要你修改的地方）
 * ================================================================
 *
 *  · ENABLE_API_ROUTER = true   → 启用路由分发（默认）
 *  · ENABLE_API_ROUTER = false  → 禁用，返回 404
 *
 *  支持访问方式：
 *    /api/index.php/baidu?limit=10      ← PATH_INFO 方式（零配置，推荐）
 *
 *  说明：
 *    · 所有平台处理器在 api/V1/{platform}.php 里，类名 {Platform}HotSearch
 *    · 平台白名单由 api/utils/config.php 的 platforms 数组控制
 *    · 关闭功能：把上面的 ENABLE_API_ROUTER 改为 false
 */

// ================================================================
// 路由开关
if (!defined('ENABLE_API_ROUTER')) {
    define('ENABLE_API_ROUTER', true);
}
// ================================================================

if (!ENABLE_API_ROUTER) {
    http_response_code(404);
    exit;
}

// CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ================================================================
// 路由解析（自动识别 3 种 URL 格式）
// ================================================================
$platform = null;

// 方式 A: PATH_INFO —— /api/index.php/baidu
if (!empty($_SERVER['PATH_INFO'])) {
    $platform = trim($_SERVER['PATH_INFO'], '/');
}

// 方式 B: REQUEST_URI —— /api/baidu 或 /api/index.php/baidu
if (empty($platform)) {
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    // 匹配 /api/baidu 或 /api/index.php/baidu
    if (preg_match('#/api/(?:index\.php/)?([a-z0-9]+)$#i', $uri, $m)) {
        $platform = strtolower($m[1]);
    }
}

// 方式 C: 兼容旧的 ?platform=baidu
if (empty($platform) && !empty($_GET['platform'])) {
    $platform = strtolower(trim($_GET['platform']));
}

// ---- 校验 ----
if (empty($platform) || !preg_match('/^[a-z0-9_]+$/', $platform)) {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => '平台名无效或缺失。请使用: /api/index.php/平台名?limit=10'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ================================================================
// 加载配置 + 平台处理器（使用绝对路径，不依赖 __DIR__ 相对关系）
// ================================================================
$baseDir = dirname(__DIR__);  // 项目根目录

$configFile = $baseDir . '/api/utils/config.php';
if (!file_exists($configFile)) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => '配置文件不存在'], JSON_UNESCAPED_UNICODE);
    exit;
}

$config = require $configFile;

// 读取 API 版本（从根配置），默认 V1
// 注意：config.php 是 $site_config = [...] 变量赋值，不是 return [...]
$siteConfigFile = $baseDir . '/config.php';
$apiVersion = 'V1';
if (file_exists($siteConfigFile)) {
    require $siteConfigFile;  // 加载 $site_config 变量
    if (isset($site_config['api_version'])) {
        $apiVersion = strtoupper($site_config['api_version']);
    }
}
$apiVersion = preg_replace('/[^V0-9]/', '', $apiVersion) ?: 'V1';  // 安全过滤

// 版本目录是唯一真相源：动态 glob 得到该版本下实际可用的平台
$versionDir = $baseDir . '/api/' . $apiVersion;
$platforms = [];
if (is_dir($versionDir)) {
    foreach (glob($versionDir . '/*.php') as $f) {
        $platforms[] = strtolower(pathinfo($f, PATHINFO_FILENAME));
    }
}

if (empty($platforms)) {
    http_response_code(501);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => '版本目录为空: ' . $apiVersion . '。请在 api/' . $apiVersion . '/ 下放置 handler 文件'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!in_array($platform, $platforms, true)) {
    http_response_code(501);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => $apiVersion . ' 不支持的平台: ' . $platform . '。可用: ' . implode(', ', $platforms)
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 严格按版本目录找 handler，不 fallback
$handlerFile = $versionDir . '/' . $platform . '.php';
if (!file_exists($handlerFile)) {
    http_response_code(501);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => '处理器不存在: ' . $apiVersion . '/' . $platform . '.php'], JSON_UNESCAPED_UNICODE);
    exit;
}

// force 参数（提前定义，供统计/限流/缓存全链路使用）
$force = isset($_GET['force']) && $_GET['force'] === '1';

// ============================================================
// API 调用统计（轻量 JSON 计数器，不影响性能）
// 按日聚合，支持按平台查看调用次数 / force=1 次数
// 文件位置：cache/_api_stats.json
// ============================================================
$statsFile = $baseDir . '/cache/_api_stats.json';
$todayKey  = date('Ymd');
$stats = [];
if (file_exists($statsFile)) {
    $stats = json_decode(@file_get_contents($statsFile), true) ?: [];
}
if (!isset($stats[$todayKey])) $stats[$todayKey] = [];
if (!isset($stats[$todayKey][$platform])) {
    $stats[$todayKey][$platform] = ['hits' => 0, 'force' => 0];
}
$stats[$todayKey][$platform]['hits']++;
if ($force) $stats[$todayKey][$platform]['force']++;
// 保留最近 7 天，自动清理更老的数据
if (count($stats) > 7) {
    $oldKeys = array_keys($stats);
    sort($oldKeys);
    foreach (array_slice($oldKeys, 0, count($oldKeys) - 7) as $k) {
        unset($stats[$k]);
    }
}
@file_put_contents($statsFile, json_encode($stats, JSON_UNESCAPED_UNICODE), LOCK_EX);

// ============================================================
// 限流层 + 平台冷却（已移除本地 bypass，所有 IP 统一对待）
// ============================================================
$rateLimitPerMinute = 30;

$lockDir = $baseDir . '/cache/_locks';
if (!is_dir($lockDir)) {
    @mkdir($lockDir, 0755, true);
}
$ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';
$ipLockFile = $lockDir . '/ip_api_' . md5($ip) . '.json';

$window = [];
if (file_exists($ipLockFile)) {
    $window = json_decode(@file_get_contents($ipLockFile), true) ?: [];
}
$window = array_values(array_filter($window, function ($t) { return $t > time() - 60; }));

if (count($window) >= $rateLimitPerMinute) {
    http_response_code(429);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => '请求过于频繁，请稍后再试（每分钟最多 ' . $rateLimitPerMinute . ' 次）',
        'retry_after' => 60 - (time() - $window[0])
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
$window[] = time();
@file_put_contents($ipLockFile, json_encode($window), LOCK_EX);

// ============================================================
// 平台冷却：force=1 时 15 秒内只能刷 1 次
// ============================================================
$cooldownSec = 15;
$sourceLockFile = $lockDir . '/source_' . $platform . '_api.lock';

if ($force) {
    if (file_exists($sourceLockFile)) {
        $last = (int)@file_get_contents($sourceLockFile);
        if (time() - $last < $cooldownSec) {
            http_response_code(429);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => '强制刷新冷却中，请 ' . ($cooldownSec - (time() - $last)) . ' 秒后再试',
                'retry_after' => $cooldownSec - (time() - $last)
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
    @file_put_contents($sourceLockFile, time(), LOCK_EX);
}

// ============================================================
// 缓存层：force=1 跳过缓存直接爬虫，否则命中缓存就返回
// ============================================================
$archiveDir = rtrim(isset($GLOBALS['archive_dir']) ? $GLOBALS['archive_dir'] : (isset($GLOBALS['cache_dir']) ? $GLOBALS['cache_dir'] : ($baseDir . '/cache')), '/');
$cacheTtl   = isset($GLOBALS['cache_ttl']) ? $GLOBALS['cache_ttl'] : (12 * 3600);
$todayDir   = date('Ymd');
$cacheFile  = $archiveDir . '/' . $todayDir . '/' . $platform . '.json';

if (!$force && file_exists($cacheFile) && filemtime($cacheFile) >= time() - $cacheTtl) {
    header('Content-Type: application/json; charset=utf-8');
    header('X-Cache: HIT');
    header('X-Cache-Age: ' . (time() - filemtime($cacheFile)) . 's');

    // fetcher 写的缓存外层有 captured_at/date 壳，需要剥离后再输出 handler 原始格式
    // 这样 debug.js 和其他调用方看到的格式统一：{success, data, title, ...}
    $raw = file_get_contents($cacheFile);
    $cache = json_decode($raw, true);
    if ($cache && isset($cache['captured_at']) && isset($cache['data']) && is_array($cache['data'])) {
        // fetcher 格式：外层 captured_at + date + data(handler 原始 JSON)
        echo json_encode($cache['data'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } else {
        // 可能是 api 自己 force=1 写的（直接就是 handler 格式，没外层壳）
        echo $raw;
    }
    exit;
}
header('X-Cache: MISS' . ($force ? ' (forced)' : ''));

require_once $handlerFile;
// 支持下划线分隔的命名，如 zhihu_daily → ZhihuDailyHotSearch
$className = '';
$parts = explode('_', strtolower($platform));
foreach ($parts as $part) {
    $className .= ucfirst($part);
}
$className .= 'HotSearch';

// 如果类名以数字开头（PHP类名不允许），前面加下划线
if (preg_match('/^\d/', $className)) {
    $className = '_' . $className;
}

if (!class_exists($className)) {
    http_response_code(501);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => '处理器类不存在: ' . $className], JSON_UNESCAPED_UNICODE);
    exit;
}

// ================================================================
// 执行
// ================================================================
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : (isset($config['default_limit']) ? $config['default_limit'] : 50);
$maxLimit = isset($config['max_limit']) ? $config['max_limit'] : 100;
$minLimit = isset($config['min_limit']) ? $config['min_limit'] : 1;
$limit = max($minLimit, min($maxLimit, $limit));

try {
    $instance = new $className();
    $result = $instance->handle($limit);
    $json = json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    // 爬成功后写回今日缓存（不管 force 还是过期重爬）
    if (!empty($result['success'])) {
        $dir = dirname($cacheFile);
        if (!is_dir($dir)) { @mkdir($dir, 0755, true); }
        @file_put_contents($cacheFile, $json, LOCK_EX);
    }

    header('Content-Type: application/json; charset=utf-8');
    echo $json;
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => '内部错误: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
exit;
?>
