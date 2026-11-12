<?php
/**
 * ============================================================
 * 分类页 - 独立渲染指定分类的平台卡片
 * ============================================================
 *
 * URL 形式：
 *   /category/tech        ← Apache .htaccess rewrite 自动生效
 *   /category.php?cat=tech ← 所有环境零配置可用
 *
 * SEO：每个分类独立 title/description/keywords/canonical
 * 缓存：和首页一样走页面静态缓存（30 分钟 TTL）
 * ============================================================
 */

$startTime = microtime(true);
require_once __DIR__ . '/function.php';

$cfg = $GLOBALS['site_config'];
$categories = $GLOBALS['categories'];
$sources = $GLOBALS['sources'];

// ============================================================
// 第 1 步：解析 cat 参数（三种 URL 形式）
// ============================================================
$cat = '';

// PATH_INFO：/category.php/tech
if (!empty($_SERVER['PATH_INFO'])) {
    $cat = trim($_SERVER['PATH_INFO'], '/');
}
// REQUEST_URI：/category/tech
elseif (!empty($_SERVER['REQUEST_URI'])) {
    $uriPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (preg_match('#/category/([a-z0-9_]+)#', $uriPath, $m)) {
        $cat = $m[1];
    }
}
// GET 参数：/category.php?cat=tech
if (empty($cat) && !empty($_GET['cat'])) {
    $cat = trim($_GET['cat']);
}

// 白名单校验（只允许已定义的分类 slug）
$validSlugs = array_filter(array_keys($categories), fn($k) => $k !== '_other');
if (!in_array($cat, $validSlugs, true)) {
    // 无效分类 → 404
    http_response_code(404);
    include __DIR__ . '/404.php';
    exit;
}

$catInfo = $categories[$cat];
$catName = $catInfo['name'];
$catIcon = $catInfo['icon'];

// ============================================================
// 第 2 步：筛选出属于这个分类的平台，再拉数据
// ============================================================
$filteredSources = array_filter($sources, function($s) use ($cat) {
    return ($s['category'] ?? '') === $cat;
});
// 按原 sources 顺序排序（保持首页一致性）
$sourceOrder = array_flip(array_column($sources, 'id'));
uasort($filteredSources, function($a, $b) use ($sourceOrder) {
    return ($sourceOrder[$a['id']] ?? 999) - ($sourceOrder[$b['id']] ?? 999);
});

// 这个分类没有任何平台 → 404（理论上不会发生，因为白名单里的分类在 config 里都有平台）
if (empty($filteredSources)) {
    http_response_code(404);
    include __DIR__ . '/404.php';
    exit;
}

// ============================================================
// 第 3 步：页面静态缓存
// ============================================================
if (!empty($cfg['page_cache_enable'])) {
    $cacheKey = 'page_category_' . $cat . '_' . md5(serialize([
        $cfg['site_name'],
        $cfg['card_height'],
        $cfg['display_limit'],
        $cfg['columns_per_row'],
        $cfg['show_heat'],
        $cfg['show_source_link'],
        $cfg['show_refresh_btn'],
        count($filteredSources),
    ]));
    $cacheFile = $cfg['cache_dir'] . '/' . $cacheKey . '.html';
    $cacheTtl = isset($cfg['page_cache_ttl']) ? intval($cfg['page_cache_ttl']) : 1800;

    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTtl) {
        $content = file_get_contents($cacheFile);
        $loadTime = microtime(true) - $startTime;
        $timeStr = $loadTime < 1 ? round($loadTime * 1000, 0) . ' ms' : round($loadTime, 2) . ' 秒';
        $timeHtml = '<span class="iconfont icon-shandian" style="font-size:12px; vertical-align:middle; margin-right:2px;"></span>页面加载耗时: ' . $timeStr . ' (来自缓存)';
        $content = str_replace('<!--GEN_TIME_PLACEHOLDER-->', $timeHtml, $content);
        echo $content;
        exit;
    }

    ob_start();
}

// ============================================================
// 第 4 步：拉数据 + SEO 变量 + header
// ============================================================
$cardsData = fetchAllSourcesData(array_values($filteredSources), $cfg['api_limit']);
$displayLimit = $cfg['display_limit'];

// 长尾词 SEO
$pageTitle    = "{$catName}热榜 - {$cfg['site_name']}";
$pageDesc     = "聚合全网{$catName}类实时热搜榜单，涵盖{$catName}领域热门平台，一站看完 {$catName} 热点动态，每日自动更新。";
$pageKeywords = "{$catName}热榜,{$catName}热搜,{$catName}榜单,{$catName}热点,{$cfg['site_name']}{$catName}";
$canonical    = $cfg['seo_domain'] . '/category.php/' . $cat;

include __DIR__ . '/home/header.php';

// 动态 CSS（和首页一样）
function genDynamicCssForCategory($cfg) {
    $css = '';
    $css .= ".hot-card { height: {$cfg['card_height']}px; }\n";
    $css .= ".hot-grid { max-width: {$cfg['content_max_width']}px; }\n";
    $css .= ".footer-content { max-width: {$cfg['content_max_width']}px; }\n";
    if ($cfg['columns_per_row'] === 'auto') {
        $css .= ".hot-grid { grid-template-columns: repeat(auto-fill, minmax({$cfg['card_min_width']}px, 1fr)); }\n";
    } else {
        $cols = max(1, min(10, intval($cfg['columns_per_row'])));
        $css .= ".hot-grid { grid-template-columns: repeat({$cols}, 1fr); }\n";
    }
    return $css;
}
$dynamicCss = genDynamicCssForCategory($cfg);
?>

    <link rel="stylesheet" href="/assets/css/notice-bar.css">
    <link rel="stylesheet" href="/assets/css/notice-bar-dark.css">
    <link rel="stylesheet" href="/assets/css/notice-bar-responsive.css">

    <style><?php echo $dynamicCss; ?></style>

    <?php if ($cfg['show_notice_bar']): ?>
        <?php include __DIR__ . '/home/notice-bar.php'; ?>
    <?php endif; ?>

    <?php
    // tab 栏：$activeCat = 当前分类 → 对应 tab 高亮
    $activeCat = $cat;
    include __DIR__ . '/home/category-tabs.php';
    unset($activeCat);
    ?>

    <!-- 主体：只渲染这一个分类 -->
    <main class="main-content" role="main">
        <div class="category-section" id="cat-<?php echo escapeHtml($cat); ?>">
            <h2 class="category-title">
                <span class="category-icon"><?php echo escapeHtml($catIcon); ?></span>
                <span><?php echo escapeHtml($catName); ?></span>
                <span class="category-count"><?php echo count($cardsData); ?></span>
            </h2>
            <section class="hot-grid" aria-label="<?php echo escapeHtml($catName); ?>平台列表">
                <?php foreach ($cardsData as $card):
                    $source     = $card['source'];
                    $data       = $card['data'];
                    $error      = $card['error'];
                    $updateTime = isset($card['time']) ? $card['time'] : time();
                    $sourceId   = $source['id'];

                    // 渲染卡片主体（复用首页的函数，这里直接内联核心逻辑）
                    $bodyHtml = '';
                    if ($error) {
                        $bodyHtml = '<div class="card-body"><div class="error"><span class="iconfont icon-cuowu" style="margin-right:4px;"></span>' . escapeHtml($error) . '</div></div>';
                    } elseif (empty($data)) {
                        $bodyHtml = '<div class="card-body"><div class="loading"><span class="iconfont icon-wushuju" style="margin-right:4px;"></span>暂无数据</div></div>';
                    } else {
                        $total = count($data);
                        $showN = min($displayLimit, $total);
                        $bodyHtml = '<div class="card-body"><ol class="hot-list">';
                        for ($i = 0; $i < $showN; $i++) {
                            $item = $data[$i];
                            $rankClass = ($i === 0) ? ' top1' : (($i === 1) ? ' top2' : (($i === 2) ? ' top3' : ''));
                            $title = isset($item['title']) ? $item['title'] : '';
                            $url   = isset($item['url'])   ? $item['url']   : '#';
                            $hot   = isset($item['hot'])   ? $item['hot']   : '';
                            $hotHtml = '';
                            if ($cfg['show_heat'] && $hot) {
                                $hotHtml = '<span class="hot-heat"><span class="iconfont icon-xiaohuomiao" style="font-size:12px; margin-right:2px;"></span>' . escapeHtml($hot) . '</span>';
                            }
                            $bodyHtml .= '<li class="hot-item" onclick="openHotLink(\'' . escapeHtml($url) . '\')" data-tooltip="' . escapeHtml($title) . '">'
                                . '<span class="rank' . $rankClass . '">' . ($i + 1) . '</span>'
                                . '<span class="hot-title">' . escapeHtml($title) . '</span>'
                                . $hotHtml . '</li>';
                        }
                        $bodyHtml .= '</ol>';
                        if ($total > $displayLimit) {
                            $bodyHtml .= '<div class="card-expand-bar hot-item-expand" onclick="toggleExpandByCard(this)">'
                                . '<span class="rank-expand"><span class="iconfont icon-arrow_down_fat"></span></span>'
                                . '<span class="expand-title">展开全部 ' . $total . ' 条</span>'
                                . '<span class="expand-arrow">点击展开</span></div>';
                        }
                        $bodyHtml .= '</div>';
                    }
                ?>
                <article class="hot-card" data-source="<?php echo escapeHtml($sourceId); ?>" data-expanded="0" aria-labelledby="card-title-<?php echo escapeHtml($sourceId); ?>">
                    <header class="card-header">
                        <h3 class="card-title" id="card-title-<?php echo escapeHtml($sourceId); ?>">
                            <span class="iconfont <?php echo escapeHtml($source['icon']); ?> platform-icon <?php echo escapeHtml($source['colorClass']); ?>"></span>
                            <span><?php echo escapeHtml($source['name']); ?></span>
                        </h3>
                        <div class="card-tag-wrap">
                            <span class="card-tag"><?php echo escapeHtml($source['tag']); ?></span>
                            <?php if ($cfg['show_source_link']): ?>
                                <a class="card-source-link" href="/platform.php/<?php echo escapeHtml($sourceId); ?>">详情</a>
                            <?php endif; ?>
                        </div>
                    </header>
                    <?php echo $bodyHtml; ?>
                    <footer class="card-footer">
                        <time class="update-time" datetime="<?php echo date('c', $updateTime); ?>">
                            <span class="iconfont icon-shijian" style="font-size:12px; margin-right:3px;"></span>
                            <?php echo escapeHtml(getUpdateTime($updateTime)); ?> 更新
                        </time>
                        <?php if ($cfg['show_refresh_btn']): ?>
                            <button class="refresh-btn" onclick="refreshCard(this)" data-source="<?php echo escapeHtml($sourceId); ?>">刷新</button>
                        <?php endif; ?>
                    </footer>
                </article>
                <?php endforeach; ?>
            </section>
        </div>
    </main>

<?php include __DIR__ . '/home/footer.php';

$genTime = microtime(true) - $startTime;
$timeStr = $genTime < 1 ? round($genTime * 1000, 0) . ' ms' : round($genTime, 2) . ' 秒';
$timeHtml = '<span class="iconfont icon-shandian" style="font-size:12px; vertical-align:middle; margin-right:2px;"></span>页面生成耗时: ' . $timeStr;

if (!empty($cfg['page_cache_enable'])) {
    $content = ob_get_contents();
    if ($content !== false) {
        $cacheContent = $content;
        $content = str_replace('<!--GEN_TIME_PLACEHOLDER-->', $timeHtml, $content);
        if (is_dir($cfg['cache_dir']) && is_writable($cfg['cache_dir'])) {
            @file_put_contents($cacheFile, $cacheContent);
        }
    }
    ob_end_clean();
    echo $content;
} elseif (ob_get_level() > 0) {
    $content = ob_get_contents();
    if ($content !== false) {
        $content = str_replace('<!--GEN_TIME_PLACEHOLDER-->', $timeHtml, $content);
    }
    ob_end_clean();
    echo $content;
}
?>