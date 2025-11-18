<?php
class Budgets extends Controllers
{
    public function __construct()
    {
        parent::__construct();
        if (!$this->isLoggedIn()) {
            $this->redirect('/login_signup');
        }
    }

    public function index()
    {
        $this->view->set('title', 'Ngân sách - SmartSpending');
        // Sample budgets data
        $budgets = [
            ['category' => 'Ăn uống', 'limit' => 1000000, 'spent' => 282000],
            ['category' => 'Di chuyển', 'limit' => 750000, 'spent' => 231000],
            ['category' => 'Mua sắm', 'limit' => 800000, 'spent' => 284000],
            ['category' => 'Tiền điện nước', 'limit' => 1200000, 'spent' => 212000],
        ];

        // calculate remaining and progress
        foreach ($budgets as &$b) {
            $b['remaining'] = $b['limit'] - $b['spent'];
            $b['progress'] = $b['limit'] > 0 ? round(($b['spent'] / $b['limit']) * 100, 1) : 0;
        }

        $this->view->set('budgets', $budgets);
        $this->view->render('budgets/index');
    }
}
