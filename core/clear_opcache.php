<?php
/**
 * OPcache 清理接口（带密钥保护）
 * ============================================================
 * 功能：
 *   1. 清理 PHP OPcache（字节码缓存）
 *   2. 清理页面级静态缓存（page_index_*.html）
 *
 * 使用方法：
 *   /core/clear_opcache.php?key=你的密钥
 *
 * 返回：JSON
 *   {
 *     "success": true/false,
 *     "message": "...",
 *     "page_cache_deleted": N
 *   }
 * ============================================================
 */

require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');

$key = isset($_GET['key']) ? $_GET['key'] : '';
$validKey = $GLOBALS['site_config']['opcache_clear_key'];

if ($key !== $validKey) {
    echo json_encode([
        'success' => false,
        'message' => '密钥错误'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$result = [
    'success' => true,
    'opcache_enabled' => false,
    'status' => null,
    'reset_result' => false,
    'page_cache_cleared' => false,
    'page_cache_deleted' => 0
];

// 清理 OPcache
if (function_exists('opcache_get_status')) {
    $status = opcache_get_status(false);
    $result['opcache_enabled'] = !empty($status);
    $result['status'] = $status ? [
        'cached_scripts' => isset($status['opcache_statistics']['num_cached_scripts']) ? $status['opcache_statistics']['num_cached_scripts'] : 0,
        'hits' => isset($status['opcache_statistics']['hits']) ? $status['opcache_statistics']['hits'] : 0,
        'memory_usage' => isset($status['memory_usage']) ? $status['memory_usage'] : []
    ] : null;
}

if (function_exists('opcache_reset')) {
    $result['reset_result'] = opcache_reset();
}

if (function_exists('opcache_get_status') && $result['reset_result']) {
    $afterStatus = opcache_get_status(false);
    $result['after_reset'] = $afterStatus ? [
        'cached_scripts' => isset($afterStatus['opcache_statistics']['num_cached_scripts']) ? $afterStatus['opcache_statistics']['num_cached_scripts'] : 0
    ] : null;
}

// 清理所有页面级静态缓存
// page_index_*.html（首页）+ page_platform_*.html（平台详情页）
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
    $result['page_cache_cleared'] = ($deleted > 0);
    $result['page_cache_deleted'] = $deleted;
}

echo json_encode($result, JSON_UNESCAPED_UNICODE);
?>
