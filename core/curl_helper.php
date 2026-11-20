<?php
/**
 * curl 封装：统一 SSL 验证配置 + 公共默认参数 + 错误日志
 * ============================================================
 * 用法：
 *   require_once __DIR__ . '/curl_helper.php';
 *   $ch = smartCurlInit();      // 替代原生 curl_init()
 *   curl_setopt($ch, CURLOPT_URL, $url);
 *   ...
 *   $html = smartCurlExec($ch);  // 替代 curl_exec()，自动记录错误
 *
 * SSL 验证：由 config.php 的 'curl_ssl_verify' 开关控制
 *   false = 跳过（调试/本地环境，开箱即用）
 *   true  = 严格验证（生产环境，安全）
 *
 * 超时（公共默认参数，handler 可覆盖）：
 *   CURLOPT_TIMEOUT        = 15 秒（整个请求）
 *   CURLOPT_CONNECTTIMEOUT = 10 秒（连接阶段）
 * ============================================================
 */

if (!function_exists('smartCurlInit')) {
    function smartCurlInit() {
        $ch = curl_init();

        // 读取全局开关（默认 false，本地开箱即用）
        $verify = false;
        if (isset($GLOBALS['site_config']['curl_ssl_verify'])) {
            $verify = (bool)$GLOBALS['site_config']['curl_ssl_verify'];
        }

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $verify);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $verify ? 2 : 0);

        // 公共默认参数（可被后续 curl_setopt 覆盖）
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

        return $ch;
    }
}

if (!function_exists('smartCurlExec')) {
    /**
     * 执行 curl + 自动错误日志
     *
     * 替代原生 curl_exec()。失败时自动写入项目 logs/curl_errors.log，
     * 返回 false 让调用方判断。成功返回响应字符串。
     *
     * 为什么用项目自己的 logs/ 而不是 PHP 内置 error_log？
     *   - 项目可控，路径固定：{项目根}/logs/curl_errors.log
     *   - 不依赖 php.ini 配置，部署到哪都能找到
     *   - 错误集中，排查方便（不用翻 phpStudy/logs/php_error.log）
     *
     * @param resource $ch curl 句柄
     * @param string $label 可选标签（平台 id），方便日志里定位
     * @return string|false
     */
    function smartCurlExec($ch, $label = '') {
        $result = curl_exec($ch);

        if ($result === false) {
            $errno = curl_errno($ch);
            $error = curl_error($ch);
            $info  = curl_getinfo($ch);
            $url   = isset($info['url']) ? $info['url'] : '(unknown)';

            // 写入项目自己的 logs/ 目录
            $projectRoot = dirname(__DIR__);
            $logDir  = $projectRoot . '/logs';
            $logFile = $logDir . '/curl_errors.log';

            // 确保目录存在
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0755, true);
            }

            // 格式化一行日志：时间 [label] errno=xx error=xxx url=xxx http_code=xx
            $line = sprintf(
                "[%s] %sFAIL: errno=%d error=%s url=%s http_code=%d\n",
                date('Y-m-d H:i:s'),
                $label !== '' ? "[$label] " : '',
                $errno, $error, $url,
                isset($info['http_code']) ? $info['http_code'] : 0
            );

            @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
        }

        return $result;
    }
}
