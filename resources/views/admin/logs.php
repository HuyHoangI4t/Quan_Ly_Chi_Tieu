<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Nhật ký hoạt động</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="<?php echo BASE_URL; ?>/css/admin.css" rel="stylesheet">
</head>
<body>

<div class="admin-wrapper">
    <?php require __DIR__ . '/partials/sidebar.php'; ?>

    <main class="main-content">
        <div class="top-bar">
            <div class="page-title">
                <h1>Nhật ký hệ thống</h1>
                <p>Theo dõi các tác vụ quan trọng của quản trị viên</p>
            </div>
            <div style="display: flex; gap: 10px;">
                <button class="btn-primary" style="background: #fff; color: #64748b; border: 1px solid #e2e8f0;">
                    <i class="fas fa-filter me-2"></i> Lọc
                </button>
                <button class="btn-primary">
                    <i class="fas fa-download me-2"></i> Xuất CSV
                </button>
            </div>
        </div>

        <div class="card-box" style="padding: 0; overflow: hidden;">
            <table class="table-pro">
                <thead>
                    <tr>
                        <th>Người thực hiện</th>
                        <th>Hành động</th>
                        <th>Chi tiết (Target)</th>
                        <th>Địa chỉ IP</th>
                        <th style="text-align: right;">Thời gian</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($logs)): ?>
                        <?php foreach ($logs as $log): ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div class="avatar" style="width: 32px; height: 32px; font-size: 0.8rem;">
                                        <?php echo strtoupper(substr($log['username'] ?? '?', 0, 1)); ?>
                                    </div>
                                    <span style="font-weight: 600; font-size: 0.9rem;">
                                        <?php echo htmlspecialchars($log['username'] ?? 'Unknown'); ?>
                                    </span>
                                </div>
                            </td>
                            <td>
                                <?php 
                                    // Tạo màu badge dựa trên hành động
                                    $action = strtolower($log['action']);
                                    $badgeClass = 'badge-role'; // Mặc định màu xanh dương
                                    if (strpos($action, 'delete') !== false) $badgeClass = 'badge-inactive'; // Đỏ
                                    if (strpos($action, 'create') !== false) $badgeClass = 'badge-active';   // Xanh lá
                                    if (strpos($action, 'update') !== false) $badgeClass = 'badge-role';     // Xanh dương
                                ?>
                                <span class="badge <?php echo $badgeClass; ?>">
                                    <?php echo htmlspecialchars($log['action']); ?>
                                </span>
                            </td>
                            <td style="color: #64748b; font-family: monospace;">
                                ID: <?php echo htmlspecialchars($log['target_id']); ?>
                            </td>
                            <td style="color: #64748b;">
                                <?php echo htmlspecialchars($log['ip_address']); ?>
                            </td>
                            <td style="text-align: right; color: #64748b;">
                                <?php echo date('H:i d/m/Y', strtotime($log['created_at'])); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 40px; color: #94a3b8;">
                                <i class="fas fa-clipboard-list" style="font-size: 2rem; margin-bottom: 10px; display: block;"></i>
                                Chưa có nhật ký nào được ghi lại
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if (isset($totalPages) && $totalPages > 1): ?>
            <div style="padding: 16px; display: flex; justify-content: flex-end; border-top: 1px solid #e2e8f0;">
                <div style="display: flex; gap: 5px;">
                    <?php for($i=1; $i<=$totalPages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>" 
                           style="padding: 6px 12px; border-radius: 6px; text-decoration: none; border: 1px solid #e2e8f0; font-size: 0.85rem;
                                  <?php echo ($i == ($currentPage ?? 1)) ? 'background:var(--primary); color:#fff;' : 'background:#fff; color:#333;'; ?>">
                           <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>

</body>
</html>