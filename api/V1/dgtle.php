<?php
/**
 * 数字尾巴处理器
 * 数据来源: https://opser.api.dgtle.com/v2/news/index
 */

class DgtleHotSearch {

    public function handle($limit = 50) {
        try {
            $url = 'https://opser.api.dgtle.com/v2/news/index';
            $json = $this->curl($url);

            if (empty($json)) {
                return array('success' => false, 'message' => '请求失败');
            }

            $data = json_decode($json, true);
            if (!$data || !isset($data['items']) || !is_array($data['items'])) {
                return array('success' => false, 'message' => '解析失败');
            }

            $list = $data['items'];
            $result = array();
            foreach ($list as $v) {
                if (count($result) >= $limit) break;

                $id = isset($v['id']) ? $v['id'] : '';
                $type = isset($v['type']) ? $v['type'] : 'news';

                $result[] = array(
                    'index' => count($result) + 1,
                    'title' => isset($v['title']) ? $v['title'] : (isset($v['content']) ? mb_substr($v['content'], 0, 50) : ''),
                    'url'   => $id ? "https://www.dgtle.com/news-{$id}-{$type}.html" : '',
                    'hot'   => isset($v['membernum']) ? intval($v['membernum']) : '',
                    'desc'  => isset($v['content']) ? $v['content'] : '',
                    'pic'   => isset($v['cover']) ? $v['cover'] : ''
                );
            }

            return array(
                'success'     => true,
                'title'       => '数字尾巴',
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
            "Referer: https://www.dgtle.com/"
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
