<?php
/**
 * ============================================================
 * 全局配置文件（首页唯一配置入口）
 * ============================================================
 */

// ============================================================
// PHP 运行时错误日志（所有错误写入 logs/php_errors.log）
// 出错了直接看这个文件，不用翻 Apache/Nginx error log
// ============================================================
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/php_errors.log');
error_reporting(E_ALL);

// 确保 logs/ 目录存在（某些环境首次运行时自动创建）
$logsDir = __DIR__ . '/logs';
if (!is_dir($logsDir)) { @mkdir($logsDir, 0755, true); }

// ============================================================
// 全局加载 curl 封装（smartCurlInit 统一 SSL 配置）
// 所有文件 include config.php 后自动可用
// ============================================================
require_once __DIR__ . '/core/curl_helper.php';

/**
 * 新手阅读建议：
 *   1. 先看第 1 部分「站点基本信息」，改网站名/描述/Logo 在这里
 *   2. 再看第 2 部分「显示配置」，改布局/卡片高度/显示条数在这里
 *   3. 第 3 部分「功能开关」，控制各种按钮和组件显示隐藏
 *   4. 第 4 部分「缓存配置」，改缓存时间在这里
 *   5. 第 5 部分「SEO 优化」，改网站描述/关键词/域名在这里
 *   6. 第 6 部分「分类定义」，新增分类或调整分类顺序在这里
 *   7. 第 7 部分「平台列表」，增减/排序平台/指定分类在这里
 *
 * 修改方法：直接改下面的变量值，保存后刷新页面立即生效。
 * ============================================================
 */


// ============================================================
// 站点配置数组（所有配置都在这里面）
// ============================================================
// 新手提示：
//   为什么把所有配置放一个数组里？
//   因为集中管理，找配置都在一个地方，不用到处翻。
//   后面代码用 $GLOBALS['site_config']['xxx'] 来读取。
// ============================================================
$site_config = [

    // ========================================================
    // 1. 站点基本信息
    // ========================================================
    // 改网站名称、描述、Logo 等基础信息在这里
    // ========================================================
    'site_name'        => '今日热榜',          // 网站名称（左上角标题 + 浏览器标签页）
    'site_desc'        => '多平台实时热点聚合',  // 网站描述（标题下面的小字）
    'site_logo'        => '/assets/images/logo-128.png',  // Logo 图片路径（128x128，Retina 屏清晰）
    'site_favicon'     => '/assets/images/favicon.png',   // 浏览器标签页小图标（保留兼容用）


    // ========================================================
    // 2. 显示配置（布局和样式相关）
    // ========================================================
    // 改卡片高度、每行几个、默认显示条数等在这里
    // ========================================================
    'columns_per_row'  => '4',       // 每行显示几个卡片：数字=固定数量(4、5、6)，'auto'=自适应（根据 card_min_width 自动排列）
    'card_min_width'   => 280,       // 卡片最小宽度（px，仅自适应模式生效）
    'card_height'      => 400,       // 卡片高度（px，桌面端）
    'content_max_width' => 1600,     // 内容区最大宽度（px）
    'display_limit'    => 10,        // 默认显示条数（折叠状态）
    'api_limit'        => 50,       // API 拉取最大条数
    'api_version'      => 'V2',     // API handler 目录版本：'V1' 或 'V2'（对应 /api/V1/ /api/V2/）
    'curl_ssl_verify'  => false,    // curl SSL 证书验证开关
                                   // false = 跳过验证（默认！本地开箱即用）
                                   // true  = 严格验证（上线前改这里为 true 即可，一行搞定）
    'default_theme'    => 'light',   // 默认主题：'light'日间 / 'dark'夜间 / 'auto'跟随系统


    // ========================================================
    // 3. 功能开关（控制各种元素显示/隐藏）
    // ========================================================
    // true = 显示，false = 隐藏
    // ========================================================
    'show_header_refresh'  => true,   // 顶部：刷新全部按钮
    'show_header_setting' => true,   // 顶部：设置按钮
    'show_notice_bar'     => true,   // 顶部通知栏
    'show_back_to_top'    => true,   // 回到顶部按钮
    'show_refresh_btn'    => true,   // 卡片右下角刷新按钮
    'show_heat'           => true,   // 热度值（小火苗+数字）
    'show_source_link'    => true,   // 数据来源链接（卡片右上角）
    'show_footer'         => true,   // 页脚
    // 第三方统计代码（百度统计/Google Analytics 等）
    // 直接粘贴完整的 <script>...</script> 代码进去即可，留空则不注入
    // 示例（百度统计）：
    // 'statistics_code' => '<script>var _hmt=_hmt||[];(function(){var b=document.createElement("script");b.src="https://hm.baidu.com/hm.js?YOUR_ID";var s=document.getElementsByTagName("script")[0];s.parentNode.insertBefore(b,s)})();</script>',
    'statistics_code'     => '',
    // 热搜链接打开方式：写 '_blank' 新窗口打开，写 '_self' 或不写就同窗口打开
    'link_open_new_window'=> '_blank',


    // ========================================================
    // 3.5 外部链接（notice-bar 等位置使用的可配置链接）
    // ========================================================
    'api_doc_link'     => '/apidoc',                              // API 文档链接（站内 clean URL）
    'github_repo_link' => 'https://github.com/yourname/hotsearch-web',  // 开源仓库地址


    // ========================================================
    // 3.6 广告配置
    // ========================================================
    // 广告位说明：
    //   top_banner  → 顶部 banner（notice-bar 下方，全宽，最多 2 条）
    //   grid_slot   → 首页卡片网格中间插入（第 $grid_insert_after 张卡片后，跟平台卡片同尺寸）
    //
    // 每个广告字段：
    //   enabled  : 是否启用（false = 不渲染这条）
    //   title    : 广告标题（img alt 文本 + 角标说明）
    //   img      : 图片 URL（建议尺寸见下方说明）
    //   url      : 点击跳转链接
    //   badge    : 角标文字（如 "广告"、"AD"，留空则自动显示 "广告"）
    //
    // 图片尺寸建议：
    //   top_banner : 宽高比 10:1 ~ 15:1，推荐 1200×120 或 1200×80，max-height CSS 限制 120px
    //   grid_slot  : 跟平台卡片同尺寸，推荐 400×300 或 500×350，保持比例不拉伸
    //
    // 图片路径写法：
    //   本地图: '/assets/ad/xxx.jpg'       （开头带斜杠，项目根目录）
    //   远程图: 'https://cdn.xxx.com/yyy.jpg' （完整 URL）
    //
    // 想暂时关掉某个广告？把 enabled 改成 false 就行，不用删
    // ========================================================
    'top_banner_ads' => [
        [
            'enabled' => false,
            'title'   => '今日热榜 - 多平台实时热点聚合',
            'img'     => '',
            'url'     => 'https://hot.cc',
            'badge'   => '',
        ],
        [
            'enabled' => false,    // 第二条占位 banner
            'title'   => '广告位招租 - 联系站长',
            'img'     => '',
            'url'     => 'https://hot.cc/apidoc',
            'badge'   => '',
        ],
    ],
    // 卡片网格中间插卡广告（最多 1 条）
    // grid_insert_after 说明：
    //   0  = 放在最前面（第一个平台卡片之前）
    //   1  = 第 1 张后，2 = 第 2 张后 ... 以此类推
    'grid_slot_ad' => [
        'enabled'           => false,
        'grid_insert_after' => 0,    // 0 = 放在最前面，让你看效果；觉得不好撤回去改 6
        'title'             => '探索更多热门内容',
        'img'               => '',
        'url'               => 'https://hot.cc/apidoc',
        'badge'             => '',
    ],


    // ========================================================
    // 4. 缓存配置
    // ========================================================
    // 改缓存时间、缓存目录在这里
    // ========================================================
    'cache_dir'        => __DIR__ . '/cache',  // 缓存目录路径（页面缓存 + 锁文件 + 历史存档）
    'archive_dir'      => __DIR__ . '/cache',  // 历史存档目录（YYYYMMDD/{source}.json），和 cache_dir 同目录，想拆一行改
    'cache_ttl'        => 12 * 3600,           // 缓存有效期（秒），默认 12 小时（12*3600=43200）
    'page_cache_enable' => true,               // 页面级静态缓存开关
    'page_cache_ttl'    => 60 * 60,            // 页面缓存有效期（秒），默认 1 小时
    'opcache_clear_key' => 'opcache_clear_2026', // OPcache 清理密钥


    // ========================================================
    // 5. SEO 优化配置
    // ========================================================
    // 改网站 SEO 相关配置在这里
    // ========================================================
    'seo_description'  => '聚合百度、微博、知乎、B站、抖音等多平台实时热搜榜单，一站看完全网热点。每日更新，免费使用。',
    'seo_keywords'     => '热搜榜,微博热搜,百度热搜,知乎热榜,B站热搜,抖音热搜,热点聚合,今日热榜,热点榜单,全网热点',
    'seo_domain'       => 'http://hot.cc',   // 网站域名（末尾不要加斜杠）
    'seo_type'         => 'website',          // 网站类型：website/blog/news
    'seo_og_image'     => '/assets/images/og-image.png',  // 社交分享预览图（建议 1200x630）
];


// ============================================================
// 热搜平台配置列表
// ============================================================
// 增减平台、调整顺序、改名称/图标在这里
//
// 每个平台的字段说明：
//   - id         : 平台唯一标识，对应 api/V1/ 或 api/V2/ 下的文件名
//   - name       : 显示在卡片上的名称
//   - icon       : iconfont 图标类名
//   - tag        : 标签文字（热搜/热榜/热帖 等）
//   - colorClass : CSS 颜色类名，定义在 assets/css/icon-color.css
//   - url        : 数据来源链接（卡片上的"数据来源"按钮）
//
// 架构说明：
//   - 首页/API/debug 等入口会根据 api_version 扫描对应版本目录的 handler 文件，
//     然后自动过滤 $sources 数组（保留顺序不变，只排除版本目录没有的）
//   - 所以 $sources 是全量定义，顺序 = 首页卡片排列顺序
//   - 切回 V1 时，下方 V1-only 平台会自动恢复显示
//
// 想隐藏某个平台？直接在它前面加 // 注释掉就行
// 想调整顺序？直接把数组项上下移动
// ============================================================

$sources = [

    // ========================================================
    // V2 平台（当前生效的 24 个）
    // ========================================================
    [
        'id' => 'baidu',
        'name' => '百度',
        'icon' => 'iconfont icon-baidu',
        'tag' => '热搜',
        'colorClass' => 'baidu-icon',
        'url'  => 'https://top.baidu.com/board?tab=realtime'
    ],
    [
        'id' => 'weibo',
        'name' => '微博',
        'icon' => 'iconfont icon-weibo',
        'tag' => '热搜',
        'colorClass' => 'weibo-icon',
        'url'  => 'https://s.weibo.com/top/summary'
    ],
    [
        'id' => 'zhihu',
        'name' => '知乎',
        'icon' => 'iconfont icon-zhihu',
        'tag' => '热榜',
        'colorClass' => 'zhihu-icon',
        'url'  => 'https://www.zhihu.com/hot'
    ],
    [
        'id' => 'rednote',
        'name' => '小红书',
        'icon' => 'iconfont icon-xiaohongshu1',
        'tag' => '热搜榜',
        'colorClass' => 'xiaohongshu-icon',
        'url'  => 'https://www.xiaohongshu.com/explore'
    ],
    [
        'id' => 'tieba',
        'name' => '百度贴吧',
        'icon' => 'iconfont icon-tieba',
        'tag' => '热议',
        'colorClass' => 'tieba-icon',
        'url'  => 'https://tieba.baidu.com/hottopic/browse/topicList'
    ],
    [
        'id' => 'douyin',
        'name' => '抖音',
        'icon' => 'iconfont icon-douyin',
        'tag' => '热榜',
        'colorClass' => 'douyin-icon',
        'url'  => 'https://www.douyin.com/hot'
    ],
	[
        'id' => 'bilibili',
        'name' => 'bilibili',
        'icon' => 'iconfont icon-bilibili',
        'tag' => '热搜',
        'colorClass' => 'bilibili-icon',
        'url'  => 'https://www.bilibili.com/v/popular/rank/all'
    ],
    [
        'id' => 'acfun',
        'name' => 'AcFun',
        'icon' => 'iconfont icon-acfun',
        'tag' => '综合榜',
        'colorClass' => 'acfun-icon',
        'url'  => 'https://www.acfun.cn/'
    ],
    [
        'id' => 'hupu',
        'name' => '虎扑步行街',
        'icon' => 'iconfont icon-hupu',
        'tag' => '热帖',
        'colorClass' => 'hupu-icon',
        'url'  => 'https://bbs.hupu.com/all-gambia'
    ],
    [
        'id' => 'ngabbs',
        'name' => 'NGA',
        'icon' => 'iconfont icon-youxiwanjia',
        'tag' => '论坛热帖',
        'colorClass' => 'ngabbs-icon',
        'url'  => 'https://ngabbs.com/'
    ],

    [
        'id' => 'toutiao',
        'name' => '今日头条',
        'icon' => 'iconfont icon-jinritoutiao',
        'tag' => '热榜',
        'colorClass' => 'toutiao-icon',
        'url'  => 'https://www.toutiao.com/'
    ],
    [
        'id' => 'thepaper',
        'name' => '澎湃新闻',
        'icon' => 'iconfont icon-pengpaixinwen',
        'tag' => '热点',
        'colorClass' => 'thepaper-icon',
        'url'  => 'https://www.thepaper.cn/'
    ],
    [
        'id' => 'sina_news',
        'name' => '新浪新闻',
        'icon' => 'iconfont icon-sina_icon',
        'tag' => '热点榜',
        'colorClass' => 'sina_news-icon',
        'url'  => 'https://news.sina.com.cn/'
    ],
    [
        'id' => 'qq_news',
        'name' => '腾讯新闻',
        'icon' => 'iconfont icon-tencent',
        'tag' => '热点榜',
        'colorClass' => 'qq_news-icon',
        'url'  => 'https://news.qq.com/'
    ],
    [
        'id' => 'wangyi',
        'name' => '网易新闻',
        'icon' => 'iconfont icon-wangyixinwen',
        'tag' => '热榜',
        'colorClass' => 'wangyi-icon',
        'url'  => 'https://news.163.com/'
    ],
    [
        'id' => 'maoyan',
        'name' => '猫眼电影',
        'icon' => 'iconfont icon-doubanwang',
        'tag' => '热映榜',
        'colorClass' => 'maoyan-icon',
        'url'  => 'https://maoyan.com/films'
    ],
    [
        'id' => 'juejin',
        'name' => '掘金',
        'icon' => 'iconfont icon-juejin',
        'tag' => '热榜',
        'colorClass' => 'juejin-icon',
        'url'  => 'https://juejin.cn/'
    ],
    [
        'id' => '36kr',
        'name' => '36氪',
        'icon' => 'iconfont icon-a-36kr',
        'tag' => '热榜',
        'colorClass' => 'kr36-icon',
        'url'  => 'https://36kr.com/'
    ],
    [
        'id' => 'yicai',
        'name' => '第一财经',
        'icon' => 'iconfont icon-diyi',
        'tag' => '新闻周榜',
        'colorClass' => 'yicai-icon',
        'url'  => 'https://www.yicai.com/news/'
    ],
    [
        'id' => 'autohome',
        'name' => '汽车之家',
        'icon' => 'iconfont icon-danchemoshi',
        'tag' => '媳妇当车模',
        'colorClass' => 'autohome-icon',
        'url'  => 'https://club.autohome.com.cn/jingxuan/104/1?sort=time'
    ],
    [
        'id' => 'weread',
        'name' => '微信读书',
        'icon' => 'iconfont icon-weixindushuchaotuoyuan',
        'tag' => '热搜',
        'colorClass' => 'weread-icon',
        'url'  => 'https://weread.qq.com/'
    ],
    [
        'id' => 'aibase',
        'name' => 'AIbase',
        'icon' => 'iconfont icon-Ai',
        'tag' => 'AI日报',
        'colorClass' => 'aibase-icon',
        'url'  => 'https://news.aibase.com/zh/daily'
    ],
    [
        'id' => 'netease',
        'name' => '网易云音乐',
        'icon' => 'iconfont icon-wangyiyunyinle',
        'tag' => '热歌榜',
        'colorClass' => 'netease-icon',
        'url'  => 'https://music.163.com/#/discover/toplist?id=3778678'
    ],
    [
        'id' => 'history',
        'name' => '历史上的今天',
        'icon' => 'iconfont icon-lishijintian',
        'tag' => '历史',
        'colorClass' => 'history-icon',
        'url'  => 'https://baike.baidu.com/'
    ],

    // ========================================================
    // V1-only 平台（V1 目录有 handler，V2 没选入）
    // 切回 api_version = 'V1' 时自动恢复显示
    // ========================================================
    [
        'id' => 'yystv',
        'name' => '游研社',
        'icon' => 'iconfont icon-youxi',
        'tag' => '热文',
        'colorClass' => 'yystv-icon',
        'url'  => 'https://www.yystv.cn/'
    ],
    [
        'id' => 'github',
        'name' => 'GitHub',
        'icon' => 'iconfont icon-GitHub',
        'tag' => 'Trending',
        'colorClass' => 'github-icon',
        'url'  => 'https://github.com/trending'
    ],
    [
        'id' => 'douban_group',
        'name' => '豆瓣讨论',
        'icon' => 'iconfont icon-doubanwang',
        'tag' => '讨论精选',
        'colorClass' => 'douban_group-icon',
        'url'  => 'https://www.douban.com/group/explore'
    ],
    [
        'id' => 'jianshu',
        'name' => '简书',
        'icon' => 'iconfont icon-jianti',
        'tag' => '热门推荐',
        'colorClass' => 'jianshu-icon',
        'url'  => 'https://www.jianshu.com/'
    ],
    [
        'id' => 'sspai',
        'name' => '少数派',
        'icon' => 'iconfont icon-sspai',
        'tag' => '热门',
        'colorClass' => 'sspai-icon',
        'url'  => 'https://sspai.com/'
    ],
    [
        'id' => 'zhihu_daily',
        'name' => '知乎日报',
        'icon' => 'iconfont icon-zhihudaily',
        'tag' => '日报',
        'colorClass' => 'zhihu-icon',
        'url'  => 'https://daily.zhihu.com/'
    ],
    [
        'id' => 'ifanr',
        'name' => '爱范儿',
        'icon' => 'iconfont icon-ifanr',
        'tag' => '热文',
        'colorClass' => 'ifanr-icon',
        'url'  => 'https://www.ifanr.com/'
    ],
    [
        'id' => 'hellogithub',
        'name' => 'HelloGitHub',
        'icon' => 'iconfont icon-github',
        'tag' => '热门',
        'colorClass' => 'hellogithub-icon',
        'url'  => 'https://hellogithub.com/'
    ],
    [
        'id' => 'guokr',
        'name' => '果壳网',
        'icon' => 'iconfont icon-guokewang',
        'tag' => '热文',
        'colorClass' => 'guokr-icon',
        'url'  => 'https://www.guokr.com/'
    ],
    [
        'id' => 'huxiu',
        'name' => '虎嗅',
        'icon' => 'iconfont icon-huxiu',
        'tag' => '热文',
        'colorClass' => 'huxiu-icon',
        'url'  => 'https://www.huxiu.com/'
    ],
    [
        'id' => 'pojie52',
        'name' => '52破解',
        'icon' => 'iconfont icon-52pojie',
        'tag' => '热门',
        'colorClass' => 'pojie52-icon',
        'url'  => 'https://www.52pojie.cn/'
    ],
    [
        'id' => '51cto',
        'name' => '51CTO',
        'icon' => 'iconfont icon-ic-51cto',
        'tag' => '推荐',
        'colorClass' => 'cto51-icon',
        'url'  => 'https://www.51cto.com/'
    ],
    [
        'id' => 'sina',
        'name' => '新浪热榜',
        'icon' => 'iconfont icon-sina',
        'tag' => '综合热榜',
        'colorClass' => 'sina-icon',
        'url'  => 'https://news.sina.com.cn/hotnews/'
    ],
    [
        'id' => 'ithome',
        'name' => 'IT之家',
        'icon' => 'iconfont icon-itzhijia',
        'tag' => '热榜',
        'colorClass' => 'ithome-icon',
        'url'  => 'https://www.ithome.com/'
    ],
    [
        'id' => '360search',
        'name' => '360搜索',
        'icon' => 'iconfont icon-chrome',
        'tag' => '热榜',
        'colorClass' => 'se360-icon',
        'url'  => 'https://ranks.hao.360.com/'
    ],
    [
        'id' => 'anquanke',
        'name' => '安全客',
        'icon' => 'iconfont icon-anquan',
        'tag' => '网络安全',
        'colorClass' => 'anquanke-icon',
        'url'  => 'https://www.anquanke.com/'
    ],
    [
        'id' => 'hackernews',
        'name' => 'Hacker News',
        'icon' => 'iconfont icon-hacker',
        'tag' => 'Popular',
        'colorClass' => 'hackernews-icon',
        'url'  => 'https://news.ycombinator.com/'
    ],
    [
        'id' => 'geekpark',
        'name' => '极客公园',
        'icon' => 'iconfont icon-jikeshijian',
        'tag' => '热门文章',
        'colorClass' => 'geekpark-icon',
        'url'  => 'https://www.geekpark.net/'
    ],
    [
        'id' => 'dgtle',
        'name' => '数字尾巴',
        'icon' => 'iconfont icon-shuzi',
        'tag' => '热门文章',
        'colorClass' => 'dgtle-icon',
        'url'  => 'https://www.dgtle.com/'
    ],
    [
        'id' => 'smzdm',
        'name' => '什么值得买',
        'icon' => 'iconfont icon-zhidemai-active',
        'tag' => '今日热门',
        'colorClass' => 'smzdm-icon',
        'url'  => 'https://www.smzdm.com/'
    ],
    [
        'id' => 'weatheralarm',
        'name' => '中央气象台',
        'icon' => 'iconfont icon-qixiang',
        'tag' => '气象预警',
        'colorClass' => 'weatheralarm-icon',
        'url'  => 'http://www.nmc.cn/'
    ],
    [
        'id' => 'yuanshen',
        'name' => '原神',
        'icon' => 'iconfont icon-anemo',
        'tag' => '公告',
        'colorClass' => 'yuanshen-icon',
        'url'  => 'https://www.miyoushe.com/ys/'
    ],
    [
        'id' => 'lol',
        'name' => '英雄联盟',
        'icon' => 'iconfont icon-lol',
        'tag' => '综合',
        'colorClass' => 'lol-icon',
        'url'  => 'https://lol.qq.com/news/index.shtml'
    ],
    [
        'id' => 'csdn',
        'name' => 'CSDN',
        'icon' => 'iconfont icon-csdn',
        'tag' => '博客热榜',
        'colorClass' => 'csdn-icon',
        'url'  => 'https://blog.csdn.net/'
    ],

    // ========================================================
    // 被注释的平台（需特殊条件：登录/cookie/国内无法直连 等）
    // 取消注释即可恢复，注意同时确认 V1/V2 目录有对应 handler
    // ========================================================
	/*
	[
        'id' => 'javbus',
        'name' => '老司机',
        'icon' => 'iconfont icon-youeryuan',
        'tag' => '热门',
        'colorClass' => 'javbus-icon',
        'url'  => 'https://www.dmmsee.ink/forum/',
    ],
	*/
	/*
	[暂时弃用] 懂球帝 API 被腾讯云 EdgeOne 安全防护拦截 (HTTP 567)，等待对方恢复公开访问后再启用
    [
        'id' => 'dongqiudi',
        'name' => '懂球帝',
        'icon' => 'iconfont icon-zuqiu',
        'tag' => '足球',
        'colorClass' => 'dongqiudi-icon',
        'url'  => 'https://dongqiudi.com/'
    ],
	*/
	/* 国内无法直连
    [
        'id' => 'v2ex',
        'name' => 'V2EX',
        'icon' => 'iconfont icon-vex',
        'tag' => '最热',
        'colorClass' => 'v2ex-icon',
        'url'  => 'https://www.v2ex.com/'
    ],
    [
        'id' => 'nytimes',
        'name' => '纽约时报中文',
        'icon' => 'iconfont icon-niuyueshibao',
        'tag' => '头条',
        'colorClass' => 'nytimes-icon',
        'url'  => 'https://cn.nytimes.com/'
    ],
	[
        'id' => 'linuxdo',
        'name' => 'LinuxDo',
        'icon' => 'iconfont icon-linuxdo',
        'tag' => '热门',
        'colorClass' => 'linuxdo-icon',
        'url'  => 'https://linux.do/'
    ],
	*/
];


// ============================================================
// API 基础路径（一般不用改）
// ============================================================
$apiBaseUrl = '/api/index.php/';


// ============================================================
// 统一放到 $GLOBALS 里，其他文件通过 $GLOBALS['xxx'] 读取
// ============================================================
// 新手提示：为什么要放 $GLOBALS 里？
//   因为 PHP 函数内部不能直接访问外部变量，
//   放到 $GLOBALS 里后，任何地方都能读到，不用每次都 global $var。
// ============================================================
$GLOBALS['site_config'] = $site_config;
$GLOBALS['sources']     = $sources;
$GLOBALS['apiBaseUrl']  = $apiBaseUrl;

// 兼容旧代码（以前的代码可能直接用这些变量名）
$GLOBALS['cache_dir']    = $site_config['cache_dir'];
$GLOBALS['archive_dir']  = $site_config['archive_dir'];
$GLOBALS['cache_ttl']    = $site_config['cache_ttl'];
$GLOBALS['apiLimit']     = $site_config['api_limit'];
$GLOBALS['displayLimit'] = $site_config['display_limit'];
?>
