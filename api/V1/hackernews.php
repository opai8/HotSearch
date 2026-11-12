<?php
/**
 * Hacker News 处理器
 * 使用官方 Firebase JSON API（稳定、无需 HTML 解析）
 * 数据来源: https://hacker-news.firebaseio.com/v0/
 */

class HackernewsHotSearch {

    public function handle($limit = 50) {
        try {
            // 1. 获取 top story IDs
            $idsJson = $this->curl('https://hacker-news.firebaseio.com/v0/topstories.json');
            if (empty($idsJson)) {
                return array('success' => false, 'message' => '请求失败');
            }
            $ids = json_decode($idsJson, true);
            if (!$ids || !is_array($ids)) {
                return array('success' => false, 'message' => '解析失败');
            }
            $ids = array_slice($ids, 0, min($limit, 30));

            // 2. 并行获取每个 story 详情
            $result = array();
            $mh = curl_multi_init();
            $handles = array();

            foreach ($ids as $id) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, "https://hacker-news.firebaseio.com/v0/item/{$id}.json");
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
                curl_multi_add_handle($mh, $ch);
                $handles[$id] = $ch;
            }

            curl_multi_exec($mh, $running);
            curl_multi_select($mh);
            while ($running > 0) {
                curl_multi_exec($mh, $running);
                curl_multi_select($mh);
            }

            foreach ($handles as $id => $ch) {
                $raw = curl_multi_getcontent($ch);
                curl_multi_remove_handle($mh, $ch);
                curl_close($ch);

                if (!$raw) continue;
                $item = json_decode($raw, true);
                if (!$item || !isset($item['title'])) continue;

                $url = isset($item['url']) ? $item['url'] : "https://news.ycombinator.com/item?id={$id}";
                $result[] = array(
                    'index' => count($result) + 1,
                    'title' => $item['title'],
                    'url'   => $url,
                    'hot'   => isset($item['score']) ? intval($item['score']) : '',
                    'desc'  => isset($item['by']) ? "by {$item['by']}" : '',
                    'pic'   => ''
                );
            }
            curl_multi_close($mh);

            return array(
                'success'     => true,
                'title'       => 'Hacker News',
                'subtitle'    => 'Popular',
                'total'       => count($result),
                'update_time' => date('Y-m-d H:i:s'),
                'data'        => $result
            );

        } catch (Exception $e) {
            return array('success' => false, 'message' => '获取失败: ' . $e->getMessage());
        }
    }

    private function curl($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
        $output = curl_exec($ch);
        curl_close($ch);
        return $output ?: '';
    }
}
?>
