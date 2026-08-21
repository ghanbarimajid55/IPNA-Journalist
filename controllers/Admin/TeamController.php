<?php
class Admin_TeamController extends BaseController {
    public function index() {
        if (!Middleware::admin()) return;

        $userModel = new User();
        $activityModel = new Activity();
        $targetModel = new MonthlyTarget();

        $year = (int)($_GET['year'] ?? date('Y'));
        $month = (int)($_GET['month'] ?? date('n'));

        $role = $_GET['role'] ?? '';
        $filters = ['status' => 'active'];
        if (in_array($role, ['reporter', 'photographer'])) {
            $filters['role'] = $role;
        } else {
            $filters['role'] = 'reporter';
        }

        $members = $userModel->findAll($filters, 'name');

        $teamData = [];
        foreach ($members as $member) {
            $stats = $activityModel->getUserStats($member['id'], $year, $month);
            $totalScore = 0;
            $totalQuantity = 0;
            foreach ($stats as $s) {
                $totalScore += (float)$s['total_score'];
                $totalQuantity += (int)$s['total_quantity'];
            }

            $target = $targetModel->findByUser($member['id'], $year, $month);
            $achievement = $target ? ($target['target_score'] > 0 ? round(($totalScore / $target['target_score']) * 100, 1) : null) : null;

            $teamData[] = array_merge($member, [
                'total_score' => $totalScore,
                'total_quantity' => $totalQuantity,
                'target_score' => $target['target_score'] ?? null,
                'achievement' => $achievement,
            ]);
        }

        $data = [
            'members' => $teamData,
            'year' => $year,
            'month' => $month,
            'role' => $filters['role'],
        ];

        if ($this->isAjax()) {
            $this->json($data);
            return;
        }

        $this->view('admin.team.index', $data);
    }

    public function create() {
        if (!Middleware::admin()) return;

        $this->view('admin.team.create');
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
            $this->redirect('/admin/team/create');
            return;
        }

        $validRoles = ['reporter', 'photographer'];
        if (!in_array($input['role'], $validRoles)) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'errors' => ['role' => 'نقش نامعتبر است']], 422);
                return;
            }
            Helper::flash('error', 'نقش نامعتبر است');
            $this->redirect('/admin/team/create');
            return;
        }

        $userModel = new User();
        $existing = $userModel->findByUsername($input['username']);
        if ($existing) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'errors' => ['username' => 'نام کاربری تکراری است']], 422);
                return;
            }
            Helper::flash('error', 'نام کاربری تکراری است');
            $this->redirect('/admin/team/create');
            return;
        }

        if (!empty($input['email'])) {
            $existingEmail = $userModel->findByEmail($input['email']);
            if ($existingEmail) {
                if ($this->isAjax()) {
                    $this->json(['success' => false, 'errors' => ['email' => 'ایمیل تکراری است']], 422);
                    return;
                }
                Helper::flash('error', 'ایمیل تکراری است');
                $this->redirect('/admin/team/create');
                return;
            }
        }

        $userId = $userModel->create([
            'name' => trim($input['name']),
            'username' => trim($input['username']),
            'email' => trim($input['email']),
            'password' => $input['password'],
            'role' => $input['role'],
            'department' => $input['department'] ?? null,
            'phone' => $input['phone'] ?? null,
            'status' => 'active',
        ]);

        Helper::auditLog('team_member_created', 'user', $userId, null, ['name' => $input['name'], 'role' => $input['role']]);

        if ($this->isAjax()) {
            $this->json(['success' => true, 'message' => 'عضو تیم با موفقیت ایجاد شد', 'id' => $userId]);
            return;
        }

        Helper::flash('success', 'عضو تیم با موفقیت ایجاد شد');
        $this->redirect('/admin/team');
    }

    public function edit($id) {
        if (!Middleware::admin()) return;

        $userModel = new User();
        $user = $userModel->findById($id);
        if (!$user) {
            Helper::flash('error', 'عضو تیم یافت نشد');
            $this->redirect('/admin/team');
            return;
        }

        $data = ['member' => $user];

        if ($this->isAjax()) {
            $this->json($data);
            return;
        }

        $this->view('admin.team.edit', $data);
    }

    public function update($id) {
        if (!Middleware::admin()) return;

        $userModel = new User();
        $existing = $userModel->findById($id);
        if (!$existing) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'message' => 'عضو تیم یافت نشد'], 404);
                return;
            }
            Helper::flash('error', 'عضو تیم یافت نشد');
            $this->redirect('/admin/team');
            return;
        }

        $input = $this->getInput();

        $updateData = [];
        if (isset($input['name'])) $updateData['name'] = trim($input['name']);
        if (isset($input['email'])) $updateData['email'] = trim($input['email']);
        if (isset($input['role'])) $updateData['role'] = $input['role'];
        if (isset($input['department'])) $updateData['department'] = $input['department'];
        if (isset($input['phone'])) $updateData['phone'] = $input['phone'];
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
                $this->redirect("/admin/team/{$id}/edit");
                return;
            }
        }

        if (!empty($updateData)) {
            $userModel->update($id, $updateData);
            Helper::auditLog('team_member_updated', 'user', $id, $existing, $updateData);
        }

        if ($this->isAjax()) {
            $this->json(['success' => true, 'message' => 'عضو تیم با موفقیت به‌روزرسانی شد']);
            return;
        }

        Helper::flash('success', 'عضو تیم با موفقیت به‌روزرسانی شد');
        $this->redirect('/admin/team');
    }

    public function delete($id) {
        if (!Middleware::admin()) return;

        $userModel = new User();
        $existing = $userModel->findById($id);
        if (!$existing) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'message' => 'عضو تیم یافت نشد'], 404);
                return;
            }
            Helper::flash('error', 'عضو تیم یافت نشد');
            $this->redirect('/admin/team');
            return;
        }

        $currentUser = (new Auth())->user();
        if ($currentUser && (int)$currentUser['id'] === (int)$id) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'message' => 'نمی‌توانید خود را حذف کنید'], 400);
                return;
            }
            Helper::flash('error', 'نمی‌توانید خود را حذف کنید');
            $this->redirect('/admin/team');
            return;
        }

        $userModel->delete($id);
        Helper::auditLog('team_member_deleted', 'user', $id, $existing, null);

        if ($this->isAjax()) {
            $this->json(['success' => true, 'message' => 'عضو تیم با موفقیت حذف شد']);
            return;
        }

        Helper::flash('success', 'عضو تیم با موفقیت حذف شد');
        $this->redirect('/admin/team');
    }

    protected function isAjax(): bool {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}
