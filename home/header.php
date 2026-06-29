<?php
/**
 * 首页 - 顶部头部区域
 * 由 index.php 引入，不单独访问
 */

if (!isset($displayLimit)) { $displayLimit = 10; }
if (!isset($cardsData)) { $cardsData = []; }

// 准备注入到 JS 的卡片数据（用于 "展开全部" 的客户端切换）
$cardDataList = [];
foreach ($cardsData as $card) {
    if (isset($card['source']['id']) && isset($card['data'])) {
        $cardDataList[$card['source']['id']] = array_values($card['data']);
    }
}
$cardDataJson = json_encode($cardDataList, JSON_UNESCAPED_UNICODE);

// 当前日期时间
$nowDate = date('Y-m-d H:i:s');
$weekDay = ['星期日', '星期一', '星期二', '星期三', '星期四', '星期五', '星期六'][date('w')];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>今日热榜 - 汇聚全网热点</title>
    <link rel="icon" type="image/png" href="../assets/images/favicon.png">
    <link rel="stylesheet" href="../assets/iconfont/iconfont.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/theme-dark.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
    <link rel="stylesheet" href="../assets/css/icon-color.css">
    <script src="../assets/js/toast.js"></script>
</head>
<body>

    <!-- ===== 顶部导航 ===== -->
    <header class="header">
        <div class="header-left">
            <a href="./" class="header-brand-link">
                <img src="../assets/images/favicon.png" alt="logo" class="header-logo">
                <div class="header-brand">
                    <h1 class="site-title">今日热榜</h1>
                    <p class="site-desc">多平台实时热点聚合</p>
                </div>
            </a>
        </div>
        <div class="header-center">
            <div class="header-time"><?php echo $nowDate; ?></div>
            <div class="header-date"><?php echo $weekDay; ?></div>
        </div>
        <div class="header-right">
            <button class="header-icon-btn" title="刷新全部" onclick="location.reload()">
                <span class="iconfont icon-yuanxunhuan"></span>
            </button>
            <button class="header-icon-btn" title="夜间模式" id="themeToggle">
                <span class="iconfont icon-yejian"></span>
            </button>
            <button class="header-icon-btn" title="设置" onclick="location.href='home/setting.php'">
                <span class="iconfont icon-shezhi"></span>
            </button>
        </div>
    </header>
