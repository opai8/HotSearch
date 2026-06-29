<?php
/**
 * 标准化响应格式类
 * 统一API响应格式
 */

class Response {
    
    /**
     * 成功响应
     * @param array $data 响应数据
     * @param string $message 响应消息
     * @param int $code 状态码
     */
    public static function success($data = array(), $message = 'success', $code = 200) {
        $response = array(
            'success' => true,
            'code' => $code,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s', time())
        );
        
        self::send($response, $code);
    }
    
    /**
     * 错误响应
     * @param string $message 错误消息
     * @param int $code 错误码
     * @param array $data 附加数据
     */
    public static function error($message = 'error', $code = 400, $data = array()) {
        $response = array(
            'success' => false,
            'code' => $code,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s', time())
        );
        
        self::send($response, $code);
    }
    
    /**
     * 未找到响应
     * @param string $message 错误消息
     */
    public static function notFound($message = '接口不存在') {
        self::error($message, 404);
    }
    
    /**
     * 方法不允许响应
     * @param string $message 错误消息
     */
    public static function methodNotAllowed($message = '请求方法不正确') {
        self::error($message, 405);
    }
    
    /**
     * 参数验证失败响应
     * @param string $message 错误消息
     */
    public static function validationError($message = '参数验证失败') {
        self::error($message, 422);
    }
    
    /**
     * 发送JSON响应
     * @param array $response 响应数据
     * @param int $httpCode HTTP状态码
     */
    private static function send($response, $httpCode = 200) {
        // 设置HTTP状态码
        http_response_code($httpCode);
        
        // 设置响应头
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        header('Access-Control-Max-Age: 86400');
        
        // 输出JSON
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * 格式化热搜数据为统一格式
     * @param array $rawData 原始数据
     * @param string $source 数据来源
     * @return array 格式化后的数据
     */
    public static function formatHotData($rawData, $source) {
        $formatted = array();
        
        foreach ($rawData as $index => $item) {
            $formatted[] = array(
                'index' => $index + 1,
                'title' => isset($item['title']) ? $item['title'] : '',
                'desc' => isset($item['desc']) ? $item['desc'] : '',
                'pic' => isset($item['pic']) ? $item['pic'] : '',
                'url' => isset($item['url']) ? $item['url'] : '',
                'hot' => isset($item['hot']) ? $item['hot'] : '',
                'source' => $source
            );
        }
        
        return $formatted;
    }
}
?>
