<?php
/**
 * 兜底保护：防止 API 工具类被直接访问
 * 正常情况下被 Nginx deny all 拦截
 */
http_response_code(403);
exit('Access denied');
