<?php
/**
 * NGA 论坛热帖处理器
 * 数据来源: https://ngabbs.com/nuke.php
 */

class NgabbsHotSearch {

    public function handle($limit = 50) {
        try {
            $url = 'https://ngabbs.com/nuke.php?__lib=load_topic&__act=load_topic_reply_ladder2&opt=1&all=1';
            $json = $this->curlPost($url, '__output=14');

            if (empty($json)) {
                return array('success' => false, 'message' => '请求失败');
            }

            $data = json_decode($json, true);
            if (!$data || !isset($data['result'][0]) || !is_array($data['result'][0])) {
                return array('success' => false, 'message' => '解析失败');
            }

            $list = $data['result'][0];
            $result = array();
            $seen = array();  // 按 tid 去重（上游接口有时返回重复条目）
            foreach ($list as $v) {
                if (count($result) >= $limit) break;

                // 用 tpcurl 里的 tid 作为唯一标识去重
                $tid = isset($v['tpcurl']) ? $v['tpcurl'] : (isset($v['tid']) ? $v['tid'] : '');
                if (!empty($tid) && isset($seen[$tid])) {
                    continue;  // 已见过，跳过
                }
                if (!empty($tid)) $seen[$tid] = true;

                $result[] = array(
                    'index' => count($result) + 1,
                    'title' => isset($v['subject']) ? $v['subject'] : '',
                    'url'   => isset($v['tpcurl']) ? "https://bbs.nga.cn{$v['tpcurl']}" : '',
                    'hot'   => isset($v['replies']) ? intval($v['replies']) : '',
                    'desc'  => isset($v['author']) ? "作者: {$v['author']}" : '',
                    'pic'   => ''
                );
            }

            return array(
                'success'     => true,
                'title'       => 'NGA',
                'subtitle'    => '论坛热帖',
                'total'       => count($result),
                'update_time' => date('Y-m-d H:i:s'),
                'data'        => $result
            );

        } catch (Exception $e) {
            return array('success' => false, 'message' => '获取失败: ' . $e->getMessage());
        }
    }

    private function curlPost($url, $postData) {
        // 使用 Chrome UA（历史：iOS App UA 会触发 NGA 接口返回官方升级公告混入列表）
        $headers = array(
            "Accept: */*",
            "Host: ngabbs.com",
            "Referer: https://ngabbs.com/",
            "Content-Type: application/x-www-form-urlencoded",
            "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36",
        );
        $ch = smartCurlInit();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
        $output = smartCurlExec($ch, 'ngabbs');
        curl_close($ch);
        return $output ?: '';
    }
}
?>
