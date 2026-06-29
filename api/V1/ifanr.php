<?php
/**
 * 爱范儿处理器
 *
 * 数据来源: https://sso.ifanr.com/api/v5/wp/buzz/?limit=20&offset=0
 */

class IfanrHotSearch {

    public function handle($limit = 50) {
        try {
            $url = 'https://sso.ifanr.com/api/v5/wp/buzz/?limit=' . intval($limit) . '&offset=0';
            $json = $this->curl($url);

            if (empty($json)) {
                return array('success' => false, 'message' => 'API请求失败');
            }

            $data = json_decode($json, true);

            if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
                return array('success' => false, 'message' => 'JSON解析失败: ' . json_last_error_msg());
            }

            $articles = array();
            if (isset($data['objects']) && is_array($data['objects'])) {
                $articles = $data['objects'];
            }

            if (empty($articles)) {
                return array('success' => false, 'message' => '数据格式错误');
            }

            $items = array();
            foreach ($articles as $v) {
                if (count($items) >= $limit) break;

                $id = isset($v['post_id']) ? $v['post_id'] : '';

                $title = '';
                if (isset($v['post_content']) && is_string($v['post_content'])) {
                    $title = strip_tags($v['post_content']);
                    $title = preg_replace('/\s+/', ' ', $title);
                    $title = trim($title);
                    $title = mb_substr($title, 0, 50);
                }

                $like = isset($v['like_count']) ? $v['like_count'] : 0;
                $comment = isset($v['comment_count']) ? $v['comment_count'] : 0;

                if (empty($title)) continue;

                $items[] = array(
                    'index' => count($items) + 1,
                    'title' => $title,
                    'url'   => $id ? ('https://www.ifanr.com/digest/' . $id) : 'https://www.ifanr.com/',
                    'hot'   => $like ? ($like . ' 👍') : '',
                    'desc'  => $comment ? ($comment . ' 评论') : '',
                    'pic'   => ''
                );
            }

            return array(
                'success'     => true,
                'title'       => '爱范儿',
                'subtitle'    => '热文',
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
            "Accept: application/json, text/plain, */*",
            "Referer: https://www.ifanr.com/"
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        $output = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 400) return '';
        return $output ?: '';
    }
}
?>
