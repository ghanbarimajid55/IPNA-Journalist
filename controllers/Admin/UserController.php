<?php
class Admin_UserController extends BaseController {
    public function index() {
        if (!Middleware::admin()) return;

        $userModel = new User();

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 15;
        $offset = ($page - 1) * $perPage;

        $filters = [];
        if (!empty($_GET['role'])) $filters['role'] = $_GET['role'];
        if (!empty($_GET['status'])) $filters['status'] = $_GET['status'];
        if (!empty($_GET['search'])) $filters['search'] = trim($_GET['search']);

        $total = $userModel->count($filters);
        $totalPages = max(1, ceil($total / $perPage));
        $users = $userModel->findAll($filters, 'name', $perPage);
        foreach ($users as &$u) {
            $u['created_at_jalali'] = Helper::jalaliDate($u['created_at']);
        }

        $data = [
            'users' => $users,
            'page' => $page,
            'totalPages' => $totalPages,
            'total' => $total,
            'filters' => $filters,
        ];

        if ($this->isAjax()) {
            $this->json($data);
            return;
        }

        $this->view('admin.users.index', $data);
    }

    public function create() {
        if (!Middleware::admin()) return;

        $this->view('admin.users.create');
    }

    public function store() {
        if (!Middleware::admin()) return;

        $input = $this->getInput();

        $errors = $this->validate($input, [
            'name' => ['required'],
            'username' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'min' => 6],
            'role' => ['required'],
        ]);

        if (!empty($errors)) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'errors' => $errors], 422);
                return;
            }
            Helper::flash('error', 'لطفاً خطاهای فرم را بررسی کنید');
            $this->redirect('/admin/users/create');
            return;
        }

        $userModel = new User();
        $existing = $userModel->findByUsername($input['username']);
        if ($existing) {
            $errors['username'] = 'نام کاربری تکراری است';
            if ($this->isAjax()) {
                $this->json(['success' => false, 'errors' => $errors], 422);
                return;
            }
            Helper::flash('error', 'نام کاربری تکراری است');
            $this->redirect('/admin/users/create');
            return;
        }

        if (!empty($input['email'])) {
            $existingEmail = $userModel->findByEmail($input['email']);
            if ($existingEmail) {
                $errors['email'] = 'ایمیل تکراری است';
                if ($this->isAjax()) {
                    $this->json(['success' => false, 'errors' => $errors], 422);
                    return;
                }
                Helper::flash('error', 'ایمیل تکراری است');
                $this->redirect('/admin/users/create');
                return;
            }
        }

        $validRoles = ['admin', 'reporter', 'photographer'];
        if (!in_array($input['role'], $validRoles)) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'errors' => ['role' => 'نقش نامعتبر است']], 422);
                return;
            }
            Helper::flash('error', 'نقش نامعتبر است');
            $this->redirect('/admin/users/create');
            return;
        }

        $userId = $userModel->create([
            'name'     => trim($input['name']),
            'username' => trim($input['username']),
            'email'    => trim($input['email']),
            'password' => $input['password'],
            'role'     => $input['role'],
            'position' => $input['position'] ?? '',
            'status'   => 'active',
        ]);

        Helper::auditLog('user_created', 'user', $userId, null, ['name' => $input['name'], 'role' => $input['role']]);

        if ($this->isAjax()) {
            $this->json(['success' => true, 'message' => 'کاربر با موفقیت ایجاد شد', 'id' => $userId]);
            return;
        }

        Helper::flash('success', 'کاربر با موفقیت ایجاد شد');
        $this->redirect('/admin/users');
    }

    public function edit($id) {
        if (!Middleware::admin()) return;

        $userModel = new User();
        $user = $userModel->findById($id);
        if (!$user) {
            Helper::flash('error', 'کاربر یافت نشد');
            $this->redirect('/admin/users');
            return;
        }

        $data = ['user' => $user];

        if ($this->isAjax()) {
            $this->json($data);
            return;
        }

        $this->view('admin.users.edit', $data);
    }

    public function update($id) {
        if (!Middleware::admin()) return;

        $userModel = new User();
        $existing = $userModel->findById($id);
        if (!$existing) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'message' => 'کاربر یافت نشد'], 404);
                return;
            }
            Helper::flash('error', 'کاربر یافت نشد');
            $this->redirect('/admin/users');
            return;
        }

        $input = $this->getInput();

        $updateData = [];
        if (isset($input['name'])) $updateData['name'] = trim($input['name']);
        if (isset($input['email'])) $updateData['email'] = trim($input['email']);
        if (isset($input['role'])) $updateData['role'] = $input['role'];
        if (isset($input['position'])) $updateData['position'] = $input['position'];
        if (isset($input['status'])) $updateData['status'] = $input['status'];
        if (!empty($input['password'])) $updateData['password'] = $input['password'];

        if (!empty($updateData['email']) && $updateData['email'] !== $existing['email']) {
            $emailCheck = $userModel->findByEmail($updateData['email']);
            if ($emailCheck) {
                if ($this->isAjax()) {
                    $this->json(['success' => false, 'errors' => ['email' => 'ایمیل تکراری است']], 422);
                    return;
                }
                Helper::flash('error', 'ایمیل تکراری است');
                $this->redirect("/admin/users/{$id}/edit");
                return;
            }
        }

        if (!empty($updateData)) {
            $userModel->update($id, $updateData);
            Helper::auditLog('user_updated', 'user', $id, $existing, $updateData);
        }

        if ($this->isAjax()) {
            $this->json(['success' => true, 'message' => 'کاربر با موفقیت به‌روزرسانی شد']);
            return;
        }

        Helper::flash('success', 'کاربر با موفقیت به‌روزرسانی شد');
        $this->redirect('/admin/users');
    }

    public function delete($id) {
        if (!Middleware::admin()) return;

        $userModel = new User();
        $existing = $userModel->findById($id);
        if (!$existing) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'message' => 'کاربر یافت نشد'], 404);
                return;
            }
            Helper::flash('error', 'کاربر یافت نشد');
            $this->redirect('/admin/users');
            return;
        }

        $currentUser = Auth::user();
        if ($currentUser && (int)$currentUser['id'] === (int)$id) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'message' => 'نمی‌توانید حساب خود را حذف کنید'], 400);
                return;
            }
            Helper::flash('error', 'نمی‌توانید حساب خود را حذف کنید');
            $this->redirect('/admin/users');
            return;
        }

        $userModel->delete($id);
        Helper::auditLog('user_deleted', 'user', $id, $existing, null);

        if ($this->isAjax()) {
            $this->json(['success' => true, 'message' => 'کاربر با موفقیت حذف شد']);
            return;
        }

        Helper::flash('success', 'کاربر با موفقیت حذف شد');
        $this->redirect('/admin/users');
    }

    private function isAjax(): bool {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}
