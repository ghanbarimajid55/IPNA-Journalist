<?php
class Admin_ActivityController extends BaseController {
    public function index() {
        if (!Middleware::admin()) return;

        $activityModel = new Activity();
        $userModel = new User();
        $activityTypeModel = new ActivityType();

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        $filters = [];
        if (!empty($_GET['user_id'])) $filters['user_id'] = (int)$_GET['user_id'];
        if (!empty($_GET['activity_type_id'])) $filters['activity_type_id'] = (int)$_GET['activity_type_id'];
        if (!empty($_GET['date_from'])) $filters['date_from'] = $_GET['date_from'];
        if (!empty($_GET['date_to'])) $filters['date_to'] = $_GET['date_to'];
        if (!empty($_GET['category'])) $filters['category'] = $_GET['category'];

        $total = $activityModel->count($filters);
        $totalPages = max(1, ceil($total / $perPage));

        $orderBy = $_GET['sort'] ?? 'created_at DESC';
        $activities = $activityModel->findAll($filters, $orderBy, $perPage);

        foreach ($activities as &$a) {
            $a['created_at_jalali'] = Helper::jalaliDate(substr($a['created_at'], 0, 10));
        }

        $users = $userModel->getTeamMembers();
        $activityTypes = $activityTypeModel->findAll('active');

        $data = [
            'activities' => $activities,
            'users' => $users,
            'activityTypes' => $activityTypes,
            'page' => $page,
            'totalPages' => $totalPages,
            'total' => $total,
            'filters' => $filters,
        ];

        if ($this->isAjax()) {
            $this->json($data);
            return;
        }

        $this->view('admin.activities.index', $data);
    }

    public function create() {
        if (!Middleware::admin()) return;

        $userModel = new User();
        $activityTypeModel = new ActivityType();

        $data = [
            'users' => $userModel->getTeamMembers(),
            'activityTypes' => $activityTypeModel->findAll('active'),
        ];

        if ($this->isAjax()) {
            $this->json($data);
            return;
        }

        $this->view('admin.activities.create', $data);
    }

    public function store() {
        if (!Middleware::admin()) return;

        $input = $this->getInput();

        $errors = $this->validate($input, [
            'user_id' => ['required', 'numeric'],
            'activity_type_id' => ['required', 'numeric'],
            'quantity' => ['required', 'numeric'],
        ]);

        if (!empty($errors)) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'errors' => $errors], 422);
                return;
            }
            Helper::flash('error', 'لطفاً خطاهای فرم را بررسی کنید');
            $this->redirect('/admin/activities/create');
            return;
        }

        $quantity = (int)$input['quantity'];
        if ($quantity <= 0) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'errors' => ['quantity' => 'تعداد باید بزرگتر از صفر باشد']], 422);
                return;
            }
            Helper::flash('error', 'تعداد باید بزرگتر از صفر باشد');
            $this->redirect('/admin/activities/create');
            return;
        }

        $currentUser = (new Auth())->user();

        $activityModel = new Activity();
        $activityId = $activityModel->create([
            'user_id' => (int)$input['user_id'],
            'activity_type_id' => (int)$input['activity_type_id'],
            'quantity' => $quantity,
            'description' => $input['description'] ?? null,
            'source_url' => $input['source_url'] ?? null,
            'created_by' => $currentUser['id'] ?? null,
        ]);

        Helper::auditLog('activity_created', 'activity', $activityId, null, $input);

        if ($this->isAjax()) {
            $this->json(['success' => true, 'message' => 'فعالیت با موفقیت ثبت شد', 'id' => $activityId]);
            return;
        }

        Helper::flash('success', 'فعالیت با موفقیت ثبت شد');
        $this->redirect('/admin/activities');
    }

    public function edit($id) {
        if (!Middleware::admin()) return;

        $activityModel = new Activity();
        $userModel = new User();
        $activityTypeModel = new ActivityType();

        $activity = $activityModel->findById($id);
        if (!$activity) {
            Helper::flash('error', 'فعالیت یافت نشد');
            $this->redirect('/admin/activities');
            return;
        }

        $data = [
            'activity' => $activity,
            'users' => $userModel->getTeamMembers(),
            'activityTypes' => $activityTypeModel->findAll('active'),
        ];

        if ($this->isAjax()) {
            $this->json($data);
            return;
        }

        $this->view('admin.activities.edit', $data);
    }

    public function update($id) {
        if (!Middleware::admin()) return;

        $activityModel = new Activity();
        $existing = $activityModel->findById($id);
        if (!$existing) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'message' => 'فعالیت یافت نشد'], 404);
                return;
            }
            Helper::flash('error', 'فعالیت یافت نشد');
            $this->redirect('/admin/activities');
            return;
        }

        $input = $this->getInput();

        $updateData = [];
        if (isset($input['user_id'])) $updateData['user_id'] = (int)$input['user_id'];
        if (isset($input['activity_type_id'])) $updateData['activity_type_id'] = (int)$input['activity_type_id'];
        if (isset($input['quantity'])) $updateData['quantity'] = (int)$input['quantity'];
        if (array_key_exists('description', $input)) $updateData['description'] = $input['description'];
        if (array_key_exists('source_url', $input)) $updateData['source_url'] = $input['source_url'];

        if (isset($updateData['quantity']) && $updateData['quantity'] <= 0) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'errors' => ['quantity' => 'تعداد باید بزرگتر از صفر باشد']], 422);
                return;
            }
            Helper::flash('error', 'تعداد باید بزرگتر از صفر باشد');
            $this->redirect("/admin/activities/{$id}/edit");
            return;
        }

        if (!empty($updateData)) {
            $activityModel->update($id, $updateData);
            Helper::auditLog('activity_updated', 'activity', $id, $existing, $updateData);
        }

        if ($this->isAjax()) {
            $this->json(['success' => true, 'message' => 'فعالیت با موفقیت به‌روزرسانی شد']);
            return;
        }

        Helper::flash('success', 'فعالیت با موفقیت به‌روزرسانی شد');
        $this->redirect('/admin/activities');
    }

    public function delete($id) {
        if (!Middleware::admin()) return;

        $activityModel = new Activity();
        $existing = $activityModel->findById($id);
        if (!$existing) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'message' => 'فعالیت یافت نشد'], 404);
                return;
            }
            Helper::flash('error', 'فعالیت یافت نشد');
            $this->redirect('/admin/activities');
            return;
        }

        $activityModel->delete($id);
        Helper::auditLog('activity_deleted', 'activity', $id, $existing, null);

        if ($this->isAjax()) {
            $this->json(['success' => true, 'message' => 'فعالیت با موفقیت حذف شد']);
            return;
        }

        Helper::flash('success', 'فعالیت با موفقیت حذف شد');
        $this->redirect('/admin/activities');
    }

    private function isAjax(): bool {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}
