<?php
/**
 * 设置页面
 * ============================================================
 * 用户个人偏好设置（仅影响当前浏览器，用 localStorage 存储）
 * 全站共享的运维配置（缓存时间、API 版本等）只能在 config.php 改
 *
 * 文件结构：
 *   setting.php               → 结构化 HTML（本文件）
 *   assets/css/setting.css    → 页面独立样式
 *   assets/js/setting.js      → 页面独立脚本
 *   assets/js/script.js       → 也读 localStorage 让首页生效
 *
 * 功能：
 *   - 主题（日间/夜间/跟随系统）
 *   - 每行显示列数
 *   - 卡片默认展开条数
 *   - 热搜链接打开方式（新窗口 / 同窗口）
 *   - 是否显示热度值 / 来源链接 / 刷新按钮
 *   - 一键恢复默认
 * ============================================================
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/function.php';
$cfg = $GLOBALS['site_config'];

// 注入页面，复用 header/footer
$_SERVER['REQUEST_URI'] = '/setting.php';
$GLOBALS['extra_scripts'] = ['/assets/js/setting.js'];
include __DIR__ . '/home/header.php';
?>

<link rel="stylesheet" href="/assets/css/setting.css">

<div class="settings-wrap">
    <a href="/" class="settings-back">← 返回首页</a>

    <h1 class="settings-title">个人设置</h1>
    <p class="settings-desc">这些设置只保存在你当前浏览器，不会影响其他用户</p>

    <!-- ===== 外观 ===== -->
    <div class="settings-section">
        <div class="settings-section-title">外观</div>

        <div class="settings-item">
            <div class="settings-item-info">
                <div class="settings-item-label">主题模式</div>
                <div class="settings-item-desc">日间 / 夜间 / 跟随系统</div>
            </div>
            <div class="settings-item-control">
                <select class="settings-select" id="set-theme">
                    <option value="light">日间模式</option>
                    <option value="dark">夜间模式</option>
                    <option value="auto">跟随系统</option>
                </select>
            </div>
        </div>

        <div class="settings-item">
            <div class="settings-item-info">
                <div class="settings-item-label">每行显示列数</div>
                <div class="settings-item-desc">自适应 = 根据屏幕宽度自动排列</div>
            </div>
            <div class="settings-item-control">
                <select class="settings-select" id="set-columns">
                    <option value="auto">自适应</option>
                    <option value="3">3 列</option>
                    <option value="4">4 列</option>
                    <option value="5">5 列</option>
                    <option value="6">6 列</option>
                </select>
            </div>
        </div>
    </div>

    <!-- ===== 内容 ===== -->
    <div class="settings-section">
        <div class="settings-section-title">内容</div>

        <div class="settings-item">
            <div class="settings-item-info">
                <div class="settings-item-label">卡片默认展开条数</div>
                <div class="settings-item-desc">折叠状态下每张卡片显示多少条热搜</div>
            </div>
            <div class="settings-item-control">
                <select class="settings-select" id="set-display-limit">
                    <option value="5">5 条</option>
                    <option value="10">10 条</option>
                </select>
            </div>
        </div>

        <div class="settings-item">
            <div class="settings-item-info">
                <div class="settings-item-label">热搜链接打开方式</div>
                <div class="settings-item-desc">点击热搜标题时在哪个窗口打开</div>
            </div>
            <div class="settings-item-control">
                <select class="settings-select" id="set-link-target">
                    <option value="_blank">新窗口打开</option>
                    <option value="_self">当前窗口打开</option>
                </select>
            </div>
        </div>
    </div>

    <!-- ===== 显示开关 ===== -->
    <div class="settings-section">
        <div class="settings-section-title">显示开关</div>

        <div class="settings-item">
            <div class="settings-item-info">
                <div class="settings-item-label">显示热度值</div>
                <div class="settings-item-desc">小火苗图标后面的数字</div>
            </div>
            <div class="settings-item-control">
                <div class="settings-switch" id="set-show-heat"></div>
            </div>
        </div>

        <div class="settings-item">
            <div class="settings-item-info">
                <div class="settings-item-label">显示来源链接</div>
                <div class="settings-item-desc">卡片右上角"详情"链接</div>
            </div>
            <div class="settings-item-control">
                <div class="settings-switch" id="set-show-source"></div>
            </div>
        </div>

        <div class="settings-item">
            <div class="settings-item-info">
                <div class="settings-item-label">显示刷新按钮</div>
                <div class="settings-item-desc">卡片右下角刷新（受 15 秒冷却限制）</div>
            </div>
            <div class="settings-item-control">
                <div class="settings-switch" id="set-show-refresh"></div>
            </div>
        </div>
    </div>

    <!-- ===== 操作 ===== -->
    <div class="settings-actions">
        <button class="settings-btn settings-btn-ghost" id="btn-reset">恢复默认</button>
        <button class="settings-btn settings-btn-primary" id="btn-save">保存设置</button>
    </div>
</div>

<?php include __DIR__ . '/home/footer.php'; ?>
