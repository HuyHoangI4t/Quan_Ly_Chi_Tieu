<?php
class Profile extends Controllers
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
        $userId = $this->getCurrentUserId();
        $userModel = $this->model('User');
        $user = $userModel ? $userModel->getUserById($userId) : null;

        $this->view->set('title', 'Hồ sơ - SmartSpending');
        $this->view->set('user', $user);
        $this->view->render('profile');
    }
}
