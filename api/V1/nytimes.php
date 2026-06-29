<?php
/**
 * 纽约时报中文网处理器（RSS）
 *
 * 数据来源: https://cn.nytimes.com/rss/
 */

class NytimesHotSearch {

    public function handle($limit = 50) {
        try {
            $url = 'https://cn.nytimes.com/rss/';
            $xml = $this->curl($url);

            if (empty($xml)) {
                return array('success' => false, 'message' => 'API请求失败');
            }

            $xml = $this->cleanXml($xml);
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
                'title'       => '纽约时报中文',
                'subtitle'    => '头条',
                'total'       => count($result),
                'update_time' => date('Y-m-d H:i:s'),
                'data'        => $result
            );

        } catch (Exception $e) {
            return array('success' => false, 'message' => '获取失败: ' . $e->getMessage());
        }
    }

    private function cleanXml($str) {
        if (empty($str)) return $str;
        if (substr($str, 0, 3) === "\xEF\xBB\xBF") {
            $str = substr($str, 3);
        }
        return trim($str);
    }

    private function parseRss($xml) {
        $items = array();

        if (class_exists('SimpleXMLElement')) {
            try {
                $rss = @simplexml_load_string($xml);
                if ($rss && isset($rss->channel->item)) {
                    foreach ($rss->channel->item as $item) {
                        $title = (string)$item->title;
                        $link = (string)$item->link;
                        $desc = '';
                        if (isset($item->description)) {
                            $desc = trim(strip_tags((string)$item->description));
                            $desc = preg_replace('/\s+/', ' ', $desc);
                        }
                        if (!empty($title)) {
                            $items[] = array(
                                'title'       => $title,
                                'link'        => $link,
                                'description' => $desc
                            );
                        }
                    }
                }
            } catch (Exception $e) {}
        }

        if (empty($items)) {
            if (preg_match_all('/<item\b[^>]*>(.*?)<\/item>/is', $xml, $itemMatches)) {
                foreach ($itemMatches[1] as $itemXml) {
                    $title = '';
                    $link = '';
                    $desc = '';

                    if (preg_match('/<title\b[^>]*>(.*?)<\/title>/is', $itemXml, $m)) {
                        $title = $m[1];
                        $title = preg_replace('/<!\[CDATA\[(.*?)\]\]>/s', '$1', $title);
                        $title = trim(strip_tags($title));
                    }
                    if (preg_match('/<link\b[^>]*>(.*?)<\/link>/is', $itemXml, $m)) {
                        $link = $m[1];
                        $link = preg_replace('/<!\[CDATA\[(.*?)\]\]>/s', '$1', $link);
                        $link = trim($link);
                    }
                    if (preg_match('/<description\b[^>]*>(.*?)<\/description>/is', $itemXml, $m)) {
                        $desc = $m[1];
                        $desc = preg_replace('/<!\[CDATA\[(.*?)\]\]>/s', '$1', $desc);
                        $desc = trim(strip_tags($desc));
                        $desc = preg_replace('/\s+/', ' ', $desc);
                    }

                    if (!empty($title)) {
                        $items[] = array(
                            'title'       => $title,
                            'link'        => $link,
                            'description' => $desc
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
            "Accept: application/rss+xml, application/xml;q=0.9, text/xml;q=0.8, */*;q=0.7"
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
