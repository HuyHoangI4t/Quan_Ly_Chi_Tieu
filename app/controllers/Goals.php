<?php
class Goals extends Controllers
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
        $this->view->set('title', 'Mục tiêu - SmartSpending');

        $goals = [
            ['title' => 'Mua Laptop Mới', 'target' => 8000000, 'saved' => 6000000],
            ['title' => 'Quỹ khẩn cấp', 'target' => 10000000, 'saved' => 4000000],
            ['title' => 'Kỳ nghỉ', 'target' => 6000000, 'saved' => 5000000],
        ];

        foreach ($goals as &$g) {
            $g['progress'] = $g['target'] > 0 ? round(($g['saved'] / $g['target']) * 100, 1) : 0;
        }

        $this->view->set('goals', $goals);
        $this->view->render('goals/index');
    }
}
