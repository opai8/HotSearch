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
        document.body.setAttribute('data-theme', theme);
        
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
    }
})();

// ========================================
// 刷新按钮 - 清除缓存并重新加载
// ========================================
(function() {
    // 查找所有 header-icon-btn 中第一个（刷新按钮）
    var headerBtns = document.querySelectorAll('.header-icon-btn');
    var refreshBtn = headerBtns[0]; // 第一个是刷新按钮
    
    if (refreshBtn) {
        refreshBtn.addEventListener('click', function() {
            // 提示正在刷新
            if (typeof showToast === 'function') {
                showToast('正在刷新所有数据...', 'info');
            }
            
            // 调用清除缓存接口
            fetch('core/refresh.php?action=clear_all', {
                method: 'GET'
            })
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                if (data.success) {
                    if (typeof showToast === 'function') {
                        showToast('缓存已清除，正在重新加载...', 'success');
                    }
                    // 刷新页面重新获取数据
                    setTimeout(function() {
                        location.reload();
                    }, 500);
                } else {
                    if (typeof showToast === 'function') {
                        showToast('清除缓存失败: ' + data.message, 'error');
                    }
                }
            })
            .catch(function(error) {
                console.error('清除缓存失败:', error);
                // 即使接口失败也尝试刷新
                if (typeof showToast === 'function') {
                    showToast('正在重新加载...', 'info');
                }
                setTimeout(function() {
                    location.reload();
                }, 300);
            });
        });
    }
})();
