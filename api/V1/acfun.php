<?php
/**
 * AcFun 综合榜处理器
 *
 * 数据来源: https://www.acfun.cn/rest/pc-direct/rank/channel?channelId=&rankLimit=30&rankPeriod=DAY
 */

class AcfunHotSearch {

    public function handle($limit = 50) {
        try {
            $url = 'https://www.acfun.cn/rest/pc-direct/rank/channel?channelId=&rankLimit=' . intval($limit) . '&rankPeriod=DAY';
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
            if (isset($data['rankList']) && is_array($data['rankList'])) {
                $articles = $data['rankList'];
            } elseif (isset($data['data']) && is_array($data['data'])) {
                if (isset($data['data']['rankList']) && is_array($data['data']['rankList'])) {
                    $articles = $data['data']['rankList'];
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
                foreach (array('title', 'name', 'contentTitle', 'videoName') as $k) {
                    if (isset($v[$k]) && is_string($v[$k])) {
                        $title = $v[$k];
                        break;
                    }
                }

                $id = '';
                foreach (array('id', 'videoId', 'dougaId', 'aid', 'acid') as $k) {
                    if (isset($v[$k]) && (is_string($v[$k]) || is_numeric($v[$k]))) {
                        $id = $v[$k];
                        break;
                    }
                }

                $hot = '';
                foreach (array('likeCountShow', 'commentCountShow', 'view', 'views', 'viewCount', 'playCount', 'clickCount', 'heatScore', 'giftPeachCount') as $k) {
                    if (isset($v[$k]) && (is_string($v[$k]) || is_numeric($v[$k]))) {
                        $hot = $v[$k];
                        break;
                    }
                }

                if (empty($title)) continue;

                $items[] = array(
                    'index' => count($items) + 1,
                    'title' => $title,
                    'url'   => $id ? ('https://www.acfun.cn/v/ac' . $id) : 'https://www.acfun.cn/',
                    'hot'   => $hot ? (is_numeric($hot) ? number_format($hot) : $hot) : '',
                    'desc'  => isset($v['contentDesc']) ? $v['contentDesc'] : (isset($v['description']) ? $v['description'] : ''),
                    'pic'   => isset($v['videoCover']) ? $v['videoCover'] : (isset($v['cover']) ? $v['cover'] : (isset($v['image']) ? $v['image'] : ''))
                );
            }

            return array(
                'success'     => true,
                'title'       => 'AcFun',
                'subtitle'    => '综合榜',
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
            "Referer: https://www.acfun.cn/",
            "Origin: https://www.acfun.cn"
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
