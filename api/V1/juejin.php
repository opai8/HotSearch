<?php
/**
 * 掘金热搜榜处理器
 *
 * 数据来源: https://api.juejin.cn/content_api/v1/content/article_rank?category_id=1&type=hot
 */

class JuejinHotSearch {

    public function handle($limit = 50) {
        try {
            $url = 'https://api.juejin.cn/content_api/v1/content/article_rank?category_id=1&type=hot';
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
                $articles = $data['data'];
            } elseif (isset($data['d']) && is_array($data['d'])) {
                $articles = $data['d'];
            }

            if (empty($articles)) {
                return array('success' => false, 'message' => '数据格式错误');
            }

            $items = array();
            foreach ($articles as $v) {
                if (count($items) >= $limit) break;

                $content = isset($v['content']) && is_array($v['content']) ? $v['content'] : $v;

                $title = '';
                foreach (array('title', 'article_title', 'name') as $k) {
                    if (isset($content[$k]) && is_string($content[$k])) {
                        $title = $content[$k];
                        break;
                    }
                }

                $id = '';
                foreach (array('id', 'content_id', 'article_id') as $k) {
                    if (isset($content[$k]) && (is_string($content[$k]) || is_numeric($content[$k]))) {
                        $id = $content[$k];
                        break;
                    }
                }

                $hot = '';
                if (isset($v['content_counter'])) {
                    foreach (array('hot_rank', 'view', 'view_count', 'digg_count') as $k) {
                        if (isset($v['content_counter'][$k]) && (is_string($v['content_counter'][$k]) || is_numeric($v['content_counter'][$k]))) {
                            $hot = $v['content_counter'][$k];
                            break;
                        }
                    }
                }
                if (empty($hot)) {
                    foreach (array('hot', 'hot_value', 'heat') as $k) {
                        if (isset($v[$k]) && (is_string($v[$k]) || is_numeric($v[$k]))) {
                            $hot = $v[$k];
                            break;
                        }
                    }
                }

                if (empty($title)) continue;

                $items[] = array(
                    'index' => count($items) + 1,
                    'title' => $title,
                    'url'   => $id ? ('https://juejin.cn/post/' . $id) : 'https://juejin.cn/',
                    'hot'   => $hot ? (is_numeric($hot) ? number_format($hot) : $hot) : '',
                    'desc'  => isset($content['brief']) ? $content['brief'] : '',
                    'pic'   => ''
                );
            }

            return array(
                'success'     => true,
                'title'       => '掘金',
                'subtitle'    => '热榜',
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
            "Referer: https://juejin.cn/"
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
