<?php
/**
 * API 配置文件
 *
 * 新增平台步骤:
 *   1. 在 /api/V1/ 目录下新建 {platform}.php 文件
 *      例如: /api/V1/hupu.php，类名为 HupuHotSearch，必须包含 handle($limit) 方法
 *   2. 在下方 platforms 数组中注册平台名（小写，与文件名一致）
 *   3. 在项目根目录 config.php 的 $sources 数组中注册平台显示信息
 *   4. 完成后通过 /api/index.php/{platform}?limit=N 访问
 *
 * 注意: ?platform=xxx 方式已废弃，仅支持路径方式。
 */

return array(
    // 支持的平台列表 (添加新平台需要在这里注册; 小写, 与 V1/ 目录下的文件名一致)
    'platforms' => array('baidu', 'bilibili', 'weibo', 'zhihu', 'zhihu_daily', 'hupu', 'douyin', 'tieba', 'yystv', 'toutiao', 'thepaper', 'sspai', 'history', 'weread', 'v2ex', 'nytimes', 'ifanr', 'github', 'hellogithub', 'guokr', 'huxiu', 'juejin', 'pojie52', '36kr', '51cto', 'wangyi', 'acfun', 'yuanshen', 'linuxdo', 'lol', 'javbus'),

    // 默认返回条数
    'default_limit' => 50,

    // 最大返回条数
    'max_limit' => 100,

    // 最小返回条数
    'min_limit' => 1,

    // 时区设置
    'timezone' => 'Asia/Shanghai',

    // 错误报告级别 (生产环境设为0, 开发环境可设为E_ALL)
    'error_reporting' => 0,

    // 版权信息
    'copyright' => '博客 www.test.com',

    // CORS设置 (跨域资源共享)
    'cors' => array(
        'allow_origin' => '*',
        'allow_methods' => 'GET, POST, OPTIONS',
        'allow_headers' => 'Content-Type, Authorization, X-Requested-With',
        'max_age' => 86400
    )
);
?>