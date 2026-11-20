<?php
/**
 * 兜底保护：防止 .htaccess 或 Nginx 配置失效时被直接访问 core/ 内部文件
 * 注意：refresh.php 和 clear_cache.php 在 Nginx 白名单里放行，
 * 但它们自身有 token/IP 限流保护，不会被恶意调用
 */
http_response_code(403);
exit('Access denied');
