<?php
/**
 * 百度贴吧热议处理器（使用官方API）
 *
 * 数据来源: https://tieba.baidu.com/hottopic/browse/topicList
 */

class TiebaHotSearch {

    public function handle($limit = 50) {
        try {
            $url = 'https://tieba.baidu.com/hottopic/browse/topicList';
            $json = $this->curl($url);

            if (empty($json)) {
                return array('success' => false, 'message' => 'API请求失败');
            }

            // 尝试将响应转换为JSON（某些API返回的是嵌入在其他格式中）
            $data = json_decode($json, true);

            // 如果直接解析失败，尝试从响应中提取JSON部分
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
                // 尝试查找JSON对象
                if (preg_match('/\{.*\}/s', $json, $matches)) {
                    $data = json_decode($matches[0], true);
                }
                if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
                    return array('success' => false, 'message' => 'JSON解析失败: ' . json_last_error_msg());
                }
            }

            if (!isset($data['data']['bang_topic']['topic_list']) || !is_array($data['data']['bang_topic']['topic_list'])) {
                return array('success' => false, 'message' => '数据格式错误');
            }

            $items = array();
            foreach ($data['data']['bang_topic']['topic_list'] as $v) {
                if (count($items) >= $limit) break;

                $title = isset($v['topic_name']) ? $v['topic_name'] : '';
                $url   = isset($v['topic_url']) ? $v['topic_url'] : '';
                $hot   = isset($v['discuss_num']) ? $v['discuss_num'] : '';

                if (empty($title)) continue;

                if (!empty($url) && strpos($url, 'http') !== 0) {
                    $url = 'https://tieba.baidu.com' . $url;
                }

                $items[] = array(
                    'index' => count($items) + 1,
                    'title' => $title,
                    'url'   => $url ?: 'https://tieba.baidu.com/hottopic/browse/topicList',
                    'hot'   => $hot ? ($hot . ' 讨论') : '',
                    'desc'  => '',
                    'pic'   => isset($v['topic_pic']) ? $v['topic_pic'] : ''
                );
            }

            return array(
                'success'     => true,
                'title'       => '百度贴吧',
                'subtitle'    => '热议',
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
            "Referer: https://tieba.baidu.com/"
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