<?php
class AuthController extends BaseController {
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $input = $this->getInput();
            $username = trim($input['username'] ?? '');
            $password = $input['password'] ?? '';
            
            if (empty($username) || empty($password)) {
                Helper::flash('error', 'نام کاربری و رمز عبور را وارد کنید');
                $this->redirect('/login');
                return;
            }
            
            $ip = $_SERVER['REMOTE_ADDR'];
            if (Auth::isRateLimited($ip)) {
                Helper::flash('error', 'تلاش‌های زیاد. لطفاً ۱۵ دقیقه صبر کنید');
                $this->redirect('/login');
                return;
            }
            
            if (Auth::login($username, $password)) {
                $role = Auth::role();
                if ($role === 'admin') {
                    $this->redirect('/admin');
                } elseif ($role === 'photographer') {
                    $this->redirect('/photographer/activities');
                } else {
                    $this->redirect('/reporter/activities');
                }
            } else {
                Helper::flash('error', 'نام کاربری یا رمز عبور اشتباه است');
                $this->redirect('/login');
            }
        } else {
            if (Auth::check()) {
                $role = Auth::role();
                if ($role === 'admin') $this->redirect('/admin');
                elseif ($role === 'photographer') $this->redirect('/photographer/activities');
                else $this->redirect('/reporter/activities');
                return;
            }
            require BASE_PATH . '/views/auth/login.php';
        }
    }
    
    public function logout() {
        Auth::logout();
        $this->redirect('/login');
    }
}
