<?php
/**
 * 新浪新闻热点榜处理器
 * 数据来源: https://top.news.sina.com.cn/ws/GetTopDataList.php
 */

class SinaNewsHotSearch {

    public function handle($limit = 50) {
        try {
            // 今天日期 YYYYMMDD
            $today = date('Ymd');
            $url = "https://top.news.sina.com.cn/ws/GetTopDataList.php?top_type=day&top_cat=www_www_all_suda_suda&top_time={$today}&top_show_num=50";
            $raw = $this->curl($url);

            if (empty($raw)) {
                return array('success' => false, 'message' => '请求失败');
            }

            // 解析 JSONP: var data = {...};
            if (!preg_match('/^var data = (.*?);\s*$/s', trim($raw), $m)) {
                return array('success' => false, 'message' => '数据格式异常');
            }

            $json = json_decode($m[1], true);
            if (!$json || !isset($json['data']) || !is_array($json['data'])) {
                return array('success' => false, 'message' => '解析失败');
            }

            $result = array();
            foreach ($json['data'] as $v) {
                if (count($result) >= $limit) break;

                $hot = isset($v['top_num']) ? floatval(str_replace(',', '', $v['top_num'])) : '';

                $result[] = array(
                    'index' => count($result) + 1,
                    'title' => isset($v['title']) ? $v['title'] : '',
                    'url'   => isset($v['url']) ? $v['url'] : '',
                    'hot'   => $hot,
                    'desc'  => '',
                    'pic'   => ''
                );
            }

            return array(
                'success'     => true,
                'title'       => '新浪新闻',
                'subtitle'    => '热点榜',
                'total'       => count($result),
                'update_time' => date('Y-m-d H:i:s'),
                'data'        => $result
            );

        } catch (Exception $e) {
            return array('success' => false, 'message' => '获取失败: ' . $e->getMessage());
        }
    }

    private function curl($url) {
        $header = array(
            "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36",
            "Accept: */*"
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
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