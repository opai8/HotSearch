<?php
/**
 * 安全客热文处理器
 * 数据来源: https://www.anquanke.com/
 */

class AnquankeHotSearch {

    public function handle($limit = 50) {
        try {
            $url = 'https://www.anquanke.com/';
            $headers = array(
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language: zh-CN,zh;q=0.9',
            );

            $ch = smartCurlInit();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
            $html = smartCurlExec($ch, 'anquanke');
            curl_close($ch);

            if (!$html) return array('success' => false, 'message' => '请求失败');

            // 解析首页推荐文章列表 .item 下的 .title a
            preg_match_all('/class="title"[^>]*>\s*<a[^>]*href="([^"]*\/post\/id\/(\d+))"[^>]*>([^<]+)<\/a>/s', $html, $matches, PREG_SET_ORDER);

            $result = array();
            $seenIds = array();

            foreach ($matches as $m) {
                if (count($result) >= $limit) break;

                $id = $m[2];
                if (isset($seenIds[$id])) continue;
                $seenIds[$id] = true;

                $result[] = array(
                    'index' => count($result) + 1,
                    'title' => trim($m[3]),
                    'url'   => 'https://www.anquanke.com' . $m[1],
                    'hot'   => '',
                    'desc'  => '',
                    'pic'   => ''
                );
            }

            if (empty($result)) {
                return array('success' => false, 'message' => '页面结构变更');
            }

            return array(
                'success'     => true,
                'title'       => '安全客',
                'subtitle'    => '网络安全热文',
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
