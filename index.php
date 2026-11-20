<?php
/**
 * ============================================================
 * 热搜榜 - 首页入口
 * ============================================================
 *
 * 页面结构（从上到下）：
 *   header.php     → 顶部导航
 *   notice-bar.php → 通知栏（可开关）
 *   index.php（本文件）→ 卡片网格渲染
 *   footer.php     → 回到顶部 + 页脚 + JS
 *
 * 新手提示：
 *   - 所有配置都在 config.php 的 $site_config 里
 *   - 想改布局、显示条数、各种开关？直接改 config.php
 *   - 本文件主要负责读取配置 + 渲染卡片
 * ============================================================
 */

$startTime = microtime(true);

// 一次性加载核心模块（配置 + 工具函数 + 数据抓取）
require_once __DIR__ . '/function.php';

// 读取配置（简写，后面用着方便）
$cfg = $GLOBALS['site_config'];

// ============================================================
// 按 api_version 过滤平台列表
// 原理：版本目录是唯一真相源，只有 api/{version}/ 下实际存在 handler 的平台才展示
// ============================================================
$apiVersion = 'V1';
if (!empty($cfg['api_version'])) {
    $apiVersion = preg_replace('/[^V0-9]/', '', strtoupper($cfg['api_version'])) ?: 'V1';
}
$versionDir = __DIR__ . '/api/' . $apiVersion;
$availableIds = [];
if (is_dir($versionDir)) {
    foreach (glob($versionDir . '/*.php') as $f) {
        $availableIds[] = strtolower(pathinfo($f, PATHINFO_FILENAME));
    }
}
// 过滤 $GLOBALS['sources']，只保留版本目录里有的
if (!empty($availableIds)) {
    $GLOBALS['sources'] = array_values(array_filter($GLOBALS['sources'], function($s) use ($availableIds) {
        return in_array(strtolower($s['id']), $availableIds, true);
    }));
}


// ============================================================
// 页面级静态缓存
// ============================================================
// 原理：把整个页面渲染好的 HTML 存成静态文件，
//       下次访问直接输出，跳过所有 PHP 执行和数据读取。
// 性能提升：5-10 倍（从几十毫秒降到几毫秒）
// 开关：config.php 的 page_cache_enable
// ============================================================
if (!empty($cfg['page_cache_enable'])) {
    // 缓存 key：基于影响页面显示的配置生成
    $cacheKey = 'page_index_' . md5(serialize([
        $cfg['site_name'],
        $cfg['card_height'],
        $cfg['display_limit'],
        $cfg['columns_per_row'],
        $cfg['show_heat'],
        $cfg['show_source_link'],
        $cfg['show_refresh_btn'],
        $cfg['show_notice_bar'],
        $apiVersion,  // API 版本（影响展示哪些平台）
        count($GLOBALS['sources']),
    ]));
    $cacheFile = $cfg['cache_dir'] . '/' . $cacheKey . '.html';
    $cacheTtl = isset($cfg['page_cache_ttl']) ? intval($cfg['page_cache_ttl']) : 1800;

    // 缓存有效 → 直接输出
    // （缓存文件里已包含生成时间，由写入时 footer.php 计算好）
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTtl) {
        echo file_get_contents($cacheFile);
        exit;
    }

    // 缓存失效 → 清理自己的过期缓存，然后开始重新渲染
    // 只清 page_index_*.html，不碰其他页面的缓存
    foreach (glob($cfg['cache_dir'] . '/page_index_*.html') ?: [] as $old) {
        if ($old !== $cacheFile && filemtime($old) < time() - $cacheTtl) {
            @unlink($old);
        }
    }
    ob_start();
}


// ============================================================
// 第 1 步：拉取所有平台的数据
// ============================================================
// $cardsData 是一个数组，每个元素包含：
//   - source  → 平台配置信息（id、name、icon、url 等）
//   - data    → 热搜数据数组（每条有 title、url、hot）
//   - error   → 错误信息（有值表示拉取失败）
//   - time    → 更新时间戳
// ============================================================
$cardsData = fetchAllSourcesData($GLOBALS['sources'], $cfg['api_limit']);
$displayLimit = $cfg['display_limit'];  // 折叠状态显示多少条


// ============================================================
// 第 2 步：动态生成 CSS（根据配置调整布局）
// ============================================================
// 新手提示：
//   为什么用 PHP 动态输出 CSS？
//   因为 Grid 列数、卡片高度这些值是从配置里读的，
//   不能写死在 CSS 文件里，所以在页面里用 <style> 动态输出。
// ============================================================
function genDynamicCss($cfg) {
    $css = '';

    // ---- 卡片高度 ----
    $cardHeight = intval($cfg['card_height']);
    $css .= ".hot-card { height: {$cardHeight}px; }\n";

    // ---- 内容区最大宽度 ----
    $maxWidth = intval($cfg['content_max_width']);
    $css .= ".hot-grid { max-width: {$maxWidth}px; }\n";
    $css .= ".footer-content { max-width: {$maxWidth}px; }\n";

    // ---- 每行卡片数量 ----
    if ($cfg['columns_per_row'] === 'auto') {
        // 自适应模式：用 minmax，最小宽度从配置读
        $minWidth = intval($cfg['card_min_width']);
        $css .= ".hot-grid { grid-template-columns: repeat(auto-fill, minmax({$minWidth}px, 1fr)); }\n";
    } else {
        // 固定列数：repeat(数字, 1fr)
        $cols = intval($cfg['columns_per_row']);
        if ($cols < 1) $cols = 1;
        if ($cols > 10) $cols = 10;
        $css .= ".hot-grid { grid-template-columns: repeat({$cols}, 1fr); }\n";
    }

    return $css;
}

$dynamicCss = genDynamicCss($cfg);


// ============================================================
// 第 3 步：渲染工具函数
// ============================================================

/**
 * 渲染单个卡片的主体内容（数据列表 + 展开控制条）
 *
 * 新手提示：card-body 内部结构
 *   <div class="card-body">       ← 滚动容器，超出高度就滚动
 *     <ul class="hot-list">       ← 数据列表
 *       <li class="hot-item">1</li>
 *       <li class="hot-item">2</li>
 *       ...
 *     </ul>
 *     <div class="card-expand-bar">展开全部</div>  ← 展开条（跟在数据后面）
 *   </div>
 *
 * @param array   $data          热搜数据数组
 * @param string  $error         错误信息（空字符串表示正常）
 * @param int     $displayLimit  折叠状态下显示的条数
 * @param array   $cfg           全局配置（用于各种开关判断）
 * @return string HTML 字符串
 */
function renderCardBody($data, $error, $displayLimit, $cfg, $sourceId = '') {
    // --- 有错误时显示错误提示 ---
    if ($error) {
        return '<div class="card-body"><div class="error"><span class="iconfont icon-cuowu card-inline-icon"></span>' . escapeHtml($error) . '</div></div>';
    }
    // --- 没数据时显示暂无数据 ---
    if (empty($data)) {
        return '<div class="card-body"><div class="loading"><span class="iconfont icon-wushuju card-inline-icon"></span>暂无数据</div></div>';
    }

    $total = count($data);

    // ===== 开始拼装 HTML =====
    $html = '<div class="card-body">';

    // --- 热搜列表（SEO：ol 表示有序列表，适合排名）
    // 渲染全部条目给搜索引擎看，折叠由 JS/CSS 控制
    $html .= '<ol class="hot-list">';

    // 循环渲染全部热搜（不再截断到 $displayLimit，SEO 需要完整内容）
    for ($i = 0; $i < $total; $i++) {
        $item = $data[$i];

        // 前三名特殊样式 class（top1/top2/top3）
        $rankClass = '';
        if      ($i === 0) $rankClass = ' top1';
        elseif  ($i === 1) $rankClass = ' top2';
        elseif  ($i === 2) $rankClass = ' top3';

        $title = isset($item['title']) ? $item['title'] : '';
        $url   = isset($item['url'])   ? $item['url']   : '';
        // 数据源 url 为空时的 fallback 映射（部分平台只提供列表不提供单条链接）
        if (empty($url) || $url === '#') {
            $urlMap = [
                'sina'     => 'https://news.sina.com.cn/hotnews/',
                'sina_news'=> 'https://news.sina.com.cn/',
                'qq_news'  => 'https://new.qq.com/',
                'wangyi'   => 'https://news.163.com/',
                'weibo'    => 'https://s.weibo.com/top/summary',
            ];
            $url = isset($urlMap[$sourceId]) ? $urlMap[$sourceId] : '#';
        }
        $hot   = isset($item['hot'])   ? $item['hot']   : '';

        // 右侧指标区：统一小火苗图标，有数值就显示，没数值就空
        $hotHtml = '';
        if ($cfg['show_heat'] && $hot !== '' && $hot !== null && $hot !== 0 && $hot !== '0') {
            $hotHtml = '<span class="hot-heat"><span class="iconfont icon-xiaohuomiao hot-fire-icon"></span>' . escapeHtml((string)$hot) . '</span>';
        }

        // 拼装单条 li
        // SEO：hot-title 用 <a> 标签让爬虫能追踪真实链接；onclick 里 return false 阻止 a 默认行为，仍走 JS 的 window.open
        // data-tooltip 是自定义属性，JS 动态读取它来显示跟随鼠标的 tooltip
        $safeTitle = escapeHtml($title);
        $safeUrl   = escapeHtml($url);
        $html .= '<li class="hot-item" data-tooltip="' . $safeTitle . '">'
              .    '<span class="rank' . $rankClass . '">' . ($i + 1) . '</span>'
              .    '<a class="hot-title" href="' . $safeUrl . '" target="_blank" rel="noopener noreferrer" onclick="openHotLink(\'' . $safeUrl . '\');return false;">' . $safeTitle . '</a>'
              .    $hotHtml
              . '</li>';
    }

    // --- "展开全部" 控制条（永远渲染，保持 11 行 grid 结构）---
    // 数据 > displayLimit：可见、可点击
    // 数据 ≤ displayLimit：visibility: hidden 占位（保持第 11 行空间但不显示）
    $expandPlaceholder = ($total <= $displayLimit) ? ' is-placeholder' : '';
    $html .= '<li class="card-expand-bar hot-item-expand' . $expandPlaceholder . '" onclick="toggleExpandByCard(this)">'
          .    '<span class="rank-expand"><span class="iconfont icon-arrow_down_fat"></span></span>'
          .    '<span class="expand-title">展开全部 ' . $total . ' 条</span>'
          .    '<span class="expand-arrow">点击展开</span>'
          . '</li>';

    $html .= '</ol>';

    $html .= '</div>';   // 关闭 card-body
    return $html;
}


// ============================================================
// 第 4 步：包含 header（顶部导航 + CSS 引用）
// ============================================================
include __DIR__ . '/home/header.php';
?>

    <!-- H1 主标题 = header.php 里可见的 site-title（避免多 h1 + 避免 sr-only cloaking 风险） -->
    <?php
    // SEO：把平台名作为关键词锚点注入 meta description 已足够，不在 H1 里重复
    // 搜索引擎会从 header 的 site-title 取主标题，meta description 取关键词补充
    ?>

    <!-- ===== 通知栏样式已合并到 style.css / theme-dark.css / responsive.css ===== -->

    <!-- ===== 动态 CSS（根据配置生成的布局样式）===== -->
    <!-- 新手提示：这里的 CSS 是 PHP 根据 config.php 的配置动态生成的 -->
    <style>
<?php echo $dynamicCss; ?>
    </style>

    <?php
    // ===== 通知栏（配置开关控制显示/隐藏）=====
    if ($cfg['show_notice_bar']) {
        include __DIR__ . '/home/notice-bar.php';
    }

    // ===== 顶部 banner 广告（notice-bar 下方，全宽）=====
    $bannerAds = isset($cfg['top_banner_ads']) ? $cfg['top_banner_ads'] : [];
    foreach ($bannerAds as $ad) {
        if (empty($ad['enabled'])) continue;
        $adTitle = escapeHtml($ad['title'] ?? '');
        $adImg   = escapeHtml($ad['img'] ?? '');
        $adUrl   = escapeHtml($ad['url'] ?? '#');
        $adBadge = escapeHtml(!empty($ad['badge']) ? $ad['badge'] : '广告');
        if (empty($adImg) || $adUrl === '#') continue;
        echo '<a class="ad-banner" href="' . $adUrl . '" target="_blank" rel="noopener noreferrer">'
           . '<img src="' . $adImg . '" alt="' . $adTitle . '" loading="lazy">'
           . '<span class="ad-badge">' . $adBadge . '</span>'
           . '</a>';
    }
    ?>

    <!-- ============================================================
         热搜卡片网格（主体内容，平铺显示）
         ============================================================ -->
    <main class="main-content" role="main">
        <section class="hot-grid" aria-label="热榜平台列表">
            <?php
            $gridSlotAd = isset($cfg['grid_slot_ad']) ? $cfg['grid_slot_ad'] : [];
            $gridSlotAfter = !empty($gridSlotAd['enabled']) ? intval($gridSlotAd['grid_insert_after'] ?? 6) : 0;

            // 辅助函数：渲染插卡广告 HTML
            function renderGridSlotAd($ad) {
                $slotTitle = escapeHtml($ad['title'] ?? '');
                $slotImg   = escapeHtml($ad['img'] ?? '');
                $slotUrl   = escapeHtml($ad['url'] ?? '#');
                $slotBadge = escapeHtml(!empty($ad['badge']) ? $ad['badge'] : '广告');
                if (empty($slotImg) || $slotUrl === '#') return '';
                return '<a class="hot-card ad-card" href="' . $slotUrl . '" target="_blank" rel="noopener noreferrer" aria-label="' . $slotTitle . '">'
                     . '<span class="ad-badge">' . $slotBadge . '</span>'
                     . '<div class="ad-card-img-wrap"><img src="' . $slotImg . '" alt="' . $slotTitle . '" loading="lazy"></div>'
                     . '<div class="ad-card-footer"><span class="ad-card-title">' . $slotTitle . '</span></div>'
                     . '</a>';
            }

            $cardIndex = 0;
            // grid_insert_after = 0 表示"第一个平台卡片之前"
            if ($gridSlotAfter === 0 && !empty($gridSlotAd['enabled'])) {
                echo renderGridSlotAd($gridSlotAd);
            }

            foreach ($cardsData as $card):
                $cardIndex++;
                $source     = $card['source'];
                $data       = $card['data'];
                $error      = $card['error'];
                $updateTime = isset($card['time']) ? $card['time'] : time();
                $sourceId   = $source['id'];
                $bodyHtml   = renderCardBody($data, $error, $displayLimit, $cfg, $sourceId);
            ?>
            <article class="hot-card" data-source="<?php echo escapeHtml($sourceId); ?>" data-expanded="0" aria-labelledby="card-title-<?php echo escapeHtml($sourceId); ?>">
                <header class="card-header">
                    <h2 class="card-title" id="card-title-<?php echo escapeHtml($sourceId); ?>">
                        <span class="iconfont <?php echo escapeHtml($source['icon']); ?> platform-icon <?php echo escapeHtml($source['colorClass']); ?>"></span>
                        <a class="card-title-link" href="/<?php echo escapeHtml($sourceId); ?>" target="_blank" rel="noopener"><?php echo escapeHtml($source['name']); ?></a>
                    </h2>
                    <div class="card-tag-wrap">
                        <span class="card-tag"><?php echo escapeHtml($source['tag']); ?></span>
                        <?php if ($cfg['show_source_link']): ?>
                            <a class="card-source-link" href="/<?php echo escapeHtml($sourceId); ?>">详情</a>
                        <?php endif; ?>
                    </div>
                </header>
                <?php echo $bodyHtml; ?>
                <footer class="card-footer">
                    <time class="update-time" datetime="<?php echo date('c', $updateTime); ?>">
                        <span class="iconfont icon-shijian update-time-icon"></span>
                        <?php echo escapeHtml(getUpdateTime($updateTime)); ?> 更新
                    </time>
                    <?php if ($cfg['show_refresh_btn']): ?>
                        <button class="refresh-btn" onclick="refreshCard(this)" data-source="<?php echo escapeHtml($sourceId); ?>">刷新</button>
                    <?php endif; ?>
                </footer>
            </article>
            <?php
            // ===== 卡片网格中间插卡广告 =====
            if ($gridSlotAfter > 0 && $cardIndex === $gridSlotAfter && !empty($gridSlotAd['enabled'])) {
                echo renderGridSlotAd($gridSlotAd);
            }
            endforeach;
            ?>
        </section>
    </main>

<?php
// ============================================================
// 第 5 步：包含 footer（页脚 + JS 引用）
// ============================================================
include __DIR__ . '/home/footer.php';


// ============================================================
// 页面生成耗时 + 写入页面缓存
// （footer.php 已自行计算并输出生成时间，这里直接存/输出）
// ============================================================

if (!empty($cfg['page_cache_enable'])) {
    if (ob_get_level() > 0) {
        $content = ob_get_contents();
        if ($content !== false) {
            // 写入缓存文件（完整内容，含 footer.php 已输出的生成时间）
            if (is_dir($cfg['cache_dir']) && is_writable($cfg['cache_dir'])) {
                if (file_put_contents($cacheFile, $content) === false) {
                    error_log('index.php 写页面缓存失败: ' . $cacheFile . ' (is_writable=false 或磁盘满)');
                }
            }
        }
        ob_end_clean();
        echo $content;
    }
} elseif (ob_get_level() > 0) {
    ob_end_flush();
}
?>
