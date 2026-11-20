<?php
/**
 * 小红书热榜 API 处理器
 *
 * 数据源（按优先级排列，第一个失败自动尝试下一个）:
 *   1. UAPI 聚合      https://uapis.cn/api/v1/misc/hotboard?type=xiaohongshu  （推荐，实测可用）
 *   2. 60s viki.moe   https://60s.viki.moe/v2/rednote                         （备用，可能 429）
 *   3. VVHan          https://api.vvhan.com/api/hotlist/xhs                    （备用）
 *
 * 使用方法:
 *   /api/index.php/rednote          - 获取默认 20 条
 *   /api/index.php/rednote?limit=10 - 获取 10 条
 */

class RednoteHotSearch {

    /**
     * 数据源列表 — 每项包含 url + 类型标识，用于差异化解析
     */
    private $sources = [
        ['type' => 'uapi',  'url' => 'https://uapis.cn/api/v1/misc/hotboard?type=xiaohongshu'],
        ['type' => 'viki',  'url' => 'https://60s.viki.moe/v2/rednote'],
        ['type' => 'vvhan', 'url' => 'https://api.vvhan.com/api/hotlist/xhs'],
    ];

    /**
     * 获取小红书热榜数据
     *
     * @param int $limit 返回条数限制
     * @return array
     */
    public function handle($limit = 20) {
        $errors = [];

        foreach ($this->sources as $source) {
            try {
                $result = $this->fetchAndParse($source, $limit);
                if ($result !== null) {
                    return $result;
                }
            } catch (\Exception $e) {
                $errors[] = $source['url'] . ': ' . $e->getMessage();
            }
        }

        return [
            'success' => false,
            'message' => '所有数据源均不可用' . (empty($errors) ? '' : '：' . implode(' | ', $errors))
        ];
    }

    /**
     * 请求并解析单个数据源
     */
    private function fetchAndParse($source, $limit) {
        $apiUrl = $source['url'];
        $type   = $source['type'];

        $response = $this->httpGet($apiUrl);

        if ($response === null || $response['code'] !== 200) {
            return null;
        }

        $data = json_decode($response['body'], true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            return null;
        }

        // 按数据源类型差异化解析
        switch ($type) {
            case 'uapi':
                return $this->parseUapi($data, $limit);
            case 'vvhan':
                return $this->parseVvhan($data, $limit);
            case 'viki':
            default:
                return $this->parseViki($data, $limit);
        }
    }

    /**
     * UAPI 格式: { type, update_time, list: [{index, title, url, cover, hot_value, extra: {type}}] }
     */
    private function parseUapi($data, $limit) {
        $list = $data['list'] ?? null;
        if (!is_array($list) || empty($list)) return null;

        $items = [];
        foreach ($list as $item) {
            if (!is_array($item)) continue;
            if (count($items) >= $limit) break;

            $title = $item['title'] ?? '';
            if (empty($title)) continue;

            $items[] = [
                'index' => $item['index'] ?? count($items) + 1,
                'title' => $title,
                'hot'   => $item['hot_value'] ?? '',
                'url'   => $item['url']
                          ?? ('https://www.xiaohongshu.com/search_result?keyword=' . urlencode($title) . '&type=51'),
                'desc'  => $item['extra']['type'] ?? '',
                'pic'   => $item['cover'] ?? ($item['extra']['cover'] ?? ''),
            ];
        }

        if (empty($items)) return null;

        return [
            'success'     => true,
            'title'       => '小红书',
            'subtitle'    => '热搜榜',
            'total'       => count($items),
            'update_time' => date('Y-m-d H:i:s'),
            'data'        => $items,
        ];
    }

    /**
     * VVHan 格式（已知可能的变体，兼容多种 list/items/data 键）
     */
    private function parseVvhan($data, $limit) {
        // VVHan 通常: { success: true, data: [{title, url, hot}] }
        // 但也可能直接返回数组
        $list = null;
        if (isset($data['data']) && is_array($data['data'])) {
            $list = $data['data'];
        } elseif (isset($data['list']) && is_array($data['list'])) {
            $list = $data['list'];
        } elseif (is_array($data)) {
            // 可能直接就是数组
            $firstKey = array_key_first($data);
            if (is_array($data[$firstKey] ?? null)) {
                $list = $data[$firstKey];
            } else {
                $list = $data;
            }
        }

        if (!$list || empty($list)) return null;

        $items = [];
        foreach ($list as $item) {
            if (!is_array($item)) continue;
            if (count($items) >= $limit) break;

            $title = $item['title'] ?? $item['name'] ?? '';
            if (empty($title)) continue;

            $items[] = [
                'index' => $item['rank'] ?? $item['index'] ?? count($items) + 1,
                'title' => $title,
                'hot'   => $item['hot'] ?? $item['hot_value'] ?? $item['heat'] ?? '',
                'url'   => $item['url']
                          ?? ('https://www.xiaohongshu.com/search_result?keyword=' . urlencode($title) . '&type=51'),
                'desc'  => $item['type'] ?? $item['tag'] ?? '',
                'pic'   => $item['cover'] ?? $item['image'] ?? '',
            ];
        }

        if (empty($items)) return null;

        return [
            'success'     => true,
            'title'       => '小红书',
            'subtitle'    => '热搜榜',
            'total'       => count($items),
            'update_time' => date('Y-m-d H:i:s'),
            'data'        => $items,
        ];
    }

    /**
     * viki.moe 格式: { code: 200, message, data: [{rank, title, score, link}] }
     */
    private function parseViki($data, $limit) {
        if (($data['code'] ?? 0) !== 200) return null;

        $list = $data['data'] ?? null;
        if (!is_array($list) || empty($list)) return null;

        $items = [];
        foreach ($list as $item) {
            if (!is_array($item)) continue;
            if (count($items) >= $limit) break;

            $title = $item['title'] ?? $item['display_title'] ?? $item['keyword'] ?? '';
            if (empty($title)) continue;

            $items[] = [
                'index' => $item['rank'] ?? count($items) + 1,
                'title' => $title,
                'hot'   => $item['score'] ?? $item['hot'] ?? $item['heat'] ?? '',
                'url'   => $item['link']
                          ?? ('https://www.xiaohongshu.com/search_result?keyword=' . urlencode($title) . '&type=51'),
                'desc'  => $item['word_type'] ?? '',
                'pic'   => $item['cover'] ?? $item['image'] ?? '',
            ];
        }

        if (empty($items)) return null;

        return [
            'success'     => true,
            'title'       => '小红书',
            'subtitle'    => '热搜榜',
            'total'       => count($items),
            'update_time' => date('Y-m-d H:i:s'),
            'data'        => $items,
        ];
    }

    /**
     * HTTP GET 请求
     */
    private function httpGet($url) {
        $headers = [
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36',
            'Accept: application/json, text/plain, */*',
            'Accept-Language: zh-CN,zh;q=0.9,en;q=0.8',
        ];

        $ch = smartCurlInit();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

        $body = smartCurlExec($ch, 'rednote');
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($body === false) {
            return null;
        }

        return ['code' => $code, 'body' => $body];
    }
}
