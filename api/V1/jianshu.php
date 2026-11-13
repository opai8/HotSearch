<?php
/**
 * 简书处理器 - 首页「今日热点」
 * 数据来源: https://www.jianshu.com/ (SSR 直接嵌入 today-selection-list)
 *
 * 反爬关键: 简书会拦截带完整现代浏览器指纹 (Sec-Ch-Ua / Sec-Fetch-* /
 * Upgrade-Insecure-Requests) 的请求，返回 403 code:9 "异常请求"。
 * 最简 curl (只有基础 UA + Accept) 反而能正常返回完整 HTML。
 */

class JianshuHotSearch {

    public function handle($limit = 20) {
        try {
            $html = $this->curl('https://www.jianshu.com/');

            if (empty($html)) {
                return array('success' => false, 'message' => '请求失败');
            }

            // 检查是否被反爬拦截
            if (strpos($html, '"code":9') !== false && strpos($html, '异常请求') !== false) {
                return array('success' => false, 'message' => '被简书反爬拦截(403)');
            }

            // 优先解析 today-selection-list (今日热点)
            if (strpos($html, 'today-selection-list') === false) {
                return array('success' => false, 'message' => '页面结构变更（未找到今日热点）');
            }

            libxml_use_internal_errors(true);
            $dom = new DOMDocument();
            @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
            $xpath = new DOMXPath($dom);

            // 找到 today-selection-list 容器
            $list = $xpath->query('//div[contains(@class, "today-selection-list")]');
            if (!$list || $list->length == 0) {
                libxml_clear_errors();
                return array('success' => false, 'message' => '未找到今日热点容器');
            }

            $container = $list->item(0);

            // 提取容器内所有 /p/ 链接
            $links = $xpath->query('.//a[contains(@href, "/p/")]', $container);

            $result = array();
            foreach ($links as $a) {
                if (count($result) >= $limit) break;

                $href = $a->getAttribute('href');
                $title = trim($a->textContent);

                if (!$href || !$title) continue;

                $url = (strpos($href, 'http') === 0) ? $href : 'https://www.jianshu.com' . $href;

                $result[] = array(
                    'index' => count($result) + 1,
                    'title' => $title,
                    'url'   => $url,
                    'hot'   => '',
                    'desc'  => '',
                    'pic'   => ''
                );
            }

            libxml_clear_errors();

            if (count($result) === 0) {
                return array('success' => false, 'message' => '今日热点列表为空');
            }

            return array(
                'success'     => true,
                'title'       => '简书',
                'subtitle'    => '今日热点',
                'total'       => count($result),
                'update_time' => date('Y-m-d H:i:s'),
                'data'        => $result
            );

        } catch (Exception $e) {
            libxml_clear_errors();
            return array('success' => false, 'message' => '获取失败: ' . $e->getMessage());
        }
    }

    /**
     * 最简 curl — 关键: 不发送 Sec-Ch-Ua / Sec-Fetch-* / Upgrade-Insecure-Requests
     * 这些现代浏览器指纹 headers，否则会触发简书反爬 (403 code:9)
     */
    private function curl($url) {
        $headers = array(
            'User-Agent: Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language: zh-CN,zh;q=0.9',
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $output = curl_exec($ch);
        curl_close($ch);
        return $output ?: '';
    }
}
?>
