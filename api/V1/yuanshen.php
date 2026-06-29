<?php
/**
 * 原神公告处理器
 *
 * 数据来源: https://bbs-api-static.miyoushe.com/painter/wapi/getNewsList?client_type=4&gids=2&last_id=&page_size=20&type=1
 */

class YuanshenHotSearch {

    public function handle($limit = 50) {
        try {
            $url = 'https://bbs-api-static.miyoushe.com/painter/wapi/getNewsList?client_type=4&gids=2&last_id=&page_size=' . intval($limit) . '&type=1';
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

            $articles = array();
            if (isset($data['data']) && is_array($data['data'])) {
                if (isset($data['data']['list']) && is_array($data['data']['list'])) {
                    $articles = $data['data']['list'];
                } elseif (isset($data['data']['items']) && is_array($data['data']['items'])) {
                    $articles = $data['data']['items'];
                } else {
                    $articles = $data['data'];
                }
            } elseif (isset($data['result']) && is_array($data['result'])) {
                $articles = $data['result'];
            }

            if (empty($articles)) {
                return array('success' => false, 'message' => '数据格式错误');
            }

            $items = array();
            foreach ($articles as $v) {
                if (count($items) >= $limit) break;

                $post = isset($v['post']) && is_array($v['post']) ? $v['post'] : $v;

                $title = '';
                foreach (array('title', 'name', 'subject', 'post_title') as $k) {
                    if (isset($post[$k]) && is_string($post[$k])) {
                        $title = $post[$k];
                        break;
                    }
                }

                $id = '';
                foreach (array('id', 'post_id', 'newsId', 'article_id', 'aid') as $k) {
                    if (isset($post[$k]) && (is_string($post[$k]) || is_numeric($post[$k]))) {
                        $id = $post[$k];
                        break;
                    }
                }

                $hot = '';
                foreach (array('view', 'views', 'view_count', 'stat_view', 'like_num') as $k) {
                    if (isset($post[$k]) && (is_string($post[$k]) || is_numeric($post[$k]))) {
                        $hot = $post[$k];
                        break;
                    }
                }

                if (empty($title)) continue;

                $cover = '';
                if (isset($v['cover']) && is_array($v['cover']) && isset($v['cover']['url'])) {
                    $cover = $v['cover']['url'];
                } elseif (isset($post['cover']) && is_string($post['cover'])) {
                    $cover = $post['cover'];
                }

                $items[] = array(
                    'index' => count($items) + 1,
                    'title' => $title,
                    'url'   => $id ? ('https://www.miyoushe.com/ys/article/' . $id) : 'https://www.miyoushe.com/ys/',
                    'hot'   => $hot ? (is_numeric($hot) ? number_format($hot) : $hot) : '',
                    'desc'  => isset($post['summary']) ? $post['summary'] : (isset($post['description']) ? $post['description'] : ''),
                    'pic'   => $cover
                );
            }

            return array(
                'success'     => true,
                'title'       => '原神',
                'subtitle'    => '公告',
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
            "Referer: https://www.miyoushe.com/",
            "Origin: https://www.miyoushe.com"
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
