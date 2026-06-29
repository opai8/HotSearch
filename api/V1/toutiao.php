<?php
/**
 * 今日头条处理器（使用官方API）
 *
 * 数据来源: https://www.toutiao.com/hot-event/hot-board/?origin=toutiao_pc
 */

class ToutiaoHotSearch {

    public function handle($limit = 50) {
        try {
            $url = 'https://www.toutiao.com/hot-event/hot-board/?origin=toutiao_pc';
            $json = $this->curl($url);

            if (empty($json)) {
                return array('success' => false, 'message' => 'API请求失败');
            }

            // 尝试将响应转换为JSON
            $data = json_decode($json, true);

            // 如果直接解析失败，尝试从响应中提取JSON部分
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
                if (preg_match('/\{.*\}/s', $json, $matches)) {
                    $data = json_decode($matches[0], true);
                }
                if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
                    return array('success' => false, 'message' => 'JSON解析失败: ' . json_last_error_msg());
                }
            }

            // 数据直接在 data 数组中
            if (!isset($data['data']) || !is_array($data['data'])) {
                return array('success' => false, 'message' => '数据格式错误');
            }

            $items = array();
            foreach ($data['data'] as $v) {
                if (count($items) >= $limit) break;

                $title = isset($v['Title']) ? $v['Title'] : '';
                $url   = isset($v['Url']) ? $v['Url'] : '';
                $hot   = isset($v['HotValue']) ? $v['HotValue'] : '';
                $label = isset($v['Label']) ? $v['Label'] : '';

                if (empty($title)) continue;

                $items[] = array(
                    'index' => count($items) + 1,
                    'title' => $title,
                    'url'   => $url ?: 'https://www.toutiao.com/',
                    'hot'   => $hot ? (is_numeric($hot) ? number_format($hot) : $hot) : '',
                    'desc'  => $label,
                    'pic'   => isset($v['image_url']) ? $v['image_url'] : ''
                );
            }

            return array(
                'success'     => true,
                'title'       => '今日头条',
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
            "Referer: https://www.toutiao.com/"
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