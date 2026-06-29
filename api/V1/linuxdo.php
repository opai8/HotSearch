<?php
/**
 * LinuxDo 热门话题处理器（RSS 格式）
 *
 * 数据来源: https://linux.do/top.rss?period=weekly
 */

class LinuxdoHotSearch {

    public function handle($limit = 50) {
        try {
            $url = 'https://linux.do/top.rss?period=weekly';
            $xml = $this->curl($url);

            if (empty($xml)) {
                return array('success' => false, 'message' => 'API请求失败');
            }

            $items = array();

            libxml_use_internal_errors(true);
            $rss = simplexml_load_string($xml);

            if ($rss && isset($rss->channel) && isset($rss->channel->item)) {
                $count = 0;
                foreach ($rss->channel->item as $item) {
                    if ($count >= $limit) break;

                    $title = isset($item->title) ? (string)$item->title : '';
                    $link  = isset($item->link) ? (string)$item->link : '';
                    $desc  = isset($item->description) ? (string)$item->description : '';
                    $pubDate = isset($item->pubDate) ? (string)$item->pubDate : '';

                    if (empty($title)) continue;

                    $items[] = array(
                        'index' => count($items) + 1,
                        'title' => $title,
                        'url'   => $link ? $link : 'https://linux.do/',
                        'hot'   => '',
                        'desc'  => $desc,
                        'pic'   => ''
                    );
                    $count++;
                }
            }

            if (empty($items)) {
                return array('success' => false, 'message' => '数据解析失败或为空');
            }

            return array(
                'success'     => true,
                'title'       => 'LinuxDo',
                'subtitle'    => '热门',
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
            "Accept: application/rss+xml, application/xml, text/xml, */*",
            "Referer: https://linux.do/"
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
