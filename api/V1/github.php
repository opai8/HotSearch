<?php
/**
 * GitHub Trending处理器
 *
 * 数据来源: https://github.com/trending
 */

class GithubHotSearch {

    public function handle($limit = 50) {
        try {
            $url = 'https://github.com/trending';
            $html = $this->curl($url);

            if (empty($html)) {
                return array('success' => false, 'message' => '页面请求失败');
            }

            $items = $this->parseRepos($html);

            if (empty($items)) {
                return array('success' => false, 'message' => '数据解析失败');
            }

            $result = array();
            foreach ($items as $item) {
                if (count($result) >= $limit) break;
                if (empty($item['title'])) continue;

                $result[] = array(
                    'index' => count($result) + 1,
                    'title' => $item['title'],
                    'url'   => $item['url'],
                    'hot'   => $item['stars'],
                    'desc'  => $item['description'],
                    'pic'   => ''
                );
            }

            return array(
                'success'     => true,
                'title'       => 'GitHub',
                'subtitle'    => 'Trending',
                'total'       => count($result),
                'update_time' => date('Y-m-d H:i:s'),
                'data'        => $result
            );

        } catch (Exception $e) {
            return array('success' => false, 'message' => '获取失败: ' . $e->getMessage());
        }
    }

    private function parseRepos($html) {
        $items = array();

        if (preg_match_all('/<article class="Box-row(.*?)<\/article>/s', $html, $repoMatches)) {
            foreach ($repoMatches[1] as $repoHtml) {
                $title = '';
                $url = '';
                $description = '';
                $stars = '';

                if (preg_match('/<h2[^>]*>\s*<a[^>]*href="([^"]+)"[^>]*>(.*?)<\/a>/s', $repoHtml, $m)) {
                    $url = 'https://github.com' . $m[1];
                    $title = trim(strip_tags($m[2]));
                    $title = preg_replace('/\s+/', ' ', $title);
                }

                if (preg_match('/<p[^>]*class="col-9[^"]*"[^>]*>(.*?)<\/p>/s', $repoHtml, $m)) {
                    $description = trim(strip_tags($m[1]));
                }

                if (preg_match_all('/href="\/[^"]+\/stargazers"[^>]*>(.*?)<\/a>/s', $repoHtml, $sm)) {
                    $stars = trim(strip_tags(end($sm[1])));
                }

                if (!empty($title)) {
                    $items[] = array(
                        'title'       => $title,
                        'url'         => $url,
                        'description' => $description,
                        'stars'       => $stars ? ($stars . ' ⭐') : ''
                    );
                }
            }
        }

        if (empty($items)) {
            if (preg_match_all('/<h2[^>]*>\s*<a[^>]*href="(\/[^"]+\/[^"]+)"[^>]*>(.*?)<\/a>/s', $html, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $m) {
                    $title = trim(strip_tags($m[2]));
                    $title = preg_replace('/\s+/', ' ', $title);
                    if (!empty($title) && strpos($title, '/') !== false) {
                        $items[] = array(
                            'title'       => $title,
                            'url'         => 'https://github.com' . $m[1],
                            'description' => '',
                            'stars'       => ''
                        );
                    }
                }
            }
        }

        return $items;
    }

    private function curl($url) {
        $header = array(
            "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36",
            "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8",
            "Referer: https://github.com/"
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
