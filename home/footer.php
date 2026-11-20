<?php
/**
 * 页面底部模板
 * ============================================================
 * 包含：回到顶部按钮、页脚、JS 引用、卡片数据注入
 * 由 index.php、setting.php 等页面引入
 *
 * 新手提示：
 *   - 回到顶部按钮、页脚是否显示，都从 config.php 的 $site_config 读取
 *   - 想改这些？直接改 config.php 就行，不用改这里
 * ============================================================
 */

// 确保配置变量存在（防止直接访问此文件报错）
if (!isset($GLOBALS['site_config'])) {
    $GLOBALS['site_config'] = [
        'show_back_to_top' => true,
        'show_footer'      => true,
    ];
}
$cfg = $GLOBALS['site_config'];

// 计算页面耗时：优先用页面自定义的 $startTime，fallback 到 PHP 请求开始时间
$genTimeHtml = '';
$tStart = null;
if (isset($startTime) && is_numeric($startTime)) {
    $tStart = $startTime;
} elseif (!empty($_SERVER['REQUEST_TIME_FLOAT'])) {
    $tStart = $_SERVER['REQUEST_TIME_FLOAT'];
}
if ($tStart !== null) {
    $t = microtime(true) - $tStart;
    $tStr = $t < 1 ? round($t * 1000, 0) . ' ms' : round($t, 2) . ' 秒';
    $genTimeHtml = '<span class="gen-time-bolt iconfont icon-shandian"></span>'
                 . '<span class="gen-time-text">页面生成耗时: ' . $tStr . '</span>';
}
?>

    <?php
    // ===== 回到顶部按钮（配置开关控制显示/隐藏）=====
    if ($cfg['show_back_to_top']):
    ?>
    <!-- ===== 回到顶部按钮 ===== -->
    <button class="back-to-top" id="backToTop" onclick="window.scrollTo({top:0,behavior:'smooth'})" title="回到顶部">
        <i class="iconfont icon-top"></i>
    </button>
    <?php endif; ?>

    <?php
    // ===== 页脚（配置开关控制显示/隐藏）=====
    if ($cfg['show_footer']):
    ?>
    <!-- ===== 页脚 ===== -->
    <footer class="footer">
        <div class="footer-content">
            <div class="status">
                <span class="status-dot"></span>
                <span>服务运行正常</span>
                <?php if ($genTimeHtml !== ''): ?>
                <span class="gen-time footer-gen-time"><?php echo $genTimeHtml; ?></span>
                <?php endif; ?>
                <span class="footer-flex-push">
                    <a href="/status" class="footer-status-link" target="_blank" rel="noopener">
                        <span class="status-dot footer-status-dot"></span>
                        状态面板
                    </a>
                    <span class="footer-sep">|</span>
                    <a href="/sitemap.php" class="footer-status-link footer-sitemap-link" target="_blank" rel="nofollow">
                        <span class="status-dot footer-status-dot"></span>
                        sitemap
                    </a>
                </span>
            </div>
        </div>
    </footer>
    <?php endif; ?>

    <!-- ===== 脚本引用 ===== -->
    <script src="/assets/js/script.js"></script>
    <script src="/assets/js/header.js"></script>
    <?php if (!empty($GLOBALS['extra_scripts'])): ?>
    <?php foreach ($GLOBALS['extra_scripts'] as $s): ?>
    <script src="<?php echo escapeHtml($s); ?>"></script>
    <?php endforeach; ?>
    <?php endif; ?>

    <?php
    // ===== 第三方统计代码（百度统计 / Google Analytics 等）=====
    // 直接在 config.php 的 'statistics_code' 里粘贴完整 <script>...</script> 代码即可
    // 留空则不注入，不影响页面
    if (!empty($cfg['statistics_code'])) {
        echo "\n    <!-- 第三方统计代码 -->\n    " . $cfg['statistics_code'] . "\n";
    }
    ?>

    <!-- 注意：card._allData 已由 script.js 顶层的 injectAllData() 提前注入，
         它依赖 header.php 里 window.__HOTCC_CARD_DATA__ 的预注入，
         比这里 DOMContentLoaded 更早，解决了 applyUserSettings 读不到真实总数的时序问题 -->

    <!-- Console 彩蛋 -->
    <script>
    (function() {
        if (!console || !console.log) return;
        var seed = Math.floor(Math.random() * 1000);
        var tricks = [
            '%c🔥 今日热榜 · 多平台实时热点聚合','color:#ff4d4f;font-size:20px;font-weight:bold;',
            '%c专注把全网热搜搬到一张卡片上','color:#667eea;font-size:16px;',
            '%c别 F12 了，快去刷热搜吧 👀','color:#faad14;font-size:14px;'
        ];
        console.log(tricks[0], tricks[1]);
        console.log(tricks[2], tricks[3]);
        console.log('%c🚀 页面加载耗时已打印在页脚','color:#888;font-size:12px;');
        console.log('%cDebug 模式: /debug · Status: /status','color:#aaa;font-size:12px;');
    })();
    </script>

</body>
</html>
