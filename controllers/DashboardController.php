<?php
class DashboardController extends BaseController {
    public function index() {
        if (!Auth::check()) {
            $this->redirect('/login');
            return;
        }

        $role = Auth::role();
        if ($role === 'admin') {
            $this->redirect('/admin');
        } elseif ($role === 'photographer') {
            $this->redirect('/photographer/activities');
        } else {
            $this->redirect('/reporter/activities');
        }
    }
}
