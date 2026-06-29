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
$platforms = isset($config['platforms']) && is_array($config['platforms']) ? $config['platforms'] : [];

if (!in_array($platform, $platforms, true)) {
    http_response_code(501);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => '不支持的平台: ' . $platform . '。已注册: ' . implode(', ', $platforms)
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$handlerFile = $baseDir . '/api/V1/' . $platform . '.php';
if (!file_exists($handlerFile)) {
    http_response_code(501);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => '处理器不存在: ' . $platform . '.php'], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once $handlerFile;
// 支持下划线分隔的命名，如 zhihu_daily → ZhihuDailyHotSearch
$className = '';
$parts = explode('_', strtolower($platform));
foreach ($parts as $part) {
    $className .= ucfirst($part);
}
$className .= 'HotSearch';

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
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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
