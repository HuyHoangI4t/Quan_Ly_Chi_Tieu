<aside class="sidebar">
    <div class="brand">
        <i class="fas fa-shield-alt"></i> <span>ADMIN<span style="color:#64748b; font-weight:400;">PANEL</span></span>
    </div>

    <nav class="sidebar-nav">
        <div class="menu-label">Tổng quan</div>
        <a href="<?php echo BASE_URL; ?>/admin/dashboard" class="nav-item <?php echo (strpos($_SERVER['REQUEST_URI'], '/admin/dashboard') !== false) ? 'active' : ''; ?>">
            <i class="fas fa-chart-pie"></i> Dashboard
        </a>

        <div class="menu-label">Quản lý dữ liệu</div>
        <a href="<?php echo BASE_URL; ?>/admin/users" class="nav-item <?php echo (strpos($_SERVER['REQUEST_URI'], '/admin/users') !== false) ? 'active' : ''; ?>">
            <i class="fas fa-users"></i> Người dùng
        </a>
        <a href="<?php echo BASE_URL; ?>/admin/categories" class="nav-item <?php echo (strpos($_SERVER['REQUEST_URI'], '/admin/categories') !== false) ? 'active' : ''; ?>">
            <i class="fas fa-folder-open"></i> Danh mục
        </a>
        <!-- <a href="<?php echo BASE_URL; ?>/admin/transactions" class="nav-item <?php echo (strpos($_SERVER['REQUEST_URI'], '/admin/transactions') !== false) ? 'active' : ''; ?>">
            <i class="fas fa-exchange-alt"></i> Giao dịch
        </a> -->

        <div class="menu-label">Hệ thống</div>
        <a href="<?php echo BASE_URL; ?>/admin/reports" class="nav-item <?php echo (strpos($_SERVER['REQUEST_URI'], '/admin/reports') !== false) ? 'active' : ''; ?>">
            <i class="fas fa-file-invoice-dollar"></i> Báo cáo & Xuất
        </a>
        <a href="<?php echo BASE_URL; ?>/admin/logs" class="nav-item <?php echo (strpos($_SERVER['REQUEST_URI'], '/admin/logs') !== false) ? 'active' : ''; ?>">
            <i class="fas fa-history"></i> Nhật ký hoạt động
        </a>
        <a href="<?php echo BASE_URL; ?>/admin/settings" class="nav-item <?php echo (strpos($_SERVER['REQUEST_URI'], '/admin/settings') !== false) ? 'active' : ''; ?>">
            <i class="fas fa-sliders-h"></i> Cấu hình
        </a>
    </nav>

    <div class="sidebar-footer">
        <a href="<?php echo BASE_URL; ?>/auth/logout" style="display:block; color: #ef4444; font-weight: 600;">
            <i class="fas fa-sign-out-alt me-2"></i> Đăng xuất
        </a>
        <div style="margin-top: 8px; opacity: 0.5;">v1.2.0 Stable</div>
    </div>
</aside>