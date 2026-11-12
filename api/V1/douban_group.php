<?php
/**
 * 豆瓣讨论广场处理器
 * 数据来源: https://www.douban.com/group/explore
 * 使用 DOMDocument + DOMXPath 解析 .channel-item
 */

class DoubanGroupHotSearch {

    public function handle($limit = 50) {
        try {
            $url = 'https://www.douban.com/group/explore';
            $html = $this->curl($url);

            if (empty($html)) {
                return array('success' => false, 'message' => '请求失败');
            }

            libxml_use_internal_errors(true);
            $dom = new DOMDocument();
            @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
            $xpath = new DOMXPath($dom);

            // 查找所有 .channel-item
            $items = $xpath->query('//div[contains(@class, "channel-item")]');
            if (!$items || $items->length == 0) {
                libxml_clear_errors();
                return array('success' => false, 'message' => '页面结构变更');
            }

            $result = array();
            foreach ($items as $item) {
                if (count($result) >= $limit) break;

                // 标题和链接：h3 > a
                $h3 = $xpath->query('.//h3/a', $item);
                $title = '';
                $url = '';
                if ($h3->length > 0) {
                    $a = $h3->item(0);
                    $title = trim($a->textContent);
                    $url = $a->getAttribute('href');
                }

                // 热度：.likes 里的数字
                $likes = $xpath->query('.//div[contains(@class, "likes")]', $item);
                $hot = '';
                if ($likes->length > 0) {
                    $likesText = trim($likes->item(0)->textContent);
                    if (preg_match('/\d+/', $likesText, $m)) $hot = intval($m[0]);
                }

                // 封面：.pic-wrap img
                $img = $xpath->query('.//img', $item);
                $pic = '';
                if ($img->length > 0) {
                    $pic = $img->item(0)->getAttribute('src');
                }

                // 摘要：p
                $p = $xpath->query('.//p', $item);
                $desc = '';
                if ($p->length > 0) {
                    $desc = trim($p->item(0)->textContent);
                    if (mb_strlen($desc) > 120) {
                        $desc = mb_substr($desc, 0, 120) . '...';
                    }
                }

                if ($title) {
                    $result[] = array(
                        'index' => count($result) + 1,
                        'title' => $title,
                        'url'   => $url,
                        'hot'   => $hot,
                        'desc'  => $desc,
                        'pic'   => $pic
                    );
                }
            }

            libxml_clear_errors();

            return array(
                'success'     => true,
                'title'       => '豆瓣讨论',
                'subtitle'    => '讨论精选',
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
            "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36",
            "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
            "Accept-Language: zh-CN,zh;q=0.9",
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
