<?php
/**
 * 通知栏组件
 * 由 index.php 引入，不单独访问
 */

if (!isset($GLOBALS['sources'])) {
    $GLOBALS['sources'] = [];
}
$cfg = $GLOBALS['site_config'];
$apiLink   = isset($cfg['api_doc_link']) ? $cfg['api_doc_link'] : '/apidoc';
$repoLink  = isset($cfg['github_repo_link']) ? $cfg['github_repo_link'] : '';
?>
<div class="notice-bar">
    <div class="notice-icon"><span class="iconfont icon-gonggao"></span></div>
    <div class="notice-content">
        点击卡片底部「<span class="iconfont icon-shuaxin notice-refresh-icon"></span> 刷新」可立即获取最新数据。数据自动缓存 <strong>12 小时</strong>。
        使用<a href="<?php echo escapeHtml($apiLink); ?>" target="_blank" rel="noopener">今日热榜 API</a> 可快速接入热榜数据，助力追踪全网热点。
        <br><span class="notice-source">数据来源：
        <?php
        $names = [];
        foreach ($GLOBALS['sources'] as $s) {
            if (isset($s['name'])) {
                $names[] = escapeHtml($s['name']);
            }
        }
        echo implode(' · ', $names);
        ?></span>
        <?php if ($repoLink !== ''): ?>
        <br><span class="notice-repo">开源地址：<a href="<?php echo escapeHtml($repoLink); ?>" target="_blank" rel="noopener">HotSearch-Web [PHP]</a></span>
        <?php endif; ?>
    </div>
</div>
