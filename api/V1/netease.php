<?php
/**
 * 网易云音乐 - 热歌榜
 * 数据源: https://music.163.com/api/playlist/detail?id=3778678
 * 官方 JSON API，热歌榜 ID=3778678
 */

class NeteaseHotSearch {

    public function handle($limit = 10) {
        // 网易云音乐只爬 10 条
        $limit = min((int)$limit, 10);

        try {
            $url = 'https://music.163.com/api/playlist/detail?id=3778678&limit=' . $limit . '&offset=0';
            $headers = array(
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
                'Accept: application/json, text/plain, */*',
                'Referer: https://music.163.com/',
            );

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_ENCODING, '');
            $json = curl_exec($ch);
            curl_close($ch);

            if (!$json) return array('success' => false, 'message' => '请求失败');

            $data = json_decode($json, true);
            if (!$data || !isset($data['result']['tracks'])) {
                return array('success' => false, 'message' => 'API 返回异常');
            }

            $tracks = $data['result']['tracks'];
            $result = array();

            foreach (array_slice($tracks, 0, $limit) as $idx => $t) {
                $name = $t['name'] ?? '';
                // 歌手（可能多个）
                $artist = '';
                if (!empty($t['ar'])) {
                    $names = array();
                    foreach ($t['ar'] as $a) $names[] = $a['name'];
                    $artist = implode('/', $names);
                }
                // 专辑
                $album = $t['al']['name'] ?? '';
                // 网易云链接
                $url = 'https://music.163.com/#/song?id=' . ($t['id'] ?? '');

                $result[] = array(
                    'index' => $idx + 1,
                    'title' => $name,
                    'url'   => $url,
                    'hot'   => $artist,
                    'desc'  => $album,
                    'pic'   => $t['al']['picUrl'] ?? '',
                );
            }

            if (empty($result)) {
                return array('success' => false, 'message' => '热歌榜为空');
            }

            return array(
                'success'     => true,
                'title'       => '网易云音乐',
                'subtitle'    => '热歌榜',
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
