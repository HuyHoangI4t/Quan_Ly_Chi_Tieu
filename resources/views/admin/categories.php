<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý Danh mục</title>
    <?php use App\Middleware\CsrfProtection; echo CsrfProtection::getTokenMeta(); ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>/css/admin.css" rel="stylesheet">
    
    <style>
        .icon-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(45px, 1fr)); gap: 10px; max-height: 180px; overflow-y: auto; padding: 10px; background: #f8fafc; border-radius: 12px; border: 1px solid #e2e8f0; }
        .icon-option { width: 45px; height: 45px; display: flex; align-items: center; justify-content: center; border-radius: 10px; border: 1px solid #e2e8f0; background: #fff; cursor: pointer; transition: all 0.2s; color: #64748b; font-size: 1.2rem; }
        .icon-option:hover { transform: translateY(-2px); border-color: var(--primary); color: var(--primary); }
        .icon-option.active { background: var(--primary); color: #fff; border-color: var(--primary); box-shadow: 0 4px 10px rgba(79, 70, 229, 0.3); }
        .color-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(40px, 1fr)); gap: 12px; padding: 15px; background: #f8fafc; border-radius: 12px; border: 1px solid #e2e8f0; }
        .color-option { width: 40px; height: 40px; border-radius: 50%; cursor: pointer; transition: transform 0.2s, box-shadow 0.2s; border: 2px solid transparent; }
        .color-option:hover { transform: scale(1.1); }
        .color-option.active { border-color: #0f172a; transform: scale(1.1); box-shadow: 0 0 0 2px #fff, 0 0 0 4px #0f172a; }
    </style>
</head>
<body>

<div class="admin-wrapper">
   <?php require __DIR__ . '/partials/sidebar.php'; ?>

    <main class="main-content">
        <div class="top-bar">
            <div class="page-title">
                <h1>Danh mục hệ thống</h1>
                <p>Cấu hình các danh mục mẫu cho User</p>
            </div>
            <button class="btn btn-primary" onclick="openCreateModal()">
                <i class="fas fa-plus me-2"></i> Thêm mới
            </button>
        </div>

        <div class="row g-4">
            <div class="col-md-6">
                <div class="card-box h-100 p-0 overflow-hidden">
                    <div class="p-3 bg-success-subtle text-success fw-bold border-bottom">
                        <i class="fas fa-arrow-down me-2"></i>Thu nhập
                    </div>
                    <table class="table-pro">
                        <tbody>
                            <?php foreach($categories as $cat): if($cat['type'] !== 'income') continue; ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="avatar" style="background:<?php echo $cat['color']; ?>;color:#fff;">
                                            <i class="fas <?php echo $cat['icon']; ?>"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold"><?php echo htmlspecialchars($cat['name']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-light border" onclick='openEditModal(<?php echo json_encode($cat); ?>)'><i class="fas fa-pen text-primary"></i></button>
                                    <button class="btn btn-sm btn-light border" onclick="deleteCategory(<?php echo $cat['id']; ?>)"><i class="fas fa-trash text-danger"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card-box h-100 p-0 overflow-hidden">
                    <div class="p-3 bg-danger-subtle text-danger fw-bold border-bottom">
                        <i class="fas fa-arrow-up me-2"></i>Chi tiêu
                    </div>
                    <table class="table-pro">
                        <tbody>
                            <?php foreach($categories as $cat): if($cat['type'] !== 'expense') continue; ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="avatar" style="background:<?php echo $cat['color']; ?>;color:#fff;">
                                            <i class="fas <?php echo $cat['icon']; ?>"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold"><?php echo htmlspecialchars($cat['name']); ?></div>
                                            <span class="badge bg-light text-muted border text-uppercase" style="font-size: 0.7rem;">
                                                <?php echo $cat['group_type'] == 'none' ? 'Chưa phân loại' : $cat['group_type']; ?>
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-light border" onclick='openEditModal(<?php echo json_encode($cat); ?>)'><i class="fas fa-pen text-primary"></i></button>
                                    <button class="btn btn-sm btn-light border" onclick="deleteCategory(<?php echo $cat['id']; ?>)"><i class="fas fa-trash text-danger"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<div class="modal fade" id="categoryModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 px-4 pt-4">
                <h5 class="modal-title fw-bold" id="modalTitle">Danh mục</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form id="categoryForm">
                    <input type="hidden" id="cat_id">
                    
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold text-muted">TÊN DANH MỤC</label>
                            <input type="text" class="form-control" id="cat_name" required>
                        </div>
                        <div class="col-3">
                            <label class="form-label small fw-bold text-muted">LOẠI</label>
                            <select class="form-select" id="cat_type" onchange="toggleGroupType()">
                                <option value="expense">Chi tiêu</option>
                                <option value="income">Thu nhập</option>
                            </select>
                        </div>
                        <div class="col-3">
                            <label class="form-label small fw-bold text-muted">HŨ (Jars)</label>
                            <select class="form-select" id="cat_group">
                                <option value="none">Không</option>
                                <option value="nec">NEC (Thiết yếu)</option>
                                <option value="play">PLAY (Hưởng thụ)</option>
                                <option value="edu">EDU (Giáo dục)</option>
                                <option value="ffa">FFA (Tự do)</option>
                                <option value="ltss">LTSS (Dài hạn)</option>
                                <option value="give">GIVE (Cho đi)</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">MÀU SẮC: <span id="color_preview" class="badge"></span></label>
                        <input type="hidden" id="cat_color" value="#3498db">
                        <div class="color-grid" id="colorPicker"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">ICON: <span id="icon_preview"></span></label>
                        <input type="hidden" id="cat_icon" value="fa-circle">
                        <div class="icon-grid" id="iconPicker"></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 px-4 pb-4">
                <button type="button" class="btn btn-primary w-100 rounded-pill" onclick="saveCategory()">Lưu lại</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const colors = ['#ef4444', '#f97316', '#f59e0b', '#84cc16', '#10b981', '#06b6d4', '#3b82f6', '#8b5cf6', '#d946ef', '#f43f5e', '#64748b', '#333333'];
    const icons = ['fa-utensils', 'fa-shopping-cart', 'fa-car', 'fa-home', 'fa-bolt', 'fa-wifi', 'fa-gamepad', 'fa-plane', 'fa-graduation-cap', 'fa-briefcase', 'fa-piggy-bank', 'fa-coins', 'fa-wallet', 'fa-gift', 'fa-heartbeat', 'fa-tshirt', 'fa-pills', 'fa-baby'];

    // Render Color Picker
    const colorGrid = document.getElementById('colorPicker');
    colors.forEach(c => {
        let el = document.createElement('div'); el.className = 'color-option'; el.style.backgroundColor = c;
        el.onclick = () => { 
            document.querySelectorAll('.color-option').forEach(e=>e.classList.remove('active')); 
            el.classList.add('active'); 
            document.getElementById('cat_color').value = c;
            const p = document.getElementById('color_preview'); p.style.background = c; p.innerText = c;
        };
        colorGrid.appendChild(el);
    });

    // Render Icon Picker
    const iconGrid = document.getElementById('iconPicker');
    icons.forEach(i => {
        let el = document.createElement('div'); el.className = 'icon-option'; el.innerHTML = `<i class="fas ${i}"></i>`;
        el.onclick = () => { 
            document.querySelectorAll('.icon-option').forEach(e=>e.classList.remove('active')); 
            el.classList.add('active'); 
            document.getElementById('cat_icon').value = i; 
            document.getElementById('icon_preview').innerHTML = `<i class="fas ${i}"></i>`; 
        };
        iconGrid.appendChild(el);
    });

    function toggleGroupType() {
        const type = document.getElementById('cat_type').value;
        const group = document.getElementById('cat_group');
        if(type === 'income') { group.value = 'none'; group.disabled = true; }
        else { group.disabled = false; }
    }

    function openCreateModal() {
        document.getElementById('modalTitle').innerText = 'Thêm mới';
        document.getElementById('cat_id').value = '';
        document.getElementById('categoryForm').reset();
        document.querySelectorAll('.active').forEach(e => e.classList.remove('active'));
        toggleGroupType();
        new bootstrap.Modal(document.getElementById('categoryModal')).show();
    }

    function openEditModal(cat) {
        document.getElementById('modalTitle').innerText = 'Chỉnh sửa';
        document.getElementById('cat_id').value = cat.id;
        document.getElementById('cat_name').value = cat.name;
        document.getElementById('cat_type').value = cat.type;
        document.getElementById('cat_group').value = cat.group_type || 'none';
        document.getElementById('cat_color').value = cat.color;
        document.getElementById('cat_icon').value = cat.icon;
        
        toggleGroupType();
        new bootstrap.Modal(document.getElementById('categoryModal')).show();
    }

    async function saveCategory() {
        const id = document.getElementById('cat_id').value;
        const url = id ? '<?php echo BASE_URL; ?>/admin/categories/api_update/' + id : '<?php echo BASE_URL; ?>/admin/categories/api_create';
        const body = {
            name: document.getElementById('cat_name').value,
            type: document.getElementById('cat_type').value,
            group_type: document.getElementById('cat_group').value,
            color: document.getElementById('cat_color').value,
            icon: document.getElementById('cat_icon').value
        };

        const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        try {
            const res = await fetch(url, { method: 'POST', headers: {'Content-Type': 'application/json', 'X-CSRF-Token': csrf}, body: JSON.stringify(body) });
            const data = await res.json();
            if(data.success) location.reload(); else alert(data.message);
        } catch(e) { alert('Lỗi kết nối'); }
    }

    async function deleteCategory(id) {
        if(!confirm('Xóa danh mục này?')) return;
        const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        try {
            const res = await fetch('<?php echo BASE_URL; ?>/admin/categories/api_delete/' + id, { method: 'POST', headers: {'X-CSRF-Token': csrf} });
            const data = await res.json();
            if(data.success) location.reload(); else alert(data.message);
        } catch(e) { alert('Lỗi kết nối'); }
    }
</script>
</body>
</html>