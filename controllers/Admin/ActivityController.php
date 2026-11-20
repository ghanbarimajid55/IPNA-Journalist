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
        if (!empty($_GET['search'])) $filters['search'] = trim($_GET['search']);

        // Map view/filter key to the model's expected key (activity_type_id -> activity_type)
        $modelFilters = $filters;
        if (isset($modelFilters['activity_type_id'])) {
            $modelFilters['activity_type'] = $modelFilters['activity_type_id'];
            unset($modelFilters['activity_type_id']);
        }

        $total = $activityModel->count($modelFilters);
        $totalPages = max(1, ceil($total / $perPage));

        $allowedSorts = [
            'a.activity_date DESC', 'a.activity_date ASC',
            'a.created_at DESC', 'a.created_at ASC',
            'a.score DESC', 'a.score ASC',
            'a.quantity DESC', 'a.quantity ASC',
        ];
        $orderBy = (!empty($_GET['sort']) && in_array($_GET['sort'], $allowedSorts, true))
            ? $_GET['sort']
            : 'a.activity_date DESC';
        $activities = $activityModel->findAll($modelFilters, $orderBy, $perPage, $offset);

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
            'pagination' => [
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'perPage' => $perPage,
            ],
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
            'title' => ['required'],
        ]);

        if (!empty($errors)) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'errors' => $errors], 422);
                return;
            }
            $this->redirectBackWithInput($input);
            return;
        }

        $quantity = (int)$input['quantity'];
        if ($quantity <= 0) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'errors' => ['quantity' => 'تعداد باید بزرگتر از صفر باشد']], 422);
                return;
            }
            Helper::flash('error', 'تعداد باید بزرگتر از صفر باشد');
            $this->redirectBackWithInput($input);
            return;
        }

        $activityModel = new Activity();
        $activityId = $activityModel->create([
            'user_id' => (int)$input['user_id'],
            'activity_date' => $this->parseActivityDate($input['date'] ?? ''),
            'activity_type' => (int)$input['activity_type_id'],
            'quantity' => $quantity,
            'title' => trim((string)$input['title']),
            'link' => !empty($input['link']) ? trim($input['link']) : null,
            'mission' => !empty($input['is_mission']) ? 1 : 0,
            'description' => !empty($input['description']) ? trim($input['description']) : null,
        ]);

        Helper::auditLog('activity_created', 'activity', (int)$activityId, null, $input);

        if ($this->isAjax()) {
            $this->json(['success' => true, 'message' => 'فعالیت با موفقیت ثبت شد', 'id' => $activityId]);
            return;
        }

        $this->clearOldInput();
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

        // Normalise field names used by the edit view and old() helper.
        $activity['date'] = Helper::jalaliDate($activity['activity_date']);
        $activity['activity_type_id'] = $activity['activity_type'];
        $activity['is_mission'] = $activity['mission'];

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
        if (isset($input['activity_type_id'])) $updateData['activity_type'] = (int)$input['activity_type_id'];
        if (isset($input['quantity'])) $updateData['quantity'] = (int)$input['quantity'];
        if (!empty($input['date'])) $updateData['activity_date'] = $this->parseActivityDate($input['date']);
        if (isset($input['title'])) $updateData['title'] = trim((string)$input['title']);
        if (array_key_exists('link', $input)) $updateData['link'] = $input['link'] !== '' ? trim($input['link']) : null;
        if (isset($input['is_mission']) || !$this->isAjax()) $updateData['mission'] = !empty($input['is_mission']) ? 1 : 0;
        if (array_key_exists('description', $input)) $updateData['description'] = $input['description'] !== '' ? trim($input['description']) : null;

        if (isset($updateData['quantity']) && $updateData['quantity'] <= 0) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'errors' => ['quantity' => 'تعداد باید بزرگتر از صفر باشد']], 422);
                return;
            }
            Helper::flash('error', 'تعداد باید بزرگتر از صفر باشد');
            $this->redirectBackWithInput($input);
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

        $this->clearOldInput();
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

    protected function isAjax(): bool {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Accepts Jalali "Y/m/d" (Persian or ASCII digits) or Gregorian "Y-m-d";
     * returns Gregorian "Y-m-d" (today on invalid/empty input).
     */
    private function parseActivityDate(string $raw): string {
        $raw = trim($raw);
        if ($raw === '') {
            return date('Y-m-d');
        }

        $raw = strtr($raw, [
            '۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9',
            '٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9',
        ]);

        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $raw, $m)) {
            return sprintf('%04d-%02d-%02d', (int)$m[1], (int)$m[2], (int)$m[3]);
        }

        // Jalali Y/m/d
        if (preg_match('/^(\d{4})\/(\d{1,2})\/(\d{1,2})$/', $raw, $m)) {
            [$gy, $gm, $gd] = Helper::jalaliToGregorian((int)$m[1], (int)$m[2], (int)$m[3]);
            return sprintf('%04d-%02d-%02d', $gy, $gm, $gd);
        }

        return date('Y-m-d');
    }
}
