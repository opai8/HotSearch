<?php
/**
 * 第一财经 - 新闻排行（周榜）
 * 数据源: https://www.yicai.com/api/ajax/getranklistbykeys?keys=newsRank
 *
 * 发现历程:
 *   - www.yicai.com/news/       → newsRank div 空壳（JS 动态加载）
 *   - m.yicai.com/news/         → 移动端也是 JS 动态加载
 *   - www.yicai.com/search.html → PHP curl 拿到空壳，WebFetch 渲染后才有内容
 *   - ✅ 找到隐藏 API: /api/ajax/getranklistbykeys?keys=newsRank
 *     从 common_pc.js 里发现的调用方式:
 *       $.get("/api/ajax/getranklistbykeys", {keys: o.join(",")}, callback, "json")
 *     返回 JSON: {"newsRank": {"week": [...], "month": [...]}}
 *     week/month 各 10 条，字段: NewsID, NewsTitle, NewsType, url
 */

class YicaiHotSearch {

    public function handle($limit = 10) {
        try {
            $url = 'https://www.yicai.com/api/ajax/getranklistbykeys?keys=newsRank';
            $headers = array(
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
                'Accept: application/json, text/javascript, */*; q=0.01',
                'Accept-Language: zh-CN,zh;q=0.9',
                'Referer: https://www.yicai.com/search.html',
                'X-Requested-With: XMLHttpRequest',
            );

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 25);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            $resp = curl_exec($ch);
            $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if (!$resp || $http != 200) {
                return array('success' => false, 'message' => "API 请求失败 (HTTP $http)");
            }

            $data = json_decode($resp, true);
            if (!$data || empty($data['newsRank']['week'])) {
                return array('success' => false, 'message' => 'API 返回数据异常');
            }

            $weekList = $data['newsRank']['week'];
            $result = array();

            foreach ($weekList as $item) {
                if (count($result) >= $limit) break;

                $newsId  = isset($item['NewsID']) ? $item['NewsID'] : '';
                $title   = isset($item['NewsTitle']) ? trim($item['NewsTitle']) : '';
                $urlPath = isset($item['url']) ? $item['url'] : '';

                if (empty($title)) continue;

                $result[] = array(
                    'index' => count($result) + 1,
                    'title' => $title,
                    'url'   => 'https://www.yicai.com' . $urlPath,
                    'hot'   => '周榜 No.' . (count($result) + 1),
                    'desc'  => '',
                    'pic'   => ''
                );
            }

            if (empty($result)) {
                return array('success' => false, 'message' => 'API 数据为空');
            }

            return array(
                'success'     => true,
                'title'       => '第一财经',
                'subtitle'    => '新闻周榜',
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
