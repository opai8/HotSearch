<?php
/**
 * ============================================================
 * API 文档页 - 对外公开的 API 接口说明
 * ============================================================
 *
 * 功能：展示可用平台、请求方式、返回字段、示例代码
 * 路由：/apidoc → /apidoc.php（nginx.conf rewrite）
 *
 * 动态：从 api/V2/*.php（config.php api_version 配置）扫描可用平台
 *
 * SEO：index, follow（公开页面）
 * ============================================================
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/function.php';

$cfg = $GLOBALS['site_config'];

// ============================================================
// 扫可用平台（和 index.php / sitemap.php 逻辑一致）
// ============================================================
$apiVersion = preg_replace('/[^V0-9]/', '', strtoupper($cfg['api_version'])) ?: 'V1';
$versionDir = __DIR__ . '/api/' . $apiVersion;
$availableIds = [];
if (is_dir($versionDir)) {
    foreach (glob($versionDir . '/*.php') as $f) {
        $availableIds[] = strtolower(pathinfo($f, PATHINFO_FILENAME));
    }
}
$apiPlatforms = [];
foreach ($GLOBALS['sources'] as $s) {
    if (empty($availableIds) || in_array(strtolower($s['id']), $availableIds, true)) {
        $apiPlatforms[] = $s;
    }
}

// Base URL（根据 seo_domain 或当前请求推断）
$baseUrl = $cfg['seo_domain'] ?: (
    (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://')
    . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost')
);

// ============================================================
// SEO 变量
// ============================================================
$pageTitle   = 'API 接口文档 - ' . $cfg['site_name'];
$pageDesc    = $cfg['site_name'] . ' 开放 API 接口文档，支持 ' . count($apiPlatforms) . ' 个实时热搜平台，JSON 格式、跨域支持、免费调用。';
$pageKeywords = 'API 文档,热搜 API,热榜接口,JSON API,开放接口,多平台热搜';
$pageRobots  = 'index, follow';
$canonical   = $baseUrl . '/apidoc';

include __DIR__ . '/home/header.php';
?>
<link rel="stylesheet" href="/assets/css/apidoc.css">

<div class="apidoc-wrap">

    <!-- 面包屑 -->
    <nav class="apidoc-breadcrumb" aria-label="面包屑导航">
        <a href="/">首页</a> <span class="bc-sep">›</span>
        <span>API 文档</span>
    </nav>

    <!-- Hero -->
    <div class="apidoc-hero">
        <h1><span class="iconfont icon-apikey"></span> API 接口文档</h1>
        <p class="apidoc-hero-desc">
            <?php echo escapeHtml($cfg['site_name']); ?> 提供实时热搜数据 API，
            支持 <?php echo count($apiPlatforms); ?> 个平台，JSON 格式、跨域支持、免费调用。
        </p>
    </div>

    <!-- ============================================================ -->
    <!-- 1. 接口概览 -->
    <!-- ============================================================ -->
    <section class="apidoc-section">
        <h2 class="apidoc-section-title">
            <span class="iconfont icon-info"></span>
            接口概览
        </h2>

        <div class="apidoc-card-grid">

            <div class="apidoc-card">
                <div class="apidoc-card-head">
                    <span class="apidoc-method">GET</span>
                    <code class="apidoc-url">/api/{平台}</code>
                </div>
                <div class="apidoc-card-body">
                    <p>获取指定平台的实时热搜榜单，返回 JSON 格式。</p>
                    <div class="apidoc-meta">
                        <span><span class="iconfont icon-lock"></span> 公开接口，无需密钥</span>
                        <span><span class="iconfont icon-time"></span> 缓存 12 小时</span>
                    </div>
                </div>
            </div>

            <div class="apidoc-card apidoc-card-alt">
                <div class="apidoc-card-head">
                    <span class="apidoc-method">GET</span>
                    <code class="apidoc-url">/archive/{平台}</code>
                </div>
                <div class="apidoc-card-body">
                    <p>历史存档页面，展示过去所有日期的热搜榜单，支持按日期浏览。</p>
                    <div class="apidoc-meta">
                        <span><span class="iconfont icon-calendar"></span> 永久积累</span>
                        <span><span class="iconfont icon-browse"></span> 网页可访问</span>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <!-- ============================================================ -->
    <!-- 2. 请求方式 -->
    <!-- ============================================================ -->
    <section class="apidoc-section">
        <h2 class="apidoc-section-title">
            <span class="iconfont icon-code"></span>
            请求方式
        </h2>

        <div class="apidoc-request-box">
            <div class="apidoc-request-row">
                <span class="apidoc-label">Base URL</span>
                <code class="apidoc-code-inline"><?php echo escapeHtml($baseUrl); ?></code>
            </div>
            <div class="apidoc-request-row">
                <span class="apidoc-label">请求方法</span>
                <code class="apidoc-code-inline">GET</code>
            </div>
            <div class="apidoc-request-row">
                <span class="apidoc-label">响应格式</span>
                <code class="apidoc-code-inline">application/json; charset=utf-8</code>
            </div>
            <div class="apidoc-request-row">
                <span class="apidoc-label">跨域</span>
                <span class="apidoc-tag apidoc-tag-ok"><span class="iconfont icon-dui"></span> 支持 CORS</span>
            </div>
            <div class="apidoc-request-row">
                <span class="apidoc-label">请求限流</span>
                <span class="apidoc-tag">每分钟 10 次 / 每平台 60 秒冷却</span>
            </div>
        </div>

        <h3 class="apidoc-subtitle">请求参数</h3>
        <table class="apidoc-table">
            <thead>
                <tr>
                    <th>参数</th>
                    <th>类型</th>
                    <th>必填</th>
                    <th>说明</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code class="apidoc-code-inline">{平台}</code></td>
                    <td>string</td>
                    <td><span class="apidoc-tag apidoc-tag-required">是</span></td>
                    <td>平台标识，见下方"支持的平台"列表</td>
                </tr>
                <tr>
                    <td><code class="apidoc-code-inline">limit</code></td>
                    <td>int</td>
                    <td>否</td>
                    <td>返回条数上限，默认 <?php echo intval($cfg['api_limit']); ?>，最大 <?php echo intval($cfg['api_limit']); ?></td>
                </tr>
            </tbody>
        </table>
    </section>

    <!-- ============================================================ -->
    <!-- 3. 支持的平台（动态扫描） -->
    <!-- ============================================================ -->
    <section class="apidoc-section">
        <h2 class="apidoc-section-title">
            <span class="iconfont icon-app"></span>
            支持的平台 <span class="apidoc-count-badge"><?php echo count($apiPlatforms); ?></span>
        </h2>

        <div class="apidoc-platform-grid">
            <?php foreach ($apiPlatforms as $p): ?>
            <a class="apidoc-platform-card"
               href="<?php echo escapeHtml($baseUrl); ?>/<?php echo escapeHtml($p['id']); ?>"
               target="_blank" rel="noopener noreferrer">
                <span class="iconfont <?php echo escapeHtml($p['icon']); ?> platform-icon <?php echo escapeHtml($p['colorClass']); ?>"></span>
                <div class="apidoc-platform-info">
                    <div class="apidoc-platform-name"><?php echo escapeHtml($p['name']); ?></div>
                    <div class="apidoc-platform-id"><code class="apidoc-code-inline"><?php echo escapeHtml($p['id']); ?></code></div>
                </div>
                <span class="apidoc-platform-arrow"><span class="iconfont icon-arrow_right_fat"></span></span>
            </a>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- ============================================================ -->
    <!-- 4. 请求示例 -->
    <!-- ============================================================ -->
    <section class="apidoc-section">
        <h2 class="apidoc-section-title">
            <span class="iconfont icon-code"></span>
            请求示例
        </h2>

        <div class="apidoc-examples">

            <div class="apidoc-example">
                <div class="apidoc-example-head">
                    <span class="apidoc-example-lang">cURL</span>
                </div>
<pre class="apidoc-code-block"><code>curl "<?php echo escapeHtml($baseUrl); ?>/api/baidu"</code></pre>
            </div>

            <div class="apidoc-example">
                <div class="apidoc-example-head">
                    <span class="apidoc-example-lang">JavaScript</span>
                </div>
<pre class="apidoc-code-block"><code>fetch('<?php echo escapeHtml($baseUrl); ?>/api/baidu')
  .then(r =&gt; r.json())
  .then(data =&gt; console.log(data));</code></pre>
            </div>

            <div class="apidoc-example">
                <div class="apidoc-example-head">
                    <span class="apidoc-example-lang">Python</span>
                </div>
<pre class="apidoc-code-block"><code>import requests
resp = requests.get('<?php echo escapeHtml($baseUrl); ?>/api/baidu')
data = resp.json()
print(data['data']['data'][:3])  # 前 3 条</code></pre>
            </div>

            <div class="apidoc-example">
                <div class="apidoc-example-head">
                    <span class="apidoc-example-lang">直接预览</span>
                </div>
                <div class="apidoc-quick-links">
                    <a href="/api/baidu" target="_blank" rel="noopener" class="apidoc-quick-link">
                        <span class="iconfont icon-arrow_right_fat"></span> 百度热搜 JSON
                    </a>
                    <a href="/api/weibo" target="_blank" rel="noopener" class="apidoc-quick-link">
                        <span class="iconfont icon-arrow_right_fat"></span> 微博热搜 JSON
                    </a>
                    <a href="/api/douyin" target="_blank" rel="noopener" class="apidoc-quick-link">
                        <span class="iconfont icon-arrow_right_fat"></span> 抖音热搜 JSON
                    </a>
                </div>
            </div>

        </div>
    </section>

    <!-- ============================================================ -->
    <!-- 5. 返回字段说明 -->
    <!-- ============================================================ -->
    <section class="apidoc-section">
        <h2 class="apidoc-section-title">
            <span class="iconfont icon-info"></span>
            返回字段说明
        </h2>

<pre class="apidoc-code-block apidoc-response-block"><code>{
  "success": true,
  "message": "ok",
  "captured_at": 1731676809,          // 数据抓取时间（Unix 时间戳）
  "version": "<?php echo escapeHtml($apiVersion); ?>",
  "source": {
    "id": "baidu",
    "name": "百度热搜",
    "url": "https://top.baidu.com/"
  },
  "data": {
    "data": [
      {
        "rank": 1,
        "title": "创新是亚太经济腾飞的翅膀",
        "url": "https://www.baidu.com/...",
        "hot": "7904521"
      },
      // ...更多条目
    ],
    "count": 50
  }
}</code></pre>

        <table class="apidoc-table">
            <thead>
                <tr>
                    <th>字段</th>
                    <th>类型</th>
                    <th>说明</th>
                </tr>
            </thead>
            <tbody>
                <tr><td><code>success</code></td><td>bool</td><td>请求是否成功</td></tr>
                <tr><td><code>message</code></td><td>string</td><td>状态描述，成功时为 "ok"</td></tr>
                <tr><td><code>captured_at</code></td><td>int</td><td>数据抓取时间（Unix 时间戳）</td></tr>
                <tr><td><code>source</code></td><td>object</td><td>平台信息（id / name / url）</td></tr>
                <tr><td><code>data.data</code></td><td>array</td><td>热搜条目数组</td></tr>
                <tr><td><code>data.data[].rank</code></td><td>int</td><td>排名（1 起）</td></tr>
                <tr><td><code>data.data[].title</code></td><td>string</td><td>热搜标题</td></tr>
                <tr><td><code>data.data[].url</code></td><td>string</td><td>原文链接</td></tr>
                <tr><td><code>data.data[].hot</code></td><td>string</td><td>热度值（原始平台的热度表示）</td></tr>
            </tbody>
        </table>
    </section>

    <!-- ============================================================ -->
    <!-- 6. 错误码 -->
    <!-- ============================================================ -->
    <section class="apidoc-section">
        <h2 class="apidoc-section-title">
            <span class="iconfont icon-cuowu"></span>
            错误码
        </h2>

        <table class="apidoc-table">
            <thead>
                <tr>
                    <th>HTTP 状态</th>
                    <th>success</th>
                    <th>message</th>
                    <th>说明</th>
                </tr>
            </thead>
            <tbody>
                <tr><td>200</td><td>true</td><td>ok</td><td>请求成功</td></tr>
                <tr><td>400</td><td>false</td><td>平台名无效或缺失</td><td>URL 中缺少平台标识</td></tr>
                <tr><td>429</td><td>false</td><td>请求过于频繁</td><td>触发限流（每分钟 10 次 / 每平台 60 秒冷却）</td></tr>
                <tr><td>501</td><td>false</td><td>V2 不支持的平台</td><td>平台不在版本目录的白名单内</td></tr>
                <tr><td>503</td><td>false</td><td>获取数据失败</td><td>上游平台请求超时或返回异常</td></tr>
            </tbody>
        </table>
    </section>

    <!-- ============================================================ -->
    <!-- 7. 缓存策略 -->
    <!-- ============================================================ -->
    <section class="apidoc-section">
        <h2 class="apidoc-section-title">
            <span class="iconfont icon-shandian"></span>
            缓存策略
        </h2>

        <div class="apidoc-card-grid apidoc-card-grid-2">
            <div class="apidoc-card apidoc-card-info">
                <div class="apidoc-card-head">
                    <span class="apidoc-tag apidoc-tag-ok"><span class="iconfont icon-dui"></span> 自动缓存</span>
                </div>
                <div class="apidoc-card-body">
                    <p>
                        数据抓取后自动缓存 <strong><?php echo intval($cfg['cache_ttl'] / 3600); ?> 小时</strong>，
                        期间同一平台的请求全部命中缓存，响应极快。
                    </p>
                </div>
            </div>
            <div class="apidoc-card apidoc-card-info">
                <div class="apidoc-card-head">
                    <span class="apidoc-tag"><span class="iconfont icon-refresh"></span> 强制刷新</span>
                </div>
                <div class="apidoc-card-body">
                    <p>
                        运维可通过 <code>/core/refresh.php?source={平台}</code> 手动触发重爬，
                        或在详情页点击"强制刷新"按钮。
                    </p>
                </div>
            </div>
        </div>
    </section>

    <div class="apidoc-footer-note">
        文档最后更新：<?php echo date('Y-m-d'); ?> · 支持 <?php echo count($apiPlatforms); ?> 个平台 · API 版本 <?php echo escapeHtml($apiVersion); ?>
    </div>

</div>

<?php include __DIR__ . '/home/footer.php'; ?>
