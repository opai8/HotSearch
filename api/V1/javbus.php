<?php
/**
 * JavBus 标签幻灯片处理器（需要登录 Cookie）
 *
 * 数据来源: https://www.javbus.com/forum/
 * 提取区域: <div class="biaoqicn_slide"><ul class="slideshow"><li>...
 *
 * 使用方法:
 *   /api/index.php/javbus?limit=20
 *
 * ================================================================
 * Cookie 配置（必填）
 * ================================================================
 * 从浏览器登录后，在开发者工具（F12）→ Network → 刷新页面
 * → 点击任意请求 → Request Headers → 复制完整的 Cookie 字段
 * → 粘贴到下方 COOKIE 常量中
 *
 * 示例格式（不要带 Cookie: 前缀）:
 *   define('COOKIE', 'username=xxx; sessionid=xxx; ...');
 * ================================================================
 */
define('COOKIE', 'PHPSESSID=0a5sip9u6vpa4rp3kqgs2dvjh2; existmag=mag; 4fJN_2132_saltkey=xnKZ377g; 4fJN_2132_lastvisit=1782758936; 4fJN_2132_sendmail=1; 4fJN_2132_seccodecSxE1bmv=43.994df639b002ce0f7e; cf_clearance=6hYMVhGe71dEbwRGswKw5ER3QDA3oeiOIoSFp8MTh00-1782762537-1.2.1.1-YLOfxZ6JkF0X0pyhPZHmtVpd4Bi7sECVR6HXvchkKpbc7IYE2OopKw7sJgDbuoMPBldGYqoPKqQ5uXQFqeSSf6EOVupExtCARQEUhPEPRvKTO2syAxaLw2ZRcT2hbbVAyhg.G3ghlDdglc343k3IG6zMTs5ujhO0Myp3o1nXpYydauz2zOQyIeC694iNTRCejLOWwVyijbiSGHWWy5lSKVITW8jw7CNV_ynspYHKhWTEh5mUVqNjSywbP6SnklsF.TKqc3Ds27R0O9pvsPT88.UGHMUkxIy5iomUU7D00z4T5jGNiGp7QgpUyvZB4U_iw85Ewr6oo4dAkAS8jvw3Gw; 4fJN_2132_ulastactivity=7ba9aE66Q2gzZgvA41kVFiQ65bjS3KWIxyRez9AVYzFgAhqLs5ZM; 4fJN_2132_lastcheckfeed=109387%7C1782762581; 4fJN_2132_checkfollow=1; 4fJN_2132_auth=96fdMdLUXMOcqis7FcoxY8lz94egTrZKejuNhYAHB0hmuMUjdN%2FE%2FG0MWgZOE2dHKWyrL0p84%2BUUayOLw16Be3m0rMw; bus_auth=16f7ICwUrFdemkwAOWX66NwMtlPBiL7NEuha5Jq5vs%2F42OVwz5a%2BsQ; 4fJN_2132_nofavfid=1; 4fJN_2132_onlineusernum=953; 4fJN_2132_sid=yEZo6A; 4fJN_2132_lip=108.162.246.64%2C1782762591; 4fJN_2132_lastact=1782762592%09home.php%09spacecp; 4fJN_2132_checkpm=1');

class JavbusHotSearch {

    public function handle($limit = 20) {
        try {
            if (empty(COOKIE) || COOKIE === '请在此处填写你的 Cookie') {
                return array(
                    'success' => false,
                    'message' => '请先在 api/V1/javbus.php 中填写 COOKIE 常量'
                );
            }

            $html = $this->curl('https://www.dmmsee.ink/forum/', COOKIE);

            if (empty($html)) {
                return array(
                    'success' => false,
                    'message' => '页面请求失败'
                );
            }

            $items = $this->parseSlideshow($html, $limit);

            if (empty($items)) {
                return array(
                    'success' => false,
                    'message' => '未找到标签幻灯片，可能页面结构已变更'
                );
            }

            return array(
                'success'     => true,
                'title'       => 'JavBus',
                'subtitle'    => '标签',
                'total'       => count($items),
                'update_time' => date('Y-m-d H:i:s'),
                'data'        => $items
            );

        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => '获取数据失败: ' . $e->getMessage()
            );
        }
    }

    /**
     * 解析 biaoqicn_slide 中的 slideshow 列表
     * 页面结构:
     *   <div class="biaoqicn_slide">
     *     <ul class="slideshow">
     *       <li><a href="..."><img src="..." alt="...">...</a></li>
     *       ...
     *     </ul>
     *   </div>
     */
    private function parseSlideshow($html, $limit) {
        $items = array();

        $slideHtml = '';
        if (preg_match('/<div\s+class="biaoqicn_slide"\s*>(.*?)<\/ul>\s*<\/div>/is', $html, $divMatch)) {
            $slideHtml = $divMatch[1];
        } elseif (preg_match('/<ul\s+class="slideshow"\s*>(.*?)<\/ul>/is', $html, $ulMatch)) {
            $slideHtml = $ulMatch[1];
        }

        if (empty($slideHtml)) {
            return $items;
        }

        if (preg_match_all('/<li\b[^>]*>(.*?)<\/li>/is', $slideHtml, $liMatches, PREG_SET_ORDER)) {
            foreach ($liMatches as $liMatch) {
                if (count($items) >= $limit) break;

                $liHtml = $liMatch[1];

                $url = '';
                $title = '';
                $pic = '';

                if (preg_match('/<a\s+href="([^"]+)"[^>]*>(.*?)<\/a>/is', $liHtml, $aMatch)) {
                    $url = $aMatch[1];
                    $aInner = $aMatch[2];

                    if (preg_match('/<img\s+[^>]*src="([^"]+)"[^>]*>/is', $aInner, $imgMatch)) {
                        $pic = $imgMatch[1];
                    }

                    if (preg_match('/alt="([^"]+)"/is', $aInner, $altMatch)) {
                        $title = trim($altMatch[1]);
                    }

                    if (empty($title)) {
                        $text = trim(strip_tags($aInner));
                        $text = preg_replace('/\s+/', ' ', $text);
                        if (!empty($text)) {
                            $title = $text;
                        }
                    }
                }

                if (empty($title)) {
                    $text = trim(strip_tags($liHtml));
                    $text = preg_replace('/\s+/', ' ', $text);
                    if (!empty($text)) {
                        $title = $text;
                    }
                }

                if (empty($title) && empty($pic)) continue;

                if (!empty($url) && strpos($url, 'http') !== 0) {
                    if (strpos($url, '/') === 0) {
                        $url = 'https://www.dmmsee.ink' . $url;
                    } else {
                        $url = 'https://www.dmmsee.ink/' . $url;
                    }
                }

                if (!empty($url) && preg_match('/[?&]tid=(\d+)/i', $url, $tidMatch)) {
                    $tid = $tidMatch[1];
                    $url = 'https://www.dmmsee.ink/forum/forum.php?mod=viewthread&tid=' . $tid;
                }

                if (!empty($pic) && strpos($pic, 'http') !== 0) {
                    if (strpos($pic, '//') === 0) {
                        $pic = 'https:' . $pic;
                    } elseif (strpos($pic, '/') === 0) {
                        $pic = 'https://www.dmmsee.ink' . $pic;
                    } else {
                        $pic = 'https://www.dmmsee.ink/' . $pic;
                    }
                }

                $items[] = array(
                    'index' => count($items) + 1,
                    'title' => $title ?: '标签' . (count($items) + 1),
                    'url'   => $url ?: 'https://www.dmmsee.ink/forum/',
                    'hot'   => '',
                    'desc'  => '',
                    'pic'   => $pic
                );
            }
        }

        return $items;
    }

    /**
     * 发起带 Cookie 的 HTTP 请求
     */
    private function curl($url, $cookie) {
        $header = array(
            "Cookie: " . $cookie,
            "User-Agent: Mozilla/5.0 (Linux; Android 15; Pixel 9) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Mobile Safari/537.36",
            "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7",
            "Accept-Language: zh,zh-CN;q=0.9",
            "Referer: https://www.dmmsee.ink/forum/"
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
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

        $output = curl_exec($ch);
        curl_close($ch);

        return $output ?: '';
    }
}
?>
