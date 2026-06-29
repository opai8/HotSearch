<?php
/**
 * ============================================================
 * 热搜榜 - 首页入口
 * ============================================================
 *
 * 页面结构（从上到下）：
 *   header.php     → 顶部导航 + 通知栏 + 注入 JS 数据
 *   index.php（本文件）→ 卡片网格渲染逻辑
 *   footer.php     → 回到顶部 + 页脚 + JS 加载
 *
 * 数据来源：
 *   通过 function.php 加载所有功能（config + helper + fetcher）
 *   直接调用 api/V1/{platform}.php 处理器获取数据
 *   文件缓存在 cache/ 目录下
 *
 * 新手阅读建议：
 *   1. 先看第 1 步（拉取数据），知道数据从哪来
 *   2. 再看第 2 步（渲染工具函数），知道卡片怎么生成
 *   3. 最后看第 3 步（页面主体），知道整体结构
 * ============================================================
 */

// 一次性加载核心模块（配置 + 工具函数 + 数据抓取）
require_once __DIR__ . '/function.php';

// ============================================================
// 第 1 步：拉取所有平台的数据
// ============================================================
// $cardsData 是一个数组，每个元素包含：
//   - source  → 平台配置信息（id、name、icon、url 等）
//   - data    → 热搜数据数组（每条有 title、url、hot）
//   - error   → 错误信息（有值表示拉取失败）
//   - time    → 更新时间戳
// ============================================================
$cardsData = fetchAllSourcesData($GLOBALS['sources'], $GLOBALS['apiLimit']);
$displayLimit = $GLOBALS['displayLimit'];  // 默认显示多少条（折叠状态）


// ============================================================
// 第 2 步：渲染工具函数
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
 * @return string HTML 字符串
 */
function renderCardBody($data, $error, $displayLimit) {
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

    // --- 热搜列表（ul）---
    $html .= '<ul class="hot-list">';

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

        // 热度值 HTML（有的平台没有热度，就不显示）
        $hotHtml = $hot ? ('<span class="hot-heat"><span class="iconfont icon-xiaohuomiao" style="font-size:12px; margin-right:2px;"></span>' . escapeHtml($hot) . '</span>') : '';

        // 拼装单条 li
        // 新手提示：data-tooltip 是自定义属性，CSS 里用 ::before 伪元素读取它来显示提示文字
        $html .= '<li class="hot-item" onclick="openHotLink(\'' . escapeHtml($url) . '\',\'_blank\')" data-tooltip="' . escapeHtml($title) . '">'
              .    '<span class="rank' . $rankClass . '">' . ($i + 1) . '</span>'
              .    '<span class="hot-title">' . escapeHtml($title) . '</span>'
              .    $hotHtml
              . '</li>';
    }

    $html .= '</ul>';

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
// 第 3 步：包含 header（顶部导航 + CSS 引用）
// ============================================================
include __DIR__ . '/home/header.php';
?>

    <!-- ===== 通知栏样式 ===== -->
    <link rel="stylesheet" href="/assets/css/notice-bar.css">
    <link rel="stylesheet" href="/assets/css/notice-bar-dark.css">
    <link rel="stylesheet" href="/assets/css/notice-bar-responsive.css">

    <?php include __DIR__ . '/home/notice-bar.php'; ?>

    <!-- ============================================================
         热搜卡片网格（主体内容）
         ============================================================
         新手提示：
         - .hot-grid 是 CSS Grid 布局，自动适应屏幕宽度
         - 每个 .hot-card 是一张卡片，固定高度，内容超出内部滚动
         - 卡片结构：card-header（顶部）+ card-body（中间滚动）+ card-footer（底部）
         ============================================================ -->
    <main class="main-content">
        <div class="hot-grid">
            <?php foreach ($cardsData as $card):
                $source     = $card['source'];     // 平台配置
                $data       = $card['data'];       // 热搜数据
                $error      = $card['error'];      // 错误信息
                $updateTime = isset($card['time']) ? $card['time'] : time();
                $sourceId   = $source['id'];
                $bodyHtml   = renderCardBody($data, $error, $displayLimit);
            ?>
            <!-- 单个热搜卡片 -->
            <div class="hot-card" data-source="<?php echo escapeHtml($sourceId); ?>" data-expanded="0">
                <!-- 卡片头部：平台图标 + 名称 | 标签 + 数据来源链接 -->
                <div class="card-header">
                    <div class="card-title">
                        <span class="iconfont <?php echo escapeHtml($source['icon']); ?> platform-icon <?php echo escapeHtml($source['colorClass']); ?>"></span>
                        <span><?php echo escapeHtml($source['name']); ?></span>
                    </div>
                    <div class="card-tag-wrap">
                        <span class="card-tag"><?php echo escapeHtml($source['tag']); ?></span>
                        <?php if (!empty($source['url'])): ?>
                            <a class="card-source-link" href="<?php echo escapeHtml($source['url']); ?>" target="_blank" rel="noopener">数据来源</a>
                        <?php endif; ?>
                    </div>
                </div>
                <!-- 卡片主体：数据列表 + 展开条（内部滚动） -->
                <?php echo $bodyHtml; ?>
                <!-- 卡片底部：更新时间 + 刷新按钮 -->
                <div class="card-footer">
                    <span class="update-time"><span class="iconfont icon-shijian" style="font-size:12px; margin-right:3px;"></span><?php echo escapeHtml(getUpdateTime($updateTime)); ?> 更新</span>
                    <button class="refresh-btn" onclick="refreshCard(this)" data-source="<?php echo escapeHtml($sourceId); ?>">刷新</button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </main>

<?php
// ============================================================
// 第 4 步：包含 footer（页脚 + JS 引用）
// ============================================================
include __DIR__ . '/home/footer.php';
?>
