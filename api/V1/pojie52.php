<?php
/**
 * 52破解处理器（RSS）
 *
 * 数据来源: https://www.52pojie.cn/forum.php?mod=guide&view=hot&rss=1
 */

class Pojie52HotSearch {

    public function handle($limit = 50) {
        try {
            $url = 'https://www.52pojie.cn/forum.php?mod=guide&view=hot&rss=1';
            $xml = $this->curl($url);

            if (empty($xml)) {
                return array('success' => false, 'message' => 'API请求失败');
            }

            $items = $this->parseRss($xml);

            if (empty($items)) {
                return array('success' => false, 'message' => '数据解析失败');
            }

            $result = array();
            foreach ($items as $item) {
                if (count($result) >= $limit) break;
                if (empty($item['title'])) continue;

                $result[] = array(
                    'index' => count($result) + 1,
                    'title' => $item['title'],
                    'url'   => $item['link'],
                    'hot'   => '',
                    'desc'  => $item['description'],
                    'pic'   => ''
                );
            }

            return array(
                'success'     => true,
                'title'       => '52破解',
                'subtitle'    => '热门',
                'total'       => count($result),
                'update_time' => date('Y-m-d H:i:s'),
                'data'        => $result
            );

        } catch (Exception $e) {
            return array('success' => false, 'message' => '获取失败: ' . $e->getMessage());
        }
    }

    private function parseRss($xml) {
        $items = array();

        if (class_exists('SimpleXMLElement')) {
            try {
                $rss = new SimpleXMLElement($xml);
                if (isset($rss->channel->item)) {
                    foreach ($rss->channel->item as $item) {
                        $items[] = array(
                            'title'       => (string)$item->title,
                            'link'        => (string)$item->link,
                            'description' => isset($item->description) ? strip_tags((string)$item->description) : '',
                            'pubDate'     => isset($item->pubDate) ? (string)$item->pubDate : ''
                        );
                    }
                }
            } catch (Exception $e) {}
        }

        if (empty($items)) {
            if (preg_match_all('/<item>(.*?)<\/item>/s', $xml, $itemMatches)) {
                foreach ($itemMatches[1] as $itemXml) {
                    $title = '';
                    $link = '';
                    $desc = '';
                    if (preg_match('/<title>(.*?)<\/title>/s', $itemXml, $m)) $title = trim(strip_tags($m[1]));
                    if (preg_match('/<link>(.*?)<\/link>/s', $itemXml, $m)) $link = trim($m[1]);
                    if (preg_match('/<description>(.*?)<\/description>/s', $itemXml, $m)) $desc = trim(strip_tags($m[1]));
                    if (!empty($title)) {
                        $items[] = array(
                            'title'       => $title,
                            'link'        => $link,
                            'description' => $desc,
                            'pubDate'     => ''
                        );
                    }
                }
            }
        }

        return $items;
    }

    private function curl($url) {
        $header = array(
            "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36",
            "Accept: application/rss+xml, application/xml, text/xml, */*",
            "Referer: https://www.52pojie.cn/"
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
