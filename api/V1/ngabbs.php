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
            foreach ($list as $v) {
                if (count($result) >= $limit) break;

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
        $headers = array(
            "Accept: */*",
            "Host: ngabbs.com",
            "Referer: https://ngabbs.com/",
            "Content-Type: application/x-www-form-urlencoded",
            "User-Agent: Apifox/1.0.0",
            "X-User-Agent: NGA_skull/7.3.1(iPhone13,2;iOS 17.2.1)",
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
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
