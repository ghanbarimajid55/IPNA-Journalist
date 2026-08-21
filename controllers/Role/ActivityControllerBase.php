<?php

abstract class Role_ActivityControllerBase extends BaseController
{
    protected Activity $activityModel;
    protected ActivityType $activityTypeModel;

    public function __construct()
    {
        parent::__construct();
        $this->activityModel = new Activity();
        $this->activityTypeModel = new ActivityType();
    }

    public function index()
    {
        $auth = new Auth();
        if (!$auth->requireRole($this->roleName(), 'admin')) {
            return;
        }

        $currentUser = $auth->user();
        $dateFrom = $_GET['date_from'] ?? '';
        $dateTo = $_GET['date_to'] ?? '';

        $activities = $this->activityModel->findByUser(
            $currentUser['id'],
            $dateFrom !== '' ? $dateFrom : null,
            $dateTo !== '' ? $dateTo : null
        );

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            $this->json(['success' => true, 'activities' => $activities]);
            return;
        }

        $this->view($this->viewPrefix() . '.index', [
            'activities' => $activities,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
    }

    public function create()
    {
        $auth = new Auth();
        if (!$auth->requireRole($this->roleName(), 'admin')) {
            return;
        }

        $activityTypes = $this->activityTypeModel->getByCategory('news');
        $allTypes = $this->activityTypeModel->findAll();

        $this->view($this->viewPrefix() . '.create', [
            'activityTypes' => $allTypes,
            'today' => date('Y-m-d'),
        ]);
    }

    public function store()
    {
        $auth = new Auth();
        if (!$auth->requireRole($this->roleName(), 'admin')) {
            return;
        }

        $currentUser = $auth->user();
        $data = $this->getInput();

        $errors = $this->validate($data, [
            'activity_type_id' => ['required'],
            'quantity'         => ['required', 'numeric'],
            'title'            => ['required'],
        ]);

        if (!empty($errors)) {
            $this->json(['success' => false, 'errors' => $errors], 422);
            return;
        }

        $quantity = max(1, (int)$data['quantity']);
        $activityTypeId = (int)$data['activity_type_id'];

        $activityType = $this->activityTypeModel->findById($activityTypeId);
        if (!$activityType) {
            $this->json(['success' => false, 'message' => 'نوع فعالیت نامعتبر است'], 422);
            return;
        }

        $activityDate = $data['activity_date'] ?? date('Y-m-d');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $activityDate)) {
            $activityDate = date('Y-m-d');
        }

        $score = $this->activityModel->calculateScore($activityTypeId, $quantity);

        $db = Database::getInstance();
        $id = $db->insert('activities', [
            'user_id'       => $currentUser['id'],
            'activity_date' => $activityDate,
            'activity_type' => $activityTypeId,
            'quantity'      => $quantity,
            'title'         => $data['title'],
            'link'          => $data['link'] ?? null,
            'mission'       => !empty($data['mission']) ? 1 : 0,
            'description'   => $data['description'] ?? null,
            'score'         => $score,
            'status'        => 'active',
            'created_at'    => date('Y-m-d H:i:s'),
            'updated_at'    => date('Y-m-d H:i:s'),
        ]);

        Helper::auditLog('create', 'activity', (int)$id, null, [
            'activity_type' => $activityTypeId,
            'quantity' => $quantity,
            'score' => $score,
        ]);

        $this->json([
            'success' => true,
            'message' => 'فعالیت با موفقیت ثبت شد',
            'id' => (int)$id,
            'score' => $score,
        ], 201);
    }

    public function edit($id)
    {
        $auth = new Auth();
        if (!$auth->requireRole($this->roleName(), 'admin')) {
            return;
        }

        $currentUser = $auth->user();
        $id = (int)$id;

        $activity = $this->activityModel->findById($id);
        if (!$activity) {
            Helper::flash('error', 'فعالیت یافت نشد');
            $this->redirect($this->basePath());
            return;
        }

        if ((int)$activity['user_id'] !== $currentUser['id'] && $currentUser['role'] !== 'admin') {
            Helper::flash('error', 'دسترسی غیرمجاز');
            $this->redirect($this->basePath());
            return;
        }

        $allTypes = $this->activityTypeModel->findAll();

        $this->view($this->viewPrefix() . '.edit', [
            'activity' => $activity,
            'activityTypes' => $allTypes,
        ]);
    }

    public function update($id)
    {
        $auth = new Auth();
        if (!$auth->requireRole($this->roleName(), 'admin')) {
            return;
        }

        $currentUser = $auth->user();
        $id = (int)$id;

        $activity = $this->activityModel->findById($id);
        if (!$activity) {
            $this->json(['success' => false, 'message' => 'فعالیت یافت نشد'], 404);
            return;
        }

        if ((int)$activity['user_id'] !== $currentUser['id'] && $currentUser['role'] !== 'admin') {
            $this->json(['success' => false, 'message' => 'دسترسی غیرمجاز'], 403);
            return;
        }

        $data = $this->getInput();

        $updateData = [];
        if (isset($data['quantity'])) {
            $updateData['quantity'] = max(1, (int)$data['quantity']);
        }
        if (isset($data['activity_type_id'])) {
            $updateData['activity_type_id'] = (int)$data['activity_type_id'];
        }
        if (isset($data['title'])) {
            $updateData['title'] = $data['title'];
        }
        if (array_key_exists('link', $data)) {
            $updateData['link'] = $data['link'] !== '' ? $data['link'] : null;
        }
        if (isset($data['mission'])) {
            $updateData['mission'] = $data['mission'] ? 1 : 0;
        }
        if (array_key_exists('description', $data)) {
            $updateData['description'] = $data['description'] !== '' ? $data['description'] : null;
        }

        $activityTypeId = $updateData['activity_type_id'] ?? (int)$activity['activity_type'];
        $quantity = $updateData['quantity'] ?? (int)$activity['quantity'];
        $updateData['score'] = $this->activityModel->calculateScore($activityTypeId, $quantity);

        $this->activityModel->update($id, $updateData);

        Helper::auditLog('update', 'activity', $id, null, $updateData);

        $this->json([
            'success' => true,
            'message' => 'فعالیت با موفقیت به‌روزرسانی شد',
            'score' => $updateData['score'],
        ]);
    }

    public function delete($id)
    {
        $auth = new Auth();
        if (!$auth->requireRole($this->roleName(), 'admin')) {
            return;
        }

        $currentUser = $auth->user();
        $id = (int)$id;

        $activity = $this->activityModel->findById($id);
        if (!$activity) {
            $this->json(['success' => false, 'message' => 'فعالیت یافت نشد'], 404);
            return;
        }

        if ((int)$activity['user_id'] !== $currentUser['id'] && $currentUser['role'] !== 'admin') {
            $this->json(['success' => false, 'message' => 'دسترسی غیرمجاز'], 403);
            return;
        }

        $this->activityModel->delete($id);

        Helper::auditLog('soft_delete', 'activity', $id);

        $this->json(['success' => true, 'message' => 'فعالیت حذف شد']);
    }
}
