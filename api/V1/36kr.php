<?php
/**
 * 36氪热榜处理器
 *
 * 数据来源: https://gateway.36kr.com/api/mis/nav/home/nav/rank/hot
 */

class _36krHotSearch {

    public function handle($limit = 50) {
        try {
            $url = 'https://gateway.36kr.com/api/mis/nav/home/nav/rank/hot';

            $postData = json_encode(array(
                'partner_id' => 'wap',
                'timestamp'  => time() * 1000,
                'param'      => array(
                    'siteId'     => 1,
                    'platformId' => 2
                )
            ));

            $json = $this->curl($url, $postData);

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
                if (isset($data['data']['hotRankList']) && is_array($data['data']['hotRankList'])) {
                    $articles = $data['data']['hotRankList'];
                } elseif (isset($data['data']['list']) && is_array($data['data']['list'])) {
                    $articles = $data['data']['list'];
                } else {
                    $articles = $data['data'];
                }
            }

            if (empty($articles)) {
                return array('success' => false, 'message' => '数据格式错误');
            }

            $items = array();
            foreach ($articles as $v) {
                if (count($items) >= $limit) break;

                $title = '';
                foreach (array('title', 'widgetTitle', 'articleTitle') as $k) {
                    if (isset($v[$k]) && is_string($v[$k])) {
                        $title = $v[$k];
                        break;
                    }
                }
                if (empty($title) && isset($v['templateMaterial']) && is_array($v['templateMaterial'])) {
                    foreach (array('title', 'widgetTitle') as $k) {
                        if (isset($v['templateMaterial'][$k]) && is_string($v['templateMaterial'][$k])) {
                            $title = $v['templateMaterial'][$k];
                            break;
                        }
                    }
                }

                $id = '';
                foreach (array('id', 'itemId', 'articleId') as $k) {
                    if (isset($v[$k]) && (is_string($v[$k]) || is_numeric($v[$k]))) {
                        $id = $v[$k];
                        break;
                    }
                }
                if (empty($id) && isset($v['templateMaterial']) && is_array($v['templateMaterial'])) {
                    foreach (array('itemId', 'id') as $k) {
                        if (isset($v['templateMaterial'][$k]) && (is_string($v['templateMaterial'][$k]) || is_numeric($v['templateMaterial'][$k]))) {
                            $id = $v['templateMaterial'][$k];
                            break;
                        }
                    }
                }

                $hot = '';
                foreach (array('hot', 'hotValue', 'heat', 'readCount', 'pv') as $k) {
                    if (isset($v[$k]) && (is_string($v[$k]) || is_numeric($v[$k]))) {
                        $hot = $v[$k];
                        break;
                    }
                }
                if (empty($hot) && isset($v['templateMaterial']) && is_array($v['templateMaterial'])) {
                    foreach (array('hot', 'statRead', 'readCount') as $k) {
                        if (isset($v['templateMaterial'][$k]) && (is_string($v['templateMaterial'][$k]) || is_numeric($v['templateMaterial'][$k]))) {
                            $hot = $v['templateMaterial'][$k];
                            break;
                        }
                    }
                }

                if (empty($title)) continue;

                $items[] = array(
                    'index' => count($items) + 1,
                    'title' => $title,
                    'url'   => $id ? ('https://36kr.com/p/' . $id) : 'https://36kr.com/',
                    'hot'   => $hot ? (is_numeric($hot) ? number_format($hot) : $hot) : '',
                    'desc'  => '',
                    'pic'   => ''
                );
            }

            return array(
                'success'     => true,
                'title'       => '36氪',
                'subtitle'    => '热榜',
                'total'       => count($items),
                'update_time' => date('Y-m-d H:i:s'),
                'data'        => $items
            );

        } catch (Exception $e) {
            return array('success' => false, 'message' => '获取失败: ' . $e->getMessage());
        }
    }

    private function curl($url, $postData = '') {
        $header = array(
            "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36",
            "Accept: application/json, text/plain, */*",
            "Content-Type: application/json",
            "Referer: https://36kr.com/",
            "Origin: https://36kr.com"
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

        if (!empty($postData)) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        }

        $output = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 400) return '';
        return $output ?: '';
    }
}
?>
