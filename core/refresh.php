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
 */

// 引入核心模块（通过根目录的 function.php 统一加载 config / helper / fetcher）
require_once dirname(__DIR__) . '/function.php';

// 设置响应头（允许跨域访问，用于 AJAX 调用）
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

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
