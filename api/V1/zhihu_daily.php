<?php
/**
 * 知乎日报处理器（使用官方API）
 *
 * 数据来源: https://daily.zhihu.com/api/4/news/latest
 */

class ZhihuDailyHotSearch {

    public function handle($limit = 20) {
        try {
            $url = 'https://daily.zhihu.com/api/4/news/latest';
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

            if (!isset($data['stories']) || !is_array($data['stories'])) {
                return array('success' => false, 'message' => '数据格式错误');
            }

            $items = array();
            foreach ($data['stories'] as $v) {
                if (count($items) >= $limit) break;

                $title = isset($v['title']) ? $v['title'] : '';
                $id    = isset($v['id']) ? $v['id'] : '';

                if (empty($title)) continue;

                $items[] = array(
                    'index' => count($items) + 1,
                    'title' => $title,
                    'url'   => 'https://daily.zhihu.com/story/' . $id,
                    'hot'   => isset($v['hint']) ? $v['hint'] : '',
                    'desc'  => '',
                    'pic'   => isset($v['images'][0]) ? $v['images'][0] : (isset($v['image']) ? $v['image'] : '')
                );
            }

            return array(
                'success'     => true,
                'title'       => '知乎日报',
                'subtitle'    => '最新',
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
            "Referer: https://daily.zhihu.com/"
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