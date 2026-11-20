/**
 * ============================================================
 * 设置页面独立脚本
 * ============================================================
 * 负责：读取/保存 localStorage、填充控件、应用主题、恢复默认
 * 不依赖全局 script.js（showToast 用 header.js 的）
 */

(function() {
    'use strict';

    // 优先用 PHP 注入的权威默认值，config.php 是单一事实来源
    var PHP_DEF = typeof PHP_CONFIG_DEFAULTS !== 'undefined' ? PHP_CONFIG_DEFAULTS : {};

    // 默认值：config.php 已经通过 header.php 注入为 PHP_CONFIG_DEFAULTS，
    // theme 例外——header.php 顶部已经输出了 defaultTheme 变量
    var DEFAULTS = {
        theme:                  (typeof defaultTheme !== 'undefined' ? defaultTheme : 'light'),
        columns_per_row:        PHP_DEF.columns_per_row        || 'auto',
        display_limit:          PHP_DEF.display_limit          || 10,
        link_open_new_window:   PHP_DEF.link_open_new_window   || '_blank',
        show_heat:              PHP_DEF.show_heat             !== undefined ? PHP_DEF.show_heat             : true,
        show_source:            PHP_DEF.show_source           !== undefined ? PHP_DEF.show_source           : true,
        show_refresh:           PHP_DEF.show_refresh          !== undefined ? PHP_DEF.show_refresh          : true
    };

    var KEY = 'hotcc_settings';

    // ---- 存储封装 ----
    function getSettings() {
        try {
            var raw = localStorage.getItem(KEY);
            if (raw) {
                var saved = JSON.parse(raw);
                for (var k in DEFAULTS) {
                    if (!(k in saved)) saved[k] = DEFAULTS[k];
                }
                return saved;
            }
        } catch(e) {}
        return JSON.parse(JSON.stringify(DEFAULTS));
    }

    function saveSettings(s) {
        localStorage.setItem(KEY, JSON.stringify(s));
    }

    // ---- 填充控件 ----
    function render() {
        var s = getSettings();

        var themeEl = document.getElementById('set-theme');
        if (themeEl) themeEl.value = s.theme;

        var colsEl = document.getElementById('set-columns');
        if (colsEl) colsEl.value = s.columns_per_row;

        var limitEl = document.getElementById('set-display-limit');
        if (limitEl) limitEl.value = String(s.display_limit);

        var targetEl = document.getElementById('set-link-target');
        if (targetEl) targetEl.value = s.link_open_new_window;

        var heatSw = document.getElementById('set-show-heat');
        if (heatSw) heatSw.classList.toggle('on', !!s.show_heat);

        var srcSw = document.getElementById('set-show-source');
        if (srcSw) srcSw.classList.toggle('on', !!s.show_source);

        var refSw = document.getElementById('set-show-refresh');
        if (refSw) refSw.classList.toggle('on', !!s.show_refresh);
    }

    // ---- 读控件值组装 settings ----
    function collect() {
        return {
            theme: (document.getElementById('set-theme') || {}).value || 'light',
            columns_per_row: (document.getElementById('set-columns') || {}).value || 'auto',
            display_limit: parseInt((document.getElementById('set-display-limit') || {}).value || '10', 10),
            link_open_new_window: (document.getElementById('set-link-target') || {}).value || '_blank',
            show_heat: !!document.getElementById('set-show-heat').classList.contains('on'),
            show_source: !!document.getElementById('set-show-source').classList.contains('on'),
            show_refresh: !!document.getElementById('set-show-refresh').classList.contains('on')
        };
    }

    // ---- 立即应用主题到当前页面 ----
    function applyTheme(theme) {
        var effective = theme;
        if (theme === 'auto') {
            var dark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            effective = dark ? 'dark' : 'light';
        }
        document.documentElement.setAttribute('data-theme', effective);
        document.body.setAttribute('data-theme', effective);
    }

    // ---- 绑定事件 ----
    function bind() {
        // 开关点击
        document.querySelectorAll('.settings-switch').forEach(function(sw) {
            sw.addEventListener('click', function() {
                sw.classList.toggle('on');
            });
        });

        // 保存
        var saveBtn = document.getElementById('btn-save');
        if (saveBtn) {
            saveBtn.addEventListener('click', function() {
                var s = collect();
                saveSettings(s);
                applyTheme(s.theme);
                if (typeof showToast === 'function') {
                    showToast('✓ 设置已保存', 'success');
                } else {
                    alert('设置已保存');
                }
            });
        }

        // 恢复默认
        var resetBtn = document.getElementById('btn-reset');
        if (resetBtn) {
            resetBtn.addEventListener('click', function() {
                if (!confirm('确定恢复所有默认设置吗？')) return;
                saveSettings(JSON.parse(JSON.stringify(DEFAULTS)));
                render();
                applyTheme('light');
                if (typeof showToast === 'function') {
                    showToast('已恢复默认', 'info');
                }
            });
        }

        // 主题选择器实时预览（选了就变）
        var themeSel = document.getElementById('set-theme');
        if (themeSel) {
            themeSel.addEventListener('change', function() {
                applyTheme(this.value);
            });
        }
    }

    // ---- 启动 ----
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() { render(); bind(); });
    } else {
        render();
        bind();
    }
})();
