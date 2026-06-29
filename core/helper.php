<?php
/**
 * 工具函数模块（时间格式化 / HTML 转义）
 */

/**
 * 格式化更新时间
 * @param int|null $timestamp 时间戳，null 表示当前
 * @param string $format 格式化字符串
 * @return string
 */
function getUpdateTime($timestamp = null, $format = 'Y-m-d H:i') {
    if ($timestamp === null) {
        $timestamp = time();
    }
    return date($format, $timestamp);
}

/**
 * 格式化相对时间（几分钟/小时/天前）
 * @param int|null $timestamp 时间戳
 * @return string
 */
function getRelativeTime($timestamp = null) {
    if ($timestamp === null) {
        $timestamp = time();
    }

    $diff = time() - $timestamp;

    if ($diff < 60) {
        return '刚刚';
    } elseif ($diff < 3600) {
        return intval($diff / 60) . ' 分钟前';
    } elseif ($diff < 86400) {
        return intval($diff / 3600) . ' 小时前';
    } elseif ($diff < 2592000) {
        return intval($diff / 86400) . ' 天前';
    } elseif ($diff < 31536000) {
        return intval($diff / 2592000) . ' 个月前';
    } else {
        return intval($diff / 31536000) . ' 年前';
    }
}

/**
 * 转义 HTML 特殊字符（安全输出必备）
 * @param string $text
 * @return string
 */
function escapeHtml($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}
?>
