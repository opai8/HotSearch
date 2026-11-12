<?php
/**
 * 404 页面
 * ============================================================
 * 当用户访问不存在的页面时显示这个页面
 *
 * 使用方法：
 *   Apache：.htaccess 文件已自动配置 ErrorDocument 404 /404.php
 *   Nginx：需要在 Nginx 配置里加 error_page 404 /404.php;
 *
 * 新手提示：
 *   - 这个页面直接用 PHP 输出，不依赖 function.php 和配置
 *   - 保证即使核心代码出错，404 页面也能正常显示
 *   - 样式和主站保持一致（紫色主题色）
 *
 * ============================================================
 * CSS 缓存说明：
 *   如果改了样式后刷新页面没变化，可能是浏览器用了缓存。
 *   解决方法：强制刷新（Ctrl+F5）或清浏览器缓存。
 *   上线前可以在 .htaccess 里调整缓存时间。
 * ============================================================
 */

// 基础配置（直接写死，不依赖 config.php，保证任何情况都能显示）
$site_name = '今日热榜';
$site_desc = '多平台实时热点聚合';
$favicon = '/assets/images/favicon.png';
$home_url = '/';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- SEO：404 页面标题 -->
    <title>404 - 页面未找到 - <?php echo $site_name; ?></title>
    <meta name="description" content="抱歉，您访问的页面不存在或已被删除。返回<?php echo $site_name; ?>查看全网热搜榜单。">
    <meta name="robots" content="noindex, follow">
    <link rel="icon" type="image/png" href="<?php echo $favicon; ?>">

    <!-- 图标字体（复用主站的） -->
    <link rel="stylesheet" href="/assets/iconfont/iconfont.css">

    <!-- 404 页面专属样式 -->
    <link rel="stylesheet" href="/assets/css/404.css">
</head>
<body>

    <!-- 装饰元素 -->
    <div class="decoration deco-1"></div>
    <div class="decoration deco-2"></div>
    <div class="decoration deco-3"></div>
    <div class="decoration deco-4"></div>

    <!-- 主体内容 -->
    <div class="error-container">
        <div class="error-code">404</div>
        <h1 class="error-title">页面未找到</h1>
        <p class="error-desc">
            抱歉，您访问的页面不存在或已被删除。<br>
            检查一下网址是否正确，或者返回首页继续浏览。
        </p>
        <div class="error-buttons">
            <a href="<?php echo $home_url; ?>" class="error-btn error-btn-primary">
                <span class="iconfont icon-shouye" style="font-size: 16px;"></span>
                返回首页
            </a>
            <button onclick="history.back()" class="error-btn error-btn-secondary">
                <span class="iconfont icon-fanhui" style="font-size: 16px;"></span>
                返回上一页
            </button>
        </div>
    </div>

</body>
</html>
