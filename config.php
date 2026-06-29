<?php
/**
 * ============================================================
 * 全局配置文件（首页唯一配置入口）
 * ============================================================
 *
 * 新手阅读建议：
 *   1. 先看第 1 部分「站点基本信息」，改网站名/描述/Logo 在这里
 *   2. 再看第 2 部分「显示配置」，改布局/卡片高度/显示条数在这里
 *   3. 第 3 部分「功能开关」，控制各种按钮和组件显示隐藏
 *   4. 第 4 部分「缓存配置」，改缓存时间在这里
 *   5. 第 5 部分「SEO 优化」，改网站描述/关键词/域名在这里
 *   6. 第 6 部分「平台列表」，增减/排序平台在这里
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
    'site_logo'        => '/assets/images/favicon.png',  // Logo 图片路径
    'site_favicon'     => '/assets/images/favicon.png',  // 浏览器标签页小图标


    // ========================================================
    // 2. 显示配置（布局和样式相关）
    // ========================================================
    // 改卡片高度、每行几个、默认显示条数等在这里
    // ========================================================
    'columns_per_row'  => 'auto',    // 每行显示几个卡片：'auto'=自适应，数字=固定数量(4、 5、 6)
    'card_min_width'   => 280,       // 卡片最小宽度（px，仅自适应模式生效）
    'card_height'      => 400,       // 卡片高度（px，桌面端）
    'content_max_width' => 1600,     // 内容区最大宽度（px）
    'display_limit'    => 10,        // 默认显示条数（折叠状态）
    'api_limit'        => 100,       // API 拉取最大条数
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


    // ========================================================
    // 4. 缓存配置
    // ========================================================
    // 改缓存时间、缓存目录在这里
    // ========================================================
    'cache_dir'        => __DIR__ . '/cache',  // 缓存目录路径
    'cache_ttl'        => 6 * 3600,            // 缓存有效期（秒），默认 6 小时


    // ========================================================
    // 5. SEO 优化配置
    // ========================================================
    // 改网站 SEO 相关配置在这里
    // ========================================================
    'seo_description'  => '聚合百度、微博、知乎、B站、抖音等多平台实时热搜榜单，一站看完全网热点。每日更新，免费使用。',
    'seo_keywords'     => '热搜榜,微博热搜,百度热搜,知乎热榜,B站热搜,抖音热搜,热点聚合,今日热榜,热点榜单,全网热点',
    'seo_domain'       => 'https://hot.cc',   // 网站域名（末尾不要加斜杠）
    'seo_type'         => 'website',          // 网站类型：website/blog/news
    'seo_og_image'     => '/assets/images/og-image.jpg',  // 社交分享预览图（建议 1200x630）
];


// ============================================================
// 热搜平台配置列表
// ============================================================
// 增减平台、调整顺序、改名称/图标在这里
//
// 每个平台的字段说明：
//   - id         : 平台唯一标识，对应 api/V1/ 下的文件名
//   - name       : 显示在卡片上的名称
//   - icon       : iconfont 图标类名
//   - tag        : 标签文字（热搜/热榜/热帖 等）
//   - colorClass : CSS 颜色类名，定义在 assets/css/icon-color.css
//   - url        : 数据来源链接（卡片上的"数据来源"按钮）
//
// 想隐藏某个平台？直接在它前面加 // 注释掉就行
// 想调整顺序？直接把数组项上下移动
// ============================================================

$sources = [
    [
        'id' => 'baidu',
        'name' => '百度热搜',
        'icon' => 'iconfont icon-baidu',
        'tag' => '热搜',
        'colorClass' => 'baidu-icon',
        'url'  => 'https://top.baidu.com/board?tab=realtime'
    ],
    [
        'id' => 'bilibili',
        'name' => 'B站热搜',
        'icon' => 'iconfont icon-bilibili',
        'tag' => '热搜',
        'colorClass' => 'bilibili-icon',
        'url'  => 'https://www.bilibili.com/v/popular/rank/all'
    ],
    [
        'id' => 'weibo',
        'name' => '微博热搜',
        'icon' => 'iconfont icon-weibo',
        'tag' => '热搜',
        'colorClass' => 'weibo-icon',
        'url'  => 'https://s.weibo.com/top/summary'
    ],
    [
        'id' => 'zhihu',
        'name' => '知乎热榜',
        'icon' => 'iconfont icon-zhihu',
        'tag' => '热榜',
        'colorClass' => 'zhihu-icon',
        'url'  => 'https://www.zhihu.com/hot'
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
        'id' => 'douyin',
        'name' => '抖音热搜',
        'icon' => 'iconfont icon-douyin',
        'tag' => '热榜',
        'colorClass' => 'douyin-icon',
        'url'  => 'https://www.douyin.com/hot'
    ],
    [
        'id' => 'tieba',
        'name' => '百度贴吧',
        'icon' => 'iconfont icon-tieba',
        'tag' => '热议',
        'colorClass' => 'tieba-icon',
        'url'  => 'https://tieba.baidu.com/hottopic/browse/topicList'
    ],
	/* 需登录获取cookie
	[
        'id' => 'javbus',
        'name' => '老司机',
        'icon' => 'iconfont icon-youeryuan',
        'tag' => '热门',
        'colorClass' => 'javbus-icon',
        'url'  => 'https://www.dmmsee.ink/forum/',
    ],
	*/
    [
        'id' => 'yystv',
        'name' => '游研社',
        'icon' => 'iconfont icon-youxi',
        'tag' => '热文',
        'colorClass' => 'yystv-icon',
        'url'  => 'https://www.yystv.cn/'
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
        'id' => 'sspai',
        'name' => '少数派',
        'icon' => 'iconfont icon-sspai',
        'tag' => '热门',
        'colorClass' => 'sspai-icon',
        'url'  => 'https://sspai.com/'
    ],
    [
        'id' => 'history',
        'name' => '历史上的今天',
        'icon' => 'iconfont icon-lishijintian',
        'tag' => '历史',
        'colorClass' => 'history-icon',
        'url'  => 'https://baike.baidu.com/'
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
        'id' => 'weread',
        'name' => '微信读书',
        'icon' => 'iconfont icon-weixindushuchaotuoyuan',
        'tag' => '热搜',
        'colorClass' => 'weread-icon',
        'url'  => 'https://weread.qq.com/'
    ],
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
    [
        'id' => 'ifanr',
        'name' => '爱范儿',
        'icon' => 'iconfont icon-ifanr',
        'tag' => '热文',
        'colorClass' => 'ifanr-icon',
        'url'  => 'https://www.ifanr.com/'
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
        'id' => '36kr',
        'name' => '36氪',
        'icon' => 'iconfont icon-a-36kr',
        'tag' => '热榜',
        'colorClass' => 'kr36-icon',
        'url'  => 'https://36kr.com/'
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
        'id' => 'wangyi',
        'name' => '网易新闻',
        'icon' => 'iconfont icon-wangyixinwen',
        'tag' => '热榜',
        'colorClass' => 'wangyi-icon',
        'url'  => 'https://news.163.com/'
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
    ]
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
$GLOBALS['cache_ttl']    = $site_config['cache_ttl'];
$GLOBALS['apiLimit']     = $site_config['api_limit'];
$GLOBALS['displayLimit'] = $site_config['display_limit'];
?>
