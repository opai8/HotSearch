<?php
/**
 * AIbase - AI日报
 * 数据源: https://news.aibase.com/zh/daily
 * Nuxt.js SSR，直接 DOM 解析每日日报链接 /zh/daily/{id}
 */

class AibaseHotSearch {

    public function handle($limit = 10) {
        try {
            $url = 'https://news.aibase.com/zh/daily';
            $headers = array(
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language: zh-CN,zh;q=0.9',
            );

            $ch = smartCurlInit();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_ENCODING, '');
            $html = smartCurlExec($ch, 'aibase');
            curl_close($ch);

            if (!$html) return array('success' => false, 'message' => '请求失败');

            // 解析 SSR 里的 /zh/daily/xxx 链接，从 img alt 属性取标题（避免正文混入）
            preg_match_all('/href="(\/zh\/daily\/(\d+))"[^>]*>.*?alt="([^"]*)"/s', $html, $matches, PREG_SET_ORDER);

            $result = array();
            $seenIds = array();

            foreach ($matches as $m) {
                if (count($result) >= $limit) break;

                $path = $m[1];      // /zh/daily/32282
                $id = $m[2];        // 32282
                $title = trim($m[3]);

                // 去掉 "AI日报：" 前缀
                $title = preg_replace('/^AI日报[：:]\s*/u', '', $title);

                if (mb_strlen($title) > 100) {
                    $title = mb_substr($title, 0, 100) . '...';
                }
                $title = trim($title);

                // 去重
                if (isset($seenIds[$id])) continue;
                $seenIds[$id] = true;

                // 只取像 "AI日报：xxx" 的链接
                if (empty($title)) continue;

                $result[] = array(
                    'index' => count($result) + 1,
                    'title' => $title,
                    'url'   => 'https://news.aibase.com' . $path,
                    'hot'   => '',
                    'desc'  => '',
                    'pic'   => ''
                );
            }

            if (empty($result)) {
                return array('success' => false, 'message' => '页面结构变更（未找到日报）');
            }

            return array(
                'success'     => true,
                'title'       => 'AIbase',
                'subtitle'    => 'AI日报',
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
