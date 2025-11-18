<?php

class Dashboard extends Controllers
{
    public function index()
    {
        // Check if user is logged in
        // if (!$this->isLoggedIn()) {
        //     $this->redirect('/login_signup');
        // }

        $userId = $this->getCurrentUserId();
        $userModel = $this->model('User');
        $user = $userModel->getUserById($userId);

        $this->view->set('title', 'Tổng quan - Quản Lý Chi Tiêu');
        $this->view->set('user', $user);

        // Sample calculated data for home
        $totals = [
            'balance' => 45320000,
            'income' => 25800000,
            'expense' => 18600000,
            'savingsRate' => 28 // percent
        ];

        // Recent transactions sample
        $recentTransactions = [
            ['title' => 'Đăng ký Netflix', 'category' => 'Giải trí', 'date' => '10/10/2025', 'amount' => -260000],
            ['title' => 'Lương tháng 10', 'category' => 'Thu nhập', 'date' => '07/10/2025', 'amount' => 25000000],
            ['title' => 'GrabFood', 'category' => 'Ăn uống', 'date' => '05/10/2025', 'amount' => -150000],
            ['title' => 'GrabBike đi làm', 'category' => 'Di chuyển', 'date' => '03/10/2025', 'amount' => -35000],
            ['title' => 'Dự án Freelance', 'category' => 'Thu nhập', 'date' => '02/10/2025', 'amount' => 5000000],
        ];

        $this->view->set('totals', $totals);
        $this->view->set('recentTransactions', $recentTransactions);

        $this->view->render('dashboard/index');
    }

    public function about()
    {
        $this->view->set('title', 'About - Quan Ly Chi Tieu');
        $this->view->render('home/about');
    }
}
