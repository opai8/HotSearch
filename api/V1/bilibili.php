<?php
/**
 * B站热搜API处理器
 * 
 * 功能: 获取B站热搜榜单数据
 * 数据来源: https://app.bilibili.com/x/v2/search/trending/ranking
 * 
 * 使用方法:
 *   /api/index.php/bilibili          - 获取默认50条
 *   /api/index.php/bilibili?limit=10 - 获取10条
 */

class BilibiliHotSearch {
    
    /**
     * 处理请求并返回数据
     * 
     * @param int $limit 返回条数限制
     * @return array 热搜数据
     */
    public function handle($limit = 50) {
        try {
            // 调用B站API接口
            $url = 'https://app.bilibili.com/x/v2/search/trending/ranking';
            $response = $this->httpRequest($url);
            
            // 检查响应是否为空
            if (empty($response)) {
                return array(
                    'success' => false,
                    'message' => '获取页面失败'
                );
            }
            
            // 解析JSON数据
            $data = json_decode($response, true);
            
            // 检查JSON解析是否成功
            if (json_last_error() !== JSON_ERROR_NONE) {
                return array(
                    'success' => false,
                    'message' => 'JSON解析错误: ' . json_last_error_msg()
                );
            }
            
            // 检查返回码和数据是否存在
            if (!isset($data['code']) || $data['code'] !== 0 || empty($data['data']['list'])) {
                return array(
                    'success' => false,
                    'message' => 'API返回数据格式错误'
                );
            }
            
            // 格式化数据
            $result = array();
            foreach ($data['data']['list'] as $index => $item) {
                // 达到限制条数时停止
                if (count($result) >= $limit) {
                    break;
                }
                
                $result[] = array(
                    'index' => $index + 1,
                    'title' => isset($item['show_name']) ? $item['show_name'] : (isset($item['keyword']) ? $item['keyword'] : ''),
                    'url' => 'https://search.bilibili.com/all?keyword=' . urlencode(isset($item['keyword']) ? $item['keyword'] : ''),
                    'hot' => isset($item['hot_score']) ? $item['hot_score'] : ''
                );
            }
            
            // 返回成功响应
            return array(
                'success' => true,
                'title' => 'B站热搜',
                'subtitle' => '实时热点',
                'total' => count($result),
                'update_time' => date('Y-m-d H:i:s'),
                'data' => $result
            );
            
        } catch (Exception $e) {
            // 捕获异常并返回错误信息
            return array(
                'success' => false,
                'message' => '获取数据失败: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * 发起HTTP请求
     * 
     * @param string $url 请求地址
     * @return string 响应内容
     */
    private function httpRequest($url) {
        // 设置请求头
        $header = array(
            'Accept: application/json',
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
        );
        
        // 初始化curl
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // 返回数据而不是输出
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // 跳过SSL验证
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10); // 超时时间10秒
        
        // 执行请求
        $output = curl_exec($ch);
        curl_close($ch);
        
        return $output;
    }
}
?>