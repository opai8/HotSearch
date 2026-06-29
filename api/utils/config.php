<?php
/**
 * API 配置文件
 *
 * 新增平台步骤:
 *   1. 在 /api/V1/ 目录下新建 {platform}.php 文件
 *      类名为 {Platform}HotSearch，必须包含 handle($limit) 方法
 *   2. 在下方 platforms 数组中注册平台名（小写，与文件名一致）
 *   3. 在项目根目录 config.php 的 $sources 数组中注册平台显示信息
 *   4. 通过 /api/index.php/{platform}?limit=N 访问
 */

return array(
    'platforms' => array('baidu', 'bilibili', 'weibo', 'zhihu', 'zhihu_daily', 'hupu', 'douyin', 'tieba', 'yystv', 'toutiao', 'thepaper', 'sspai', 'history', 'weread', 'v2ex', 'nytimes', 'ifanr', 'github', 'hellogithub', 'guokr', 'huxiu', 'juejin', 'pojie52', '36kr', '51cto', 'wangyi', 'acfun', 'yuanshen', 'linuxdo', 'lol', 'javbus'),

    'default_limit' => 50,
    'max_limit' => 100,
    'min_limit' => 1,

    'timezone' => 'Asia/Shanghai',
    'error_reporting' => 0,

    'copyright' => '今日热榜',

    'cors' => array(
        'allow_origin' => '*',
        'allow_methods' => 'GET, POST, OPTIONS',
        'allow_headers' => 'Content-Type, Authorization, X-Requested-With',
        'max_age' => 86400
    )
);
?>
