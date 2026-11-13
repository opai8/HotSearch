<?php
/**
 * CSDN 博客热榜处理器
 * 数据来源: https://blog.csdn.net/phoenix/web/blog/hotRank
 */

class CsdnHotSearch {

    public function handle($limit = 20) {
        // CSDN 只爬 20 条
        $limit = min((int)$limit, 20);

        try {
            $url = 'https://blog.csdn.net/phoenix/web/blog/hotRank?page=0&pageSize=' . $limit;
            $json = $this->curl($url);

            if (empty($json)) {
                return array('success' => false, 'message' => '请求失败');
            }

            $data = json_decode($json, true);
            if (!$data || !isset($data['code']) || $data['code'] != 200 || !isset($data['data']) || !is_array($data['data'])) {
                return array('success' => false, 'message' => '解析失败');
            }

            $list = $data['data'];
            $result = array();
            foreach ($list as $v) {
                if (count($result) >= $limit) break;

                $pic = isset($v['picList']) && is_array($v['picList']) ? $v['picList'][0] : '';
                $author = isset($v['nickName']) ? $v['nickName'] : '';
                $viewCount = isset($v['viewCount']) ? $v['viewCount'] : '';
                $commentCount = isset($v['commentCount']) ? $v['commentCount'] : '';

                $descParts = [];
                if ($author) $descParts[] = "作者: {$author}";
                if ($viewCount) $descParts[] = "浏览 {$viewCount}";
                if ($commentCount) $descParts[] = "评论 {$commentCount}";

                $result[] = array(
                    'index' => count($result) + 1,
                    'title' => isset($v['articleTitle']) ? $v['articleTitle'] : '',
                    'url'   => isset($v['articleDetailUrl']) ? $v['articleDetailUrl'] : '',
                    'hot'   => isset($v['hotRankScore']) ? intval($v['hotRankScore']) : '',
                    'desc'  => implode(' | ', $descParts),
                    'pic'   => $pic
                );
            }

            return array(
                'success'     => true,
                'title'       => 'CSDN',
                'subtitle'    => '博客热榜',
                'total'       => count($result),
                'update_time' => date('Y-m-d H:i:s'),
                'data'        => $result
            );

        } catch (Exception $e) {
            return array('success' => false, 'message' => '获取失败: ' . $e->getMessage());
        }
    }

    private function curl($url, $headers = array()) {
        $defaultHeaders = array(
            "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36",
            "Accept: application/json, text/plain, */*",
            "Accept-Language: zh-CN,zh;q=0.9,en;q=0.8",
            "Referer: https://blog.csdn.net/",
            "Origin: https://blog.csdn.net",
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge($defaultHeaders, $headers));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_ENCODING, '');  // 自动处理所有压缩格式
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        $output = curl_exec($ch);
        curl_close($ch);
        return $output ?: '';
    }
}
?>
