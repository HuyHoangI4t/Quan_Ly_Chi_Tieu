<?php
class Reports extends Controllers
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
        $this->view->set('title', 'Báo cáo - SmartSpending');

        // Sample data for reports charts
        $line = [
            'labels' => ['Th1','Th2','Th3','Th4','Th5','Th6'],
            'income' => [12000,15000,13000,16000,17000,19000],
            'expense' => [9000,10000,11000,12000,12500,13000]
        ];

        $pie = [
            'labels' => ['Ăn uống','Di chuyển','Giải trí','Mua sắm','Khác'],
            'data' => [26.1,22.8,16.3,13.1,21.7]
        ];

        $this->view->set('reportLine', $line);
        $this->view->set('reportPie', $pie);
        $this->view->render('reports/index');
    }
}
