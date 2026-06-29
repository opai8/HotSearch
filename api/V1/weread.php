<?php
/**
 * 微信读书热搜榜处理器
 *
 * 数据来源: https://weread.qq.com/web/bookListInCategory/hot_search?rank=1
 */

class WereadHotSearch {

    public function handle($limit = 50) {
        try {
            $url = 'https://weread.qq.com/web/bookListInCategory/hot_search?rank=1';
            $json = $this->curl($url);

            if (empty($json)) {
                return array('success' => false, 'message' => 'API请求失败');
            }

            $data = json_decode($json, true);

            if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
                return array('success' => false, 'message' => 'JSON解析失败: ' . json_last_error_msg());
            }

            $books = array();
            if (isset($data['books']) && is_array($data['books'])) {
                $books = $data['books'];
            }

            if (empty($books)) {
                return array('success' => false, 'message' => '数据格式错误');
            }

            $items = array();
            foreach ($books as $v) {
                if (count($items) >= $limit) break;

                $bookInfo = isset($v['bookInfo']) && is_array($v['bookInfo']) ? $v['bookInfo'] : $v;

                $title = '';
                foreach (array('title', 'bookTitle', 'book_name', 'name') as $k) {
                    if (isset($bookInfo[$k]) && is_string($bookInfo[$k])) {
                        $title = $bookInfo[$k];
                        break;
                    }
                }

                $bookId = '';
                foreach (array('bookId', 'book_id', 'id') as $k) {
                    if (isset($bookInfo[$k]) && (is_string($bookInfo[$k]) || is_numeric($bookInfo[$k]))) {
                        $bookId = $bookInfo[$k];
                        break;
                    }
                }

                $hot = '';
                if (isset($v['readingCount']) && (is_string($v['readingCount']) || is_numeric($v['readingCount']))) {
                    $hot = $v['readingCount'];
                } elseif (isset($v['searchCount']) && (is_string($v['searchCount']) || is_numeric($v['searchCount']))) {
                    $hot = $v['searchCount'];
                }

                if (empty($title)) continue;

                $items[] = array(
                    'index' => count($items) + 1,
                    'title' => $title,
                    'url'   => $bookId ? ('https://weread.qq.com/web/bookDetail/' . $bookId) : 'https://weread.qq.com/',
                    'hot'   => $hot ? (is_numeric($hot) ? (number_format($hot) . ' 人在读') : $hot) : '',
                    'desc'  => isset($bookInfo['intro']) ? $bookInfo['intro'] : (isset($bookInfo['bookIntro']) ? $bookInfo['bookIntro'] : ''),
                    'pic'   => isset($bookInfo['cover']) ? $bookInfo['cover'] : (isset($bookInfo['img']) ? $bookInfo['img'] : '')
                );
            }

            return array(
                'success'     => true,
                'title'       => '微信读书',
                'subtitle'    => '热搜',
                'total'       => count($items),
                'update_time' => date('Y-m-d H:i:s'),
                'data'        => $items
            );

        } catch (Exception $e) {
            return array('success' => false, 'message' => '获取失败: ' . $e->getMessage());
        }
    }

    private function curl($url) {
        $header = array(
            "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36",
            "Accept: application/json, text/plain, */*",
            "Referer: https://weread.qq.com/"
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        $output = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 400) return '';
        return $output ?: '';
    }
}
?>
