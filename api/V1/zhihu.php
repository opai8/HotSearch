<?php
/**
 * 知乎热榜处理器（使用官方API）
 *
 * 数据来源: https://api.zhihu.com/topstory/hot-lists/total?limit=50
 */

class ZhihuHotSearch {

    public function handle($limit = 50) {
        try {
            $url = 'https://api.zhihu.com/topstory/hot-lists/total?limit=' . intval($limit);
            $json = $this->curl($url);

            if (empty($json)) {
                return array('success' => false, 'message' => 'API请求失败');
            }

            $data = json_decode($json, true);
            if (!isset($data['data']) || !is_array($data['data'])) {
                return array('success' => false, 'message' => 'JSON解析失败');
            }

            $items = array();
            foreach ($data['data'] as $v) {
                if (count($items) >= $limit) break;

                $target = isset($v['target']) ? $v['target'] : [];
                $title = isset($target['title']) ? $target['title'] : (isset($v['title']) ? $v['title'] : '');
                $url   = isset($target['url']) ? $target['url'] : (isset($v['url']) ? $v['url'] : '');
                $hot   = isset($v['detail_text']) ? $v['detail_text'] : (isset($v['hot']) ? $v['hot'] : '');

                if (empty($title)) continue;

                // 构建知乎问题链接
                $questionId = isset($target['id']) ? $target['id'] : '';
                $questionUrl = $questionId ? 'https://www.zhihu.com/question/' . urlencode($questionId) : ($url ?: 'https://www.zhihu.com/hot');

                $items[] = array(
                    'index' => count($items) + 1,
                    'title' => $title,
                    'url'   => $questionUrl,
                    'hot'   => $hot,
                    'desc'  => '',
                    'pic'   => isset($target['thumbnail_url']) ? $target['thumbnail_url'] : ''
                );
            }

            return array(
                'success'     => true,
                'title'       => '知乎',
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
            "Referer: https://www.zhihu.com/hot"
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