<?php
/**
 * 首页 - 页脚区域
 * 由 index.php 引入，不单独访问
 */
?>

    <!-- ===== 回到顶部按钮 ===== -->
    <button class="back-to-top" id="backToTop" onclick="window.scrollTo({top:0,behavior:'smooth'})" title="回到顶部">
        <i class="iconfont icon-top"></i>
    </button>

    <!-- ===== 页脚 ===== -->
    <footer class="footer">
        <div class="footer-content">
            <div class="status">
                <span class="status-dot"></span>
                <span>服务运行正常</span>
            </div>
        </div>
    </footer>

    <!-- ===== 脚本 ===== -->
    <script src="../assets/js/script.js"></script>
    <script src="../assets/js/header.js"></script>
    <script>
        // 页面加载完成后，把每张卡片的完整热搜数据注入到对应的 .hot-card DOM 上，
        // 这样 "展开全部" 就不需要再次请求网络，直接在本地切换。
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
