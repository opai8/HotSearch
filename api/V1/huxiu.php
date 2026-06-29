<?php
/**
 * 虎嗅网处理器
 *
 * 数据来源: https://moment-api.huxiu.com/web-v3/moment/feed?platform=www
 */

class HuxiuHotSearch {

    public function handle($limit = 50) {
        try {
            $url = 'https://moment-api.huxiu.com/web-v3/moment/feed?platform=www';
            $json = $this->curl($url);

            if (empty($json)) {
                return array('success' => false, 'message' => 'API请求失败');
            }

            $data = json_decode($json, true);

            if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
                return array('success' => false, 'message' => 'JSON解析失败: ' . json_last_error_msg());
            }

            $articles = array();
            if (isset($data['data']) && is_array($data['data'])) {
                if (isset($data['data']['moment_list']['datalist']) && is_array($data['data']['moment_list']['datalist'])) {
                    $articles = $data['data']['moment_list']['datalist'];
                } elseif (isset($data['data']['momentList']) && is_array($data['data']['momentList'])) {
                    $articles = $data['data']['momentList'];
                } elseif (isset($data['data']['list']) && is_array($data['data']['list'])) {
                    $articles = $data['data']['list'];
                }
            }

            if (empty($articles)) {
                return array('success' => false, 'message' => '数据格式错误');
            }

            $items = array();
            foreach ($articles as $v) {
                if (count($items) >= $limit) break;

                $title = '';
                if (isset($v['content']) && is_string($v['content'])) {
                    $title = strip_tags($v['content']);
                    $title = preg_replace('/\s+/', ' ', $title);
                    $title = trim($title);
                    $title = mb_substr($title, 0, 60);
                }
                if (empty($title) && isset($v['title'])) {
                    $title = $v['title'];
                }

                $id = '';
                foreach (array('object_id', 'id', 'moment_id', 'article_id') as $k) {
                    if (isset($v[$k]) && (is_string($v[$k]) || is_numeric($v[$k]))) {
                        $id = $v[$k];
                        break;
                    }
                }

                $hot = '';
                if (isset($v['count_info']) && is_array($v['count_info'])) {
                    if (isset($v['count_info']['agree_num'])) {
                        $hot = $v['count_info']['agree_num'] . ' 👍';
                    } elseif (isset($v['count_info']['total_comment_num'])) {
                        $hot = $v['count_info']['total_comment_num'] . ' 💬';
                    }
                }

                if (empty($title)) continue;

                $url = '';
                if (!empty($v['url'])) {
                    $url = $v['url'];
                } elseif ($id) {
                    $url = 'https://www.huxiu.com/article/' . $id . '.html';
                } else {
                    $url = 'https://www.huxiu.com/';
                }

                $items[] = array(
                    'index' => count($items) + 1,
                    'title' => $title,
                    'url'   => $url,
                    'hot'   => $hot,
                    'desc'  => '',
                    'pic'   => ''
                );
            }

            return array(
                'success'     => true,
                'title'       => '虎嗅',
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
            "Referer: https://www.huxiu.com/"
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
