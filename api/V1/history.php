<?php
/**
 * 历史上的今天处理器（使用百度百科API）
 *
 * 数据来源: https://baike.baidu.com/cms/home/eventsOnHistory/{month}.json
 */

class HistoryHotSearch {

    public function handle($limit = 50) {
        try {
            $month = date('m', time());
            $day = date('d', time());
            $api_url = 'https://baike.baidu.com/cms/home/eventsOnHistory/' . $month . '.json';

            $json = $this->getCurl($api_url, 'https://baike.baidu.com');

            if (empty($json)) {
                return array('success' => false, 'message' => 'API请求失败');
            }

            $jsonRes = json_decode($json, true);

            if (json_last_error() !== JSON_ERROR_NONE || !is_array($jsonRes)) {
                if (preg_match('/\{.*\}/s', $json, $matches)) {
                    $jsonRes = json_decode($matches[0], true);
                }
                if (json_last_error() !== JSON_ERROR_NONE || !is_array($jsonRes)) {
                    return array('success' => false, 'message' => 'JSON解析失败: ' . json_last_error_msg());
                }
            }

            $key = $month . $day;
            if (!isset($jsonRes[$month][$key]) || !is_array($jsonRes[$month][$key])) {
                return array('success' => false, 'message' => '数据格式错误');
            }

            $data = $jsonRes[$month][$key];
            $tempArr = array();

            foreach ($data as $item) {
                if (count($tempArr) >= $limit) break;

                $title = isset($item['title']) ? strip_tags($item['title']) : '';
                $year = isset($item['year']) ? $item['year'] : '';
                $url = isset($item['link']) ? $item['link'] : '';

                if (empty($title)) continue;

                if (!empty($url) && strpos($url, 'http') !== 0) {
                    $url = 'https://baike.baidu.com' . $url;
                }

                $tempArr[] = array(
                    'index' => count($tempArr) + 1,
                    'title' => $title,
                    'url'   => $url ?: 'https://baike.baidu.com/',
                    'hot'   => $year ? $year . '年' : '',
                    'desc'  => isset($item['desc']) ? strip_tags($item['desc']) : '',
                    'pic'   => isset($item['picUrl']) ? $item['picUrl'] : ''
                );
            }

            return array(
                'success'     => true,
                'title'       => '历史上的今天',
                'subtitle'    => date('m月d日'),
                'total'       => count($tempArr),
                'update_time' => date('Y-m-d H:i:s'),
                'data'        => $tempArr
            );

        } catch (Exception $e) {
            return array('success' => false, 'message' => '获取失败: ' . $e->getMessage());
        }
    }

    private function getCurl($url, $referer = '') {
        $header = array(
            "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36",
            "Accept: application/json, text/plain, */*",
            "Referer: " . ($referer ?: 'https://baike.baidu.com/')
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
