<?php
/**
 * 通知栏组件
 * 由 index.php 引入，不单独访问
 */

if (!isset($GLOBALS['sources'])) {
    $GLOBALS['sources'] = [];
}
?>
<div class="notice-bar">
    <div class="notice-icon"><span class="iconfont icon-gonggao"></span></div>
    <div class="notice-content">
        点击卡片底部「<span class="iconfont icon-shuaxin" style="font-size:12px;"></span> 刷新」可立即获取最新数据。数据自动缓存 <strong>6 小时</strong>。
        <br><span style="color:#888; font-size: 12px;">数据来源：
        <?php
        $names = [];
        foreach ($GLOBALS['sources'] as $s) {
            if (isset($s['name'])) {
                $names[] = escapeHtml($s['name']);
            }
        }
        echo implode(' · ', $names);
        ?></span>
    </div>
    <div class="notice-extra">
        <a href="home/test.html" class="test-link" target="_blank">🧪 接口测试</a>
    </div>
</div>
