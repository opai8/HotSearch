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
$home_url = '/index.php';
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
    <style>
        /* ============================================================
           全局重置
           ============================================================ */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'PingFang SC',
                         'Hiragino Sans GB', 'Microsoft YaHei', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            overflow: hidden;
        }

        /* ============================================================
           容器
           ============================================================ */
        .error-container {
            text-align: center;
            padding: 40px 20px;
            max-width: 600px;
            width: 100%;
        }

        /* ============================================================
           404 大数字
           ============================================================ */
        .error-code {
            font-size: 120px;
            font-weight: 800;
            line-height: 1;
            margin-bottom: 20px;
            text-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
            background: rgba(255, 255, 255, 0.95);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* ============================================================
           标题和描述
           ============================================================ */
        .error-title {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 12px;
        }

        .error-desc {
            font-size: 16px;
            opacity: 0.85;
            margin-bottom: 40px;
            line-height: 1.6;
        }

        /* ============================================================
           按钮组
           ============================================================ */
        .error-buttons {
            display: flex;
            gap: 16px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .error-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 28px;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
        }

        /* 主按钮：白色实心 */
        .error-btn-primary {
            background: #fff;
            color: #667eea;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
        }

        .error-btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
        }

        /* 次按钮：白色边框 */
        .error-btn-secondary {
            background: transparent;
            color: #fff;
            border: 2px solid rgba(255, 255, 255, 0.6);
        }

        .error-btn-secondary:hover {
            background: rgba(255, 255, 255, 0.15);
            border-color: #fff;
            transform: translateY(-2px);
        }

        /* ============================================================
           装饰元素（浮动的几何图形）
           ============================================================ */
        .decoration {
            position: fixed;
            opacity: 0.1;
            pointer-events: none;
        }

        .deco-1 {
            top: 10%;
            left: 8%;
            width: 80px;
            height: 80px;
            border: 4px solid #fff;
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }

        .deco-2 {
            top: 60%;
            right: 10%;
            width: 60px;
            height: 60px;
            border: 4px solid #fff;
            transform: rotate(45deg);
            animation: float 8s ease-in-out infinite reverse;
        }

        .deco-3 {
            bottom: 15%;
            left: 15%;
            width: 40px;
            height: 40px;
            border: 3px solid #fff;
            border-radius: 50%;
            animation: float 5s ease-in-out infinite;
        }

        .deco-4 {
            top: 20%;
            right: 20%;
            width: 30px;
            height: 30px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            animation: float 7s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(5deg); }
        }

        /* ============================================================
           响应式
           ============================================================ */
        @media (max-width: 768px) {
            .error-code {
                font-size: 80px;
            }

            .error-title {
                font-size: 22px;
            }

            .error-desc {
                font-size: 14px;
            }

            .error-btn {
                padding: 10px 22px;
                font-size: 14px;
            }

            .deco-1, .deco-2, .deco-3, .deco-4 {
                display: none;
            }
        }
    </style>
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
