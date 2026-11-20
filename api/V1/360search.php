<?php
/**
 * 360搜索热榜处理器
 * 数据来源: https://ranks.hao.360.com/ (Vite SPA 前端 API)
 * 真实 API: /mbsug-api/hotnewsquery (新闻热搜)
 */

class _360searchHotSearch {

    public function handle($limit = 50) {
        try {
            // 360 搜索热搜 API（从 Vite bundle 逆向出的真实接口）
            $url = 'https://ranks.hao.360.com/mbsug-api/hotnewsquery?type=news&realhot_limit=50&src=hao_ranklist_so';
            $headers = array(
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
                'Accept: application/json, text/plain, */*',
                'Referer: https://ranks.hao.360.com/',
            );

            $ch = smartCurlInit();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
            $json = smartCurlExec($ch, '360search');
            curl_close($ch);

            if (!$json) return array('success' => false, 'message' => '请求失败');

            $data = json_decode($json, true);
            if (!$data || !is_array($data)) {
                return array('success' => false, 'message' => '解析失败');
            }

            $result = array();
            foreach ($data as $v) {
                if (count($result) >= $limit) break;

                // rise: 1=上升, -1=下降, 0=持平
                $rise = isset($v['rise']) ? intval($v['rise']) : 0;
                $riseText = $rise === 1 ? '🔥' : ($rise === -1 ? '⬇️' : '');

                $result[] = array(
                    'index' => count($result) + 1,
                    'title' => isset($v['title']) ? $v['title'] : '',
                    'url'   => isset($v['url']) ? $v['url'] : '',
                    'hot'   => isset($v['score']) ? intval($v['score']) : '',
                    'desc'  => isset($v['sumtxt']) ? mb_substr($v['sumtxt'], 0, 80) : '',
                    'pic'   => isset($v['newscard_imgurl']) ? $v['newscard_imgurl'] : ''
                );
            }

            if (empty($result)) {
                return array('success' => false, 'message' => '接口返回空数据');
            }

            return array(
                'success'     => true,
                'title'       => '360搜索',
                'subtitle'    => '新闻热搜',
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
