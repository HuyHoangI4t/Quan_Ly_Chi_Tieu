<aside class="sidebar">
	<div class="brand">
		<i class="fas fa-layer-group"></i> SmartSpending
	</div>

	<div class="menu-label">Tổng quan</div>
	<a href="<?php echo BASE_URL; ?>/admin/dashboard" class="nav-item <?php echo (strpos($_SERVER['REQUEST_URI'], 'dashboard') !== false) ? 'active' : ''; ?>">
		<i class="fas fa-home"></i> Dashboard
	</a>

	<div class="menu-label">Quản lý</div>
	<a href="<?php echo BASE_URL; ?>/admin/users" class="nav-item <?php echo (strpos($_SERVER['REQUEST_URI'], 'users') !== false) ? 'active' : ''; ?>">
		<i class="fas fa-users"></i> Người dùng
	</a>
	<a href="<?php echo BASE_URL; ?>/admin/categories" class="nav-item <?php echo (strpos($_SERVER['REQUEST_URI'], 'categories') !== false) ? 'active' : ''; ?>">
		<i class="fas fa-tags"></i> Danh mục
	</a>

	<a href="<?php echo BASE_URL; ?>/admin/logs" class="nav-item <?php echo (strpos($_SERVER['REQUEST_URI'], 'logs') !== false) ? 'active' : ''; ?>">
		<i class="fas fa-clock"></i> Nhật ký
	</a>

	<a href="<?php echo BASE_URL; ?>/admin/settings" class="nav-item <?php echo (strpos($_SERVER['REQUEST_URI'], 'settings') !== false) ? 'active' : ''; ?>">
		<i class="fas fa-cog"></i> Cài đặt
	</a>

	<div class="menu-label" style="margin-top: auto;">Hệ thống</div>
	<a href="javascript:void(0);" class="nav-item impersonate-btn">
		<i class="fas fa-arrow-left"></i> Về User View
	</a>
	<a href="<?php echo BASE_URL; ?>/auth/login/logout" class="nav-item" style="color: #ef4444;">
		<i class="fas fa-sign-out-alt"></i> Đăng xuất
	</a>
</aside>
<script>
    // Polyfill basic SmartSpending UI if main script not loaded
    (function() {
        if (window.SmartSpending) return;

        function createElem(tag, attrs) {
            var el = document.createElement(tag);
            attrs = attrs || {};
            for (var k in attrs) {
                if (k === 'html') el.innerHTML = attrs[k];
                else if (k === 'class') el.className = attrs[k];
                else el.setAttribute(k, attrs[k]);
            }
            return el;
        }

        // Modal
        var _modal, _overlay, _loader, _toast;
        function ensureUI() {
            if (_overlay) return;
            _overlay = createElem('div', { class: 'ss-overlay', style: 'position:fixed;inset:0;background:rgba(0,0,0,0.4);display:none;z-index:9998;' });
            _modal = createElem('div', { class: 'ss-modal', style: 'position:fixed;left:50%;top:50%;transform:translate(-50%,-50%);background:#fff;padding:16px;border-radius:8px;min-width:280px;box-shadow:0 6px 20px rgba(0,0,0,0.2);display:none;z-index:9999;' });
            _loader = createElem('div', { class: 'ss-loader', style: 'position:fixed;inset:0;display:none;align-items:center;justify-content:center;z-index:10000;' , html: '<div style="width:48px;height:48px;border:5px solid rgba(0,0,0,0.1);border-top-color:#3490dc;border-radius:50%;animation:ss-spin 1s linear infinite"></div>'});
            _toast = createElem('div', { class: 'ss-toast', style: 'position:fixed;right:20px;bottom:20px;z-index:10001;pointer-events:none;' });

            document.body.appendChild(_overlay);
            document.body.appendChild(_modal);
            document.body.appendChild(_loader);
            document.body.appendChild(_toast);

            var style = createElem('style', { html: '\
                @keyframes ss-spin{to{transform:rotate(360deg)}}\
                .ss-btn{margin-left:8px;padding:6px 12px;border-radius:6px;border:0;cursor:pointer}\
            ' });
            document.head.appendChild(style);
        }

        function showConfirm(title, message, onConfirm, opts) {
            ensureUI();
            _overlay.style.display = 'block';
            _modal.style.display = 'block';
            _modal.innerHTML = '';
            var h = createElem('div', { html: '<strong>' + title + '</strong>' });
            var p = createElem('div', { html: '<div style="margin-top:8px;">' + message + '</div>' });
            var actions = createElem('div', { style: 'margin-top:12px;text-align:right;' });
            var cancel = createElem('button', { class: 'ss-btn', html: opts && opts.cancelText ? opts.cancelText : 'Hủy' });
            var confirm = createElem('button', { class: 'ss-btn', html: opts && opts.confirmText ? opts.confirmText : 'Đồng ý', style: 'background:#0ea5a4;color:#fff;' });
            actions.appendChild(cancel);
            actions.appendChild(confirm);
            _modal.appendChild(h);
            _modal.appendChild(p);
            _modal.appendChild(actions);

            function close() {
                _overlay.style.display = 'none';
                _modal.style.display = 'none';
                cancel.removeEventListener('click', onCancel);
                confirm.removeEventListener('click', onOk);
            }
            function onCancel() { close(); }
            function onOk() { close(); if (typeof onConfirm === 'function') onConfirm(); }

            cancel.addEventListener('click', onCancel);
            confirm.addEventListener('click', onOk);
        }

        function showLoader() { ensureUI(); _loader.style.display = 'flex'; _loader.style.alignItems='center'; _loader.style.justifyContent='center'; }
        function hideLoader() { if (_loader) _loader.style.display = 'none'; }

        function showToast(msg, type) {
            ensureUI();
            var t = createElem('div', { style: 'background:rgba(0,0,0,0.8);color:#fff;padding:8px 12px;border-radius:6px;margin-top:8px;pointer-events:auto;', html: msg });
            _toast.appendChild(t);
            setTimeout(function() { t.style.transition = 'opacity .3s'; t.style.opacity = '0'; setTimeout(function(){try{_toast.removeChild(t);}catch(e){}},350); }, 3000);
        }

        window.SmartSpending = {
            showConfirm: showConfirm,
            showLoader: showLoader,
            hideLoader: hideLoader,
            showToast: showToast
        };
    })();

    document.addEventListener('DOMContentLoaded', function() {
        var btn = document.querySelector('.nav-item.impersonate-btn');
        if (!btn) return;
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            if (window.SmartSpending && typeof SmartSpending.showConfirm === 'function') {
                SmartSpending.showConfirm('Chuyển sang User', 'Bạn có muốn đăng nhập vào tài khoản demo?', function() {
                    SmartSpending.showLoader();
                    fetch('<?php echo BASE_URL; ?>/admin/users/api_login_as_user', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            user_id: 2
                        }),
                        credentials: 'same-origin'
                    }).then(function(res) {
                        SmartSpending.hideLoader();
                        return res.json();
                    }).then(function(data) {
                        if (data && data.success) {
                            window.location.href = '<?php echo BASE_URL; ?>/dashboard';
                        } else {
                            SmartSpending.showToast(data && data.message ? data.message : 'Không thể chuyển đổi tài khoản', 'error');
                        }
                    }).catch(function(err) {
                        SmartSpending.hideLoader();
                        console.error('Impersonate error', err);
                        SmartSpending.showToast('Lỗi mạng khi chuyển đổi tài khoản', 'error');
                    });
                }, {
                    cancelText: 'Hủy',
                    confirmText: 'Đồng ý'
                });
            } else {
                console.warn('SmartSpending.showConfirm not available — using native confirm fallback');
                if (!confirm('Bạn có muốn đăng nhập vào tài khoản demo?')) return;
                fetch('<?php echo BASE_URL; ?>/admin/users/api_login_as_user', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        user_id: 2
                    }),
                    credentials: 'same-origin'
                }).then(function(res) {
                    return res.json();
                }).then(function(data) {
                    if (data && data.success) {
                        window.location.href = '<?php echo BASE_URL; ?>/dashboard';
                    } else {
                        alert(data && data.message ? data.message : 'Không thể chuyển đổi tài khoản');
                    }
                }).catch(function(err) {
                    console.error('Impersonate error', err);
                    alert('Lỗi mạng khi chuyển đổi tài khoản');
                });
            }
        });
    });
</script>