/**
 * ============================================================
 * 热搜榜 - 首页交互脚本
 * ============================================================
 *
 * 依赖关系：
 *   - index.php 会在每个 .hot-card 上挂载 _allData（完整数据数组）
 *   - PHP 渲染页面时输出初始数据，JS 负责交互（展开/折叠、刷新等）
 *
 * 主要功能：
 *   1. 展开/折叠热搜列表（toggleExpand）
 *   2. 刷新单个平台数据（refreshCard，走 AJAX）
 *   3. 打开热搜详情链接（openHotLink）
 *   4. Toast 提示（showToast）
 *   5. 回到顶部按钮（DOMContentLoaded 里绑定）
 *
 * 新手阅读建议：
 *   - 先看最下面的初始化部分，知道页面加载后做了什么
 *   - 再看各个函数，每个函数上面都有注释说明用途
 *   - 看不懂的地方可以先放一放，结合效果去理解
 * ============================================================
 */


// ============================================================
// 配置区（新手：想改参数改这里就行）
// ============================================================
var API_LIMIT    = 100;   // 刷新时从 API 拉取的最大条数

// 优先用 PHP 注入的权威默认值，没有的话用硬编码兜底
var PHP_DEF = typeof PHP_CONFIG_DEFAULTS !== 'undefined' ? PHP_CONFIG_DEFAULTS : {};

// 从 localStorage 读用户设置
function getUserSetting(key, fallback) {
    try {
        var raw = localStorage.getItem('hotcc_settings');
        if (raw) {
            var s = JSON.parse(raw);
            if (s && key in s) return s[key];
        }
    } catch(e) {}
    return fallback;
}

// 兜底优先级：PHP_CONFIG_DEFAULTS > 旧硬编码值
var DISPLAY_LIMIT = getUserSetting('display_limit', PHP_DEF.display_limit || 10);
var USER_COLUMNS    = getUserSetting('columns_per_row', PHP_DEF.columns_per_row || 'auto');
var USER_SHOW_HEAT  = getUserSetting('show_heat', PHP_DEF.show_heat !== undefined ? PHP_DEF.show_heat : true);
var USER_SHOW_SOURCE = getUserSetting('show_source', PHP_DEF.show_source !== undefined ? PHP_DEF.show_source : true);
var USER_SHOW_REFRESH = getUserSetting('show_refresh', PHP_DEF.show_refresh !== undefined ? PHP_DEF.show_refresh : true);
var USER_LINK_TARGET = getUserSetting('link_open_new_window', PHP_DEF.link_open_new_window || '_blank');


// ============================================================
// 提前注入完整卡片数据（解决 _allData 注入时序在 applyUserSettings 之后的 race condition）
// header.php 已在 <head> 里输出 window.__HOTCC_CARD_DATA__，
// script.js 在 footer.php 引入，此时 body 里的 .hot-card DOM 已由 PHP 渲染完毕，
// 所以可以立即操作 DOM，不用等 DOMContentLoaded
// ============================================================
(function injectAllData() {
    var CD = window.__HOTCC_CARD_DATA__;
    if (!CD || typeof CD !== 'object') return;
    var cards = document.querySelectorAll('.hot-card');
    cards.forEach(function(card) {
        var sid = card.getAttribute('data-source');
        if (sid && CD[sid]) {
            card._allData = CD[sid];
        }
    });
})();


// ============================================================
// 工具函数区
// ============================================================

/**
 * HTML 转义（防止 XSS 攻击）
 *
 * 新手提示：为什么要转义？
 *   如果用户输入的内容里有 <script> 这类标签，直接插到页面上会执行恶意代码。
 *   转义就是把 < > " ' & 这些特殊字符变成 HTML 实体，让它们只显示不执行。
 *
 * @param {string} text - 要转义的文本
 * @return {string} 转义后的安全文本
 */
function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    return String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

/**
 * 格式化当前时间（年月日 时:分）
 *
 * 新手提示：padStart(2, '0') 的意思是不足两位时前面补 0，
 * 比如 9 点变成 "09"，5 分变成 "05"
 *
 * @return {string} 格式如 "2024-01-15 14:30"
 */
function formatNow() {
    var d = new Date();
    var y = d.getFullYear();
    var m = String(d.getMonth() + 1).padStart(2, '0');
    var day = String(d.getDate()).padStart(2, '0');
    var hh = String(d.getHours()).padStart(2, '0');
    var mm = String(d.getMinutes()).padStart(2, '0');
    return y + '-' + m + '-' + day + ' ' + hh + ':' + mm;
}

/**
 * 打开热搜详情链接（新窗口打开）
 *
 * 新手提示：容错处理 - 如果是空链接或者 '#'，就什么都不做，
 * 避免打开一个空白页面
 *
 * @param {string} url - 链接地址
 * @param {string} target - 打开方式（默认 _blank 新窗口）
 */
function openHotLink(url, target) {
    if (!url || url === '#') return;
    // target 未传时用全局 LINK_TARGET（由 PHP 从 config 注入）
    window.open(url, target || (typeof LINK_TARGET !== 'undefined' ? LINK_TARGET : '_blank'));
}


// ============================================================
// 渲染函数区（生成 HTML 字符串）
// ============================================================

/**
 * 渲染单条热搜为 HTML 字符串（li 元素）
 *
 * 结构：
 *   <li class="hot-item">
 *     <span class="rank">排名</span>
 *     <span class="hot-title">标题</span>
 *     <span class="hot-heat">热度</span>
 *   </li>
 *
 * @param {Object} item    数据对象 { title, url, hot }
 * @param {Number} index   从 0 开始的序号（用来决定排名样式）
 * @return {string} HTML 字符串
 */
function renderHotItem(item, index) {
    if (!item) return '';
    var title = item.title || '';
    var url   = item.url   || '#';
    var hot   = item.hot   || '';

    // 前三名特殊颜色 class（top1 红 / top2 橙 / top3 黄）
    var rankClass = '';
    if      (index === 0) rankClass = ' top1';
    else if (index === 1) rankClass = ' top2';
    else if (index === 2) rankClass = ' top3';

    // 热度值（用户设置关闭 or 没热度时就不显示）
    var showHeat = typeof USER_SHOW_HEAT !== 'undefined' ? USER_SHOW_HEAT : true;
    var hotHtml = (hot && showHeat) ? ('<span class="hot-heat"><span class="iconfont icon-xiaohuomiao" style="font-size:12px; margin-right:2px;"></span>' + escapeHtml(hot) + '</span>') : '';

    var linkTarget = (typeof USER_LINK_TARGET !== 'undefined') ? USER_LINK_TARGET : '_blank';
    return '<li class="hot-item" onclick="openHotLink(\'' + escapeHtml(url) + '\',\'' + linkTarget + '\')" data-tooltip="' + escapeHtml(title) + '">'
         +   '<span class="rank' + rankClass + '">' + (index + 1) + '</span>'
         +   '<span class="hot-title">' + escapeHtml(title) + '</span>'
         +   hotHtml
         + '</li>';
}

/**
 * 渲染"展开全部 / 已展开全部"控制条的 HTML
 *
 * 新手提示：两种状态用不同的类名区分
 *   - 折叠状态：.hot-item-expand（紫色）
 *   - 展开状态：.hot-item-footer（绿色）
 *
 * @param {HTMLElement} card  卡片根元素（用来读 data-expanded 状态）
 * @param {Number} total      总数据条数
 * @return {string} HTML 字符串
 */
function renderControlItem(card, total) {
    var expanded = card.getAttribute('data-expanded') === '1';

    if (expanded) {
        // ===== 已展开状态（绿色，显示"已展开全部 N 条，点击折叠"）=====
        return '<li class="card-expand-bar hot-item-footer" onclick="toggleExpandByCard(this)">'
             +   '<span class="rank-expand" style="background: linear-gradient(135deg,#52c41a 0%,#73d13d 100%);"><span class="iconfont icon-arrow_up_fat"></span></span>'
             +   '<span class="footer-title">已展开全部 ' + total + ' 条，点击折叠</span>'
             +   '<span class="footer-heat"><span class="iconfont icon-dui" style="font-size:11px; margin-right:2px;"></span>完成</span>'
             + '</li>';
    } else {
        // ===== 折叠状态（绿色，显示"展开全部 N 条 / 点击展开"）=====
        return '<li class="card-expand-bar hot-item-expand" onclick="toggleExpandByCard(this)">'
             +   '<span class="rank-expand"><span class="iconfont icon-arrow_down_fat"></span></span>'
             +   '<span class="expand-title">展开全部 ' + total + ' 条</span>'
             +   '<span class="expand-arrow">点击展开</span>'
             + '</li>';
    }
}


// ============================================================
// 展开 / 折叠 功能
// ============================================================

/**
 * 从控制条（点击的那个元素）向上找到卡片，然后切换展开/折叠
 *
 * 新手提示：closest('.hot-card') 会一直往父元素找，直到找到 class 是 hot-card 的，
 * 这样不管点击的是控制条里的哪个子元素，都能找到卡片本身
 *
 * @param {HTMLElement} controlEl  被点击的控制条元素
 */
function toggleExpandByCard(controlEl) {
    var card = controlEl.closest('.hot-card');
    toggleExpand(card);
}

/**
 * 展开 / 折叠（核心函数）
 *
 * 工作原理：
 *   1. 完整数据存在 card._allData 里（PHP 渲染时或刷新后存进去的）
 *   2. 状态存在 card.dataset.expanded 里（'1'=展开，'0'=折叠）
 *   3. 切换时：根据状态渲染前 N 条或者全部，然后更新控制条的样式
 *
 * 新手提示：为什么不直接操作 DOM 显示隐藏？
 *   因为数据量可能很大（几十上百条），直接 innerHTML 替换比一个个
 *   操作 DOM 元素性能更好，代码也更简单
 *
 * @param {HTMLElement} card  卡片根元素
 */
function toggleExpand(card) {
    if (!card) return;
    var cardBody = card.querySelector('.card-body');
    var listEl   = card.querySelector('.hot-list');
    if (!cardBody || !listEl) return;

    var allItems = listEl.querySelectorAll('.hot-item');
    var total    = allItems.length;

    // 当前状态
    var expanded = card.getAttribute('data-expanded') === '1';

    // ===== 切换状态（只切 display，不重建 DOM — 性能更好、SEO 友好）=====
    if (expanded) {
        // --- 折叠：隐藏 DISPLAY_LIMIT 之后的条目 ---
        card.setAttribute('data-expanded', '0');
        var showN = Math.min(DISPLAY_LIMIT, total);
        for (var i = showN; i < total; i++) {
            allItems[i].style.display = 'none';
        }
    } else {
        // --- 展开：显示全部 ---
        card.setAttribute('data-expanded', '1');
        for (var i = 0; i < total; i++) {
            allItems[i].style.display = '';
        }
    }

    // ===== 更新展开条的内容（箭头、文字、颜色）=====
    var expandBar = cardBody.querySelector('.card-expand-bar');
    if (expandBar) {
        expandBar.outerHTML = renderControlItem(card, total);
    }
}


// ============================================================
// 刷新功能（AJAX 拉取最新数据）
// ============================================================

/**
 * 刷新单个卡片的数据
 *
 * 流程：
 *   1. 禁用按钮 + 显示加载动画
 *   2. 发 AJAX 请求到 core/refresh.php
 *   3. 收到响应后解析 JSON
 *   4. 更新数据 + 更新时间 + 显示 Toast
 *   5. 出错了也显示错误提示
 *
 * 新手提示：XMLHttpRequest 是原生 JS 的 AJAX 对象，
 * 不用引入 jQuery 也能发请求
 *
 * @param {HTMLElement} btnEl  刷新按钮本身（用来加禁用状态）
 */
function refreshCard(btnEl) {
    var card = btnEl.closest('.hot-card');
    if (!card) return;
    var cardBody  = card.querySelector('.card-body');
    var listEl    = card.querySelector('.hot-list');
    var timeEl    = card.querySelector('.update-time');
    var sourceId  = btnEl.getAttribute('data-source') || '';

    // ===== 防刷第 1 层：前端冷却（15 秒内同一张卡片只能点一次）=====
    // 和后端 refresh.php 冷却（15 秒）对齐，避免前端先挡住用户
    var COOLDOWN_MS = 15000;
    var lastKey = '__hotcc_last_refresh_' + sourceId;
    var now = Date.now();
    var last = parseInt(sessionStorage.getItem(lastKey) || '0', 10);
    if (last && (now - last) < COOLDOWN_MS) {
        var remain = Math.ceil((COOLDOWN_MS - (now - last)) / 1000);
        showToast('刷新太频繁啦，等 ' + remain + ' 秒再试', 'error');
        return;
    }
    sessionStorage.setItem(lastKey, now);

    // ===== 进入"加载中"状态 =====
    btnEl.disabled = true;
    btnEl.style.opacity = '0.6';

    // 清空列表
    if (listEl) {
        listEl.innerHTML = '';
    }
    // 移除旧的展开条
    var oldExpand = cardBody.querySelector('.card-expand-bar');
    if (oldExpand) oldExpand.remove();

    // 显示加载文字
    var loadingHtml = '<li class="loading-li" style="padding:20px 16px; text-align:center; color:#999; font-size:13px;">'
                    + '<span class="iconfont icon-shuaxin" style="margin-right:4px; animation: spin 1s linear infinite;"></span>正在获取最新数据...</li>';
    if (listEl) {
        listEl.innerHTML = loadingHtml;
    }

    // ===== 发 AJAX 请求 =====
    var xhr = new XMLHttpRequest();
    xhr.open('GET', 'core/refresh.php?source=' + encodeURIComponent(sourceId)
         + '&limit=' + API_LIMIT, true);
    xhr.timeout = 15000;  // 15秒超时

    // --- 请求成功 ---
    xhr.onload = function () {
        btnEl.disabled = false;
        btnEl.style.opacity = '1';

        try {
            var res = JSON.parse(xhr.responseText);
            if (res.success && Array.isArray(res.data)) {
                var data = res.data;
                var total = data.length;

                if (total === 0) {
                    // 没数据
                    listEl.innerHTML = '<li class="loading-li" style="padding:20px 16px; text-align:center; color:#999; font-size:13px;">'
                                     + '<span class="iconfont icon-wushuju" style="margin-right:4px;"></span>暂无数据</li>';
                } else {
                    // 存数据 + 默认折叠状态
                    card._allData = data;
                    card.setAttribute('data-expanded', '0');

                    // 渲染全部（刷新也要 SEO 友好，全部塞进 DOM）
                    var html = '';
                    for (var i = 0; i < total; i++) {
                        html += renderHotItem(data[i], i);
                    }
                    listEl.innerHTML = html;

                    // 折叠：隐藏 DISPLAY_LIMIT 之后的条目
                    var showN = Math.min(DISPLAY_LIMIT, total);
                    var items = listEl.querySelectorAll('.hot-item');
                    for (var i = showN; i < items.length; i++) {
                        items[i].style.display = 'none';
                    }

                    // 永远追加"展开全部"控制条（保持 11 行 grid 结构）
                    // 数据 > DISPLAY_LIMIT → 可见、可点击
                    // 数据 ≤ DISPLAY_LIMIT → is-placeholder（visibility:hidden 占位）
                    var expandHtml = renderControlItem(card, total);
                    if (total <= DISPLAY_LIMIT) {
                        // 在返回的 <li class="card-expand-bar ..."> 上加 is-placeholder
                        expandHtml = expandHtml.replace(
                            'class="card-expand-bar',
                            'class="card-expand-bar is-placeholder'
                        );
                    }
                    listEl.insertAdjacentHTML('beforeend', expandHtml);
                }

                // 更新时间
                if (timeEl) {
                    timeEl.innerHTML = '<span class="iconfont icon-shijian" style="font-size:12px; margin-right:3px;"></span>' + (res.time_str || formatNow()) + ' 更新';
                }
                showToast(sourceId.toUpperCase() + ' 刷新成功', 'success');
            } else {
                // 后端返回失败
                listEl.innerHTML = '<li class="error-li" style="padding:20px 16px; text-align:center; color:#ff4d4f; font-size:13px;">'
                                 + '<span class="iconfont icon-cuowu" style="margin-right:4px;"></span>'
                                 + (res.message || '获取数据失败') + '</li>';
                showToast('刷新失败', 'error');
            }
        } catch (e) {
            // JSON 解析失败
            console.error('解析错误:', e);
            listEl.innerHTML = '<li class="error-li" style="padding:20px 16px; text-align:center; color:#ff4d4f; font-size:13px;">'
                             + '<span class="iconfont icon-cuowu" style="margin-right:4px;"></span>数据解析错误</li>';
            showToast('数据解析失败', 'error');
        }
    };

    // --- 网络错误 ---
    xhr.onerror = function () {
        btnEl.disabled = false;
        btnEl.style.opacity = '1';
        if (listEl) {
            listEl.innerHTML = '<li class="error-li" style="padding:20px 16px; text-align:center; color:#ff4d4f; font-size:13px;">'
                             + '<span class="iconfont icon-cuowu" style="margin-right:4px;"></span>网络错误</li>';
        }
        showToast('网络错误，请稍后重试', 'error');
    };

    // --- 请求超时 ---
    xhr.ontimeout = function () {
        btnEl.disabled = false;
        btnEl.style.opacity = '1';
        if (listEl) {
            listEl.innerHTML = '<li class="error-li" style="padding:20px 16px; text-align:center; color:#ff4d4f; font-size:13px;">'
                             + '<span class="iconfont icon-cuowu" style="margin-right:4px;"></span>请求超时</li>';
        }
        showToast('请求超时', 'error');
    };

    xhr.send();
}


// ============================================================
// 初始化区（页面加载完后执行）
// ============================================================
document.addEventListener('DOMContentLoaded', function () {

    // ===== Tooltip 管理器（跟随鼠标 + JS body 插入避开 overflow 裁剪）=====
    (function initTooltipManager() {
        var tooltipEl = null;
        var currentTarget = null;
        var lastMouseX = 0;
        var lastMouseY = 0;

        // 全局监听鼠标坐标
        document.addEventListener('mousemove', function(e) {
            lastMouseX = e.clientX;
            lastMouseY = e.clientY;
        });

        // 创建 tooltip 元素（只创建一次，反复复用）
        function ensureTooltip() {
            if (tooltipEl) return tooltipEl;
            tooltipEl = document.createElement('div');
            tooltipEl.className = 'js-tooltip';
            document.body.appendChild(tooltipEl);
            return tooltipEl;
        }

        // 显示 tooltip（跟随鼠标）
        function showTooltip(target) {
            if (!target || !target.isConnected) return;  // 节点已脱离 DOM 则跳过
            var text = target.getAttribute('data-tooltip');
            if (!text) return;

            var tt = ensureTooltip();
            tt.textContent = text;
            tt.classList.add('visible');

            var gap = 10;

            // 先让它渲染出来拿到实际尺寸
            tt.style.left = '-9999px';
            tt.style.top = '0';
            var tooltipW = tt.offsetWidth;
            var tooltipH = tt.offsetHeight;

            // 离顶部太近则向下弹
            var flipDown = (lastMouseY - tooltipH - gap) < 8;

            // tooltip 中心对齐鼠标 X
            var left = lastMouseX - tooltipW / 2;
            var top;
            if (flipDown) {
                top = lastMouseY + gap;
            } else {
                top = lastMouseY - tooltipH - gap;
            }

            // 边界修正
            var vw = window.innerWidth;
            var vh = window.innerHeight;
            if (left < 8) left = 8;
            if (left + tooltipW > vw - 8) left = vw - 8 - tooltipW;
            if (!flipDown && top < 8) top = 8;
            if (flipDown && top + tooltipH > vh - 8) top = vh - 8 - tooltipH;

            tt.style.top = top + 'px';
            tt.style.left = left + 'px';
        }

        // 隐藏 tooltip
        function hideTooltip() {
            if (tooltipEl) {
                tooltipEl.classList.remove('visible');
            }
            currentTarget = null;  // 必须无条件清空，否则 mousemove 还会重新 show
        }

        // 事件委托：监听所有带 data-tooltip 的元素
        document.addEventListener('mouseover', function(e) {
            if (e.clientX !== undefined) {
                lastMouseX = e.clientX;
                lastMouseY = e.clientY;
            }
            var target = e.target.closest('[data-tooltip]');
            if (target && target !== currentTarget) {
                currentTarget = target;
                showTooltip(target);
            }
        });

        document.addEventListener('mouseout', function(e) {
            var target = e.target.closest('[data-tooltip]');
            if (target) {
                var related = e.relatedTarget;
                if (!related || !target.contains(related)) {
                    hideTooltip();
                }
            }
        });

        // 鼠标在 item 内移动时实时更新 tooltip 位置（rAF 节流）
        document.addEventListener('mousemove', function(e) {
            if (!currentTarget) return;
            if (this._rafPending) return;
            this._rafPending = true;
            var self = this;
            requestAnimationFrame(function() {
                self._rafPending = false;
                showTooltip(currentTarget);
            });
        });

        // 滚动/窗口变化时隐藏
        window.addEventListener('scroll', hideTooltip, true);
        window.addEventListener('resize', hideTooltip);
    })();

    // ===== 应用用户设置（localStorage）到已渲染的 DOM =====
    (function applyUserSettings() {
        var hasSetting = false;

        // 1. 隐藏来源链接
        if (typeof USER_SHOW_SOURCE !== 'undefined' && !USER_SHOW_SOURCE) {
            document.querySelectorAll('.card-source-link').forEach(function(el) {
                el.style.display = 'none';
            });
            hasSetting = true;
        }

        // 2. 隐藏卡片刷新按钮
        if (typeof USER_SHOW_REFRESH !== 'undefined' && !USER_SHOW_REFRESH) {
            document.querySelectorAll('.refresh-btn').forEach(function(el) {
                el.style.display = 'none';
            });
            hasSetting = true;
        }

        // 3. 隐藏热度值
        if (typeof USER_SHOW_HEAT !== 'undefined' && !USER_SHOW_HEAT) {
            document.querySelectorAll('.hot-heat').forEach(function(el) {
                el.style.display = 'none';
            });
            hasSetting = true;
        }

        // 4. 修改链接打开方式 — 遍历所有 .hot-item 的 onclick
        if (typeof USER_LINK_TARGET !== 'undefined') {
            document.querySelectorAll('.hot-item').forEach(function(li) {
                // 从 onclick 里提取 url（openHotLink('url','target')）
                var oc = li.getAttribute('onclick') || '';
                var match = oc.match(/openHotLink\(['"](.+?)['"]\s*,\s*['"](.+?)['"]\)/);
                if (match) {
                    li.setAttribute('onclick', "openHotLink('" + match[1] + "','" + USER_LINK_TARGET + "')");
                }
            });
            hasSetting = true;
        }

        // 5. 调整卡片折叠条数（DISPLAY_LIMIT）
        // PHP 永远渲染展开条（保持 11 行 grid 结构）
        // 数据 ≤ DISPLAY_LIMIT → 展开条加 is-placeholder（visibility:hidden 占位）
        // 数据 > DISPLAY_LIMIT → 展开条可见、可点击
        if (typeof DISPLAY_LIMIT !== 'undefined') {
            document.querySelectorAll('.hot-card').forEach(function(card) {
                var ul = card.querySelector('.hot-list');
                if (!ul) return;
                var allLi = ul.querySelectorAll('.hot-item');

                // 真实总数从 _allData 读，这才是完整数据（header.php 注入的 CARD_DATA）
                var realTotal = (card._allData && card._allData.length > 0)
                    ? card._allData.length
                    : allLi.length;  // 兜底（平台详情页没有 _allData）

                // 先把 DOM 里所有 li 恢复显示（为后面折叠做准备）
                allLi.forEach(function(li) { li.style.display = ''; });

                var ctrl = card.querySelector('.card-expand-bar');
                card.setAttribute('data-expanded', '0');

                if (realTotal <= DISPLAY_LIMIT) {
                    // --- 真实总数 ≤ 用户设置的折叠上限：全部显示 ---
                    card.setAttribute('data-expanded', '0');

                    // 补渲染：如果 PHP 只渲染了前 config.display_limit 条，
                    // 但 realTotal 可能更多（虽然 <= DISPLAY_LIMIT），需要补渲染完整列表
                    if (realTotal > allLi.length && typeof renderHotItem === 'function') {
                        var html = '';
                        for (var k = 0; k < realTotal; k++) {
                            html += renderHotItem(card._allData[k], k);
                        }
                        ul.innerHTML = html;
                        // 补回展开条（永远在 DOM 里）
                        ctrl = ul.querySelector('.card-expand-bar');
                    }

                    // 展开条占位模式：visibility:hidden 但保持 grid 行
                    if (ctrl) {
                        ctrl.classList.add('is-placeholder');
                    }
                } else {
                    // --- 真实总数 > 用户设置的折叠上限：需要折叠 ---

                    // 折叠：只显示前 DISPLAY_LIMIT 条
                    // 但 PHP 只渲染了前 config.display_limit 条到 DOM
                    // 如果 DISPLAY_LIMIT <= PHP 渲染的 DOM li 数量，直接隐藏后面的就行
                    // 否则（DISPLAY_LIMIT > config.display_limit），需要补渲染
                    if (DISPLAY_LIMIT <= allLi.length) {
                        // 直接隐藏 DOM 里多余的
                        for (var i = DISPLAY_LIMIT; i < allLi.length; i++) {
                            allLi[i].style.display = 'none';
                        }
                    } else {
                        // DOM 里 li 不够，用 _allData 重建 ul 前 DISPLAY_LIMIT 条
                        var html2 = '';
                        for (var m = 0; m < DISPLAY_LIMIT && m < realTotal; m++) {
                            html2 += renderHotItem(card._allData[m], m);
                        }
                        // 先找到展开条节点（在 innerHTML 重建 ul 前保存引用）
                        ctrl = ul.querySelector('.card-expand-bar');
                        var ctrlOuter = ctrl ? ctrl.outerHTML : null;
                        ul.innerHTML = html2;
                        // 补回展开条
                        if (ctrlOuter) {
                            ul.insertAdjacentHTML('beforeend', ctrlOuter);
                        } else if (typeof renderControlItem === 'function') {
                            ul.insertAdjacentHTML('beforeend', renderControlItem(card, realTotal));
                        }
                        ctrl = ul.querySelector('.card-expand-bar');
                    }

                    // 展开条可见、可点击（移除占位类）
                    if (ctrl) {
                        ctrl.classList.remove('is-placeholder');
                    } else if (typeof renderControlItem === 'function') {
                        // 极端情况：PHP 没渲染展开条，补上
                        ul.insertAdjacentHTML('beforeend', renderControlItem(card, realTotal));
                    }
                }
            });
        }

        // 6. 调整列数 — 改 .hot-grid 的 grid-template-columns
        if (typeof USER_COLUMNS !== 'undefined') {
            var grids = document.querySelectorAll('.hot-grid');
            grids.forEach(function(grid) {
                if (USER_COLUMNS === 'auto') {
                    // 让 PHP 动态 CSS 生效（优先用它）
                    grid.style.gridTemplateColumns = '';
                    // 用 config.php 的 card_min_width（兜底 260）
                    var cw = (PHP_DEF && PHP_DEF.card_min_width) ? PHP_DEF.card_min_width : 260;
                    grid.style.gridTemplateColumns = 'repeat(auto-fill, minmax(' + cw + 'px, 1fr))';
                } else {
                    var n = parseInt(USER_COLUMNS, 10);
                    if (n >= 1 && n <= 10) {
                        grid.style.gridTemplateColumns = 'repeat(' + n + ', 1fr)';
                    }
                }
            });
            hasSetting = true;
        }
    })();

    // ----- 回到顶部按钮 -----
    var backToTop = document.getElementById('backToTop');
    if (backToTop) {
        // 监听页面滚动
        window.addEventListener('scroll', function () {
            // 滚动超过 300px 就显示按钮，否则隐藏
            if (window.scrollY > 300) {
                backToTop.classList.add('visible');
            } else {
                backToTop.classList.remove('visible');
            }
        });

        // 点击按钮回到顶部
        backToTop.addEventListener('click', function () {
            // 新手提示：smooth 表示平滑滚动，不是一下子跳上去
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }

});
