<?php
/**
 * 澎湃新闻处理器（使用官方API）
 *
 * 数据来源: https://cache.thepaper.cn/contentapi/wwwIndex/rightSidebar
 */

class ThepaperHotSearch {

    public function handle($limit = 50) {
        try {
            $url = 'https://cache.thepaper.cn/contentapi/wwwIndex/rightSidebar';
            $json = $this->curl($url);

            if (empty($json)) {
                return array('success' => false, 'message' => 'API请求失败');
            }

            $data = json_decode($json, true);
            if (!isset($data['data']['hotNews']) || !is_array($data['data']['hotNews'])) {
                return array('success' => false, 'message' => 'JSON解析失败');
            }

            $items = array();
            foreach ($data['data']['hotNews'] as $v) {
                if (count($items) >= $limit) break;

                $title = isset($v['name']) ? $v['name'] : '';
                $id    = isset($v['contId']) ? $v['contId'] : '';
                $hot   = isset($v['praiseTimes']) ? ($v['praiseTimes'] . ' 赞') : '';

                if (empty($title)) continue;

                $items[] = array(
                    'index' => count($items) + 1,
                    'title' => $title,
                    'url'   => 'https://www.thepaper.cn/newsDetail_forward_' . $id,
                    'hot'   => $hot,
                    'desc'  => '',
                    'pic'   => isset($v['image']) ? $v['image'] : ''
                );
            }

            return array(
                'success'     => true,
                'title'       => '澎湃新闻',
                'subtitle'    => '热闻',
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
            "Accept: application/json",
            "Referer: https://www.thepaper.cn/"
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