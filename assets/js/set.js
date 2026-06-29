/**
 * 设置页面 - 交互脚本
 *
 * 依赖：
 * - toast.js（showToast 函数）
 * - header.js（夜间模式切换）
 */

(function () {
    'use strict';

    // ============== 工具函数 ==============

    function loadSettings() {
        var displayLimit = localStorage.getItem('displayLimit') || '10';
        var themeMode = localStorage.getItem('themeMode') || 'light';
        var cacheTime = localStorage.getItem('cacheTime') || '6';

        document.getElementById('displayLimit').value = displayLimit;
        document.getElementById('themeMode').value = themeMode;
        document.getElementById('cacheTime').value = cacheTime;
    }

    function saveSettings() {
        var displayLimit = document.getElementById('displayLimit').value;
        var themeMode = document.getElementById('themeMode').value;
        var cacheTime = document.getElementById('cacheTime').value;

        localStorage.setItem('displayLimit', displayLimit);
        localStorage.setItem('themeMode', themeMode);
        localStorage.setItem('cacheTime', cacheTime);

        if (typeof showToast === 'function') {
            showToast('设置已保存', 'success');
        }
    }

    function clearAllCache() {
        fetch('../core/refresh.php?action=clear_all')
            .then(function (res) { return res.json(); })
            .then(function (json) {
                if (json.success) {
                    if (typeof showToast === 'function') {
                        showToast('缓存已清除', 'success');
                    }
                } else {
                    if (typeof showToast === 'function') {
                        showToast('清除失败: ' + json.message, 'error');
                    }
                }
            })
            .catch(function (e) {
                if (typeof showToast === 'function') {
                    showToast('清除失败: ' + e.message, 'error');
                }
            });
    }

    // ============== 初始化 ==============

    document.addEventListener('DOMContentLoaded', function () {
        loadSettings();

        document.getElementById('displayLimit').addEventListener('change', saveSettings);

        document.getElementById('themeMode').addEventListener('change', function () {
            saveSettings();
            var mode = this.value;
            if (mode !== 'auto') {
                document.body.setAttribute('data-theme', mode);
                localStorage.setItem('theme', mode);
                var themeToggle = document.getElementById('themeToggle');
                if (themeToggle) {
                    var iconSpan = themeToggle.querySelector('.iconfont');
                    if (iconSpan) {
                        iconSpan.className = 'iconfont ' + (mode === 'dark' ? 'icon-rijian' : 'icon-yejian');
                    }
                }
            }
        });

        document.getElementById('cacheTime').addEventListener('change', saveSettings);

        var clearBtn = document.querySelector('.setting-item .btn');
        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                clearAllCache();
            });
        }
    });
})();
