/**
 * Toast 轻提示工具
 *
 * 用法：
 *   showToast('操作成功', 'success');
 *   showToast('操作失败', 'error');
 *   showToast('提示信息', 'info');
 */

function showToast(message, type) {
    type = type || 'info';
    var old = document.querySelector('.toast-message');
    if (old) old.remove();

    var toast = document.createElement('div');
    toast.className = 'toast-message toast-' + type;
    toast.textContent = message;
    document.body.appendChild(toast);

    setTimeout(function () { toast.classList.add('show'); }, 10);
    setTimeout(function () {
        toast.classList.remove('show');
        setTimeout(function () { toast.remove(); }, 300);
    }, 2500);
}
