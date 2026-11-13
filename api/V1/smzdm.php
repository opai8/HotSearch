<?php
/**
 * 什么值得买处理器
 * 数据来源: https://post.smzdm.com/rank/json_more/
 */

class SmzdmHotSearch {

    public function handle($limit = 20) {
        // 什么值得买只爬 20 条
        $limit = min((int)$limit, 20);

        try {
            $url = 'https://post.smzdm.com/rank/json_more/?unit=1';
            $json = $this->curl($url);

            if (empty($json)) {
                return array('success' => false, 'message' => '请求失败');
            }

            $data = json_decode($json, true);
            if (!$data || !isset($data['data']) || !is_array($data['data'])) {
                return array('success' => false, 'message' => '解析失败');
            }

            $list = $data['data'];
            $result = array();
            foreach ($list as $v) {
                if (count($result) >= $limit) break;

                $result[] = array(
                    'index' => count($result) + 1,
                    'title' => isset($v['title']) ? $v['title'] : '',
                    'url'   => isset($v['jump_link']) ? $v['jump_link'] : '',
                    'hot'   => isset($v['collection_count']) ? intval($v['collection_count']) : '',
                    'desc'  => isset($v['content']) ? $v['content'] : '',
                    'pic'   => isset($v['pic_url']) ? $v['pic_url'] : ''
                );
            }

            return array(
                'success'     => true,
                'title'       => '什么值得买',
                'subtitle'    => '今日热门',
                'total'       => count($result),
                'update_time' => date('Y-m-d H:i:s'),
                'data'        => $result
            );

        } catch (Exception $e) {
            return array('success' => false, 'message' => '获取失败: ' . $e->getMessage());
        }
    }

    private function curl($url, $headers = array()) {
        $defaultHeaders = array(
            "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36",
            "Accept: application/json, text/plain, */*",
            "Referer: https://www.smzdm.com/"
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
