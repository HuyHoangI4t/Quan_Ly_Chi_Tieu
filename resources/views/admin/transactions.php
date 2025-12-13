<?php $this->partial('admin_header', ['title' => $title ?? 'Quản lý giao dịch']); ?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Quản lý giao dịch</h3>
        <div>
            <a href="<?php echo BASE_URL; ?>/admin/transactions/new" class="btn btn-primary">Thêm giao dịch</a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="get" class="row g-2">
                <div class="col-md-4"><input name="q" placeholder="Tìm kiếm..." value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>" class="form-control"></div>
                <div class="col-md-3">
                    <select name="category_id" class="form-select">
                        <option value="">Tất cả danh mục</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?php echo $c['id']; ?>" <?php echo (isset($_GET['category_id']) && $_GET['category_id']==$c['id'])? 'selected':''; ?>><?php echo htmlspecialchars($c['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="type" class="form-select">
                        <option value="">Loại</option>
                        <option value="income" <?php echo (isset($_GET['type']) && $_GET['type']=='income')? 'selected':''; ?>>Thu</option>
                        <option value="expense" <?php echo (isset($_GET['type']) && $_GET['type']=='expense')? 'selected':''; ?>>Chi</option>
                    </select>
                </div>
                <div class="col-md-3"><button class="btn btn-secondary">Lọc</button></div>
            </form>
        </div>
    </div>

    <div class="card table-card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Ngày</th>
                            <th>Người dùng</th>
                            <th>Danh mục</th>
                            <th>Mô tả</th>
                            <th class="text-end">Số tiền</th>
                            <th>Loại</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($transactions)): foreach ($transactions as $t): ?>
                            <tr>
                                <td><?php echo $t['id']; ?></td>
                                <td><?php echo date('d/m/Y', strtotime($t['date'])); ?></td>
                                <td><?php echo htmlspecialchars($t['username'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($t['category_name'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($t['description']); ?></td>
                                <td class="text-end"><?php echo number_format($t['amount'],0,',','.'); ?>đ</td>
                                <td><?php echo ucfirst($t['type']); ?></td>
                                <td class="text-end">
                                    <a href="<?php echo BASE_URL; ?>/admin/transactions/edit/<?php echo $t['id']; ?>" class="btn btn-sm btn-outline-primary">Sửa</a>
                                    <a href="<?php echo BASE_URL; ?>/admin/transactions/delete/<?php echo $t['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Xác nhận xóa?')">Xóa</a>
                                </td>
                            </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="8" class="text-center">Không có giao dịch</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Pagination -->
    <nav aria-label="Page navigation">
        <ul class="pagination">
            <?php for ($p=1;$p<=$total_pages;$p++): ?>
                <li class="page-item <?php echo ($p==$current_page)?'active':''; ?>"><a class="page-link" href="?page=<?php echo $p; ?>"><?php echo $p; ?></a></li>
            <?php endfor; ?>
        </ul>
    </nav>

</div>

<?php $this->partial('admin_footer'); ?>
