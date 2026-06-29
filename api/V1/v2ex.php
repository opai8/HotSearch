<?php
/**
 * V2EX热搜榜处理器
 *
 * 数据来源: https://www.v2ex.com/api/topics/hot.json
 */

class V2exHotSearch {

    public function handle($limit = 50) {
        try {
            $url = 'https://www.v2ex.com/api/topics/hot.json';
            $json = $this->curl($url);

            if (empty($json)) {
                return array('success' => false, 'message' => 'API请求失败');
            }

            $json = $this->cleanJson($json);
            $data = json_decode($json, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return array('success' => false, 'message' => 'JSON解析失败: ' . json_last_error_msg());
            }

            if (!is_array($data)) {
                return array('success' => false, 'message' => '数据格式错误');
            }

            $items = array();
            foreach ($data as $v) {
                if (count($items) >= $limit) break;
                if (!is_array($v)) continue;

                $title = isset($v['title']) ? strval($v['title']) : '';
                $id = isset($v['id']) ? $v['id'] : '';
                $replies = isset($v['replies']) ? intval($v['replies']) : 0;
                $node = '';
                if (isset($v['node']['title'])) {
                    $node = strval($v['node']['title']);
                } elseif (isset($v['node']['name'])) {
                    $node = strval($v['node']['name']);
                }

                if (empty($title)) continue;

                $items[] = array(
                    'index' => count($items) + 1,
                    'title' => $title,
                    'url'   => $id ? ('https://www.v2ex.com/t/' . $id) : 'https://www.v2ex.com/',
                    'hot'   => $replies ? ($replies . ' 回复') : '',
                    'desc'  => $node ? '[' . $node . ']' : '',
                    'pic'   => ''
                );
            }

            return array(
                'success'     => true,
                'title'       => 'V2EX',
                'subtitle'    => '最热',
                'total'       => count($items),
                'update_time' => date('Y-m-d H:i:s'),
                'data'        => $items
            );

        } catch (Exception $e) {
            return array('success' => false, 'message' => '获取失败: ' . $e->getMessage());
        }
    }

    private function cleanJson($str) {
        if (empty($str)) return $str;
        if (substr($str, 0, 3) === "\xEF\xBB\xBF") {
            $str = substr($str, 3);
        }
        return trim($str);
    }

    private function curl($url) {
        $header = array(
            "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36",
            "Accept: */*"
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        $output = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 400 && !empty($output)) {
            return $output;
        }

        return '';
    }
}
?>
