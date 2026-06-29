<?php
/**
 * 百度热搜API处理器
 */

class BaiduHotSearch {
    
    /**
     * 获取百度热搜数据
     * @param int $limit 返回条数限制
     * @return array 热搜数据
     */
    public function handle($limit = 50) {
        try {
            // 获取HTML内容
            $html = $this->curl('https://top.baidu.com/board?tab=realtime');
            
            if (empty($html)) {
                return array(
                    'success' => false,
                    'message' => '获取页面失败'
                );
            }
            
            // 移除换行和多余空格
            $html = preg_replace('/\s+/', ' ', $html);
            
            // 提取JSON数据 - 修复正则表达式
            preg_match('/<!--s-data:\s*(.*?)\s*-->/', $html, $matches);
            
            if (empty($matches[1])) {
                return array(
                    'success' => false,
                    'message' => '数据解析失败，未找到数据标记'
                );
            }
            
            $jsonData = json_decode($matches[1], true);
            
            if (!$jsonData || !isset($jsonData['data']['cards'])) {
                return array(
                    'success' => false,
                    'message' => 'JSON解析失败或数据结构不正确'
                );
            }
            
            // 格式化数据
            $tempArr = array();
            foreach ($jsonData['data']['cards'] as $v) {
                if (!isset($v['content']) || !is_array($v['content'])) {
                    continue;
                }
                
                foreach ($v['content'] as $k => $_v) {
                    // 应用limit限制
                    if (count($tempArr) >= $limit) {
                        break 2;
                    }
                    
                    array_push($tempArr, array(
                        'index' => count($tempArr) + 1,
                        'title' => isset($_v['word']) ? $_v['word'] : '',
                        'desc' => isset($_v['desc']) ? $_v['desc'] : '',
                        'pic' => isset($_v['img']) ? $_v['img'] : '',
                        'url' => isset($_v['url']) ? $_v['url'] : '',
                        'hot' => isset($_v['hotScore']) ? $_v['hotScore'] : '',
                        'mobilUrl' => isset($_v['appUrl']) ? $_v['appUrl'] : ''
                    ));
                }
            }
            
            return array(
                'success' => true,
                'title' => '百度热点',
                'subtitle' => '指数',
                'total' => count($tempArr),
                'update_time' => date('Y-m-d H:i:s', time()),
                'data' => $tempArr
            );
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => '获取数据失败: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * 发起HTTP请求
     */
    private function curl($url) {
        $header = array(
            "accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8",
            "Accept-Language: zh-CN,zh;q=0.9",
            "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"
        );
        
        // 随机IP
        $ip = rand(0, 255) . '.' . rand(0, 255) . '.' . rand(0, 255) . '.' . rand(0, 255);
        $header[] = "CLIENT-IP:" . $ip;
        $header[] = "X-FORWARDED-FOR:" . $ip;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
        $output = curl_exec($ch);
        curl_close($ch);
        
        return $output;
    }
}
?>