<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý Danh mục</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>/css/admin.css" rel="stylesheet">
    
    <style>
        /* CSS CHO ICON PICKER */
        .icon-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(45px, 1fr));
            gap: 10px;
            max-height: 180px;
            overflow-y: auto;
            padding: 10px;
            background: #f8fafc;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
        }
        .icon-option {
            width: 45px; height: 45px;
            display: flex; align-items: center; justify-content: center;
            border-radius: 10px; border: 1px solid #e2e8f0;
            background: #fff; cursor: pointer; transition: all 0.2s;
            color: #64748b; font-size: 1.2rem;
        }
        .icon-option:hover { transform: translateY(-2px); border-color: var(--primary); color: var(--primary); }
        .icon-option.active { background: var(--primary); color: #fff; border-color: var(--primary); box-shadow: 0 4px 10px rgba(79, 70, 229, 0.3); }

        /* CSS CHO COLOR PICKER */
        .color-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(40px, 1fr));
            gap: 12px;
            padding: 15px;
            background: #f8fafc;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
        }
        .color-option {
            width: 40px; height: 40px;
            border-radius: 50%;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            border: 2px solid transparent;
        }
        .color-option:hover { transform: scale(1.1); }
        .color-option.active {
            border-color: #0f172a; /* Viền đen khi chọn */
            transform: scale(1.1);
            box-shadow: 0 0 0 2px #fff, 0 0 0 4px #0f172a; /* Hiệu ứng vòng tròn bao quanh */
        }
        
        /* Scrollbar đẹp */
        .icon-grid::-webkit-scrollbar { width: 6px; }
        .icon-grid::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
    </style>
</head>
<body>

<div class="admin-wrapper">
    
   <?php require __DIR__ . '/partials/sidebar.php'; ?>


    <main class="main-content">
        <?php use App\Middleware\CsrfProtection; echo CsrfProtection::getTokenMeta(); ?>

        <div class="top-bar">
            <div class="page-title">
                <h1>Danh mục hệ thống</h1>
                <p>Phân loại các khoản Thu - Chi mặc định</p>
            </div>
            <button class="btn-primary" onclick="openCreateModal()">
                <i class="fas fa-plus me-2"></i> Thêm mới
            </button>
        </div>

        <?php 
            $incomeCats = array_filter($categories, fn($c) => $c['type'] === 'income');
            $expenseCats = array_filter($categories, fn($c) => $c['type'] === 'expense');
        ?>

        <div class="row g-4">
            <div class="col-md-6">
                <div class="card-box h-100 p-0 overflow-hidden">
                    <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-success-subtle">
                        <h6 class="fw-bold mb-0 text-success"><i class="fas fa-arrow-down me-2"></i>Danh mục Thu nhập</h6>
                        <span class="badge bg-success rounded-pill"><?php echo count($incomeCats); ?></span>
                    </div>
                    <table class="table-pro">
                        <thead>
                            <tr>
                                <th>Tên</th>
                                <th>Icon & Màu</th>
                                <th class="text-end">Sửa/Xóa</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!empty($incomeCats)): foreach($incomeCats as $cat): ?>
                            <tr>
                                <td><div class="fw-bold text-dark"><?php echo htmlspecialchars($cat['name']); ?></div></td>
                                <td>
                                    <div class="avatar" style="background:<?php echo htmlspecialchars($cat['color']); ?>; color:#fff;">
                                        <i class="fas <?php echo htmlspecialchars($cat['icon']); ?>"></i>
                                    </div>
                                </td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-light border" onclick='openEditModal(<?php echo json_encode($cat); ?>)'><i class="fas fa-pen text-primary"></i></button>
                                    <button class="btn btn-sm btn-light border ms-1" onclick="deleteCategory(<?php echo $cat['id']; ?>)"><i class="fas fa-trash text-danger"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; else: ?>
                                <tr><td colspan="3" class="text-center py-4 text-muted">Chưa có danh mục</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card-box h-100 p-0 overflow-hidden">
                    <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-danger-subtle">
                        <h6 class="fw-bold mb-0 text-danger"><i class="fas fa-arrow-up me-2"></i>Danh mục Chi tiêu</h6>
                        <span class="badge bg-danger rounded-pill"><?php echo count($expenseCats); ?></span>
                    </div>
                    <table class="table-pro">
                        <thead>
                            <tr>
                                <th>Tên</th>
                                <th>Icon & Màu</th>
                                <th class="text-end">Sửa/Xóa</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!empty($expenseCats)): foreach($expenseCats as $cat): ?>
                            <tr>
                                <td><div class="fw-bold text-dark"><?php echo htmlspecialchars($cat['name']); ?></div></td>
                                <td>
                                    <div class="avatar" style="background:<?php echo htmlspecialchars($cat['color']); ?>; color:#fff;">
                                        <i class="fas <?php echo htmlspecialchars($cat['icon']); ?>"></i>
                                    </div>
                                </td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-light border" onclick='openEditModal(<?php echo json_encode($cat); ?>)'><i class="fas fa-pen text-primary"></i></button>
                                    <button class="btn btn-sm btn-light border ms-1" onclick="deleteCategory(<?php echo $cat['id']; ?>)"><i class="fas fa-trash text-danger"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; else: ?>
                                <tr><td colspan="3" class="text-center py-4 text-muted">Chưa có danh mục</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<div class="modal fade" id="categoryModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header border-0 px-4 pt-4">
                <h5 class="modal-title fw-bold" id="modalTitle">Thêm Danh mục</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form id="categoryForm">
                    <input type="hidden" id="category_id">
                    
                    <div class="row g-3 mb-3">
                        <div class="col-8">
                            <label class="form-label small fw-bold text-muted">TÊN DANH MỤC</label>
                            <input type="text" class="form-control bg-light border-0 rounded-3 p-3" id="category_name" required placeholder="Ví dụ: Ăn uống...">
                        </div>
                        <div class="col-4">
                            <label class="form-label small fw-bold text-muted">LOẠI</label>
                            <select class="form-select bg-light border-0 rounded-3 p-3" id="category_type">
                                <option value="expense">Chi tiêu</option>
                                <option value="income">Thu nhập</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted d-flex justify-content-between">
                            MÀU SẮC
                            <span id="selected_color_preview" class="badge rounded-pill" style="background: #3498db">#3498db</span>
                        </label>
                        <input type="hidden" id="category_color" value="#3498db">
                        
                        <div class="color-grid" id="colorPickerGrid">
                            </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted d-flex justify-content-between">
                            BIỂU TƯỢNG
                            <span id="selected_icon_display" class="badge bg-secondary">Chưa chọn</span>
                        </label>
                        <input type="hidden" id="category_icon" value="fa-circle">
                        
                        <div class="icon-grid" id="iconPickerGrid">
                            </div>
                    </div>

                </form>
            </div>
            <div class="modal-footer border-0 px-4 pb-4">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-primary rounded-pill px-4" onclick="saveCategory()" style="background: var(--primary); border:none;">Lưu lại</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // 1. DANH SÁCH MÀU ĐẸP (Palette)
    const presetColors = [
        '#ef4444', '#f97316', '#f59e0b', '#eab308', '#84cc16', 
        '#22c55e', '#10b981', '#14b8a6', '#06b6d4', '#0ea5e9', 
        '#3b82f6', '#6366f1', '#8b5cf6', '#a855f7', '#d946ef', 
        '#ec4899', '#f43f5e', '#64748b'
    ];

    // 2. DANH SÁCH ICON
    const availableIcons = [
        'fa-utensils', 'fa-shopping-cart', 'fa-car', 'fa-home', 'fa-bolt', 
        'fa-mobile-alt', 'fa-wifi', 'fa-gamepad', 'fa-plane', 'fa-tshirt', 
        'fa-graduation-cap', 'fa-pills', 'fa-baby', 'fa-paw', 'fa-tools', 
        'fa-gift', 'fa-coffee', 'fa-beer', 'fa-film', 'fa-music',
        'fa-wallet', 'fa-money-bill', 'fa-briefcase', 'fa-chart-line', 'fa-piggy-bank', 
        'fa-hand-holding-usd', 'fa-landmark', 'fa-coins', 'fa-credit-card', 'fa-university'
    ];

    // -- RENDER COLOR PICKER --
    function renderColorPicker(selectedColor = '#3498db') {
        const grid = document.getElementById('colorPickerGrid');
        grid.innerHTML = ''; 
        presetColors.forEach(color => {
            const div = document.createElement('div');
            div.className = `color-option ${color === selectedColor ? 'active' : ''}`;
            div.style.backgroundColor = color;
            div.onclick = function() {
                document.querySelectorAll('.color-option').forEach(el => el.classList.remove('active'));
                this.classList.add('active');
                document.getElementById('category_color').value = color;
                // Cập nhật preview text
                const badge = document.getElementById('selected_color_preview');
                badge.style.background = color;
                badge.innerText = color;
            };
            grid.appendChild(div);
        });
        // Set initial preview
        const badge = document.getElementById('selected_color_preview');
        badge.style.background = selectedColor;
        badge.innerText = selectedColor;
    }

    // -- RENDER ICON PICKER --
    function renderIconPicker(selectedIcon = 'fa-circle') {
        const grid = document.getElementById('iconPickerGrid');
        grid.innerHTML = '';
        availableIcons.forEach(icon => {
            const div = document.createElement('div');
            div.className = `icon-option ${icon === selectedIcon ? 'active' : ''}`;
            div.innerHTML = `<i class="fas ${icon}"></i>`;
            div.onclick = function() {
                document.querySelectorAll('.icon-option').forEach(el => el.classList.remove('active'));
                this.classList.add('active');
                document.getElementById('category_icon').value = icon;
                document.getElementById('selected_icon_display').innerHTML = `<i class="fas ${icon} me-1"></i> ${icon}`;
            };
            grid.appendChild(div);
        });
        document.getElementById('selected_icon_display').innerHTML = `<i class="fas ${selectedIcon} me-1"></i> ${selectedIcon}`;
    }

    // -- MODAL ACTIONS --
    function openCreateModal() {
        document.getElementById('modalTitle').innerText = 'Thêm Danh mục Mới';
        document.getElementById('categoryForm').reset();
        document.getElementById('category_id').value = '';
        
        // Mặc định
        const defaultColor = '#3b82f6';
        const defaultIcon = 'fa-circle';
        
        document.getElementById('category_color').value = defaultColor;
        document.getElementById('category_icon').value = defaultIcon;
        
        renderColorPicker(defaultColor);
        renderIconPicker(defaultIcon);
        
        new bootstrap.Modal(document.getElementById('categoryModal')).show();
    }

    function openEditModal(category) {
        document.getElementById('modalTitle').innerText = 'Chỉnh sửa Danh mục';
        document.getElementById('category_id').value = category.id;
        document.getElementById('category_name').value = category.name;
        document.getElementById('category_type').value = category.type;
        document.getElementById('category_color').value = category.color;
        document.getElementById('category_icon').value = category.icon;
        
        renderColorPicker(category.color);
        renderIconPicker(category.icon);
        
        new bootstrap.Modal(document.getElementById('categoryModal')).show();
    }

    async function saveCategory() {
        const id = document.getElementById('category_id').value;
        const name = document.getElementById('category_name').value;
        const type = document.getElementById('category_type').value;
        const color = document.getElementById('category_color').value;
        const icon = document.getElementById('category_icon').value;

        if (!name) { alert('Vui lòng nhập tên danh mục'); return; }

        const url = id 
            ? '<?php echo BASE_URL; ?>/admin/categories/api_update/' + id 
            : '<?php echo BASE_URL; ?>/admin/categories/api_create';
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
                body: JSON.stringify({ name, type, color, icon })
            });
            const data = await response.json();
            if (data.success) location.reload(); else alert(data.message || 'Lỗi');
        } catch (error) { alert('Lỗi kết nối'); }
    }

    async function deleteCategory(id) {
        if (!confirm('Xóa danh mục này?')) return;
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        try {
            const response = await fetch('<?php echo BASE_URL; ?>/admin/categories/api_delete/' + id, {
                method: 'POST', headers: { 'X-CSRF-Token': csrfToken }
            });
            const data = await response.json();
            if (data.success) location.reload(); else alert(data.message);
        } catch (e) { alert('Lỗi kết nối'); }
    }
</script>

</body>
</html>