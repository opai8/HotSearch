<?php
/**
 * 虎扑步行街热帖 API 处理器（修复版）
 *
 * 数据来源:
 *   - 主策略: https://bbs.hupu.com/all-gambia  （HTML 页面，帖子标题/链接直接在 <a> 中）
 *   - 备用策略: HTML DOM 解析（页面结构稳定时最可靠）
 *   - 兜底策略: 正则抓取 <a class="post-title"> 中的链接
 *
 * 说明: 虎扑服务端渲染页面，帖子数据直接在 HTML 中。
 *       之前使用 "topicCount"/"topics" JSON 键名查找，
 *       由于虎扑频繁更新前端变量名，这里改为直接 DOM 解析。
 */

class HupuHotSearch {

    /**
     * 抓取步行街热帖
     * @param int $limit
     * @return array
     */
    public function handle($limit = 50) {
        try {
            // ---- 策略 A: 直接请求步行街主页面 ----
            $html = $this->curl('https://bbs.hupu.com/all-gambia');
            if (empty($html)) {
                // ---- 策略 B: 请求移动端页面 ----
                $html = $this->curl('https://m.hupu.com/bbs/all-gambia');
            }
            if (empty($html)) {
                return array('success' => false, 'message' => '页面请求失败（可能被限制）');
            }

            // 统一处理：去掉换行，避免 XPath/正则被 \n 干扰
            $htmlClean = preg_replace('/\s+/', ' ', $html);

            $items = null;

            // ---- 尝试 1: 用 DOM + XPath 查找带链接的帖子标题 ----
            if (function_exists('libxml_use_internal_errors')) {
                $items = $this->parseByDOM($html);
            }

            // ---- 尝试 2: 正则匹配 <a href="帖子链接" ...>标题</a> ----
            if (!is_array($items) || empty($items)) {
                $items = $this->parseByRegex($htmlClean);
            }

            // ---- 尝试 3: 在页面中查找 "topics"/"topicList" 等 JSON 块 ----
            if (!is_array($items) || empty($items)) {
                $items = $this->parseByJsonSearch($htmlClean);
            }

            if (!is_array($items) || empty($items)) {
                return array('success' => false, 'message' => '数据解析失败（页面结构可能已更新）');
            }

            // ---- 格式化输出 ----
            $tempArr = array();
            foreach ($items as $item) {
                if (count($tempArr) >= $limit) break;

                $title = isset($item['title']) ? trim($item['title']) : '';
                $url   = isset($item['url'])   ? trim($item['url'])   : '';
                $hot   = isset($item['hot'])   ? trim($item['hot'])   : '';
                if (empty($title)) continue;

                // 补全相对 URL
                if (!empty($url) && strpos($url, 'http') !== 0) {
                    if (strpos($url, '//') === 0) {
                        $url = 'https:' . $url;
                    } elseif (strpos($url, '/') === 0) {
                        $url = 'https://bbs.hupu.com' . $url;
                    } else {
                        $url = 'https://bbs.hupu.com/' . $url;
                    }
                }

                $tempArr[] = array(
                    'index' => count($tempArr) + 1,
                    'title' => $title,
                    'desc'  => '',
                    'pic'   => '',
                    'url'   => $url,
                    'hot'   => $hot
                );
            }

            if (empty($tempArr)) {
                return array('success' => false, 'message' => '未找到有效热帖数据');
            }

            return array(
                'success'     => true,
                'title'       => '虎扑步行街',
                'subtitle'    => '热帖',
                'total'       => count($tempArr),
                'update_time' => date('Y-m-d H:i:s', time()),
                'data'        => $tempArr
            );

        } catch (Exception $e) {
            return array('success' => false, 'message' => '获取数据失败: ' . $e->getMessage());
        }
    }

    // ============================================================
    // 解析策略 A: DOM + XPath
    // ============================================================
    private function parseByDOM($html) {
        $dom = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);

        // 去掉 <meta charset> 问题，用 mb_convert_encoding 确保中文正常
        $html = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');
        $dom->loadHTML('<div>' . $html . '</div>');
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $items = array();

        // 虎扑帖子的常见结构: <a href="/xxx.html">标题</a>
        // 尝试多种 XPath
        $queries = array(
            '//a[contains(@href, ".html") and not(contains(@href, "javascript:"))]',
            '//a[contains(@class, "title")]',
            '//a[contains(@class, "post-title")]',
            '//a[@class="t-title"]',
            '//a[contains(@class, "bbs-title")]',
        );

        $seen = array();
        foreach ($queries as $query) {
            $nodes = $xpath->query($query);
            if (!$nodes || $nodes->length === 0) continue;

            foreach ($nodes as $node) {
                $title = trim($node->nodeValue);
                $href  = $node->getAttribute('href');

                if (empty($title) || empty($href)) continue;
                // 过滤: 只保留帖子链接（含数字ID的 html）
                if (!preg_match('#/\d+\.html#', $href) && !preg_match('#/bbs/\d+#', $href)) continue;
                // 过滤太短或太长的异常标题
                if (mb_strlen($title) < 4 || mb_strlen($title) > 120) continue;

                $key = md5($href);
                if (isset($seen[$key])) continue;
                $seen[$key] = true;

                $items[] = array('title' => $title, 'url' => $href, 'hot' => '');
            }

            if (count($items) >= 30) break;
        }

        return $items;
    }

    // ============================================================
    // 解析策略 B: 正则抓取 <a href="/数字.html">标题</a>
    // ============================================================
    private function parseByRegex($html) {
        $items = array();
        $seen  = array();

        // 匹配: <a ... href="链接" ...>标题</a>
        // 链接必须包含数字 .html 或 /数字.html
        if (preg_match_all('/<a[^>]+href=["\']([^"\']+\d+\.html[^"\']*)["\'][^>]*>([^<]{2,120})<\/a>/u', $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $url   = trim($m[1]);
                $title = trim(strip_tags($m[2]));
                if (empty($title) || empty($url)) continue;
                if (mb_strlen($title) < 4 || mb_strlen($title) > 120) continue;

                // 过滤掉导航/广告等
                if (strpos($title, '步行街') !== false && strpos($title, '首页') !== false) continue;

                $key = md5($url);
                if (isset($seen[$key])) continue;
                $seen[$key] = true;

                $items[] = array('title' => $title, 'url' => $url, 'hot' => '');
                if (count($items) >= 50) break;
            }
        }

        // 回退: 找更宽松的 <a href="/数字.数字.html"> 结构
        if (count($items) < 10 && preg_match_all('/<a[^>]+href=["\'](\/\d+\.html)["\'][^>]*>([^<]{2,120})<\/a>/u', $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $title = trim(strip_tags($m[2]));
                $url   = trim($m[1]);
                if (empty($title)) continue;
                $key = md5($url);
                if (isset($seen[$key])) continue;
                $seen[$key] = true;
                $items[] = array('title' => $title, 'url' => $url, 'hot' => '');
                if (count($items) >= 50) break;
            }
        }

        return $items;
    }

    // ============================================================
    // 解析策略 C: 查找页面内嵌 JSON 中的 topics 数组
    // ============================================================
    private function parseByJsonSearch($html) {
        $items = array();

        // 找 "topics": [ {...}, {...} ] 结构
        if (preg_match('/"topics"\s*:\s*(\[[^\]]{1,500000}\])/', $html, $matches)) {
            $parsed = json_decode($matches[1], true);
            if (is_array($parsed)) {
                foreach ($parsed as $item) {
                    if (!is_array($item)) continue;
                    $title = '';
                    $url   = '';
                    $hot   = '';
                    foreach (array('title', 'topicTitle', 'name') as $k) {
                        if (isset($item[$k]) && is_string($item[$k])) { $title = $item[$k]; break; }
                    }
                    foreach (array('topicId', 'id', 'tid') as $k) {
                        if (isset($item[$k]) && is_numeric($item[$k])) { $url = 'https://bbs.hupu.com/' . $item[$k] . '.html'; break; }
                    }
                    foreach (array('replies', 'reply_num', 'views', 'hotValue') as $k) {
                        if (isset($item[$k]) && (is_numeric($item[$k]) || is_string($item[$k]))) { $hot = $item[$k]; break; }
                    }
                    if (!empty($title) && !empty($url)) {
                        $items[] = array('title' => $title, 'url' => $url, 'hot' => $hot);
                    }
                }
            }
        }

        // 再尝试: window.__INITIAL_STATE__
        if (empty($items) && preg_match('/window\.__INITIAL_STATE__\s*=\s*(\{.*?\});?\s*<\/script>/', $html, $matches)) {
            $parsed = json_decode($matches[1], true);
            if (is_array($parsed)) {
                $found = $this->searchArrayForTopics($parsed);
                if (!empty($found)) $items = $found;
            }
        }

        return $items;
    }

    // 递归在数组中查找 "topics" 键
    private function searchArrayForTopics($arr) {
        if (!is_array($arr)) return null;
        foreach ($arr as $k => $v) {
            if (is_array($v)) {
                if (strpos(strtolower($k), 'topic') !== false && is_array($v) && !empty($v)) {
                    $items = array();
                    foreach ($v as $item) {
                        if (is_array($item) && isset($item['title'])) {
                            $url = isset($item['topicId']) ? 'https://bbs.hupu.com/' . $item['topicId'] . '.html' : '';
                            $hot = isset($item['replies']) ? $item['replies'] : '';
                            $items[] = array('title' => $item['title'], 'url' => $url, 'hot' => $hot);
                        }
                    }
                    if (!empty($items)) return $items;
                }
                $found = $this->searchArrayForTopics($v);
                if ($found && !empty($found)) return $found;
            }
        }
        return null;
    }

    // ============================================================
    // HTTP 请求（带真实浏览器 UA + Cookie）
    // ============================================================
    private function curl($url) {
        $header = array(
            "accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8",
            "Accept-Language: zh-CN,zh;q=0.9,en;q=0.8",
            "User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36",
            "Cache-Control: max-age=0",
            "Referer: https://bbs.hupu.com/"
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        // 支持 Cookie（部分页面需要）
        $cookieFile = sys_get_temp_dir() . '/hot_hupu_cookie_' . md5($url) . '.txt';
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);

        $output = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // 403/404 等返回空
        if ($httpCode >= 400) return '';
        return $output;
    }
}
?>
