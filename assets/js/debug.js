/**
 * Debug 调试面板 JS
 * 从 #debugBootstrap 读 PHP 注入的平台数据（含缓存状态）
 * 首开只展示缓存，点按钮才真正 curl 测试
 */

(function() {
    'use strict';

    var API_BASE = '/api/index.php/';
    var bootstrap = document.getElementById('debugBootstrap');
    if (!bootstrap) { console.error('debugBootstrap 未找到'); return; }

    var platforms = [];
    try {
        platforms = JSON.parse(bootstrap.dataset.platforms || '[]');
    } catch (e) { console.error('平台数据解析失败:', e); return; }

    var selectedId = null;
    var testCacheTtl = parseInt(bootstrap.dataset.cacheTtl) || 43200;

    // ========== 渲染左侧列表 ==========
    var listEl = document.getElementById('debugList');
    var searchEl = document.getElementById('debugSearch');

    function renderList(filter) {
        filter = (filter || '').toLowerCase();
        listEl.innerHTML = '';
        platforms.forEach(function(p) {
            if (filter && p.id.indexOf(filter) < 0 && p.name.toLowerCase().indexOf(filter) < 0) return;

            var statusClass = 'none';
            if (p.cache) {
                if (p.cache.age < testCacheTtl) statusClass = 'cache';
                else statusClass = 'expired';
            }

            var item = document.createElement('div');
            item.className = 'debug-item' + (selectedId === p.id ? ' active' : '');
            item.dataset.id = p.id;
            item.innerHTML =
                '<span class="iconfont ' + escapeAttr(p.icon) + ' platform-icon ' + escapeAttr(p.color) + ' debug-item-icon"></span>' +
                '<div class="debug-item-info">' +
                    '<div class="debug-item-name">' + escapeHtml(p.name) + '</div>' +
                    '<div class="debug-item-id">' + escapeHtml(p.id) + '</div>' +
                '</div>' +
                '<span class="debug-item-status ' + statusClass + '"></span>';
            item.addEventListener('click', function() { selectPlatform(p.id); });
            listEl.appendChild(item);
        });
    }

    searchEl.addEventListener('input', function() { renderList(searchEl.value); });
    renderList('');

    // ========== 选中平台 ==========
    function selectPlatform(id) {
        selectedId = id;
        renderList(searchEl.value);  // 更新 active 态

        var emptyEl = document.getElementById('debugEmpty');
        var detailEl = document.getElementById('debugDetail');
        var p = platforms.find(function(x) { return x.id === id; });

        if (!p) { emptyEl.style.display = ''; detailEl.style.display = 'none'; return; }

        emptyEl.style.display = 'none';
        detailEl.style.display = '';

        var cacheHtml = renderCacheHtml(p);

        detailEl.innerHTML =
            '<div class="debug-detail-head">' +
                '<span class="iconfont ' + escapeAttr(p.icon) + ' platform-icon ' + escapeAttr(p.color) + ' debug-detail-icon"></span>' +
                '<div class="debug-detail-title">' +
                    '<div class="debug-detail-name">' + escapeHtml(p.name) + '</div>' +
                    '<div class="debug-detail-id">/api/' + escapeHtml(p.id) + '</div>' +
                '</div>' +
                '<a href="' + escapeAttr(p.url) + '" target="_blank" rel="noopener noreferrer" class="debug-link" style="font-size:12px">原始榜单 ↗</a>' +
            '</div>' +
            cacheHtml +
            '<div class="debug-actions">' +
                '<button class="debug-btn debug-btn-primary" id="btnTest">▶ 测试（强制刷新）</button>' +
                '<button class="debug-btn" id="btnTestNormal">测试（走缓存）</button>' +
                '<button class="debug-btn" id="btnOpenDetail">打开详情页 ↗</button>' +
            '</div>' +
            '<div class="debug-result" id="debugResult">等待测试...<br><span style="color:#bbb;font-size:12px">点击上方按钮开始实时 curl 测试</span></div>';

        document.getElementById('btnTest').addEventListener('click', function() { runTest(id, true); });
        document.getElementById('btnTestNormal').addEventListener('click', function() { runTest(id, false); });
        document.getElementById('btnOpenDetail').addEventListener('click', function() { window.open('/' + id, '_blank'); });
    }

    function renderCacheHtml(p) {
        var cacheCard = document.createElement('div');
        cacheCard.className = 'debug-cache-card' + (p.cache ? ' has-data' : '');

        if (p.cache) {
            var cacheAgeText = formatAge(p.cache.age);
            var cacheSizeText = formatSize(p.cache.size);
            var cacheTimeText = new Date(p.cache.mtime * 1000).toLocaleString('zh-CN', { hour12: false });
            var isExpired = p.cache.age >= testCacheTtl;

            return '<div class="debug-cache-card">' +
                '<div><div class="debug-cache-item-label">缓存文件</div><div class="debug-cache-item-val">' + escapeHtml(p.id) + '.json</div></div>' +
                '<div><div class="debug-cache-item-label">大小</div><div class="debug-cache-item-val">' + cacheSizeText + '</div></div>' +
                '<div><div class="debug-cache-item-label">最后更新</div><div class="debug-cache-item-val">' + cacheTimeText + '</div></div>' +
                '<div><div class="debug-cache-item-label">状态</div><div class="debug-cache-item-val ' + (isExpired ? 'muted' : '') + '">' + cacheAgeText + (isExpired ? '（已过期）' : '（有效）') + '</div></div>' +
            '</div>';
        } else {
            return '<div class="debug-cache-card">' +
                '<div><div class="debug-cache-item-label">缓存文件</div><div class="debug-cache-item-val muted">今日无</div></div>' +
                '<div><div class="debug-cache-item-label">大小</div><div class="debug-cache-item-val muted">—</div></div>' +
                '<div><div class="debug-cache-item-label">最后更新</div><div class="debug-cache-item-val muted">—</div></div>' +
                '<div><div class="debug-cache-item-label">状态</div><div class="debug-cache-item-val muted">需测试抓取</div></div>' +
            '</div>';
        }
    }

    // ========== 测试 ==========
    function runTest(sourceId, force) {
        var resultEl = document.getElementById('debugResult');
        if (!resultEl) return;

        // 更新列表状态
        setListItemStatus(sourceId, 'testing');

        var limit = 10;
        resultEl.className = 'debug-result testing';
        resultEl.textContent = '请求中...';

        var startTime = performance.now();
        var url = API_BASE + sourceId + '?limit=' + limit + (force ? '&force=1' : '');

        fetch(url)
            .then(function(resp) {
                var elapsed = ((performance.now() - startTime) / 1000).toFixed(2);
                var httpCode = resp.status;
                return resp.text().then(function(text) {
                    return { text: text, httpCode: httpCode, elapsed: elapsed, ok: resp.ok };
                });
            })
            .then(function(info) {
                // 去除 BOM
                var cleanText = info.text.replace(/^\uFEFF/, '');
                var data = null, success = false, message = '';
                try {
                    data = JSON.parse(cleanText);
                    success = !!data.success;
                    message = data.message || '';
                } catch (e) {
                    success = false;
                    message = '(非 JSON 响应) ' + info.text.substring(0, 200);
                }

                resultEl.className = 'debug-result ' + (success ? 'pass' : 'fail');
                setListItemStatus(sourceId, success ? 'pass' : 'fail');

                var html = '';
                html += '<b>' + (success ? '✓ 成功' : '✗ 失败') + '</b>';
                html += ' · ' + info.elapsed + 's';
                html += ' · HTTP ' + info.httpCode;
                html += ' · ' + (force ? '强制刷新' : '正常请求');

                if (success && data) {
                    var count = 0;
                    if (data.data && Array.isArray(data.data)) count = data.data.length;
                    else if (data.data && data.data.data && Array.isArray(data.data.data)) count = data.data.data.length;
                    html += '<br>' + count + ' 条数据';
                    if (message) html += ' · ' + escapeHtml(message);

                    var items = (data.data && Array.isArray(data.data) && data.data.slice(0, 3)) ||
                                (data.data && data.data.data && Array.isArray(data.data.data) && data.data.data.slice(0, 3)) || [];
                    if (items.length > 0) {
                        html += '<pre>' + escapeHtml(JSON.stringify(items, null, 2)) + '</pre>';
                        if (count > 3) html += '<div style="color:#fa8c16;font-size:11px;margin-top:4px">... 只显示前 3 条，共 ' + count + ' 条</div>';
                    }
                } else {
                    if (message) html += '<br>' + escapeHtml(message);
                    html += '<pre>' + escapeHtml(info.text.substring(0, 800)) + '</pre>';
                    if (info.text.length > 800) html += '<div style="color:#fa8c16;font-size:11px;margin-top:4px">... 响应已截断 (' + info.text.length + ' 字符)</div>';
                }

                resultEl.innerHTML = html;
            })
            .catch(function(err) {
                var elapsed = ((performance.now() - startTime) / 1000).toFixed(2);
                resultEl.className = 'debug-result fail';
                setListItemStatus(sourceId, 'fail');
                resultEl.innerHTML =
                    '<b>✗ 网络错误</b> · ' + elapsed + 's' +
                    '<pre>' + escapeHtml(String(err)) + '</pre>';
            });
    }

    function setListItemStatus(id, status) {
        var item = document.querySelector('.debug-item[data-id="' + id + '"] .debug-item-status');
        if (item) {
            item.className = 'debug-item-status ' + status;
        }
    }

    // ========== 批量 ==========
    document.getElementById('btnRunAll').addEventListener('click', runAllTests);
    document.getElementById('btnClearAll').addEventListener('click', function() {
        alert('清缓存请用 phpStudy 面板清页面缓存，或手动删 cache/ 目录');
    });

    async function runAllTests() {
        var ids = platforms.map(function(p) { return p.id; });
        // 串行 3 个
        var workers = Math.min(3, ids.length);
        var idx = 0;
        var worker = async function() {
            while (idx < ids.length) {
                var current = idx++;
                if (selectedId === ids[current]) {
                    runTest(ids[current], true);
                } else {
                    setListItemStatus(ids[current], 'testing');
                    await fetch(API_BASE + ids[current] + '?limit=10&force=1')
                        .then(function(r) { return r.text(); })
                        .then(function() { setListItemStatus(ids[current], 'pass'); })
                        .catch(function() { setListItemStatus(ids[current], 'fail'); });
                }
                await new Promise(function(r) { setTimeout(r, 50); });
            }
        };
        await Promise.all(Array.from({ length: workers }, function() { return worker(); }));
    }

    // ========== 工具 ==========
    function escapeHtml(str) {
        if (!str) return '';
        var div = document.createElement('div');
        div.textContent = String(str);
        return div.innerHTML;
    }
    function escapeAttr(str) {
        if (!str) return '';
        return String(str).replace(/"/g, '&quot;').replace(/'/g, '&#39;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }
    function formatAge(sec) {
        if (sec < 60) return sec + ' 秒前';
        if (sec < 3600) return Math.floor(sec / 60) + ' 分钟前';
        if (sec < 86400) return Math.floor(sec / 3600) + ' 小时前';
        return Math.floor(sec / 86400) + ' 天前';
    }
    function formatSize(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / 1048576).toFixed(2) + ' MB';
    }
})();
