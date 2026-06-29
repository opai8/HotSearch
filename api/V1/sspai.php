<?php
/**
 * 少数派处理器（使用官方API）
 *
 * 数据来源: https://sspai.com/api/v1/article/tag/page/get?limit=20&tag=热门文章
 */

class SspaiHotSearch {

    public function handle($limit = 20) {
        try {
            $url = 'https://sspai.com/api/v1/article/tag/page/get?limit=' . intval($limit) . '&tag=' . urlencode('热门文章');
            $json = $this->curl($url);

            if (empty($json)) {
                return array('success' => false, 'message' => 'API请求失败');
            }

            $data = json_decode($json, true);

            if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
                if (preg_match('/\{.*\}/s', $json, $matches)) {
                    $data = json_decode($matches[0], true);
                }
                if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
                    return array('success' => false, 'message' => 'JSON解析失败: ' . json_last_error_msg());
                }
            }

            if (!isset($data['data']) || !is_array($data['data'])) {
                return array('success' => false, 'message' => '数据格式错误');
            }

            $items = array();
            foreach ($data['data'] as $v) {
                if (count($items) >= $limit) break;

                $title = isset($v['title']) ? $v['title'] : '';
                $id    = isset($v['id']) ? $v['id'] : '';
                $likes = isset($v['like_count']) ? $v['like_count'] : 0;

                if (empty($title)) continue;

                $items[] = array(
                    'index' => count($items) + 1,
                    'title' => $title,
                    'url'   => 'https://sspai.com/post/' . $id,
                    'hot'   => $likes ? ($likes . ' 👍') : '',
                    'desc'  => isset($v['summary']) ? $v['summary'] : '',
                    'pic'   => isset($v['banner']) ? $v['banner'] : ''
                );
            }

            return array(
                'success'     => true,
                'title'       => '少数派',
                'subtitle'    => '热门',
                'total'       => count($items),
                'update_time' => date('Y-m-d H:i:s'),
                'data'        => $items
            );

        } catch (Exception $e) {
            return array('success' => false, 'message' => '获取失败: ' . $e->getMessage());
        }
    }

    private function curl($url) {
        $header = array(
            "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36",
            "Accept: application/json",
            "Referer: https://sspai.com/"
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
