<?php $this->partial('admin_header', ['title' => 'Nhật ký hoạt động']); ?>

<div class="table-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h5 mb-0"><i class="fas fa-list-alt"></i> Nhật ký hoạt động</h1>
        <a href="<?php echo BASE_URL; ?>/admin/dashboard" class="btn btn-secondary">Quay lại</a>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Người thực hiện</th>
                                <th>Hành động</th>
                                <th>Target ID</th>
                                <th>IP</th>
                                <th>Thời gian</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($logs)): ?>
                                <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($log['id']); ?></td>
                                        <td><?php echo htmlspecialchars($log['username'] ?? ('#' . ($log['user_id'] ?? '-'))); ?></td>
                                        <td><?php echo htmlspecialchars($log['action']); ?></td>
                                        <td><?php echo htmlspecialchars($log['target_id'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($log['ip_address'] ?? '-'); ?></td>
                                        <td><?php echo date('d/m/Y H:i:s', strtotime($log['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="6" class="text-center">Không có nhật ký</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        </div>
    </div>

    <?php if (!empty($total_pages) && $total_pages > 1): ?>
        <nav aria-label="Page navigation" class="mt-3">
            <ul class="pagination">
                <?php $base = BASE_URL . '/admin/logs'; $current = $current_page ?? 1; $tp = $total_pages ?? 1; ?>
                <li class="page-item <?php echo ($current <= 1) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="<?php echo $base . '?page=' . max(1, $current - 1); ?>">Previous</a>
                </li>
                <?php for ($i = 1; $i <= $tp; $i++): ?>
                    <li class="page-item <?php echo ($i == $current) ? 'active' : ''; ?>">
                        <a class="page-link" href="<?php echo $base . '?page=' . $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?php echo ($current >= $tp) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="<?php echo $base . '?page=' . min($tp, $current + 1); ?>">Next</a>
                </li>
            </ul>
        </nav>
    <?php endif; ?>

<?php $this->partial('admin_footer'); ?>
