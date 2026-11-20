<?php
/**
 * 懂球帝热榜处理器
 * 数据来源: https://dongqiudi.com/
 * 注意：该站有 EdgeOne WAF 拦截，暂不可用（config.php 已注释）
 */

class DongqiudiHotSearch {

    public function handle($limit = 50) {
        return array('success' => false, 'message' => 'EdgeOne WAF 拦截，暂不可用');
    }
}
?>
