/**
 * 头部导航按钮功能
 * 
 * 功能：
 * 1. 刷新按钮 - 清除所有缓存，重新获取所有模块数据
 * 2. 夜间模式按钮 - 切换深色/浅色主题
 * 3. 设置按钮 - 预留
 * 
 * 依赖：
 * - script.js（必须在 header.js 之前加载）
 * - showToast 函数由 script.js 提供
 */

// ========================================
// 夜间模式切换
// ========================================
(function() {
    var themeToggle = document.getElementById('themeToggle');
    if (!themeToggle) return;

    // 从 localStorage 读取保存的主题
    var savedTheme = localStorage.getItem('theme') || 'light';
    setTheme(savedTheme);

    themeToggle.addEventListener('click', function() {
        var currentTheme = document.body.getAttribute('data-theme') || 'light';
        var newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        setTheme(newTheme);
        localStorage.setItem('theme', newTheme);
    });

    function setTheme(theme) {
        // 临时禁用 transition，避免主题切换时颜色过渡产生视觉恍惚
        document.body.classList.add('no-theme-transition');

        document.body.setAttribute('data-theme', theme);
        
        // 同时同步 html 元素的 data-theme（header.php 里也设了它）
        document.documentElement.setAttribute('data-theme', theme);

        // 切换图标
        var iconSpan = themeToggle.querySelector('.iconfont');
        if (iconSpan) {
            if (theme === 'dark') {
                iconSpan.className = 'iconfont icon-rijian';
                themeToggle.setAttribute('title', '日间模式');
            } else {
                iconSpan.className = 'iconfont icon-yejian';
                themeToggle.setAttribute('title', '夜间模式');
            }
        }

        // 下一帧移除禁用类，让 transition 恢复正常（仅主题切换瞬间跳过）
        requestAnimationFrame(function() {
            requestAnimationFrame(function() {
                document.body.classList.remove('no-theme-transition');
            });
        });
    }
})();

// ========================================
// 刷新按钮 - 清缓存 + 强制重爬 + 刷新页面
// 防刷三层：
//   第 1 层 前端防抖 → 点击后 30 秒内按钮 disabled
//   第 2 层 后端冷却 → clear_cache.php 全局 30 秒 + IP 每分钟 3 次
//   第 3 层 Nginx/.htaccess 白名单 → core/ 下仅 refresh.php + clear_cache.php 可外部访问
// ========================================
(function() {
    var refreshBtn = document.getElementById('headerRefreshBtn');
    if (!refreshBtn) return;

    var COOLDOWN_MS = 15000; // 前端 15 秒防抖（和后端 clear_cache.php 冷却对齐）
    var cooldownTimer = null;

    refreshBtn.addEventListener('click', function() {
        // 前端防抖：冷却中直接提示
        if (cooldownTimer) {
            var remain = Math.ceil((parseInt(refreshBtn.getAttribute('data-cooldown-end') || '0') - Date.now()) / 1000);
            if (remain > 0 && typeof showToast === 'function') {
                showToast('清缓存冷却中，请 ' + remain + ' 秒后再点', 'info');
            }
            return;
        }

        // 立即禁用按钮 + 设冷却时间戳
        refreshBtn.disabled = true;
        refreshBtn.classList.add('is-cooling');
        var endTime = Date.now() + COOLDOWN_MS;
        refreshBtn.setAttribute('data-cooldown-end', endTime);

        if (typeof showToast === 'function') {
            showToast('正在清除缓存并重新爬取数据...', 'info');
        }

        // 前端冷却结束后恢复按钮
        cooldownTimer = setTimeout(function() {
            refreshBtn.disabled = false;
            refreshBtn.classList.remove('is-cooling');
            refreshBtn.removeAttribute('data-cooldown-end');
            cooldownTimer = null;
        }, COOLDOWN_MS);

        fetch('/core/clear_cache.php?t=' + Date.now(), {
            method: 'GET'
        })
        .then(function(response) {
            return response.json();
        })
        .then(function(data) {
            if (typeof showToast === 'function') {
                var msg = '页面缓存已清除';
                if (data.page_cache_deleted > 0) msg += '（' + data.page_cache_deleted + ' 个）';
                msg += '，正在重新加载...';
                showToast(msg, data.success ? 'success' : 'error');
            }
            setTimeout(function() {
                location.reload();
            }, 600);
        })
        .catch(function() {
            if (typeof showToast === 'function') {
                showToast('请求失败，直接刷新页面...', 'error');
            }
            setTimeout(function() {
                location.reload();
            }, 400);
        });
    });
})();
