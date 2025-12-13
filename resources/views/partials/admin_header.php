<?php
// Admin header partial - expects $title variable optionally
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?php echo $this->escape($title ?? 'Admin'); ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
  <link href="<?php echo BASE_URL; ?>/shared/style.css" rel="stylesheet">
  <link href="<?php echo BASE_URL; ?>/css/admin.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <script>window.BASE_URL = '<?php echo BASE_URL; ?>';</script>
</head>
<body class="admin-layout">
  <div class="admin-shell">
    <aside class="admin-sidebar" id="adminSidebar">
      <div class="admin-brand"><i class="fas fa-wallet"></i> <span>SmartSpending Admin</span></div>
      <nav class="admin-nav">
        <?php
        $u = trim($_GET['url'] ?? '', '/'); $seg = $u ? explode('/', $u) : []; $act = $seg[1] ?? $seg[0] ?? 'dashboard';
        $link = function($p,$label){ $url = BASE_URL . '/admin/' . $p; $cls = (strpos(trim($_GET['url'] ?? ''), 'admin/'.$p) === 0) ? 'active' : ''; return '<a class="'.$cls.'" href="'.$url.'">'.$label.'</a>'; };
        echo $link('dashboard','Dashboard');
        echo $link('users','Quản lý Users');
        echo $link('categories','Quản lý Danh mục');
        echo $link('logs','Nhật ký hoạt động');
        ?>
      </nav>
    </aside>
    <main class="admin-main">
      <div class="admin-topbar">
        <div class="flex-gap">
          <div class="top-left">
            <h4 class="m-0"><?php echo $this->escape($title ?? 'Admin'); ?></h4>
          </div>
        </div>

        <div class="flex-gap">
          <form class="top-search" method="get" action="<?php echo BASE_URL; ?>/admin/users">
            <input type="search" name="q" placeholder="Tìm kiếm..." aria-label="Search">
            <button class="btn btn-primary btn-sm" type="submit"><i class="fas fa-search"></i></button>
          </form>

          <div class="top-avatar dropdown">
            <a href="#" class="d-flex align-items-center text-decoration-none" id="adminUserMenu" data-bs-toggle="dropdown" aria-expanded="false">
              <img src="<?php echo BASE_URL; ?>/shared/avatar-placeholder.png" alt="avatar">
              <div style="margin-left:8px;display:inline-block;text-align:left"><div style="font-weight:600"><?php echo $_SESSION['full_name'] ?? 'Admin'; ?></div><small class="text-muted">Administrator</small></div>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="adminUserMenu">
              <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/profile"><i class="fas fa-user me-2"></i>Hồ sơ</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/auth/login/logout"><i class="fas fa-sign-out-alt me-2"></i>Đăng xuất</a></li>
            </ul>
          </div>
        </div>
      </div>
