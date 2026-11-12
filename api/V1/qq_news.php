<?php
/**
 * 腾讯新闻热点榜处理器
 * 数据来源: https://r.inews.qq.com/gw/event/hot_ranking_list
 */

class QqNewsHotSearch {

    public function handle($limit = 50) {
        try {
            $url = 'https://r.inews.qq.com/gw/event/hot_ranking_list?page_size=' . min($limit + 1, 50);
            $json = $this->curl($url);

            if (empty($json)) {
                return array('success' => false, 'message' => '请求失败');
            }

            $data = json_decode($json, true);
            if (!$data || !isset($data['idlist'][0]['newslist']) || !is_array($data['idlist'][0]['newslist'])) {
                return array('success' => false, 'message' => '解析失败');
            }

            // 去掉第 0 条（腾讯新闻自己的广告）
            $list = array_slice($data['idlist'][0]['newslist'], 1);

            $result = array();
            foreach ($list as $v) {
                if (count($result) >= $limit) break;

                $hot = '';
                if (isset($v['hotEvent']['hotScore'])) {
                    $hot = intval($v['hotEvent']['hotScore']);
                }

                $result[] = array(
                    'index' => count($result) + 1,
                    'title' => isset($v['title']) ? $v['title'] : '',
                    'url'   => 'https://new.qq.com/rain/a/' . (isset($v['id']) ? $v['id'] : ''),
                    'hot'   => $hot,
                    'desc'  => isset($v['abstract']) ? $v['abstract'] : '',
                    'pic'   => isset($v['miniProShareImage']) ? $v['miniProShareImage'] : ''
                );
            }

            return array(
                'success'     => true,
                'title'       => '腾讯新闻',
                'subtitle'    => '热点榜',
                'total'       => count($result),
                'update_time' => date('Y-m-d H:i:s'),
                'data'        => $result
            );

        } catch (Exception $e) {
            return array('success' => false, 'message' => '获取失败: ' . $e->getMessage());
        }
    }

    private function curl($url) {
        $header = array(
            "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36",
            "Accept: application/json, text/plain, */*",
            "Referer: https://news.qq.com/"
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