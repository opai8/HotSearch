<?php
/**
 * ============================================================
 * API 调用统计页 - 独立展示 cache/_api_stats.json
 * ============================================================
 *
 * 功能：展示 API 平台调用次数、force=1 次数、7 天趋势
 * 路由：/stats → /stats.php（nginx.conf rewrite）
 *
 * SEO：noindex, nofollow（运维/内部页面）
 * ============================================================
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/function.php';

$cfg = $GLOBALS['site_config'];

// ============================================================
// SEO 变量（noindex，内部运维页）
// ============================================================
$pageTitle   = 'API 调用统计 - ' . $cfg['site_name'];
$pageDesc    = '站长内部 API 调用统计面板，查看各平台热搜 API 的调用次数和强制爬虫情况。';
$pageKeywords = 'API统计,调用统计,爬虫统计';
$pageRobots  = 'noindex, nofollow';
$canonical   = $cfg['seo_domain'] . '/stats';
$breadcrumbJsonLd = '<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "BreadcrumbList",
    "itemListElement": [
        {"@type":"ListItem","position":1,"name":"首页","item":"' . $cfg['seo_domain'] . '/"},
        {"@type":"ListItem","position":2,"name":"API 调用统计","item":"' . $cfg['seo_domain'] . '/stats"}
    ]
}
</script>';

// ============================================================
// 读 cache/_api_stats.json
// 格式：{ "20261118": { "baidu": {"hits":42, "force":3}, ... }, ... }
// ============================================================
$apiStats = [
    'today'      => [],
    'today_total'=> 0,
    'today_force'=> 0,
    'total_7d'   => 0,
    'force_7d'   => 0,
    'days'       => [],
    'top_today'  => [],
    'top_7d'     => [],
    'platforms'  => [],   // 今日全平台（按 hits 倒序）
];

$statsFile = __DIR__ . '/cache/_api_stats.json';
if (file_exists($statsFile)) {
    $raw = json_decode(@file_get_contents($statsFile), true);
    if (is_array($raw)) {
        $todayKey = date('Ymd');

        // 1. 今日
        if (isset($raw[$todayKey]) && is_array($raw[$todayKey])) {
            $apiStats['today'] = $raw[$todayKey];
            $apiStats['today_total'] = array_sum(array_map(function($v) {
                return intval($v['hits'] ?? 0);
            }, $apiStats['today']));
            $apiStats['today_force'] = array_sum(array_map(function($v) {
                return intval($v['force'] ?? 0);
            }, $apiStats['today']));
            arsort($apiStats['today']);
            $apiStats['top_today'] = array_slice($apiStats['today'], 0, 5, true);
            $apiStats['platforms'] = $apiStats['today'];
        }

        // 2. 7 天聚合 + 按天汇总
        $platform7d = [];
        foreach ($raw as $dayKey => $platformData) {
            if (!is_array($platformData)) continue;
            $dayHits = 0; $dayForce = 0;
            foreach ($platformData as $p => $c) {
                $h = intval($c['hits'] ?? 0);
                $f = intval($c['force'] ?? 0);
                $dayHits += $h;
                $dayForce += $f;
                // 累计到平台 7 天
                if (!isset($platform7d[$p])) $platform7d[$p] = ['hits' => 0, 'force' => 0];
                $platform7d[$p]['hits'] += $h;
                $platform7d[$p]['force'] += $f;
            }
            $apiStats['days'][] = ['date' => $dayKey, 'hits' => $dayHits, 'force' => $dayForce];
            $apiStats['total_7d'] += $dayHits;
            $apiStats['force_7d'] += $dayForce;
        }
        // 按日期倒序
        usort($apiStats['days'], function($a, $b) { return $b['date'] <=> $a['date']; });
        // Top 7 平台（7 天累计 hits）
        arsort($platform7d);
        $apiStats['top_7d'] = array_slice($platform7d, 0, 7, true);
    }
}

// 缓存文件信息
$statsFileInfo = [
    'exists'  => file_exists($statsFile),
    'size'    => 0,
    'mtime'   => 0,
    'path'    => 'cache/_api_stats.json',
];
if ($statsFileInfo['exists']) {
    $statsFileInfo['size']  = filesize($statsFile);
    $statsFileInfo['mtime'] = filemtime($statsFile);
}

// 引入头部（header.php 会读 SEO 变量）
include __DIR__ . '/home/header.php';
?>
<link rel="stylesheet" href="/assets/css/status.css">
<link rel="stylesheet" href="/assets/css/stats.css">

<div class="status-wrap">
    <!-- 面包屑 -->
    <div class="status-breadcrumb">
        <a href="/">首页</a> &raquo; API 调用统计
        <span class="status-breadcrumb-right">
            <a href="/stats" class="status-refresh" id="statsRefreshBtn">↻ 刷新</a>
        </span>
    </div>

    <h1 class="stats-page-title">
        <span class="iconfont icon-shujuzhanbao stats-page-icon"></span>
        API 调用统计
    </h1>
    <p class="stats-page-desc">
        记录各平台公开 API 的每日调用次数。缓存文件：<code><?php echo escapeHtml($statsFileInfo['path']); ?></code>
        <?php if ($statsFileInfo['exists']): ?>
            · 大小 <?php echo number_format($statsFileInfo['size']); ?> B
            · 写入 <?php echo date('Y-m-d H:i:s', $statsFileInfo['mtime']); ?>
        <?php endif; ?>
    </p>

    <?php if (empty($apiStats['days'])): ?>
    <!-- 空数据态 -->
    <div class="stats-empty-wrap">
        <div class="stats-empty-icon">📊</div>
        <p>暂无 API 调用数据</p>
        <p class="stats-empty-hint">首次调用 API（如访问 <code>/api/baidu</code>）后会自动生成统计文件。</p>
    </div>
    <?php else: ?>

    <!-- 上排 4 个概览卡 -->
    <div class="stats-row">
        <div class="stats-card stats-blue">
            <div class="stats-card-num"><?php echo number_format($apiStats['today_total']); ?></div>
            <div class="stats-card-label">今日总调用</div>
        </div>
        <div class="stats-card stats-purple">
            <div class="stats-card-num"><?php echo number_format($apiStats['total_7d']); ?></div>
            <div class="stats-card-label">7 日总调用</div>
        </div>
        <div class="stats-card stats-warn">
            <div class="stats-card-num"><?php echo number_format($apiStats['today_force']); ?></div>
            <div class="stats-card-label">今日强制爬虫</div>
        </div>
        <div class="stats-card stats-ok">
            <div class="stats-card-num"><?php echo count($apiStats['platforms']); ?></div>
            <div class="stats-card-label">今日活跃平台</div>
        </div>
    </div>

    <!-- 趋势条：force 占比警示 -->
    <?php if ($apiStats['today_total'] > 0): ?>
    <?php $forceRate = round($apiStats['today_force'] / $apiStats['today_total'] * 100, 1); ?>
    <div class="stats-alert <?php echo $forceRate > 30 ? 'stats-alert-warn' : ($forceRate > 10 ? 'stats-alert-info' : ''); ?>">
        <span class="iconfont icon-tishi"></span>
        <span>
            今日 force=1 占比 <strong><?php echo $forceRate; ?>%</strong>
            （<?php echo number_format($apiStats['today_force']); ?> / <?php echo number_format($apiStats['today_total']); ?>）
            <?php if ($forceRate > 30): ?>
                — 较高 force 占比可能意味着缓存频繁失效或有人在狂刷强制爬虫。
            <?php elseif ($forceRate > 10): ?>
                — 中等 force 占比，正常波动。
            <?php else: ?>
                — 缓存命中率良好。
            <?php endif; ?>
        </span>
    </div>
    <?php endif; ?>

    <!-- 下方两个详情并排 -->
    <div class="stats-detail-row">
        <!-- 左：今日 Top 5 排行 -->
        <div class="stats-block">
            <h4>🏆 今日 Top 5 平台</h4>
            <ul class="stats-rank">
                <?php $rank = 1; foreach ($apiStats['top_today'] as $pid => $cnt): ?>
                <li>
                    <span class="rank-badge <?php echo $rank > 3 ? 'rank-n' : 'rank-' . $rank; ?>"><?php echo $rank; ?></span>
                    <span class="rank-name"><?php echo escapeHtml($pid); ?></span>
                    <span class="rank-hits"><?php echo number_format($cnt['hits']); ?> 次</span>
                    <?php if (!empty($cnt['force'])): ?>
                    <span class="rank-force" title="强制爬虫次数">⚡ <?php echo number_format($cnt['force']); ?></span>
                    <?php endif; ?>
                </li>
                <?php $rank++; endforeach; ?>
            </ul>
        </div>

        <!-- 右：7 天 Top 平台 -->
        <div class="stats-block">
            <h4>📈 7 日 Top 平台</h4>
            <ul class="stats-rank">
                <?php $rank = 1; foreach ($apiStats['top_7d'] as $pid => $cnt): ?>
                <li>
                    <span class="rank-badge <?php echo $rank > 3 ? 'rank-n' : 'rank-' . $rank; ?>"><?php echo $rank; ?></span>
                    <span class="rank-name"><?php echo escapeHtml($pid); ?></span>
                    <span class="rank-hits"><?php echo number_format($cnt['hits']); ?> 次</span>
                    <?php if (!empty($cnt['force'])): ?>
                    <span class="rank-force" title="强制爬虫次数">⚡ <?php echo number_format($cnt['force']); ?></span>
                    <?php endif; ?>
                </li>
                <?php $rank++; endforeach; ?>
            </ul>
        </div>
    </div>

    <!-- 7 天趋势表 -->
    <div class="stats-table-wrap">
        <h4>📅 最近 7 天每日明细</h4>
        <table class="stats-day-table">
            <thead>
                <tr>
                    <th>日期</th>
                    <th class="align-right">总调用</th>
                    <th class="align-right">强制爬虫</th>
                    <th class="align-right">平台数</th>
                    <th class="align-right">force 占比</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($apiStats['days'] as $d):
                $dForceRate = $d['hits'] > 0 ? round($d['force'] / $d['hits'] * 100, 1) : 0;
                // 统计这天有多少平台（从原始数据重算）
                $dPlatformCount = 0;
                $dayRaw = $raw[$d['date']] ?? [];
                if (is_array($dayRaw)) $dPlatformCount = count($dayRaw);
            ?>
                <tr>
                    <td class="stats-date-cell">
                        <?php echo substr($d['date'], 0, 4); ?>-<?php echo substr($d['date'], 4, 2); ?>-<?php echo substr($d['date'], 6, 2); ?>
                        <?php if ($d['date'] === $todayKey): ?><span class="stats-today-tag">今</span><?php endif; ?>
                    </td>
                    <td class="align-right stats-num-blue"><?php echo number_format($d['hits']); ?></td>
                    <td class="align-right stats-num-red"><?php echo number_format($d['force']); ?></td>
                    <td class="align-right"><?php echo $dPlatformCount; ?></td>
                    <td class="align-right stats-rate-<?php echo $dForceRate > 30 ? 'high' : ($dForceRate > 10 ? 'mid' : 'low'); ?>"><?php echo $dForceRate; ?>%</td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- 今日全平台明细表 -->
    <?php if (!empty($apiStats['platforms'])): ?>
    <div class="stats-table-wrap stats-table-wrap-gap">
        <h4>🔍 今日全平台明细（<?php echo count($apiStats['platforms']); ?> 个）</h4>
        <table class="stats-day-table">
            <thead>
                <tr>
                    <th>平台</th>
                    <th class="align-right">hits</th>
                    <th class="align-right">force=1</th>
                    <th class="align-right">force 占比</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($apiStats['platforms'] as $pid => $cnt):
                $pForceRate = $cnt['hits'] > 0 ? round($cnt['force'] / $cnt['hits'] * 100, 1) : 0;
            ?>
                <tr>
                    <td class="stats-date-cell"><?php echo escapeHtml($pid); ?></td>
                    <td class="align-right stats-num-blue"><?php echo number_format($cnt['hits']); ?></td>
                    <td class="align-right <?php echo $cnt['force'] > 0 ? 'stats-num-red' : 'stats-num-muted'; ?>"><?php echo number_format($cnt['force']); ?></td>
                    <td class="align-right stats-rate-<?php echo $pForceRate > 50 ? 'high' : ($pForceRate > 10 ? 'mid' : 'low'); ?>"><?php echo $pForceRate; ?>%</td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <?php endif; /* end has data */ ?>
</div>

<?php include __DIR__ . '/home/footer.php'; ?>

<script>
// stats 页面刷新按钮
document.getElementById('statsRefreshBtn')?.addEventListener('click', function(e) {
    e.preventDefault();
    if (typeof showToast === 'function') showToast('正在刷新统计...', 'info');
    setTimeout(function() { location.reload(); }, 400);
});
</script>
