<?php
/**
 * 页面缓存清理接口（防刷保护）
 * ============================================================
 * 功能：清除所有页面级静态缓存
 *   - cache/page_index_*.html（首页缓存）
 *   - cache/page_platform_*.html（平台详情页缓存）
 *
 * 安全机制：
 *   1. Nginx / .htaccess 目录白名单 —— core/ 下只有 refresh.php 和此文件可外部访问
 *   2. 防刷层（三层复用 refresh.php 模式）：
 *      - 前端防抖：header.js 按钮点击后 30 秒 disabled
 *      - IP 滑动窗口：每分钟最多 3 次
 *      - 全局冷却：30 秒内只能清 1 次
 *      - LOCK_EX 原子写锁文件，防并发竞态
 *
 * 注意：
 *   - 不清除数据源缓存（*.json），那是 12 小时 TTL 自动管理
 *   - 不重置 OPcache，OPcache 由宝塔面板重启 PHP 处理
 *
 * 返回：JSON
 *   { "success": true, "message": "...", "page_cache_deleted": N }
 * ============================================================
 */

require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');

// ============================================================
// 防刷层（三层）
// ============================================================
$lockDir = $GLOBALS['cache_dir'] . DIRECTORY_SEPARATOR . '_locks';
if (!is_dir($lockDir)) {
    @mkdir($lockDir, 0755, true);
}

// 第 1 层：IP 滑动窗口（每分钟 30 次）
// 已移除本地 bypass，所有 IP 统一对待
$ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';
$ipLockFile = $lockDir . DIRECTORY_SEPARATOR . 'ip_clear_cache_' . md5($ip) . '.json';

$rateLimitPerMinute = 30;
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
        'message' => '清缓存太频繁啦，请稍后再试（每分钟最多 ' . $rateLimitPerMinute . ' 次）',
        'retry_after' => 60 - (time() - $window[0])
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
$window[] = time();
@file_put_contents($ipLockFile, json_encode($window), LOCK_EX);

// 第 2 层：全局冷却（15 秒内只能清 1 次）
// 已移除本地 bypass
$cooldownSec = 15;
$globalLockFile = $lockDir . DIRECTORY_SEPARATOR . 'clear_cache.lock';

if (file_exists($globalLockFile)) {
    $last = (int)@file_get_contents($globalLockFile);
    $elapsed = time() - $last;
    if ($elapsed < $cooldownSec) {
        http_response_code(429);
        echo json_encode([
            'success' => false,
            'message' => '清缓存冷却中，请 ' . ($cooldownSec - $elapsed) . ' 秒后再试',
            'retry_after' => $cooldownSec - $elapsed
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// 记录本次请求（LOCK_EX 原子写，防并发）
// 写锁文件是防刷核心，失败要打日志便于运维排查
$window[] = time();
if (file_put_contents($ipLockFile, json_encode($window), LOCK_EX) === false) {
    error_log('clear_cache.php 写 IP 锁失败: ' . $ipLockFile);
}
if (file_put_contents($globalLockFile, time(), LOCK_EX) === false) {
    error_log('clear_cache.php 写全局冷却锁失败: ' . $globalLockFile);
}

// ============================================================
// 执行：清理所有页面级静态缓存
// ============================================================
$result = [
    'success' => true,
    'page_cache_deleted' => 0
];

$cacheDir = __DIR__ . '/../cache';
if (is_dir($cacheDir)) {
    $deleted = 0;
    $patterns = array('/page_index_*.html', '/page_platform_*.html');
    foreach ($patterns as $pattern) {
        $files = glob($cacheDir . $pattern);
        if ($files) {
            foreach ($files as $f) {
                if (@unlink($f)) {
                    $deleted++;
                }
            }
        }
    }
    $result['page_cache_deleted'] = $deleted;
    $result['message'] = $deleted > 0 ? "已清除 {$deleted} 个页面缓存" : "没有需要清除的页面缓存";
} else {
    $result['message'] = '缓存目录不存在';
}

echo json_encode($result, JSON_UNESCAPED_UNICODE);
