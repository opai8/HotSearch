<?php
/**
 * 51CTO 推荐榜处理器
 *
 * 数据来源: https://api-media.51cto.com/index/index/recommend
 */

class _51ctoHotSearch {

    public function handle($limit = 50) {
        try {
            // EdgeOne CDN 反爬：先 GET 首页拿 cookie，再请求 API
            $cookieFile = tempnam(sys_get_temp_dir(), '51cto_cookie_');
            $this->curl('https://www.51cto.com/', $cookieFile, true);

            $url = 'https://api-media.51cto.com/index/index/recommend';
            $json = $this->curl($url, $cookieFile, false);
            @unlink($cookieFile);

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
                if (isset($data['data']['data']) && is_array($data['data']['data'])) {
                    if (isset($data['data']['data']['list']) && is_array($data['data']['data']['list'])) {
                        $articles = $data['data']['data']['list'];
                    } else {
                        $articles = $data['data']['data'];
                    }
                } elseif (isset($data['data']['list']) && is_array($data['data']['list'])) {
                    $articles = $data['data']['list'];
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

                $title = '';
                foreach (array('title', 'name', 'article_title') as $k) {
                    if (isset($v[$k]) && is_string($v[$k])) {
                        $title = $v[$k];
                        break;
                    }
                }

                $id = '';
                foreach (array('id', 'article_id', 'aid', 'wid') as $k) {
                    if (isset($v[$k]) && (is_string($v[$k]) || is_numeric($v[$k]))) {
                        $id = $v[$k];
                        break;
                    }
                }

                $hot = '';
                foreach (array('view', 'views', 'view_count', 'click', 'hits', 'pv') as $k) {
                    if (isset($v[$k]) && (is_string($v[$k]) || is_numeric($v[$k]))) {
                        $hot = $v[$k];
                        break;
                    }
                }

                $url = '';
                foreach (array('url', 'link', 'url_web') as $k) {
                    if (isset($v[$k]) && is_string($v[$k]) && !empty($v[$k])) {
                        $url = $v[$k];
                        break;
                    }
                }

                if (empty($url) && !empty($id)) {
                    $url = 'https://blog.51cto.com/article/' . $id;
                }

                if (empty($title)) continue;

                $items[] = array(
                    'index' => count($items) + 1,
                    'title' => $title,
                    'url'   => $url ? $url : 'https://www.51cto.com/',
                    'hot'   => $hot ? (is_numeric($hot) ? number_format($hot) : $hot) : '',
                    'desc'  => isset($v['abstract']) ? $v['abstract'] : (isset($v['summary']) ? $v['summary'] : (isset($v['description']) ? $v['description'] : '')),
                    'pic'   => isset($v['cover']) ? $v['cover'] : (isset($v['image']) ? $v['image'] : '')
                );
            }

            return array(
                'success'     => true,
                'title'       => '51CTO',
                'subtitle'    => '推荐',
                'total'       => count($items),
                'update_time' => date('Y-m-d H:i:s'),
                'data'        => $items
            );

        } catch (Exception $e) {
            return array('success' => false, 'message' => '获取失败: ' . $e->getMessage());
        }
    }

    private function curl($url, $cookieFile = '', $isBakeCookie = false) {
        $header = array(
            "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36",
            "Accept: application/json, text/plain, */*",
            "Accept-Language: zh-CN,zh;q=0.9,en;q=0.8",
            "Referer: https://www.51cto.com/",
            "Origin: https://www.51cto.com",
            "Connection: keep-alive",
            "sec-ch-ua: \"Not_A Brand\";v=\"8\", \"Chromium\";v=\"120\", \"Google Chrome\";v=\"120\"",
            "sec-ch-ua-mobile: ?0",
            "sec-ch-ua-platform: \"Windows\"",
            "Sec-Fetch-Dest: empty",
            "Sec-Fetch-Mode: cors",
            "Sec-Fetch-Site: same-site"
        );

        $ch = smartCurlInit();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1); // EdgeOne CDN 反爬：HTTP/2 会被拦截
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_VERBOSE, false);

        if ($cookieFile) {
            if ($isBakeCookie) {
                curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
            } else {
                curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
            }
        }

        $output = smartCurlExec($ch, '51cto');
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 400) return '';
        return $output ?: '';
    }
}
?>
