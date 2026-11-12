<?php
/**
 * 新浪热榜处理器（移动端 API，区别于已有的 sina_news）
 * 数据来源: https://newsapp.sina.cn/api/hotlist
 */

class SinaHotSearch {

    public function handle($limit = 50) {
        try {
            $url = 'https://newsapp.sina.cn/api/hotlist?newsId=HB-1-snhs%2Ftop_news_list-all';
            $json = $this->curl($url);

            if (empty($json)) {
                return array('success' => false, 'message' => '请求失败');
            }

            $data = json_decode($json, true);
            if (!$data || !isset($data['data']['hotList']) || !is_array($data['data']['hotList'])) {
                return array('success' => false, 'message' => '解析失败');
            }

            $list = $data['data']['hotList'];
            $result = array();
            foreach ($list as $v) {
                if (count($result) >= $limit) break;

                $result[] = array(
                    'index' => count($result) + 1,
                    'title' => isset($v['info']['title']) ? $v['info']['title'] : '',
                    'url'   => isset($v['base']['url']) ? $v['base']['url'] : '',
                    'hot'   => isset($v['info']['hotValue']) ? $this->parseChineseNumber($v['info']['hotValue']) : '',
                    'desc'  => '',
                    'pic'   => ''
                );
            }

            return array(
                'success'     => true,
                'title'       => '新浪热榜',
                'subtitle'    => '综合热榜',
                'total'       => count($result),
                'update_time' => date('Y-m-d H:i:s'),
                'data'        => $result
            );

        } catch (Exception $e) {
            return array('success' => false, 'message' => '获取失败: ' . $e->getMessage());
        }
    }

    /**
     * 解析中文数字（如 1.2万 → 12000）
     */
    private function parseChineseNumber($str) {
        if (!is_string($str)) return intval($str);
        $str = trim($str);
        if (is_numeric($str)) return intval($str);
        if (preg_match('/^([\d\.]+)万$/', $str, $m)) return intval(floatval($m[1]) * 10000);
        if (preg_match('/^([\d\.]+)亿$/', $str, $m)) return intval(floatval($m[1]) * 100000000);
        return intval($str);
    }

    private function curl($url, $headers = array()) {
        $defaultHeaders = array(
            "User-Agent: Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148",
            "Accept: application/json, text/plain, */*",
            "Referer: https://newsapp.sina.cn/"
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge($defaultHeaders, $headers));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $output = curl_exec($ch);
        curl_close($ch);
        return $output ?: '';
    }
}
?>
