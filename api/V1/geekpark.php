<?php
/**
 * 极客公园处理器
 * 数据来源: https://mainssl.geekpark.net/api/v2
 */

class GeekparkHotSearch {

    public function handle($limit = 50) {
        try {
            $url = 'https://mainssl.geekpark.net/api/v2';
            $json = $this->curl($url);

            if (empty($json)) {
                return array('success' => false, 'message' => '请求失败');
            }

            $data = json_decode($json, true);
            if (!$data || !isset($data['homepage_posts']) || !is_array($data['homepage_posts'])) {
                return array('success' => false, 'message' => '解析失败');
            }

            $list = $data['homepage_posts'];
            $result = array();
            foreach ($list as $v) {
                if (count($result) >= $limit) break;

                if (!isset($v['post'])) continue;
                $post = $v['post'];
                $id = isset($post['id']) ? $post['id'] : '';

                $result[] = array(
                    'index' => count($result) + 1,
                    'title' => isset($post['title']) ? $post['title'] : '',
                    'url'   => $id ? "https://www.geekpark.net/news/{$id}" : '',
                    'hot'   => isset($post['views']) ? intval($post['views']) : '',
                    'desc'  => isset($post['abstract']) ? $post['abstract'] : '',
                    'pic'   => isset($post['cover_url']) ? $post['cover_url'] : ''
                );
            }

            return array(
                'success'     => true,
                'title'       => '极客公园',
                'subtitle'    => '热门文章',
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
            "Referer: https://www.geekpark.net/"
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
