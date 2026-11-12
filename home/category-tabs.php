<?php
/**
 * 分类 Tab 栏（首页 + 分类页共用）
 * ============================================================
 * 当前页面通过 $activeCat 变量控制高亮：
 *   - 首页调用：include 时 $activeCat 未设置 → 「全部」高亮
 *   - 分类页调用：include 前设置 $activeCat = 'tech' → 对应分类高亮
 *
 * 新手提示：
 *   - 修改分类顺序/名称 → config.php 的 $categories
 *   - 修改 tab 链接跳转 → 统一用 /category/{slug}（纯 <a>，无 JS 拦截）
 *   - 「全部」tab 跳首页（/），其他跳 /category/{slug}
 * ============================================================
 */
$categories = $GLOBALS['categories'];
$sources    = $GLOBALS['sources'];

// 每个分类的卡片数量（提前算好，避免模板里重复计算）
$catCounts = [];
foreach ($sources as $s) {
    $c = $s['category'] ?? '_other';
    $catCounts[$c] = ($catCounts[$c] ?? 0) + 1;
}
$totalCount = count($sources);
?>

<nav class="category-tabs" aria-label="分类导航">
    <a href="/" class="category-tab<?php echo empty($activeCat) ? ' active' : ''; ?>">
        <span>全部</span>
        <span class="category-tab-count"><?php echo $totalCount; ?></span>
    </a>
    <?php foreach ($categories as $slug => $info): ?>
        <?php if ($slug === '_other') continue; ?>
        <?php
        // 链接格式：PATH_INFO 风格 /category.php/{slug}
        // 好处：不需要 Nginx rewrite 就能工作（phpstudy 自带 fastcgi_split_path_info）
        // 如果以后 nginx.htaccess rewrite 生效了，/category/{slug} 也能用
        ?>
        <a href="/category.php/<?php echo escapeHtml($slug); ?>"
           class="category-tab<?php echo (!empty($activeCat) && $slug === $activeCat) ? ' active' : ''; ?>">
            <span class="category-tab-icon"><?php echo escapeHtml($info['icon']); ?></span>
            <span><?php echo escapeHtml($info['name']); ?></span>
            <span class="category-tab-count"><?php echo $catCounts[$slug] ?? 0; ?></span>
        </a>
    <?php endforeach; ?>
</nav>