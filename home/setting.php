<?php
/**
 * 设置页面
 * 使用 header 头部
 */

require_once __DIR__ . '/../function.php';
?>
<?php include __DIR__ . '/header.php'; ?>

    <!-- ===== 设置页面专属样式 ===== -->
    <link rel="stylesheet" href="../assets/css/set.css">
    <link rel="stylesheet" href="../assets/css/set-dark.css">
    <link rel="stylesheet" href="../assets/css/set-responsive.css">

    <!-- ===== 设置页面内容 ===== -->
    <main class="main-content">
        <div class="setting-container">
            <h2 class="setting-title">⚙️ 设置</h2>

            <div class="setting-section">
                <h3>显示设置</h3>
                <div class="setting-item">
                    <label>每页显示条数</label>
                    <select id="displayLimit">
                        <option value="5">5 条</option>
                        <option value="10" selected>10 条</option>
                        <option value="15">15 条</option>
                        <option value="20">20 条</option>
                    </select>
                </div>
            </div>

            <div class="setting-section">
                <h3>主题设置</h3>
                <div class="setting-item">
                    <label>主题模式</label>
                    <select id="themeMode">
                        <option value="light">日间模式</option>
                        <option value="dark">夜间模式</option>
                        <option value="auto">跟随系统</option>
                    </select>
                </div>
            </div>
			
			<div class="setting-section">
                <h3>首页布局</h3>
                <div class="setting-item">
                    <label>每行展示几个</label>
                    <select id="themeMode">
                        <option value="light">4</option>
                        <option value="dark">5</option>
                    </select>
                </div>
            </div>

            <div class="setting-section">
                <h3>缓存设置</h3>
                <div class="setting-item">
                    <label>缓存时间</label>
                    <select id="cacheTime">
                        <option value="1">1 小时</option>
                        <option value="3">3 小时</option>
                        <option value="6" selected>6 小时</option>
                        <option value="12">12 小时</option>
                        <option value="24">24 小时</option>
                    </select>
                </div>
                <div class="setting-item">
                    <button class="btn" id="clearCacheBtn">🧹 清除所有缓存</button>
                </div>
            </div>

            <div class="setting-section">
                <h3>关于</h3>
                <div class="about-info">
                    <p><strong>今日热榜</strong></p>
                    <p>版本：1.0.0</p>
                    <p>汇聚全网热点，热门尽览无余</p>
                </div>
            </div>

            <div class="setting-actions">
                <a href="../index.php" class="btn">← 返回首页</a>
            </div>
        </div>
    </main>

    <!-- ===== 设置页面脚本 ===== -->
    <script src="../assets/js/set.js"></script>

<?php include __DIR__ . '/footer.php'; ?>
