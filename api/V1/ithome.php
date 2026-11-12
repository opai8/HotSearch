<?php
/**
 * IT之家热榜处理器
 * 数据来源: https://m.ithome.com/rankm/
 */

class IthomeHotSearch {

    public function handle($limit = 50) {
        try {
            $url = 'https://m.ithome.com/rankm/';
            $html = $this->curl($url);

            if (empty($html)) {
                return array('success' => false, 'message' => '请求失败');
            }

            // PHP DOMDocument 解析 HTML
            libxml_use_internal_errors(true);
            $dom = new DOMDocument();
            @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
            $xpath = new DOMXPath($dom);

            // 查找 .rank-box .placeholder 元素
            $items = $xpath->query('//div[contains(@class, "rank-box")]//div[contains(@class, "placeholder")]');
            if (!$items || $items->length == 0) {
                return array('success' => false, 'message' => '页面结构变更');
            }

            $result = array();
            foreach ($items as $item) {
                if (count($result) >= $limit) break;

                // 提取链接
                $a = $xpath->query('.//a', $item);
                $href = '';
                if ($a->length > 0) {
                    $href = $a->item(0)->getAttribute('href');
                }

                // 提取标题 .plc-title
                $titleNode = $xpath->query('.//p[contains(@class, "plc-title")]', $item);
                $title = $titleNode->length > 0 ? trim($titleNode->item(0)->textContent) : '';

                // 提取热度 .review-num
                $reviewNode = $xpath->query('.//span[contains(@class, "review-num")]', $item);
                $hot = '';
                if ($reviewNode->length > 0) {
                    $hotText = trim($reviewNode->item(0)->textContent);
                    if (preg_match('/\d+/', $hotText, $m)) $hot = intval($m[0]);
                }

                // 格式化链接
                $finalUrl = '';
                if ($href && preg_match('/(html|live)\/(\d+)\.htm/', $href, $m)) {
                    $id = $m[2];
                    $prefix = substr($id, 0, 3);
                    $suffix = substr($id, 3);
                    $finalUrl = "https://www.ithome.com/0/{$prefix}/{$suffix}.htm";
                }

                $result[] = array(
                    'index' => count($result) + 1,
                    'title' => $title,
                    'url'   => $finalUrl ?: $href,
                    'hot'   => $hot,
                    'desc'  => '',
                    'pic'   => ''
                );
            }

            libxml_clear_errors();

            return array(
                'success'     => true,
                'title'       => 'IT之家',
                'subtitle'    => '热榜',
                'total'       => count($result),
                'update_time' => date('Y-m-d H:i:s'),
                'data'        => $result
            );

        } catch (Exception $e) {
            libxml_clear_errors();
            return array('success' => false, 'message' => '获取失败: ' . $e->getMessage());
        }
    }

    private function curl($url, $headers = array()) {
        $defaultHeaders = array(
            "User-Agent: Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148",
            "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge($defaultHeaders, $headers));
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
