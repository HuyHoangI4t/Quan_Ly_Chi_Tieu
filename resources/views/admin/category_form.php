<?php $this->partial('admin_header', ['title' => $title ?? 'Danh mục']); ?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3><?php echo isset($category) ? 'Chỉnh sửa danh mục' : 'Thêm danh mục'; ?></h3>
        <div><a href="<?php echo BASE_URL; ?>/admin/categories" class="btn btn-outline-secondary">Quay lại</a></div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="post" action="<?php echo BASE_URL; ?>/admin/categories/save">
                <?php if (!empty($category)): ?><input type="hidden" name="id" value="<?php echo $category['id']; ?>"><?php endif; ?>
                <div class="mb-3">
                    <label class="form-label">Tên</label>
                    <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($category['name'] ?? ''); ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Loại</label>
                    <select name="type" class="form-select">
                        <option value="expense" <?php echo (!empty($category) && $category['type']=='expense')? 'selected':''; ?>>Chi</option>
                        <option value="income" <?php echo (!empty($category) && $category['type']=='income')? 'selected':''; ?>>Thu</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Nhóm (needs/wants/savings)</label>
                    <input type="text" name="group_type" class="form-control" value="<?php echo htmlspecialchars($category['group_type'] ?? 'needs'); ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Màu</label>
                    <input type="color" name="color" class="form-control form-control-color" value="<?php echo htmlspecialchars($category['color'] ?? '#3498db'); ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Icon (FontAwesome)</label>
                    <input type="text" name="icon" class="form-control" value="<?php echo htmlspecialchars($category['icon'] ?? 'fa-circle'); ?>">
                </div>
                <button class="btn btn-primary">Lưu</button>
            </form>
        </div>
    </div>
</div>

<?php $this->partial('admin_footer'); ?>
