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
                <span class="gen-time" style="margin-left:12px; color:#999; font-size:12px;"><!--GEN_TIME_PLACEHOLDER--></span>
            </div>
        </div>
    </footer>
    <?php endif; ?>

    <!-- ===== 脚本引用 ===== -->
    <script src="/assets/js/script.js"></script>
    <script src="/assets/js/header.js"></script>
    <script>
    /**
     * 清理 OPcache + 页面缓存
     * 使用项目已有的 showToast 弹窗函数
     */
    function clearOpcache() {
        showToast('正在清理缓存...', 'info');
        fetch('/core/clear_opcache.php?key=<?php echo $cfg['opcache_clear_key']; ?>')
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.success) {
                    var msg = 'OPcache 清理成功';
                    if (res.page_cache_cleared && res.page_cache_deleted > 0) {
                        msg += '，页面缓存已清除 ' + res.page_cache_deleted + ' 个';
                    }
                    showToast(msg + '，正在刷新...', 'success');
                    setTimeout(function() { location.reload(); }, 800);
                } else {
                    showToast('清理失败: ' + (res.message || '未知错误'), 'error');
                }
            })
            .catch(function() {
                showToast('请求失败，请检查网络', 'error');
            });
    }
    </script>
    <script>
        // 新手提示：
        // 页面加载完成后，把每张卡片的完整热搜数据注入到对应的 .hot-card DOM 上，
        // 这样 "展开全部" 就不需要再次请求网络，直接在本地切换，又快又流畅。
        document.addEventListener('DOMContentLoaded', function () {
            var CARD_DATA = <?php echo isset($cardDataJson) ? $cardDataJson : '{}'; ?>;
            var cards = document.querySelectorAll('.hot-card');
            cards.forEach(function (card) {
                var sid = card.getAttribute('data-source');
                if (sid && CARD_DATA[sid]) {
                    card._allData = CARD_DATA[sid];
                }
            });
        });
    </script>

</body>
</html>
