<?php
/**
 * 简书处理器
 * 数据来源: https://www.jianshu.com/ (note-list > li)
 * 使用 DOMDocument + DOMXPath 解析
 */

class JianshuHotSearch {

    public function handle($limit = 50) {
        try {
            $url = 'https://www.jianshu.com/';
            $html = $this->curl($url);

            if (empty($html)) {
                return array('success' => false, 'message' => '请求失败');
            }

            libxml_use_internal_errors(true);
            $dom = new DOMDocument();
            @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
            $xpath = new DOMXPath($dom);

            // 找到 ul.note-list 下所有 li
            $items = $xpath->query('//ul[contains(@class, "note-list")]//li');
            if (!$items || $items->length == 0) {
                libxml_clear_errors();
                return array('success' => false, 'message' => '页面结构变更（未找到 note-list）');
            }

            $result = array();
            foreach ($items as $item) {
                if (count($result) >= $limit) break;

                // 标题和链接：a.title
                $titleNode = $xpath->query('.//a[contains(@class, "title")]', $item);
                $title = '';
                $url = '';
                if ($titleNode->length > 0) {
                    $a = $titleNode->item(0);
                    $title = trim($a->textContent);
                    $href = $a->getAttribute('href');
                    if ($href) {
                        $url = (strpos($href, 'http') === 0) ? $href : 'https://www.jianshu.com' . $href;
                    }
                }

                // 摘要：p.abstract
                $absNode = $xpath->query('.//p[contains(@class, "abstract")]', $item);
                $desc = '';
                if ($absNode->length > 0) {
                    $desc = trim($absNode->item(0)->textContent);
                    if (mb_strlen($desc) > 100) {
                        $desc = mb_substr($desc, 0, 100) . '...';
                    }
                }

                // 作者：a.nickname
                $nickNode = $xpath->query('.//a[contains(@class, "nickname")]', $item);
                $author = '';
                if ($nickNode->length > 0) {
                    $author = trim($nickNode->item(0)->textContent);
                }

                // 点赞数: 找 meta 里的数字 (ic-list-like 后面)
                $hot = '';
                $metaText = '';
                $meta = $xpath->query('.//div[contains(@class, "meta")]', $item);
                if ($meta->length > 0) {
                    $metaText = trim($meta->item(0)->textContent);
                    // 清除换行多余空格
                    $metaText = preg_replace('/\s+/', ' ', $metaText);
                    // 尝试匹配最后一个数字（通常是点赞）
                    if (preg_match_all('/(\d+)/', $metaText, $nums)) {
                        $hot = intval(end($nums[1]));
                    }
                }

                if ($title) {
                    $result[] = array(
                        'index' => count($result) + 1,
                        'title' => $title,
                        'url'   => $url,
                        'hot'   => $hot,
                        'desc'  => $author ? "作者: {$author} | {$desc}" : $desc,
                        'pic'   => ''
                    );
                }
            }

            libxml_clear_errors();

            return array(
                'success'     => true,
                'title'       => '简书',
                'subtitle'    => '热门推荐',
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
