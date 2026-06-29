<?php
/**
 * 热搜源配置
 *
 * 参数说明:
 * - id: API 接口名称，对应 /api/index.php/{id}
 * - name: 显示在页面上的名称
 * - icon: iconfont 图标类名
 * - tag: 标签文字
 * - colorClass: CSS 颜色类名
 * - url: 数据来源链接（用于卡片上的"📌 数据来源"按钮）
 */

// ========================================
// 缓存配置
// ========================================

// 缓存目录路径（当前在根目录）
$GLOBALS['cache_dir'] = __DIR__ . '/cache';

// 缓存有效期（秒），默认 6 小时
$GLOBALS['cache_ttl'] = 6 * 3600;

// ========================================
// 热搜平台配置
// ========================================

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
	/*
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
    ]
];

// ========================================
// API 数据获取配置
// ========================================

/**
 * 每个平台从 API 获取的默认条数
 */
$apiLimit = 100;

/**
 * 首页默认展示的热搜条数（超出部分通过按钮展开）
 */
$displayLimit = 10;

// API 接口基础路径（指向 api/index.php）
$apiBaseUrl = '/api/index.php/';

// 将变量设为全局，供其他文件使用
$GLOBALS['sources'] = $sources;
$GLOBALS['apiBaseUrl'] = $apiBaseUrl;
$GLOBALS['apiLimit'] = $apiLimit;
$GLOBALS['displayLimit'] = $displayLimit;
?>
