<?php
/**
 * 抖音热搜处理器（使用官方API）
 *
 * 数据来源: https://www.douyin.com/aweme/v1/web/hot/search/list/
 */

class DouyinHotSearch {

    public function handle($limit = 50) {
        try {
            $url = 'https://www.douyin.com/aweme/v1/web/hot/search/list/';
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

            $wordList = array();
            if (isset($data['data']['word_list']) && is_array($data['data']['word_list'])) {
                $wordList = $data['data']['word_list'];
            } elseif (isset($data['data']) && is_array($data['data'])) {
                // 尝试查找其他可能的字段
                foreach (array('word_list', 'hot_list', 'hot_words', 'list', 'words') as $key) {
                    if (isset($data['data'][$key]) && is_array($data['data'][$key])) {
                        $wordList = $data['data'][$key];
                        break;
                    }
                }
            }

            if (empty($wordList)) {
                return array('success' => false, 'message' => '数据格式错误');
            }

            $items = array();
            foreach ($wordList as $v) {
                if (count($items) >= $limit) break;

                $title = '';
                foreach (array('word', 'title', 'name', 'keyword', 'hot_word') as $k) {
                    if (isset($v[$k]) && is_string($v[$k])) {
                        $title = $v[$k];
                        break;
                    }
                }

                $hot = '';
                foreach (array('hot_value', 'hotValue', 'hot', 'heat', 'score', 'view_count', 'views', 'count') as $k) {
                    if (isset($v[$k]) && (is_string($v[$k]) || is_numeric($v[$k]))) {
                        $hot = $v[$k];
                        break;
                    }
                }

                if (empty($title)) continue;

                $items[] = array(
                    'index' => count($items) + 1,
                    'title' => $title,
                    'url'   => 'https://www.douyin.com/search/' . urlencode($title),
                    'hot'   => $hot ? (is_numeric($hot) ? number_format($hot) : $hot) : '',
                    'desc'  => isset($v['event_desc']) ? $v['event_desc'] : (isset($v['desc']) ? $v['desc'] : ''),
                    'pic'   => ''
                );
            }

            if (empty($items)) {
                return array('success' => false, 'message' => '未找到有效数据');
            }

            return array(
                'success'     => true,
                'title'       => '抖音',
                'subtitle'    => '热搜',
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
            "Referer: https://www.douyin.com/hot",
            "Origin: https://www.douyin.com"
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
