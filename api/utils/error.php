<?php
/**
 * 错误处理类
 * 统一处理API错误 - 直接输出错误信息,不记录日志
 */

class ErrorHandler {
    
    /**
     * 错误码定义
     */
    const ERROR_PLATFORM_NOT_SUPPORTED = 1001;
    const ERROR_HANDLER_NOT_FOUND = 1002;
    const ERROR_INVALID_LIMIT = 1003;
    const ERROR_CURL_FAILED = 2001;
    const ERROR_PARSE_FAILED = 2002;
    
    /**
     * 错误消息映射
     */
    private static $errorMessages = array(
        self::ERROR_PLATFORM_NOT_SUPPORTED => '不支持的平台',
        self::ERROR_HANDLER_NOT_FOUND => 'API处理器不存在',
        self::ERROR_INVALID_LIMIT => '无效的条数限制',
        self::ERROR_CURL_FAILED => '网络请求失败',
        self::ERROR_PARSE_FAILED => '数据解析失败'
    );
    
    /**
     * 获取错误消息
     * @param int $code 错误码
     * @return string 错误消息
     */
    public static function getMessage($code) {
        return isset(self::$errorMessages[$code]) ? self::$errorMessages[$code] : '未知错误';
    }
    
    /**
     * 处理平台不支持错误
     * @param string $platform 平台名称
     * @param array $supported 支持的平台列表
     */
    public static function platformNotSupported($platform, $supported = array()) {
        $message = '不支持的平台: ' . $platform;
        if (!empty($supported)) {
            $message .= ', 支持的平台: ' . implode(', ', $supported);
        }
        Response::error($message, 400, array(
            'platform' => $platform,
            'supported' => $supported
        ));
    }
    
    /**
     * 处理参数验证错误
     * @param string $param 参数名
     * @param string $message 错误消息
     */
    public static function invalidParameter($param, $message = '') {
        Response::validationError('参数 ' . $param . ' 无效: ' . $message);
    }
    
    /**
     * 处理文件不存在错误
     * @param string $file 文件路径
     */
    public static function handlerNotFound($file) {
        Response::error('API处理文件不存在: ' . basename($file), 404, array(
            'file' => $file
        ));
    }
    
    /**
     * 处理类不存在错误
     * @param string $className 类名
     */
    public static function classNotFound($className) {
        Response::error('API处理类不存在: ' . $className, 500, array(
            'class' => $className
        ));
    }
    
    /**
     * 处理方法不存在错误
     * @param string $className 类名
     * @param string $method 方法名
     */
    public static function methodNotFound($className, $method) {
        Response::error('API处理方法不存在: ' . $className . '::' . $method, 500, array(
            'class' => $className,
            'method' => $method
        ));
    }
    
    /**
     * 输出错误信息 (不记录日志)
     * @param string $message 错误消息
     * @param int $level 错误级别 (已废弃,仅为兼容)
     */
    public static function log($message, $level = E_ERROR) {
        // 不记录日志,直接输出错误信息到响应
        // 此方法保留仅为兼容现有调用
    }
}
?>
