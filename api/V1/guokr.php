<?php
/**
 * 果壳网处理器
 *
 * 数据来源: https://www.guokr.com/beta/proxy/science_api/articles?limit=30
 */

class GuokrHotSearch {

    public function handle($limit = 50) {
        try {
            $url = 'https://www.guokr.com/beta/proxy/science_api/articles?limit=' . intval($limit);
            $json = $this->curl($url);

            if (empty($json)) {
                return array('success' => false, 'message' => 'API请求失败');
            }

            $data = json_decode($json, true);

            if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
                return array('success' => false, 'message' => 'JSON解析失败: ' . json_last_error_msg());
            }

            $articles = array();
            if (isset($data['result']) && is_array($data['result'])) {
                $articles = $data['result'];
            } elseif (isset($data['data']) && is_array($data['data'])) {
                if (isset($data['data']['articles']) && is_array($data['data']['articles'])) {
                    $articles = $data['data']['articles'];
                } else {
                    $articles = $data['data'];
                }
            } elseif (isset($data[0]) && is_array($data[0])) {
                $articles = $data;
            }

            if (empty($articles)) {
                return array('success' => false, 'message' => '数据格式错误');
            }

            $items = array();
            foreach ($articles as $v) {
                if (count($items) >= $limit) break;

                $title = '';
                foreach (array('title', 'name', 'subject', 'article_title') as $k) {
                    if (isset($v[$k]) && is_string($v[$k])) {
                        $title = $v[$k];
                        break;
                    }
                }

                $id = '';
                foreach (array('id', 'article_id', 'guid', 'ukey') as $k) {
                    if (isset($v[$k]) && (is_string($v[$k]) || is_numeric($v[$k]))) {
                        $id = $v[$k];
                        break;
                    }
                }

                $likes = '';
                foreach (array('like_count', 'likes', 'recommend', 'view_count', 'reply_count') as $k) {
                    if (isset($v[$k]) && (is_string($v[$k]) || is_numeric($v[$k]))) {
                        $likes = $v[$k];
                        break;
                    }
                }

                if (empty($title)) continue;

                $url = '';
                if (isset($v['url']) && !empty($v['url'])) {
                    $url = $v['url'];
                } elseif ($id) {
                    $url = 'https://www.guokr.com/article/' . $id . '/';
                } else {
                    $url = 'https://www.guokr.com/';
                }

                $items[] = array(
                    'index' => count($items) + 1,
                    'title' => $title,
                    'url'   => $url,
                    'hot'   => $likes ? (is_numeric($likes) ? ($likes . ' 👍') : $likes) : '',
                    'desc'  => isset($v['summary']) ? $v['summary'] : (isset($v['description']) ? $v['description'] : ''),
                    'pic'   => isset($v['image']) ? $v['image'] : (isset($v['cover']) ? $v['cover'] : '')
                );
            }

            return array(
                'success'     => true,
                'title'       => '果壳网',
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
            "Referer: https://www.guokr.com/"
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
