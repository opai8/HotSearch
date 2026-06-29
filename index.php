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
        count($GLOBALS['sources']),
    ]));
    $cacheFile = $cfg['cache_dir'] . '/' . $cacheKey . '.html';
    $cacheTtl = isset($cfg['page_cache_ttl']) ? intval($cfg['page_cache_ttl']) : 1800;

    // 缓存有效 → 直接输出
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTtl) {
        $content = file_get_contents($cacheFile);
        $loadTime = microtime(true) - $startTime;
        $timeStr = $loadTime < 1 ? round($loadTime * 1000, 0) . ' ms' : round($loadTime, 2) . ' 秒';
        $timeHtml = '<span class="iconfont icon-shandian" style="font-size:12px; vertical-align:middle; margin-right:2px;"></span>页面加载耗时: ' . $timeStr . ' (来自缓存)';
        $content = str_replace('<!--GEN_TIME_PLACEHOLDER-->', $timeHtml, $content);
        echo $content;
        exit;
    }

    // 缓存失效 → 开始捕获输出
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
function renderCardBody($data, $error, $displayLimit, $cfg) {
    // --- 有错误时显示错误提示 ---
    if ($error) {
        return '<div class="card-body"><div class="error"><span class="iconfont icon-cuowu" style="margin-right:4px;"></span>' . escapeHtml($error) . '</div></div>';
    }
    // --- 没数据时显示暂无数据 ---
    if (empty($data)) {
        return '<div class="card-body"><div class="loading"><span class="iconfont icon-wushuju" style="margin-right:4px;"></span>暂无数据</div></div>';
    }

    $total = count($data);
    $showN = min($displayLimit, $total);  // 实际显示的条数（不超过总数）

    // ===== 开始拼装 HTML =====
    $html = '<div class="card-body">';

    // --- 热搜列表（SEO：ol 表示有序列表，适合排名）
    $html .= '<ol class="hot-list">';

    // 循环渲染前 N 条热搜
    for ($i = 0; $i < $showN; $i++) {
        $item = $data[$i];

        // 前三名特殊样式 class（top1/top2/top3）
        $rankClass = '';
        if      ($i === 0) $rankClass = ' top1';
        elseif  ($i === 1) $rankClass = ' top2';
        elseif  ($i === 2) $rankClass = ' top3';

        $title = isset($item['title']) ? $item['title'] : '';
        $url   = isset($item['url'])   ? $item['url']   : '#';
        $hot   = isset($item['hot'])   ? $item['hot']   : '';

        // 热度值 HTML（配置里关了就不显示）
        $hotHtml = '';
        if ($cfg['show_heat'] && $hot) {
            $hotHtml = '<span class="hot-heat"><span class="iconfont icon-xiaohuomiao" style="font-size:12px; margin-right:2px;"></span>' . escapeHtml($hot) . '</span>';
        }

        // 拼装单条 li
        // 新手提示：data-tooltip 是自定义属性，CSS 里用 ::before 伪元素读取它来显示提示文字
        $html .= '<li class="hot-item" onclick="openHotLink(\'' . escapeHtml($url) . '\',\'_blank\')" data-tooltip="' . escapeHtml($title) . '">'
              .    '<span class="rank' . $rankClass . '">' . ($i + 1) . '</span>'
              .    '<span class="hot-title">' . escapeHtml($title) . '</span>'
              .    $hotHtml
              . '</li>';
    }

    $html .= '</ol>';

    // --- "展开全部" 控制条（仅当总条数超过显示上限时显示）---
    // 新手提示：展开条在 ul 外面，跟在列表后面，和数据一起滚动
    if ($total > $displayLimit) {
        $html .= '<div class="card-expand-bar hot-item-expand" onclick="toggleExpandByCard(this)">'
              .    '<span class="rank-expand"><span class="iconfont icon-arrow_down_fat"></span></span>'
              .    '<span class="expand-title">展开全部 ' . $total . ' 条</span>'
              .    '<span class="expand-arrow">点击展开</span>'
              . '</div>';
    }

    $html .= '</div>';
    return $html;
}


// ============================================================
// 第 4 步：包含 header（顶部导航 + CSS 引用）
// ============================================================
include __DIR__ . '/home/header.php';
?>

    <!-- SEO：页面主标题 -->
    <h1 class="sr-only"><?php echo escapeHtml($cfg['site_name']); ?> - <?php echo escapeHtml($cfg['site_desc']); ?></h1>

    <!-- ===== 通知栏样式 ===== -->
    <link rel="stylesheet" href="/assets/css/notice-bar.css">
    <link rel="stylesheet" href="/assets/css/notice-bar-dark.css">
    <link rel="stylesheet" href="/assets/css/notice-bar-responsive.css">

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
    ?>

    <!-- ============================================================
         热搜卡片网格（主体内容）
         ============================================================
         新手提示：
         - .hot-grid 是 CSS Grid 布局，列数从配置读取
         - 每个 .hot-card 是一张卡片，固定高度，内容超出内部滚动
         - 卡片结构：card-header（顶部）+ card-body（中间滚动）+ card-footer（底部）
         ============================================================ -->
    <main class="main-content" role="main">
        <!-- SEO：每个平台用 <section> 语义化标签 -->
        <section class="hot-grid" aria-label="热搜平台列表">
            <?php foreach ($cardsData as $card):
                $source     = $card['source'];     // 平台配置
                $data       = $card['data'];       // 热搜数据
                $error      = $card['error'];      // 错误信息
                $updateTime = isset($card['time']) ? $card['time'] : time();
                $sourceId   = $source['id'];
                $bodyHtml   = renderCardBody($data, $error, $displayLimit, $cfg);
            ?>
            <!-- SEO：单个卡片用 <article> 语义化标签 -->
            <article class="hot-card" data-source="<?php echo escapeHtml($sourceId); ?>" data-expanded="0" aria-labelledby="card-title-<?php echo escapeHtml($sourceId); ?>">
                <!-- 卡片头部：平台图标 + 名称 | 标签 + 数据来源链接 -->
                <header class="card-header">
                    <!-- SEO：平台名称用 <h2> 标题标签 -->
                    <h2 class="card-title" id="card-title-<?php echo escapeHtml($sourceId); ?>">
                        <span class="iconfont <?php echo escapeHtml($source['icon']); ?> platform-icon <?php echo escapeHtml($source['colorClass']); ?>"></span>
                        <span><?php echo escapeHtml($source['name']); ?></span>
                    </h2>
                    <div class="card-tag-wrap">
                        <span class="card-tag"><?php echo escapeHtml($source['tag']); ?></span>
                        <?php
                        // 数据来源链接（配置开关控制显示/隐藏）
                        if ($cfg['show_source_link'] && !empty($source['url'])):
                        ?>
                            <a class="card-source-link" href="<?php echo escapeHtml($source['url']); ?>" target="_blank" rel="noopener">数据来源</a>
                        <?php endif; ?>
                    </div>
                </header>
                <!-- 卡片主体：数据列表 + 展开条（内部滚动） -->
                <?php echo $bodyHtml; ?>
                <!-- 卡片底部：更新时间 + 刷新按钮 -->
                <footer class="card-footer">
                    <!-- SEO：时间用 <time> 标签 -->
                    <time class="update-time" datetime="<?php echo date('c', $updateTime); ?>">
                        <span class="iconfont icon-shijian" style="font-size:12px; margin-right:3px;"></span>
                        <?php echo escapeHtml(getUpdateTime($updateTime)); ?> 更新
                    </time>
                    <?php
                    // 刷新按钮（配置开关控制显示/隐藏）
                    if ($cfg['show_refresh_btn']):
                    ?>
                        <button class="refresh-btn" onclick="refreshCard(this)" data-source="<?php echo escapeHtml($sourceId); ?>">刷新</button>
                    <?php endif; ?>
                </footer>
            </article>
            <?php endforeach; ?>
        </section>
    </main>

<?php
// ============================================================
// 第 5 步：包含 footer（页脚 + JS 引用）
// ============================================================
include __DIR__ . '/home/footer.php';


// ============================================================
// 页面生成耗时 + 写入页面缓存
// ============================================================
$genTime = microtime(true) - $startTime;
$timeStr = $genTime < 1 ? round($genTime * 1000, 0) . ' ms' : round($genTime, 2) . ' 秒';
$timeHtml = '<span class="iconfont icon-shandian" style="font-size:12px; vertical-align:middle; margin-right:2px;"></span>页面生成耗时: ' . $timeStr;

if (!empty($cfg['page_cache_enable'])) {
    $content = ob_get_contents();
    if ($content !== false) {
        // 缓存内容里放占位符，读取时再替换成实际加载时间
        $cacheContent = $content;
        // 输出给用户的内容直接替换成生成时间
        $content = str_replace('<!--GEN_TIME_PLACEHOLDER-->', $timeHtml, $content);
        // 写入缓存文件（带占位符，下次读取时替换）
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
