<?php $this->partial('admin_header', ['title' => 'Quản lý người dùng']); ?>

<div class="table-card">
        <div class="admin-card">
            <div class="card-body">
                <div class="d-flex mb-3">
                    <form class="d-flex w-100" method="get" action="<?php echo BASE_URL; ?>/admin/users">
                        <input type="search" name="q" class="form-control me-2" placeholder="Tìm theo username, email hoặc họ tên" value="<?php echo htmlspecialchars($q ?? ''); ?>">
                        <button class="btn btn-primary" type="submit">Tìm kiếm</button>
                    </form>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tên đăng nhập</th>
                                <th>Email</th>
                                <th>Họ và tên</th>
                                <th>Vai trò</th>
                                <th>Trạng thái</th>
                                <th>Ngày tạo</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($users)): ?>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($user['id']); ?></td>
                                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><?php echo htmlspecialchars($user['full_name'] ?? '-'); ?></td>
                                        <td>
                                            <span class="badge <?php echo $user['role'] === 'admin' ? 'badge-admin' : 'badge-user'; ?>">
                                                <?php echo $user['role'] === 'admin' ? 'Admin' : 'User'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo $user['is_active'] ? 'badge-active' : 'badge-inactive'; ?>">
                                                <?php echo $user['is_active'] ? 'Hoạt động' : 'Vô hiệu hóa'; ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?></td>
                                        <td>
                                            <?php if ($user['id'] != 1 && $user['id'] != $_SESSION['user_id']): ?>
                                                <button class="btn btn-sm btn-<?php echo $user['role'] === 'admin' ? 'warning' : 'success'; ?>" 
                                                        onclick="toggleRole(<?php echo $user['id']; ?>, '<?php echo $user['role']; ?>')">
                                                    <?php echo $user['role'] === 'admin' ? 'Hạ xuống User' : 'Thăng lên Admin'; ?>
                                                </button>
                                                <button class="btn btn-sm btn-<?php echo $user['is_active'] ? 'danger' : 'primary'; ?>" 
                                                        onclick="toggleStatus(<?php echo $user['id']; ?>, <?php echo $user['is_active'] ? 0 : 1; ?>)">
                                                    <?php echo $user['is_active'] ? 'Vô hiệu hóa' : 'Kích hoạt'; ?>
                                                </button>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center">Không có người dùng nào</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php if (!empty($total_pages) && $total_pages > 1): ?>
            <nav aria-label="Page navigation" class="mt-3">
                <ul class="pagination">
                    <?php $base = BASE_URL . '/admin/users'; $current = $current_page ?? 1; $tp = $total_pages ?? 1; $qparam = isset($q) && $q !== '' ? '&q=' . urlencode($q) : ''; ?>
                    <li class="page-item <?php echo ($current <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="<?php echo $base . '?page=' . max(1, $current - 1) . $qparam; ?>" aria-label="Previous">Previous</a>
                    </li>

                    <?php for ($i = 1; $i <= $tp; $i++): ?>
                        <li class="page-item <?php echo ($i == $current) ? 'active' : ''; ?>">
                            <a class="page-link" href="<?php echo $base . '?page=' . $i . $qparam; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>

                    <li class="page-item <?php echo ($current >= $tp) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="<?php echo $base . '?page=' . min($tp, $current + 1) . $qparam; ?>" aria-label="Next">Next</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>

        <div class="mt-3">
            <a href="<?php echo BASE_URL; ?>/admin/dashboard" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Quay lại Dashboard
            </a>
        </div>
    </div>

<?php $this->partial('admin_footer'); ?>

<!-- inline scripts (kept for compatibility) -->
<script>
        async function toggleRole(userId, currentRole) {
            const newRole = currentRole === 'admin' ? 'user' : 'admin';
            const confirmMsg = currentRole === 'admin' 
                ? 'Bạn có chắc muốn hạ quyền user này xuống User?'
                : 'Bạn có chắc muốn thăng quyền user này lên Admin?';

            if (!confirm(confirmMsg)) return;

            try {
                const response = await fetch('<?php echo BASE_URL; ?>/admin/api_update_user_role', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ user_id: userId, role: newRole })
                });

                const data = await response.json();
                alert(data.message);
                
                if (data.success) {
                    location.reload();
                }
            } catch (error) {
                alert('Có lỗi xảy ra: ' + error.message);
            }
        }

        async function toggleStatus(userId, newStatus) {
            const confirmMsg = newStatus === 1 
                ? 'Bạn có chắc muốn kích hoạt tài khoản này?'
                : 'Bạn có chắc muốn vô hiệu hóa tài khoản này?';

            if (!confirm(confirmMsg)) return;

            try {
                const response = await fetch('<?php echo BASE_URL; ?>/admin/api_toggle_user_status', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ user_id: userId, is_active: newStatus })
                });

                const data = await response.json();
                alert(data.message);
                
                if (data.success) {
                    location.reload();
                }
            } catch (error) {
                alert('Có lỗi xảy ra: ' + error.message);
            }
        }
    </script>
