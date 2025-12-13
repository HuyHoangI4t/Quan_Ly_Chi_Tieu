<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý người dùng</title>
    <?php use App\Middleware\CsrfProtection; echo CsrfProtection::getTokenMeta(); ?>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>/css/admin.css" rel="stylesheet">
</head>
<body>

<div class="admin-wrapper">
    <?php require __DIR__ . '/partials/sidebar.php'; ?>


    <main class="main-content">
        <div class="top-bar">
            <div class="page-title">
                <h1>Quản lý người dùng</h1>
                <p>Danh sách tài khoản hệ thống</p>
            </div>
            <form method="GET" style="background: #fff; padding: 8px 16px; border-radius: 8px; border: 1px solid #e2e8f0; display: flex; gap: 8px; align-items: center;">
                <button type="submit" style="background:none;border:none;cursor:pointer"><i class="fas fa-search text-muted"></i></button>
                <input type="text" name="q" value="<?php echo htmlspecialchars($search ?? ''); ?>" placeholder="Tìm user..." style="border: none; outline: none; width: 200px;">
            </form>
        </div>

        <div class="card-box" style="padding: 0; overflow: hidden;">
            <table class="table-pro">
                <thead>
                    <tr>
                        <th>Thông tin User</th>
                        <th>Vai trò</th>
                        <th>Trạng thái</th>
                        <th>Ngày tham gia</th>
                        <th style="text-align: right;">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($users)): ?>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div class="avatar"><?php echo strtoupper(substr($user['username'], 0, 1)); ?></div>
                                    <div>
                                        <div style="font-weight: 600;">
                                            <?php echo htmlspecialchars($user['username']); ?>
                                            <?php if($user['id'] == 2): ?>
                                                <span class="badge badge-active ms-2">DEMO USER</span>
                                            <?php endif; ?>
                                        </div>
                                        <div style="font-size: 0.8rem; color: #64748b;"><?php echo htmlspecialchars($user['email']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge <?php echo $user['role'] === 'admin' ? 'badge-role' : 'badge-inactive'; ?>" 
                                      style="background: <?php echo $user['role'] === 'admin' ? '#e0e7ff; color:#4338ca' : '#f1f5f9; color:#64748b'; ?>">
                                    <?php echo strtoupper($user['role']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge <?php echo $user['is_active'] ? 'badge-active' : 'badge-inactive'; ?>">
                                    <?php echo $user['is_active'] ? 'Active' : 'Banned'; ?>
                                </span>
                            </td>
                            <td style="color: #64748b;"><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                            <td style="text-align: right;">
                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                    <button class="btn btn-sm btn-light border" onclick="toggleRole(<?php echo $user['id']; ?>, '<?php echo $user['role']; ?>')">
                                        <i class="fas fa-user-shield text-primary"></i>
                                    </button>
                                    <button class="btn btn-sm btn-light border ms-1" onclick="toggleStatus(<?php echo $user['id']; ?>, <?php echo $user['is_active']; ?>)">
                                        <i class="fas <?php echo $user['is_active'] ? 'fa-ban text-danger' : 'fa-unlock text-success'; ?>"></i>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align:center; padding: 30px;">Không tìm thấy kết quả</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <?php if (isset($totalPages) && $totalPages > 1): ?>
            <div style="padding: 16px; display: flex; justify-content: flex-end; border-top: 1px solid #e2e8f0;">
                <div style="display: flex; gap: 5px;">
                    <?php for($i=1; $i<=$totalPages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>" style="padding: 6px 12px; border: 1px solid #e2e8f0; border-radius: 6px; text-decoration: none; <?php echo ($i == ($currentPage ?? 1)) ? 'background:var(--primary);color:#fff;' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<script>
    async function loginAsUser(userId) {
        if (!confirm('Bạn muốn chuyển sang giao diện của User này?')) return;

        // Lấy CSRF Token để không bị chặn
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        try {
            // [QUAN TRỌNG] Sửa đường dẫn thành /admin/users/api_login_as_user
            const response = await fetch('<?php echo BASE_URL; ?>/admin/users/api_login_as_user', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken // Gửi kèm token bảo mật
                },
                body: JSON.stringify({ user_id: userId })
            });

            const data = await response.json();
            
            if (data.success) {
                // Thành công -> Chuyển hướng
                window.location.href = '<?php echo BASE_URL; ?>/dashboard'; 
            } else {
                alert(data.message || 'Lỗi hệ thống');
            }
        } catch (error) {
            console.error(error);
            alert('Lỗi kết nối Server');
        }
    }
</script>
</body>
</html>