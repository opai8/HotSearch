<?php
/**
 * 猫眼电影热映榜处理器
 * 数据来源: https://maoyan.com/films
 */

class MaoyanHotSearch {

    public function handle($limit = 50) {
        try {
            $url = 'https://maoyan.com/films?showType=1';
            $headers = array(
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language: zh-CN,zh;q=0.9',
                'Referer: https://maoyan.com/',
            );

            $ch = smartCurlInit();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
            $html = smartCurlExec($ch, 'maoyan');
            curl_close($ch);

            if (!$html) return array('success' => false, 'message' => '请求失败（可能被墙或被反爬）');

            // 尝试解析电影列表
            preg_match_all('/class="movie-item-title"[^>]*>\s*<a[^>]*href="(\/films\/(\d+))"[^>]*>([^<]+)<\/a>/s', $html, $matches, PREG_SET_ORDER);

            $result = array();
            foreach ($matches as $m) {
                if (count($result) >= $limit) break;
                $result[] = array(
                    'index' => count($result) + 1,
                    'title' => trim($m[3]),
                    'url'   => 'https://maoyan.com' . $m[1],
                    'hot'   => '',
                    'desc'  => '',
                    'pic'   => ''
                );
            }

            if (empty($result)) {
                return array('success' => false, 'message' => '页面结构变更或反爬拦截');
            }

            return array(
                'success'     => true,
                'title'       => '猫眼电影',
                'subtitle'    => '热映榜',
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
