<?php
/**
 * 首页数据获取模块（含文件缓存）
 *
 * 缓存策略：
 * - 每个平台一个 JSON 文件，保存在 /cache/ 目录
 * - 缓存有效期：6 小时（可在 config.php 中调整）
 * - 通过 force=1 参数可以跳过缓存，立即刷新
 *
 * 注意：本文件从 root/config.php 读取 $GLOBALS，因此不会被直接 include。
 * 请通过 root/function.php 间接引用。
 */

/**
 * 确保缓存目录存在并可写
 */
function ensureCacheDir() {
    $cacheDir = $GLOBALS['cache_dir'];
    if (!is_dir($cacheDir)) {
        @mkdir($cacheDir, 0755, true);
    }
    // 创建 .htaccess 保护缓存目录
    $htaccess = $cacheDir . '/.htaccess';
    if (!file_exists($htaccess)) {
        @file_put_contents($htaccess, "Deny from all\n");
    }
}

/**
 * 获取缓存文件路径（基于写入时的精确时间戳）
 * 写入时: baidu_20260621_194950.json
 * 读取时: 通过 glob 找到最新的匹配文件
 * @param string $sourceId 数据源 ID
 * @param int|null $timestamp 写入时的时间戳（读取时传 null 自动查找最新文件）
 * @return string
 */
function getCacheFilePath($sourceId, $timestamp = null) {
    $dir = rtrim($GLOBALS['cache_dir'], '/');
    if ($timestamp !== null) {
        // 写入模式：使用精确时间戳生成文件名
        $filename = $sourceId . '_' . date('Ymd_His', $timestamp) . '.json';
        return $dir . '/' . $filename;
    }
    // 读取模式：glob 查找最新的匹配文件
    $pattern = $dir . '/' . $sourceId . '_*.json';
    $files = glob($pattern);
    if ($files && !empty($files)) {
        // 精确匹配：确保是 {sourceId}_YYYYMMDD_HHMMSS.json 格式，防止 zhihu 匹配到 zhihu_daily
        $validFiles = array();
        $prefix = $sourceId . '_';
        foreach ($files as $f) {
            $basename = basename($f);
            if (strpos($basename, $prefix) === 0) {
                $rest = substr($basename, strlen($prefix));
                // 剩余部分应为 YYYYMMDD_HHMMSS.json 格式（15位数字+下划线+.json）
                if (preg_match('/^\d{8}_\d{6}\.json$/', $rest)) {
                    $validFiles[] = $f;
                }
            }
        }
        if (!empty($validFiles)) {
            // 按修改时间倒序，取最新的
            usort($validFiles, function($a, $b) { return filemtime($b) - filemtime($a); });
            return $validFiles[0];
        }
    }
    return $dir . '/' . $sourceId . '_' . date('Ymd_His') . '.json';
}

/**
 * 从缓存读取数据
 * @param string $sourceId 数据源 ID
 * @return array|null 返回 [ 'time' => 时间戳, 'data' => 数据 ] 或 null（缓存失效/不存在）
 */
function readCache($sourceId) {
    $file = getCacheFilePath($sourceId);
    if (!file_exists($file)) {
        return null;
    }

    $content = @file_get_contents($file);
    if ($content === false || empty($content)) {
        return null;
    }

    $cache = json_decode($content, true);
    if ($cache === null || !isset($cache['time']) || !isset($cache['data'])) {
        return null;
    }

    // 检查是否过期
    $age = time() - $cache['time'];
    if ($age >= $GLOBALS['cache_ttl']) {
        return null;
    }

    return $cache;
}

/**
 * 写入缓存
 *
 * 做了三件事：
 *   1. 生成带时间戳的新缓存文件
 *   2. 用临时文件 + rename 保证原子写入（防止写到一半被读取）
 *   3. 【方案A】写入成功后，顺手删除这个平台所有的旧缓存文件，只留刚写入的这一份
 *
 * @param string $sourceId 数据源 ID，比如 "baidu"、"zhihu"
 * @param array  $data     要缓存的数据数组
 * @return bool  是否写入成功
 */
function writeCache($sourceId, $data) {
    ensureCacheDir();

    $now = time();
    $cache = [
        'time' => $now,
        'data' => $data
    ];

    // 生成新的缓存文件名（带精确时间戳）
    $newFile = getCacheFilePath($sourceId, $now);
    $json = json_encode($cache, JSON_UNESCAPED_UNICODE);

    // 使用临时文件 + rename 保证原子写入
    // （防止写到一半时被读取，导致拿到不完整的 JSON）
    $tmpFile = $newFile . '.tmp';
    if (@file_put_contents($tmpFile, $json) === false) {
        return false;
    }

    if (!@rename($tmpFile, $newFile)) {
        @unlink($tmpFile);
        return false;
    }

    // ============================================================
    // 【方案 A】写入成功后，清理同一平台的旧缓存文件
    // ============================================================
    // 思路：glob 找出这个平台所有的缓存文件，删掉除了刚写入这份之外的
    // 这样 cache 目录里每个平台永远只留最新的 1 份缓存
    // ============================================================
    $dir = rtrim($GLOBALS['cache_dir'], '/');
    $pattern = $dir . '/' . $sourceId . '_*.json';
    $allFiles = glob($pattern);

    if (!empty($allFiles)) {
        $prefix = $sourceId . '_';
        foreach ($allFiles as $f) {
            // 跳过刚写入的新文件
            if ($f === $newFile) {
                continue;
            }
            // 精确校验文件名格式，防止误删（比如 zhihu 不小心删了 zhihu_daily 的）
            $basename = basename($f);
            if (strpos($basename, $prefix) !== 0) {
                continue;
            }
            $rest = substr($basename, strlen($prefix));
            if (preg_match('/^\d{8}_\d{6}\.json$/', $rest)) {
                @unlink($f);
            }
        }
    }

    return true;
}

/**
 * 清除某数据源的所有缓存文件（支持新旧格式）
 * @param string $sourceId 数据源 ID
 * @return bool
 */
function clearCache($sourceId) {
    $dir = rtrim($GLOBALS['cache_dir'], '/');
    $newFiles = glob($dir . '/' . $sourceId . '_*.json');
    // 精确过滤，防止 zhihu 清除 zhihu_daily 的缓存
    $validFiles = array();
    $prefix = $sourceId . '_';
    foreach ($newFiles ?: [] as $f) {
        $basename = basename($f);
        if (strpos($basename, $prefix) === 0) {
            $rest = substr($basename, strlen($prefix));
            if (preg_match('/^\d{8}_\d{6}\.json$/', $rest)) {
                $validFiles[] = $f;
            }
        }
    }
    $oldFile = $dir . '/' . $sourceId . '.json';
    $allFiles = array_merge($validFiles, file_exists($oldFile) ? [$oldFile] : []);
    foreach ($allFiles as $f) { @unlink($f); }
    return true;
}

/**
 * 获取某个数据源的缓存时间（用于显示"xx:xx 更新"）
 * @param string $sourceId 数据源 ID
 * @return int|null 时间戳或 null（缓存不存在）
 */
function getCacheTime($sourceId) {
    $file = getCacheFilePath($sourceId);
    if (!file_exists($file)) return null;
    $content = @file_get_contents($file);
    if ($content === false) return null;
    $cache = json_decode($content, true);
    return isset($cache['time']) ? $cache['time'] : null;
}

/**
 * 直接调用 API 处理器获取数据（避免 HTTP 请求回环，更可靠、更快速）
 *
 * @param string $sourceId 数据源 ID
 * @param int $limit 条数
 * @return array [ 'success' => bool, 'data' => [...], 'error' => string|null ]
 */
function fetchFromApi($sourceId, $limit) {
    $baseDir = dirname(__DIR__);  // 项目根目录（core/ 的上一级）

    // 1. 加载平台白名单（api/utils/config.php）
    $configFile = $baseDir . '/api/utils/config.php';
    if (!file_exists($configFile)) {
        return [
            'success' => false,
            'data' => [],
            'error' => '配置文件不存在: api/utils/config.php'
        ];
    }
    $config = require $configFile;
    $platforms = isset($config['platforms']) && is_array($config['platforms']) ? $config['platforms'] : [];

    if (!in_array($sourceId, $platforms, true)) {
        return [
            'success' => false,
            'data' => [],
            'error' => '不支持的平台: ' . $sourceId
        ];
    }

    // 2. 加载处理器文件（api/V1/{platform}.php）
    $handlerFile = $baseDir . '/api/V1/' . $sourceId . '.php';
    if (!file_exists($handlerFile)) {
        return [
            'success' => false,
            'data' => [],
            'error' => '处理器不存在: ' . basename($handlerFile)
        ];
    }

    require_once $handlerFile;

    // 3. 实例化处理器类
    $className = '';
    $parts = explode('_', strtolower($sourceId));
    foreach ($parts as $part) {
        $className .= ucfirst($part);
    }
    $className .= 'HotSearch';

    if (!class_exists($className)) {
        return [
            'success' => false,
            'data' => [],
            'error' => '处理器类不存在: ' . $className
        ];
    }

    // 4. 执行处理器获取数据
    try {
        $instance = new $className();
        $result = $instance->handle($limit);

        if (is_array($result) && isset($result['success']) && $result['success']) {
            $items = isset($result['data']) && is_array($result['data']) ? $result['data'] : [];
            return [
                'success' => true,
                'data' => $items,
                'error' => null
            ];
        }

        return [
            'success' => false,
            'data' => [],
            'error' => is_array($result) && isset($result['message']) ? $result['message'] : '获取数据失败'
        ];
    } catch (Throwable $e) {
        return [
            'success' => false,
            'data' => [],
            'error' => '执行错误: ' . $e->getMessage()
        ];
    }
}

/**
 * 获取单个数据源的数据（带缓存）
 *
 * @param string $sourceId 数据源 ID
 * @param int $limit 条数
 * @param bool $forceRefresh 是否强制刷新（跳过缓存）
 * @return array [ 'source' => ..., 'data' => [...], 'error' => string|null, 'time' => 时间戳 ]
 */
function fetchApiData($sourceId, $limit = null, $forceRefresh = false) {
    if ($limit === null) {
        $limit = isset($GLOBALS['apiLimit']) ? $GLOBALS['apiLimit'] : 20;
    }

    // 1. 非强制模式：尝试读取缓存
    if (!$forceRefresh) {
        $cache = readCache($sourceId);
        if ($cache !== null) {
            return array_merge($cache['data'], [
                'time' => $cache['time']
            ]);
        }
    }

    // 2. 从 API 拉取
    $result = fetchFromApi($sourceId, $limit);

    // 3. 成功则写入缓存
    if ($result['success']) {
        writeCache($sourceId, $result);
    }

    // 4. 返回数据（带上当前时间戳）
    $result['time'] = time();
    return $result;
}

/**
 * 获取所有数据源的数据
 *
 * @param array $sources 数据源配置数组
 * @param int $limit 每个数据源的条数
 * @param bool $forceRefresh 是否强制刷新
 * @return array 所有数据源的数据
 */
function fetchAllSourcesData($sources, $limit = 10, $forceRefresh = false) {
    $cardsData = [];

    foreach ($sources as $source) {
        $result = fetchApiData($source['id'], $limit, $forceRefresh);
        $cardsData[$source['id']] = [
            'source' => $source,
            'data' => $result['data'],
            'error' => $result['error'],
            'time' => isset($result['time']) ? $result['time'] : time()
        ];
    }

    return $cardsData;
}

// ============================================================
// 【方案 B】全局过期缓存清理（概率触发，兜底保障）
// ============================================================
//
// 为什么需要这一层？
//   方案 A 只能清理"正在被访问的平台"的旧缓存。
//   如果某个平台以后再也没人访问了（比如被从配置里移除了），
//   它的缓存文件就会一直留在 cache 目录里。
//
// 工作原理：
//   每次访问首页时，有 1% 的概率触发一次全量扫描，
//   把所有超过 24 小时没更新过的缓存文件都删掉。
//
// 为什么用概率而不是每次都扫？
//   全量扫描需要遍历目录里的所有文件，如果 cache 文件很多，
//   每次都扫会影响页面加载速度。1% 的概率基本无感，
//   但只要网站有访问量，迟早会清理干净。
// ============================================================

/**
 * 清理所有过期的缓存文件（全局扫描，兜底用）
 *
 * @param int $expireSeconds 过期时间，单位秒。默认 86400 秒 = 24 小时
 * @return int 清理掉的文件数量
 */
function cleanAllExpiredCache($expireSeconds = 86400) {
    $cacheDir = rtrim($GLOBALS['cache_dir'], '/');

    if (!is_dir($cacheDir)) {
        return 0;
    }

    $now = time();
    $count = 0;

    // 找出 cache 目录下所有 .json 文件
    $files = glob($cacheDir . '/*.json');
    if (empty($files)) {
        return 0;
    }

    foreach ($files as $file) {
        // 跳过 .htaccess 和非文件
        if (!is_file($file)) {
            continue;
        }

        // 跳过 .htaccess 等非缓存文件
        $basename = basename($file);
        if ($basename === '.htaccess') {
            continue;
        }

        // 只清理符合命名规范的缓存文件
        // 格式: {platform}_YYYYMMDD_HHMMSS.json
        if (!preg_match('/^[a-z0-9_]+_\d{8}_\d{6}\.json$/', $basename)) {
            continue;
        }

        // 用文件修改时间判断是否过期
        $mtime = @filemtime($file);
        if ($mtime === false) {
            continue;
        }

        if ($now - $mtime >= $expireSeconds) {
            if (@unlink($file)) {
                $count++;
            }
        }
    }

    return $count;
}

/**
 * 概率触发全局缓存清理
 *
 * 调用方式：在页面入口（function.php）直接调用这个函数就行。
 * 它会自己判断是否要执行清理，大部分时候什么都不做，直接返回。
 *
 * @param int $percent 触发概率（百分比），默认 1%
 * @param int $expireSeconds 过期时间，默认 24 小时
 * @return int 实际清理的文件数（没触发时返回 0）
 */
function maybeCleanExpiredCache($percent = 1, $expireSeconds = 86400) {
    // 概率判断：生成 1~100 的随机数，如果 <= percent 就执行
    // 比如 percent=1，就有 1% 的概率触发
    if (mt_rand(1, 100) <= $percent) {
        return cleanAllExpiredCache($expireSeconds);
    }
    return 0;
}
?>
