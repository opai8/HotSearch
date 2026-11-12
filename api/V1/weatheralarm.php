<?php
/**
 * 中央气象台气象预警处理器
 * 数据来源: http://www.nmc.cn/rest/findAlarm
 */

class WeatheralarmHotSearch {

    public function handle($limit = 50) {
        try {
            $url = 'http://www.nmc.cn/rest/findAlarm?pageNo=1&pageSize=' . min($limit, 50) . '&signaltype=&signallevel=&province=';
            $json = $this->curl($url);

            if (empty($json)) {
                return array('success' => false, 'message' => '请求失败');
            }

            $data = json_decode($json, true);
            if (!$data || !isset($data['data']['page']['list']) || !is_array($data['data']['page']['list'])) {
                return array('success' => false, 'message' => '解析失败');
            }

            $list = $data['data']['page']['list'];
            $result = array();
            foreach ($list as $v) {
                if (count($result) >= $limit) break;

                $result[] = array(
                    'index' => count($result) + 1,
                    'title' => isset($v['title']) ? $v['title'] : '',
                    'url'   => (isset($v['url']) && $v['url']) ? 'http://nmc.cn' . $v['url'] : '',
                    'hot'   => '',
                    'desc'  => isset($v['issuetime']) ? $v['issuetime'] : '',
                    'pic'   => isset($v['pic']) ? $v['pic'] : ''
                );
            }

            return array(
                'success'     => true,
                'title'       => '中央气象台',
                'subtitle'    => '气象预警',
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
            "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36",
            "Accept: application/json, text/plain, */*",
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge($defaultHeaders, $headers));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $output = curl_exec($ch);
        curl_close($ch);
        return $output ?: '';
    }
}
?>
