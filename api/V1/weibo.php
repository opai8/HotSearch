<?php
/**
 * 微博热搜API处理器
 */

class WeiboHotSearch {
    
    /**
     * 获取微博热搜数据
     * @param int $limit 返回条数限制
     * @return array 热搜数据
     */
    public function handle($limit = 50) {
        try {
            // 生成Cookie
            $md5 = md5(time());
            $cookie = "Cookie: {$md5}:FG=1";
            
            // 获取微博热搜数据
            $jsonRes = json_decode($this->curl(
                'https://weibo.com/ajax/side/hotSearch', 
                null, 
                $cookie, 
                "https://s.weibo.com"
            ), true);
            
            if (!isset($jsonRes['data']['realtime']) || !is_array($jsonRes['data']['realtime'])) {
                return array(
                    'success' => false,
                    'message' => '数据解析失败'
                );
            }
            
            // 格式化数据
            $tempArr = array();
            foreach ($jsonRes['data']['realtime'] as $k => $v) {
                // 应用limit限制
                if (count($tempArr) >= $limit) {
                    break;
                }
                
                array_push($tempArr, array(
                    'index' => count($tempArr) + 1,
                    'title' => isset($v['note']) ? $v['note'] : '',
                    'hot' => isset($v['num']) ? $v['num'] . '万' : '',
                    'url' => "https://s.weibo.com/weibo?q=" . urlencode(isset($v['word_scheme']) ? $v['word_scheme'] : '') . "&t=31&band_rank=12&Refer=top",
                    'mobilUrl' => "https://s.weibo.com/weibo?q=" . urlencode(isset($v['word_scheme']) ? $v['word_scheme'] : '') . "&t=31&band_rank=12&Refer=top"
                ));
            }
            
            return array(
                'success' => true,
                'title' => '微博',
                'subtitle' => '热搜榜',
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
    private function curl($url, $header = null, $cookie = null, $refer = 'https://www.baidu.com') {
        if ($header === null) {
            $header = array(
                "accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8",
                "Accept-Language: zh-CN,zh;q=0.9",
                "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"
            );
        }
        
        // 随机IP
        $ip = rand(0, 255) . '.' . rand(0, 255) . '.' . rand(0, 255) . '.' . rand(0, 255);
        $header[] = "CLIENT-IP:" . $ip;
        $header[] = "X-FORWARDED-FOR:" . $ip;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_COOKIE, $cookie);
        curl_setopt($ch, CURLOPT_REFERER, $refer);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $output = curl_exec($ch);
        curl_close($ch);
        
        return $output;
    }
}
?>