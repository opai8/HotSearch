<?php
/**
 * 小红书处理器（首页推荐 / Explore 热榜）
 * 数据来源: https://www.xiaohongshu.com/explore
 * 技术要点:
 *   1. 完整浏览器 headers（Sec-Fetch-*, Sec-Ch-Ua, Upgrade-Insecure-Requests）
 *   2. 手动大括号匹配 window.__INITIAL_STATE__（不能用正则 .*? 会被第一个 } 截断）
 *   3. 需要浏览器 cookie（a1/webId/gid/websectiga）才能拿到 SSR 完整数据
 *
 * 使用方法:
 *   1. 用浏览器打开 https://www.xiaohongshu.com/explore 并登录
 *   2. F12 → Console → 执行 document.cookie → 复制完整 cookie 字符串
 *   3. 粘贴到 cache/_xhs_user_cookies.txt（一行，纯 cookie name=value; name2=value2 格式）
 *   4. Cookie 过期后重复步骤 2-3
 */

class XiaohongshuHotSearch {

    private static function getCookieJarFile() {
        return __DIR__ . '/../../cache/_xhs_cookies.txt';
    }

    private static function getUserCookieFile() {
        return __DIR__ . '/../../cache/_xhs_user_cookies.txt';
    }

    public function handle($limit = 50) {
        try {
            $html = $this->curl('https://www.xiaohongshu.com/explore');
            if (empty($html)) {
                return array('success' => false, 'message' => '请求失败');
            }

            // 检查是否被重定向到登录页
            if (strpos($html, 'redirectPath=') !== false && strpos($html, '/login') !== false) {
                return array('success' => false, 'message' => 'Cookie 已过期，请更新 cache/_xhs_user_cookies.txt');
            }

            // 手动大括号匹配提取 window.__INITIAL_STATE__ = {...}
            $prefix = 'window.__INITIAL_STATE__=';
            $startIdx = strpos($html, $prefix);
            if ($startIdx === false) {
                return array('success' => false, 'message' => '页面结构变更（未找到 INITIAL_STATE）');
            }

            $afterPrefix = substr($html, $startIdx + strlen($prefix));
            $jsonStr = $this->matchBrace($afterPrefix);
            if ($jsonStr === null) {
                return array('success' => false, 'message' => 'INITIAL_STATE 解析失败');
            }

            // JS → JSON 兼容处理
            $jsonStr = preg_replace('/\bundefined\b/', 'null', $jsonStr);
            $jsonStr = preg_replace('/,\s*([}\]])/', '$1', $jsonStr);

            $state = json_decode($jsonStr, true);
            if (!$state || !isset($state['feed']['feeds']) || !is_array($state['feed']['feeds'])) {
                return array('success' => false, 'message' => '未获取到笔记数据（Cookie 可能过期，INITIAL_STATE len=' . strlen($jsonStr) . '）');
            }

            $feeds = $state['feed']['feeds'];
            if (count($feeds) === 0) {
                return array('success' => false, 'message' => '笔记列表为空（Cookie 可能过期）');
            }

            $result = array();
            foreach ($feeds as $feed) {
                if (count($result) >= $limit) break;

                $noteCard = isset($feed['noteCard']) ? $feed['noteCard'] : array();
                $noteId = isset($feed['id']) ? $feed['id'] : '';
                $xsecToken = isset($feed['xsecToken']) ? $feed['xsecToken'] : '';

                $title = isset($noteCard['displayTitle']) ? $noteCard['displayTitle'] : '';
                $author = isset($noteCard['user']['nickName']) ? $noteCard['user']['nickName'] : '';
                $likedCount = isset($noteCard['interactInfo']['likedCount']) ? $noteCard['interactInfo']['likedCount'] : '';
                $pic = isset($noteCard['cover']['urlDefault']) ? $noteCard['cover']['urlDefault'] : '';

                $url = '';
                if ($noteId) {
                    $url = 'https://www.xiaohongshu.com/explore/' . $noteId;
                    if ($xsecToken) {
                        $url .= '?xsec_token=' . urlencode($xsecToken) . '&xsec_source=';
                    }
                }

                $descParts = [];
                if ($author) $descParts[] = "@{$author}";
                if ($likedCount) $descParts[] = "❤️ {$likedCount}";

                $result[] = array(
                    'index' => count($result) + 1,
                    'title' => $title,
                    'url'   => $url,
                    'hot'   => $likedCount,
                    'desc'  => implode(' | ', $descParts),
                    'pic'   => $pic
                );
            }

            return array(
                'success'     => true,
                'title'       => '小红书',
                'subtitle'    => '首页推荐',
                'total'       => count($result),
                'update_time' => date('Y-m-d H:i:s'),
                'data'        => $result
            );

        } catch (Exception $e) {
            return array('success' => false, 'message' => '获取失败: ' . $e->getMessage());
        }
    }

    /**
     * 手动匹配大括号：从第一个 { 开始，到对应深度为 0 的 } 结束
     * 必须忽略字符串内的大括号
     */
    private function matchBrace($str) {
        $depth = 0;
        $start = -1;
        $end = -1;
        $inString = false;
        $escape = false;

        for ($i = 0; $i < strlen($str); $i++) {
            $c = $str[$i];
            if ($inString) {
                if ($escape) { $escape = false; continue; }
                if ($c === '\\') { $escape = true; continue; }
                if ($c === '"') { $inString = false; }
            } else {
                if ($c === '"') { $inString = true; continue; }
                if ($c === '{') {
                    if ($depth === 0) $start = $i;
                    $depth++;
                } elseif ($c === '}') {
                    $depth--;
                    if ($depth === 0) { $end = $i; break; }
                }
            }
        }

        if ($start === -1 || $end === -1) return null;
        return substr($str, $start, $end - $start + 1);
    }

    private function curl($url) {
        $cookieDir = dirname(self::getCookieJarFile());
        if (!is_dir($cookieDir)) {
            @mkdir($cookieDir, 0755, true);
        }

        // 读取用户手动导出的浏览器 cookies（优先）
        $userCookieStr = '';
        $userFile = self::getUserCookieFile();
        if (file_exists($userFile)) {
            $userCookieStr = trim(file_get_contents($userFile));
        }

        $headers = array(
            "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36",
            "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8",
            "Accept-Language: zh-CN,zh;q=0.9,en;q=0.8",
            "Accept-Encoding: gzip, deflate, br, zstd",
            "Cache-Control: max-age=0",
            "Sec-Ch-Ua: \"Google Chrome\";v=\"131\", \"Chromium\";v=\"131\", \"Not_A Brand\";v=\"24\"",
            "Sec-Ch-Ua-Mobile: ?0",
            "Sec-Ch-Ua-Platform: \"Windows\"",
            "Sec-Fetch-Dest: document",
            "Sec-Fetch-Mode: navigate",
            "Sec-Fetch-Site: none",
            "Sec-Fetch-User: ?1",
            "Upgrade-Insecure-Requests: 1",
        );

        // 如果有用户 cookies，加到 Cookie header
        if ($userCookieStr !== '') {
            $headers[] = 'Cookie: ' . $userCookieStr;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 25);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

        // 关键：如果有用户 cookies，就不要用 cookie jar（服务器返回的 acw_tc 等会干扰）
        if ($userCookieStr === '') {
            // 没有用户 cookies 时，用 cookie jar 自动管理（首次访问会让服务器 set cookie）
            $jarFile = self::getCookieJarFile();
            curl_setopt($ch, CURLOPT_COOKIEJAR, $jarFile);
            if (file_exists($jarFile)) {
                curl_setopt($ch, CURLOPT_COOKIEFILE, $jarFile);
            }
        }

        $output = curl_exec($ch);
        curl_close($ch);
        return $output ?: '';
    }
}
?>
