<?php
/**
 * ============================================================
 * 状态面板 - 只读运维页面
 * ============================================================
 *
 * 功能：查看每个平台的缓存状态
 *   - 最后抓取时间（captured_at）
 *   - 条目数
 *   - 成功/过期/未抓取
 *   - 缓存文件大小
 *
 * URL：
 *   /status            ← Apache .htaccess rewrite 自动生效
 *   /status.php        ← 所有环境零配置可用
 *
 * 安全：不敏感，只暴露缓存状态，不需要密钥保护
 * CSS：/assets/css/status.css（独立文件）
 * ============================================================
 */

require_once __DIR__ . '/function.php';
$cfg = $GLOBALS['site_config'];

// ============================================================
// 第 1 步：扫描今天的缓存目录
// ============================================================
$todayDir = date('Ymd');
$cacheDir = rtrim($cfg['cache_dir'], '/') . '/' . $todayDir;
$sources = $GLOBALS['sources'];
$now = time();

// 统计用
$stats = [
    'total'    => count($sources),
    'success'  => 0,  // 缓存存在且未过期
    'expired'  => 0,  // 缓存存在但已过期
    'missing'  => 0,  // 没有缓存文件
    'total_size' => 0, // 总文件大小
];

$platforms = [];  // 每个平台的状态

foreach ($sources as $source) {
    $id = $source['id'];
    $file = $cacheDir . '/' . $id . '.json';

    $row = [
        'id'           => $id,
        'name'         => $source['name'],
        'icon'         => $source['icon'],
        'colorClass'   => $source['colorClass'],
        'url'          => $source['url'],
        'captured_at'  => null,
        'count'        => 0,
        'size'         => 0,
        'status'       => 'missing',   // success / expired / missing
        'age_text'     => '-',
    ];

    if (file_exists($file)) {
        $row['size'] = filesize($file);
        $stats['total_size'] += $row['size'];

        $content = @file_get_contents($file);
        if ($content !== false) {
            $cache = json_decode($content, true);
            if ($cache !== null && isset($cache['captured_at'])) {
                $row['captured_at'] = intval($cache['captured_at']);

                // 数据条数：兼容两种格式
                // 格式 A：cache['data']['data'] 是数组（handler 返回的原始结构）
                // 格式 B：cache['data'] 直接是数组（简单情况）
                $count = 0;
                if (isset($cache['data']['data']) && is_array($cache['data']['data'])) {
                    $count = count($cache['data']['data']);
                } elseif (isset($cache['data']) && is_array($cache['data']) && isset($cache['data'][0])) {
                    $count = count($cache['data']);
                }
                $row['count'] = $count;

                // 过期判断
                $age = $now - $row['captured_at'];
                if ($age < $cfg['cache_ttl']) {
                    $row['status'] = 'success';
                    $stats['success']++;
                } else {
                    $row['status'] = 'expired';
                    $stats['expired']++;
                }
                $row['age_text'] = formatAge($age);
            }
        }
    } else {
        $stats['missing']++;
    }

    $platforms[] = $row;
}

// 按状态排序：expired/missing 在前（运维优先关注异常），success 在后
// 同一状态组内保持 config.php 里定义的原始顺序
usort($platforms, function($a, $b) {
    $order = ['expired' => 0, 'missing' => 1, 'success' => 2];
    return $order[$a['status']] - $order[$b['status']];
});

// 辅助函数：格式化时间差
function formatAge($seconds) {
    if ($seconds < 60)  return $seconds . ' 秒前';
    if ($seconds < 3600) return intval($seconds / 60) . ' 分钟前';
    if ($seconds < 86400) return intval($seconds / 3600) . ' 小时前';
    return intval($seconds / 86400) . ' 天前';
}
// 辅助函数：格式化文件大小
function formatSize($bytes) {
    if ($bytes < 1024) return $bytes . ' B';
    if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
    return round($bytes / 1048576, 2) . ' MB';
}

// ============================================================
// SEO 变量（必须在 include header.php 之前设置）
// 运维页面设为 noindex，不让搜索引擎收录
// ============================================================
$pageTitle   = '平台状态面板 - ' . $cfg['site_name'];
$pageDesc    = '查看 ' . $cfg['site_name'] . ' 各平台缓存状态：最后抓取时间、数据条目、缓存文件大小、是否过期。';
$pageKeywords = '状态面板,缓存监控,' . $cfg['site_name'] . '状态';
$pageRobots  = 'noindex, nofollow';
$canonical   = $cfg['seo_domain'] . '/status.php';

include __DIR__ . '/home/header.php';
?>
    <link rel="stylesheet" href="/assets/css/status.css">

    <div class="status-wrap">
        <nav class="status-breadcrumb">
            <a href="/">首页</a> <span>›</span> <span>状态面板</span>
            <a href="/status.php" class="status-refresh">↻ 刷新</a>
        </nav>

        <!-- 汇总卡片 -->
        <div class="status-summary">
            <div class="summary-card summary-total">
                <div class="summary-num"><?php echo $stats['total']; ?></div>
                <div class="summary-label">平台总数</div>
            </div>
            <div class="summary-card summary-ok">
                <div class="summary-num"><?php echo $stats['success']; ?></div>
                <div class="summary-label"><i class="iconfont icon-dui" style="font-size:12px; margin-right:2px;"></i>正常</div>
            </div>
            <div class="summary-card summary-warn">
                <div class="summary-num"><?php echo $stats['expired']; ?></div>
                <div class="summary-label"><i class="iconfont icon-shijian" style="font-size:12px; margin-right:2px;"></i>已过期</div>
            </div>
            <div class="summary-card summary-err">
                <div class="summary-num"><?php echo $stats['missing']; ?></div>
                <div class="summary-label"><i class="iconfont icon-cuowu" style="font-size:12px; margin-right:2px;"></i>未抓取</div>
            </div>
            <div class="summary-card summary-size">
                <div class="summary-num"><?php echo formatSize($stats['total_size']); ?></div>
                <div class="summary-label">今日缓存大小</div>
            </div>
        </div>

        <!-- 状态表格 -->
        <div class="status-table-wrap">
            <table class="status-table">
                <thead>
                    <tr>
                        <th style="width:40px;">#</th>
                        <th>平台</th>
                        <th style="width:120px;">最后抓取</th>
                        <th style="width:80px;">条目</th>
                        <th style="width:80px;">大小</th>
                        <th style="width:80px;">状态</th>
                        <th style="width:100px;">操作</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($platforms as $i => $p): ?>
                    <tr class="status-row status-<?php echo $p['status']; ?>">
                        <td class="status-idx"><?php echo $i + 1; ?></td>
                        <td>
                            <span class="iconfont <?php echo escapeHtml($p['icon']); ?> platform-icon <?php echo escapeHtml($p['colorClass']); ?>" style="font-size:18px; margin-right:6px;"></span>
                            <a href="/platform.php/<?php echo $p['id']; ?>" title="查看详情"><?php echo escapeHtml($p['name']); ?></a>
                        </td>
                        <td>
                            <?php if ($p['captured_at']): ?>
                                <div class="status-age"><?php echo $p['age_text']; ?></div>
                                <div class="status-time"><?php echo date('Y-m-d H:i:s', $p['captured_at']); ?></div>
                            <?php else: ?>
                                <span class="status-empty">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="status-count"><?php echo $p['count'] ?: '-'; ?></td>
                        <td><?php echo $p['size'] ? formatSize($p['size']) : '-'; ?></td>
                        <td>
                            <?php if ($p['status'] === 'success'): ?>
                                <span class="badge badge-ok">正常</span>
                            <?php elseif ($p['status'] === 'expired'): ?>
                                <span class="badge badge-warn">过期</span>
                            <?php else: ?>
                                <span class="badge badge-err">未抓取</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="/core/refresh.php?source=<?php echo $p['id']; ?>" class="status-action">刷新</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="status-footer">
            <p>缓存目录：<code>cache/<?php echo $todayDir; ?>/</code> &nbsp;·&nbsp; 缓存有效期：<?php echo intval($cfg['cache_ttl'] / 3600); ?> 小时</p>
            <p class="status-note">提示：页面静态缓存（page_index_*.html / page_platform_*.html）不在此面板显示。点击右上角「清理缓存」按钮可一键清除所有缓存。</p>
        </div>
    </div>

<?php include __DIR__ . '/home/footer.php'; ?>
