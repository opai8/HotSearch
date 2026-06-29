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
var DISPLAY_LIMIT = 10;   // 折叠状态下默认显示的条数


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
    window.open(url, target || '_blank');
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

    // 热度值（有的平台没有热度，就不显示）
    var hotHtml = hot ? ('<span class="hot-heat"><span class="iconfont icon-xiaohuomiao" style="font-size:12px; margin-right:2px;"></span>' + escapeHtml(hot) + '</span>') : '';

    return '<li class="hot-item" onclick="openHotLink(\'' + escapeHtml(url) + '\',\'_blank\')" data-tooltip="' + escapeHtml(title) + '">'
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
        return '<div class="card-expand-bar hot-item-footer" onclick="toggleExpandByCard(this)">'
             +   '<span class="rank-expand" style="background: linear-gradient(135deg,#52c41a 0%,#73d13d 100%);"><span class="iconfont icon-arrow_up_fat"></span></span>'
             +   '<span class="footer-title">已展开全部 ' + total + ' 条，点击折叠</span>'
             +   '<span class="footer-heat"><span class="iconfont icon-dui" style="font-size:11px; margin-right:2px;"></span>完成</span>'
             + '</div>';
    } else {
        // ===== 折叠状态（紫色，显示"展开全部 N 条 / 点击展开"）=====
        return '<div class="card-expand-bar hot-item-expand" onclick="toggleExpandByCard(this)">'
             +   '<span class="rank-expand"><span class="iconfont icon-arrow_down_fat"></span></span>'
             +   '<span class="expand-title">展开全部 ' + total + ' 条</span>'
             +   '<span class="expand-arrow">点击展开</span>'
             + '</div>';
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

    // 拿完整数据（存在卡片元素的 _allData 属性上）
    var allData = card._allData || [];
    var total   = allData.length;
    if (total === 0) return;

    // 当前状态
    var expanded = card.getAttribute('data-expanded') === '1';

    // ===== 切换状态 =====
    if (expanded) {
        // --- 折叠：只显示前 DISPLAY_LIMIT 条 ---
        card.setAttribute('data-expanded', '0');
        var showN = Math.min(DISPLAY_LIMIT, total);
        var html = '';
        for (var i = 0; i < showN; i++) {
            html += renderHotItem(allData[i], i);
        }
        listEl.innerHTML = html;
    } else {
        // --- 展开：显示全部 ---
        card.setAttribute('data-expanded', '1');
        var html = '';
        for (var i = 0; i < total; i++) {
            html += renderHotItem(allData[i], i);
        }
        listEl.innerHTML = html;
    }

    // ===== 更新展开条的内容（箭头、文字、颜色）=====
    var expandBar = cardBody.querySelector('.card-expand-bar');
    if (expandBar) {
        // outerHTML 替换整个元素（包括它自己）
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

                    // 渲染前 DISPLAY_LIMIT 条
                    var showN = Math.min(DISPLAY_LIMIT, total);
                    var html = '';
                    for (var i = 0; i < showN; i++) {
                        html += renderHotItem(data[i], i);
                    }
                    listEl.innerHTML = html;

                    // 如果超过 DISPLAY_LIMIT，追加"展开全部"控制条（在 ul 外面，card-body 里面）
                    if (total > DISPLAY_LIMIT) {
                        cardBody.insertAdjacentHTML('beforeend', renderControlItem(card, total));
                    }
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
