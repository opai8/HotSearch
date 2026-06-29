<?php
/**
 * 游研社处理器（使用官方API）
 *
 * 数据来源: https://www.yystv.cn/home/get_home_docs_by_page
 */

class YystvHotSearch {

    public function handle($limit = 20) {
        try {
            $url = 'https://www.yystv.cn/home/get_home_docs_by_page';
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

                $title = isset($v['title']) ? $v['title'] : '';
                $id    = isset($v['id']) ? $v['id'] : '';
                $hot   = isset($v['comment_num']) ? ($v['comment_num'] . ' 评论') : '';

                if (empty($title)) continue;

                $items[] = array(
                    'index' => count($items) + 1,
                    'title' => $title,
                    'url'   => 'https://www.yystv.cn/p/' . $id,
                    'hot'   => $hot,
                    'desc'  => isset($v['desc']) ? $v['desc'] : '',
                    'pic'   => isset($v['cover']) ? $v['cover'] : ''
                );
            }

            return array(
                'success'     => true,
                'title'       => '游研社',
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
            "Accept: application/json",
            "Referer: https://www.yystv.cn/"
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