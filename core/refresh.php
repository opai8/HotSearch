<?php
/**
 * 首页缓存刷新接口（AJAX 调用）
 *
 * 使用方法：
 *   GET /core/refresh.php?source=baidu   ← 刷新某个平台并返回最新数据
 *   GET /core/refresh.php?source=all     ← 刷新所有平台（返回状态）
 *
 * 返回格式（JSON）：
 *   {
 *     "success": true,
 *     "source": "baidu",
 *     "time": 1718899900,
 *     "time_str": "2026-06-21 19:49",
 *     "data": [ ... ]
 *   }
 *
 * 防刷策略（三层）：
 *   第 1 层 前端防抖    → script.js 里 30 秒内不能点同一按钮
 *   第 2 层 后端平台冷却 → 同一平台 120 秒内只能刷 1 次（文件锁）
 *   第 3 层 后端 IP 限流 → 同一 IP 每分钟最多 3 次刷新（滑动窗口）
 */

// 引入核心模块（通过根目录的 function.php 统一加载 config / helper / fetcher）
require_once dirname(__DIR__) . '/function.php';

// 设置响应头（允许跨域访问，用于 AJAX 调用）
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// ============================================================
// 防刷：IP 总限流（每分钟 30 次）+ 单平台冷却（15 秒）
// 锁文件带 _refresh 后缀，和 platform.php / api 入口隔离，互不干扰
// 已移除本地 bypass，所有 IP 统一对待
// ============================================================
$rateLimitPerMinute = 30;
$cooldownSec        = 15;

$lockDir = $GLOBALS['cache_dir'] . DIRECTORY_SEPARATOR . '_locks';
if (!is_dir($lockDir)) {
    @mkdir($lockDir, 0755, true);
}
$ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';
$ipLockFile = $lockDir . DIRECTORY_SEPARATOR . 'ip_refresh_' . md5($ip) . '.json';

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

// 获取参数
$source = isset($_GET['source']) ? $_GET['source'] : '';
$action = isset($_GET['action']) ? $_GET['action'] : '';
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : $GLOBALS['apiLimit'];

// 清除所有缓存
if ($action === 'clear_all') {
    $cacheDir = $GLOBALS['cache_dir'];
    $count = 0;
    if (is_dir($cacheDir)) {
        $files = glob($cacheDir . '/*.json');
        foreach ($files as $file) {
            if (is_file($file) && unlink($file)) {
                $count++;
            }
        }
    }
    echo json_encode([
        'success' => true,
        'message' => '已清除 ' . $count . ' 个缓存文件'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 验证参数
if (empty($source)) {
    echo json_encode([
        'success' => false,
        'message' => '参数 source 不能为空'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 1. 刷新单个平台
if ($source !== 'all') {
    // 验证平台是否在配置中
    $validSources = array_column($GLOBALS['sources'], 'id');
    if (!in_array($source, $validSources)) {
        echo json_encode([
            'success' => false,
            'message' => '不支持的平台: ' . $source
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ============================================================
    // 单平台冷却（15 秒）——用独立锁文件，和 platform.php force 互不干扰
    // 已移除本地 bypass，所有 IP 统一对待
    // ============================================================
    $sourceLockFile = $lockDir . DIRECTORY_SEPARATOR . 'source_' . $source . '_refresh.lock';
    if (file_exists($sourceLockFile)) {
        $last = (int)@file_get_contents($sourceLockFile);
        $elapsed = time() - $last;
        if ($elapsed < $cooldownSec) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(429);
            echo json_encode([
                'success' => false,
                'message' => '刷新太频繁啦，请 ' . ($cooldownSec - $elapsed) . ' 秒后再试',
                'retry_after' => $cooldownSec - $elapsed
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
    @file_put_contents($sourceLockFile, time(), LOCK_EX);

    // 清除旧缓存，然后强制刷新
    clearCache($source);
    $result = fetchApiData($source, $limit, true);

    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'source' => $source,
            'time' => $result['time'],
            'time_str' => date('Y-m-d H:i', $result['time']),
            'data' => $result['data']
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            'success' => false,
            'source' => $source,
            'message' => $result['error'] ?: '刷新失败'
        ], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

// 2. 刷新所有平台
$cardsData = fetchAllSourcesData($GLOBALS['sources'], $limit, true);
$results = [];
foreach ($cardsData as $id => $card) {
    $results[$id] = [
        'success' => empty($card['error']),
        'time' => $card['time'],
        'data_count' => count($card['data']),
        'error' => $card['error']
    ];
}
echo json_encode([
    'success' => true,
    'source' => 'all',
    'time' => time(),
    'time_str' => date('Y-m-d H:i'),
    'results' => $results
], JSON_UNESCAPED_UNICODE);
?>
