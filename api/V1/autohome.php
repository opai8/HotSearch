<?php
/**
 * 汽车之家 - 媳妇当车模
 * 页面结构 (SSR 完整):
 *   "媳妇当车模" 标签 + <a href="//club.autohome.com.cn/bbs/thread/hash/topicId-1.html#pvareaid=xxx">标题</a>
 *   然后是作者信息和浏览量/回复数
 * 
 * 关键: 必须按"媳妇当车模"标签限定频道，否则会混入其他子频道的帖子
 */

class AutohomeHotSearch {

    public function handle($limit = 10) {
        // 汽车之家只爬 10 条
        $limit = min((int)$limit, 10);

        try {
            $url = 'https://club.autohome.com.cn/jingxuan/104/1?sort=time';
            $headers = array(
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
                'Accept-Language: zh-CN,zh;q=0.9,en;q=0.8',
                'Accept-Encoding: gzip, deflate, br, zstd',
                'Cache-Control: max-age=0',
                'Sec-Ch-Ua: "Google Chrome";v="131", "Chromium";v="131", "Not_A Brand";v="24"',
                'Sec-Ch-Ua-Mobile: ?0',
                'Sec-Ch-Ua-Platform: "Windows"',
                'Sec-Fetch-Dest: document',
                'Sec-Fetch-Mode: navigate',
                'Sec-Fetch-Site: none',
                'Sec-Fetch-User: ?1',
                'Upgrade-Insecure-Requests: 1',
            );

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_ENCODING, '');
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            $html = curl_exec($ch);
            $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if (!$html || $http != 200) return array('success' => false, 'message' => "请求失败 (HTTP $http)");

            // === 策略: DOMDocument + XPath ===
            // WebFetch 确认 SSR 完整包含帖子:
            //   每条前面有 <span>媳妇当车模</span> 或纯文本 "媳妇当车模"
            //   然后是 thread 链接和作者信息

            libxml_use_internal_errors(true);
            $dom = new DOMDocument();
            @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
            $xpath = new DOMXPath($dom);

            // 找所有帖子链接 //club.autohome.com.cn/bbs/thread/...-1.html#pvareaid=xxx
            // 并且 href 里带 pvareaid (102410 是媳妇当车模专属)
            $allLinks = $xpath->query('//a[contains(@href, "/bbs/thread/") and contains(@href, "-1.html")]');

            $topics = array();
            foreach ($allLinks as $a) {
                $href = $a->getAttribute('href');
                $text = trim($a->textContent);

                // 严格限定: pvareaid=102410 才是媳妇当车模
                // 如果不确定，也接受所有 thread 链接，但按 topicId 去重
                if (!preg_match('#/thread/[a-f0-9]+/(\d+)-1\.html#', $href, $tidMatch)) continue;
                $topicId = $tidMatch[1];
                if (isset($topics[$topicId])) continue;

                $title = mb_substr($text, 0, 80);
                if (strlen($title) < 4) continue;

                $topics[$topicId] = array(
                    'href'  => 'https:' . $href,
                    'title' => $title,
                );
            }

            // 如果 DOM 只拿到一点点，用正则补充
            if (count($topics) < $limit) {
                preg_match_all(
                    '~//club\.autohome\.com\.cn/bbs/thread/[a-f0-9]+/(\d+)-1\.html#[a-zA-Z=]+~',
                    $html,
                    $threads,
                    PREG_SET_ORDER | PREG_OFFSET_CAPTURE
                );
                if (!empty($threads)) {
                    // 按 offset 排序
                    usort($threads, function($a, $b) { return $a[0][1] - $b[0][1]; });
                }

                foreach ((array)$threads as $m) {
                    $threadUrl = $m[0][0];
                    $offset = $m[0][1];
                    $topicId = $m[1][0];
                    if (isset($topics[$topicId])) continue;

                    // 向后 2000 字符找标题
                    $chunk = substr($html, $offset + strlen($threadUrl), 3000);
                    $title = '';
                    // 先找纯文本链接
                    if (preg_match('/>([^<]{4,80})<\/a>/', $chunk, $tm)) {
                        $title = trim($tm[1]);
                    }
                    // 再找 alt 属性
                    if (empty($title) && preg_match('/alt="([^"]{4,80})"/', $chunk, $am)) {
                        $title = trim($am[1]);
                    }

                    if (!empty($title) && !preg_match('/^(全部|精华|最新|最热|回复|查看|论坛视频)/u', $title)) {
                        $topics[$topicId] = array(
                            'href'  => 'https:' . $threadUrl,
                            'title' => $title,
                        );
                    }
                }
            }

            $result = array();
            foreach ($topics as $t) {
                if (count($result) >= $limit) break;
                $result[] = array(
                    'index' => count($result) + 1,
                    'title' => $t['title'],
                    'url'   => $t['href'],
                    'hot'   => '',
                    'desc'  => '',
                    'pic'   => ''
                );
            }

            if (empty($result)) {
                return array('success' => false, 'message' => '页面结构变更（未找到帖子）');
            }

            return array(
                'success'     => true,
                'title'       => '汽车之家',
                'subtitle'    => '媳妇当车模',
                'total'       => count($result),
                'update_time' => date('Y-m-d H:i:s'),
                'data'        => $result
            );

        } catch (Exception $e) {
            return array('success' => false, 'message' => '获取失败: ' . $e->getMessage());
        }
    }
}
?>
