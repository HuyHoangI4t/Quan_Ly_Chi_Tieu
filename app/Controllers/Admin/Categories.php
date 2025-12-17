<?php
namespace App\Controllers\Admin;

use App\Core\Controllers;
use App\Core\Response;
use App\Middleware\AuthCheck;
use App\Middleware\CsrfProtection;

class Categories extends Controllers
{
    private $categoryModel;

    public function __construct()
    {
        parent::__construct();
        AuthCheck::requireAdmin();
        $this->categoryModel = $this->model('Category');
    }

    public function index()
    {
        $categories = $this->categoryModel->getAll(null);
        // Dữ liệu giả cho stats để tránh lỗi view
        $stats = ['total_users' => 0, 'active_users' => 0, 'total_transactions' => 0]; 
        
        $data = [
            'title' => 'Quản lý Danh mục Gốc',
            'categories' => $categories,
            'stats' => $stats
        ];
        
        $this->view('admin/categories', $data);
    }

    public function api_create()
    {
        if ($this->request->method() !== 'POST') {
            Response::errorResponse('Method Not Allowed', null, 405);
            return;
        }

        CsrfProtection::verify();

        try {
            $data = $this->request->json();

            if (empty($data['name'])) {
                Response::errorResponse('Tên danh mục không được để trống', null, 400);
                return;
            }

            $categoryData = [
                'name' => $data['name'],
                'type' => $data['type'],
                'group_type' => $data['group_type'] ?? 'none', // Lấy group_type
                'color' => $data['color'] ?? '#3498db',
                'icon' => $data['icon'] ?? 'fa-circle'
            ];

            // Gọi model create với userId = null (Admin)
            $categoryId = $this->categoryModel->create(null, $categoryData);

            if ($categoryId) {
                Response::successResponse('Tạo danh mục thành công', ['category_id' => $categoryId]);
            } else {
                Response::errorResponse('Lỗi DB: Không thể tạo danh mục', null, 500);
            }
        } catch (\Exception $e) {
            Response::errorResponse('Lỗi Server: ' . $e->getMessage(), null, 500);
        }
    }

    public function api_update($id)
    {
        if ($this->request->method() !== 'POST') {
            Response::errorResponse('Method Not Allowed', null, 405);
            return;
        }

        CsrfProtection::verify();

        try {
            $data = $this->request->json();

            $categoryData = [
                'name' => $data['name'],
                'type' => $data['type'],
                'group_type' => $data['group_type'] ?? 'none',
                'color' => $data['color'],
                'icon' => $data['icon']
            ];

            $result = $this->categoryModel->update($id, null, $categoryData);

            if ($result) {
                Response::successResponse('Cập nhật danh mục thành công');
            } else {
                Response::errorResponse('Không thể cập nhật danh mục', null, 500);
            }
        } catch (\Exception $e) {
            Response::errorResponse('Lỗi Server: ' . $e->getMessage(), null, 500);
        }
    }

    public function api_delete($id)
    {
        if ($this->request->method() !== 'POST') {
            Response::errorResponse('Method Not Allowed', null, 405);
            return;
        }
        CsrfProtection::verify();

        $result = $this->categoryModel->delete($id, null);

        if ($result === true) {
            Response::successResponse('Xóa danh mục thành công');
        } else {
            $msg = is_string($result) ? $result : 'Lỗi không xác định';
            Response::errorResponse($msg, null, 400);
        }
    }
}