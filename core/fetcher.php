<?php
/**
 * 首页数据获取模块（含文件缓存 + 历史永久存档）
 *
 * 缓存策略（按日期目录永久保留）：
 *   cache/
 *   ├── 20261112/          ← 今天（不断被刷新覆盖）
 *   │   ├── baidu.json     ← captured_at: 1731346800
 *   │   ├── weibo.json
 *   │   └── ...
 *   ├── 20261111/          ← 昨天（0点后不再写入，永久冻结）
 *   │   └── baidu.json
 *   └── ...
 *
 * JSON 结构：
 *   {
 *     "captured_at": 1731346800,    ← 精确抓取时间（秒级时间戳，核心！）
 *     "date": "2026-11-12",         ← 日期字符串（方便阅读）
 *     "data": [...]                  ← 热榜数据
 *   }
 *
 * 为什么 captured_at 很重要？
 *   - 缓存路径改成按日期目录后，文件名不再带精确时间
 *   - 需要 JSON 里有精确时间来判断"6小时是否过期"
 *   - 首页/详情页显示"5 分钟前更新"也需要
 *
 * 历史页访问：
 *   /weibo/2026-11-10  → 读 cache/20261110/weibo.json
 *   不存在 → 404
 *
 * 注意：本文件从 root/config.php 读取 $GLOBALS，因此不会被直接 include。
 * 请通过 root/function.php 间接引用。
 */

/**
 * 确保缓存根目录存在并可写
 * 同时为当天自动创建日期目录
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

    // 自动创建今天的日期目录
    $todayDir = $cacheDir . '/' . date('Ymd');
    if (!is_dir($todayDir)) {
        @mkdir($todayDir, 0755, true);
    }
}

/**
 * 获取缓存文件路径
 *
 * @param string $sourceId  数据源 ID，比如 "baidu"、"zhihu_daily"
 * @param string|null $dateDir 日期目录名 "20261112"，null = 今天
 * @return string 文件完整路径
 */
function getCacheFilePath($sourceId, $dateDir = null) {
    if ($dateDir === null) {
        $dateDir = date('Ymd');
    }
    $dir = rtrim($GLOBALS['cache_dir'], '/') . '/' . $dateDir;
    return $dir . '/' . $sourceId . '.json';
}

/**
 * 从缓存读取数据（读今天的目录，用于首页）
 *
 * @param string $sourceId 数据源 ID
 * @return array|null [ 'captured_at' => 时间戳, 'data' => [...], 'date' => '2026-11-12' ]
 *                    或 null（缓存不存在/过期/损坏）
 */
function readCache($sourceId) {
    $file = getCacheFilePath($sourceId);
    return readCacheFile($file);
}

/**
 * 从缓存读取指定日期的数据（用于历史页）
 *
 * @param string $sourceId 数据源 ID
 * @param string $dateStr  日期字符串，支持多种格式：
 *                         "2026-11-12" / "2026/11/12" / "20261112"
 * @return array|null 同 readCache()，或 null（那天没有数据）
 */
function readCacheByDate($sourceId, $dateStr) {
    // 统一转成 Ymd 格式
    $dateDir = preg_replace('/[^0-9]/', '', $dateStr);  // 去掉所有非数字
    if (strlen($dateDir) !== 8) {
        return null;  // 格式不对
    }
    $file = getCacheFilePath($sourceId, $dateDir);
    return readCacheFile($file);
}

/**
 * 内部通用读取函数
 */
function readCacheFile($file) {
    if (!file_exists($file)) {
        return null;
    }

    $content = @file_get_contents($file);
    if ($content === false || empty($content)) {
        return null;
    }

    $cache = json_decode($content, true);
    if ($cache === null || !isset($cache['captured_at']) || !isset($cache['data'])) {
        return null;
    }

    // 过期判断：用 captured_at
    // 但注意：历史页读旧日期文件时，我们不应该判断过期！
    // 只有读今天的文件才需要判断过期
    $todayDir = date('Ymd');
    $dirName = basename(dirname($file));
    if ($dirName === $todayDir) {
        $age = time() - intval($cache['captured_at']);
        if ($age >= $GLOBALS['cache_ttl']) {
            return null;  // 今天的缓存过期了，返回 null 触发重新抓取
        }
    }
    // 不是今天的目录 → 历史数据 → 永远不过期

    return $cache;
}

/**
 * 写入缓存（写到今天的日期目录）
 *
 * 做了三件事：
 *   1. 确保今天的日期目录存在
 *   2. 用临时文件 + rename 保证原子写入
 *   3. 不再删除旧文件！历史目录永久保留
 *      （一天只有一份，刷新只是覆盖今天目录里的那一份）
 *
 * @param string $sourceId 数据源 ID
 * @param array  $data     要缓存的数据数组
 * @return bool 是否写入成功
 */
function writeCache($sourceId, $data) {
    ensureCacheDir();

    $now = time();
    $cache = [
        'captured_at' => $now,          // 精确抓取时间（核心字段）
        'date'        => date('Y-m-d', $now),
        'data'        => $data
    ];

    $file = getCacheFilePath($sourceId);  // 今天的目录
    $json = json_encode($cache, JSON_UNESCAPED_UNICODE);

    // 原子写入：临时文件 + rename
    $tmpFile = $file . '.tmp';
    if (@file_put_contents($tmpFile, $json) === false) {
        return false;
    }

    if (!@rename($tmpFile, $file)) {
        @unlink($tmpFile);
        return false;
    }

    return true;
}

/**
 * 清除某数据源的缓存（只清今天目录里的那份）
 *
 * @param string $sourceId 数据源 ID
 * @return bool
 */
function clearCache($sourceId) {
    $file = getCacheFilePath($sourceId);
    @unlink($file);
    // 旧格式迁移：如果还有旧文件也一起清
    $dir = rtrim($GLOBALS['cache_dir'], '/');
    $oldPattern = $dir . '/' . $sourceId . '_*.json';
    $prefix = $sourceId . '_';
    foreach (glob($oldPattern) ?: [] as $f) {
        $basename = basename($f);
        if (strpos($basename, $prefix) === 0) {
            @unlink($f);
        }
    }
    return true;
}

/**
 * 获取某个数据源的缓存时间（用于显示"xx:xx 更新"）
 *
 * @param string $sourceId 数据源 ID
 * @return int|null 时间戳或 null
 */
function getCacheTime($sourceId) {
    $file = getCacheFilePath($sourceId);
    if (!file_exists($file)) return null;
    $content = @file_get_contents($file);
    if ($content === false) return null;
    $cache = json_decode($content, true);
    return isset($cache['captured_at']) ? intval($cache['captured_at']) : null;
}

/**
 * 直接调用 API 处理器获取数据（避免 HTTP 请求回环，更可靠、更快速）
 */
function fetchFromApi($sourceId, $limit) {
    $baseDir = dirname(__DIR__);

    $configFile = $baseDir . '/api/utils/config.php';
    if (!file_exists($configFile)) {
        return ['success' => false, 'data' => [], 'error' => '配置文件不存在'];
    }
    $config = require $configFile;
    $platforms = isset($config['platforms']) && is_array($config['platforms']) ? $config['platforms'] : [];

    if (!in_array($sourceId, $platforms, true)) {
        return ['success' => false, 'data' => [], 'error' => '不支持的平台: ' . $sourceId];
    }

    $handlerFile = $baseDir . '/api/V1/' . $sourceId . '.php';
    if (!file_exists($handlerFile)) {
        return ['success' => false, 'data' => [], 'error' => '处理器不存在'];
    }
    require_once $handlerFile;

    $className = '';
    $parts = explode('_', strtolower($sourceId));
    foreach ($parts as $part) { $className .= ucfirst($part); }
    $className .= 'HotSearch';
    if (preg_match('/^\d/', $className)) { $className = '_' . $className; }

    if (!class_exists($className)) {
        return ['success' => false, 'data' => [], 'error' => '处理器类不存在: ' . $className];
    }

    try {
        $instance = new $className();
        $result = $instance->handle($limit);

        if (is_array($result) && isset($result['success']) && $result['success']) {
            $items = isset($result['data']) && is_array($result['data']) ? $result['data'] : [];
            return ['success' => true, 'data' => $items, 'error' => null];
        }
        return ['success' => false, 'data' => [], 'error' => '获取数据失败'];
    } catch (Throwable $e) {
        return ['success' => false, 'data' => [], 'error' => '执行错误: ' . $e->getMessage()];
    }
}

/**
 * 获取单个数据源的数据（带缓存）
 */
function fetchApiData($sourceId, $limit = null, $forceRefresh = false) {
    if ($limit === null) {
        $limit = isset($GLOBALS['apiLimit']) ? $GLOBALS['apiLimit'] : 20;
    }

    // 1. 非强制模式：尝试读取今天的缓存
    if (!$forceRefresh) {
        $cache = readCache($sourceId);
        if ($cache !== null) {
            return array_merge($cache['data'], [
                'time' => intval($cache['captured_at'])
            ]);
        }
    }

    // 2. 从 API 拉取
    $result = fetchFromApi($sourceId, $limit);

    // 3. 成功则写入缓存（到今天目录）
    if ($result['success']) {
        writeCache($sourceId, $result);
    }

    // 4. 返回数据
    $result['time'] = time();
    return $result;
}

/**
 * 获取所有数据源的数据
 */
function fetchAllSourcesData($sources, $limit = 10, $forceRefresh = false) {
    $cardsData = [];
    foreach ($sources as $source) {
        $result = fetchApiData($source['id'], $limit, $forceRefresh);
        $cardsData[$source['id']] = [
            'source' => $source,
            'data'   => $result['data'],
            'error'  => $result['error'],
            'time'   => isset($result['time']) ? $result['time'] : time()
        ];
    }
    return $cardsData;
}


// ============================================================
// 【方案 B】全局过期缓存清理（概率触发，兜底保障）
// ============================================================
// 改动点：
//   - 历史 JSON 永久保留，不再过期清理
//   - 只清理：页面静态缓存 + cache/ 根目录的旧格式 JSON
//   - 防止磁盘被垃圾文件塞满
// ============================================================

/**
 * 清理所有过期的缓存文件
 *
 * @param int $expireSeconds 过期时间，默认 24 小时
 * @return int 清理掉的文件数量
 */
function cleanAllExpiredCache($expireSeconds = 86400) {
    $cacheDir = rtrim($GLOBALS['cache_dir'], '/');
    if (!is_dir($cacheDir)) return 0;

    $now = time();
    $count = 0;

    // 1. 清理 cache/ 根目录的旧格式 JSON（迁移用，历史目录里的不碰）
    foreach (glob($cacheDir . '/*.json') ?: [] as $file) {
        if (!is_file($file)) continue;
        $basename = basename($file);
        if ($basename === '.htaccess') continue;
        // 旧格式：baidu_20261112_090000.json
        // 新格式都在子目录里，不会出现在根目录
        if (!preg_match('/^[a-z0-9_]+_\d{8}_\d{6}\.json$/', $basename)) continue;
        $mtime = @filemtime($file);
        if ($mtime !== false && ($now - $mtime) >= $expireSeconds) {
            if (@unlink($file)) $count++;
        }
    }

    // 2. 清理页面级静态缓存
    $pagePatterns = ['/page_index_*.html', '/page_platform_*.html'];
    foreach ($pagePatterns as $pat) {
        foreach (glob($cacheDir . $pat) ?: [] as $f) {
            $mtime = @filemtime($f);
            if ($mtime !== false && ($now - $mtime) >= $expireSeconds) {
                if (@unlink($f)) $count++;
            }
        }
    }

    return $count;
}

/**
 * 概率触发全局缓存清理
 */
function maybeCleanExpiredCache($percent = 1, $expireSeconds = 86400) {
    if (mt_rand(1, 100) <= $percent) {
        return cleanAllExpiredCache($expireSeconds);
    }
    return 0;
}
?>
