<?php
class Admin_ActivityTypeController extends BaseController {
    public function index() {
        if (!Middleware::admin()) return;

        $activityTypeModel = new ActivityType();

        $status = $_GET['status'] ?? 'active';
        $types = $activityTypeModel->findAll($status);

        $allTypes = $activityTypeModel->findAll('active');
        $inactiveTypes = $activityTypeModel->findAll('inactive');

        $data = [
            'types' => array_merge($types, $inactiveTypes),
            'status' => $status,
        ];

        if ($this->isAjax()) {
            $this->json($data);
            return;
        }

        $this->view('admin.activity-types.index', $data);
    }

    public function store() {
        if (!Middleware::admin()) return;

        $input = $this->getInput();

        $errors = $this->validate($input, [
            'name' => ['required'],
            'category' => ['required'],
            'score_per_unit' => ['required', 'numeric'],
        ]);

        if (!empty($errors)) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'errors' => $errors], 422);
                return;
            }
            Helper::flash('error', 'لطفاً خطاهای فرم را بررسی کنید');
            $this->redirect('/admin/activity-types');
            return;
        }

        $validCategories = ['article', 'photo', 'video', 'social_media', 'general'];
        if (!in_array($input['category'], $validCategories)) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'errors' => ['category' => 'دسته‌بندی نامعتبر است']], 422);
                return;
            }
            Helper::flash('error', 'دسته‌بندی نامعتبر است');
            $this->redirect('/admin/activity-types');
            return;
        }

        $activityTypeModel = new ActivityType();
        $typeId = $activityTypeModel->create([
            'name' => trim($input['name']),
            'category' => $input['category'],
            'score_per_unit' => (float)$input['score_per_unit'],
            'unit_label' => $input['unit_label'] ?? 'عدد',
            'description' => $input['description'] ?? null,
            'status' => 'active',
            'sort_order' => (int)($input['sort_order'] ?? 0),
        ]);

        Helper::auditLog('activity_type_created', 'activity_type', $typeId, null, $input);

        if ($this->isAjax()) {
            $this->json(['success' => true, 'message' => 'نوع فعالیت با موفقیت ایجاد شد', 'id' => $typeId]);
            return;
        }

        Helper::flash('success', 'نوع فعالیت با موفقیت ایجاد شد');
        $this->redirect('/admin/activity-types');
    }

    public function update($id) {
        if (!Middleware::admin()) return;

        $activityTypeModel = new ActivityType();
        $existing = $activityTypeModel->findById($id);
        if (!$existing) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'message' => 'نوع فعالیت یافت نشد'], 404);
                return;
            }
            Helper::flash('error', 'نوع فعالیت یافت نشد');
            $this->redirect('/admin/activity-types');
            return;
        }

        $input = $this->getInput();

        $updateData = [];
        if (isset($input['name'])) $updateData['name'] = trim($input['name']);
        if (isset($input['category'])) $updateData['category'] = $input['category'];
        if (isset($input['score_per_unit'])) $updateData['score_per_unit'] = (float)$input['score_per_unit'];
        if (isset($input['unit_label'])) $updateData['unit_label'] = $input['unit_label'];
        if (array_key_exists('description', $input)) $updateData['description'] = $input['description'];
        if (isset($input['status'])) $updateData['status'] = $input['status'];
        if (isset($input['sort_order'])) $updateData['sort_order'] = (int)$input['sort_order'];

        if (!empty($updateData['category'])) {
            $validCategories = ['article', 'photo', 'video', 'social_media', 'general'];
            if (!in_array($updateData['category'], $validCategories)) {
                if ($this->isAjax()) {
                    $this->json(['success' => false, 'errors' => ['category' => 'دسته‌بندی نامعتبر است']], 422);
                    return;
                }
                Helper::flash('error', 'دسته‌بندی نامعتبر است');
                $this->redirect('/admin/activity-types');
                return;
            }
        }

        if (!empty($updateData)) {
            $activityTypeModel->update($id, $updateData);
            Helper::auditLog('activity_type_updated', 'activity_type', $id, $existing, $updateData);
        }

        if ($this->isAjax()) {
            $this->json(['success' => true, 'message' => 'نوع فعالیت با موفقیت به‌روزرسانی شد']);
            return;
        }

        Helper::flash('success', 'نوع فعالیت با موفقیت به‌روزرسانی شد');
        $this->redirect('/admin/activity-types');
    }

    public function updateScore($id) {
        if (!Middleware::admin()) return;

        $activityTypeModel = new ActivityType();
        $existing = $activityTypeModel->findById($id);
        if (!$existing) {
            $this->json(['success' => false, 'message' => 'نوع فعالیت یافت نشد'], 404);
            return;
        }

        $input = $this->getInput();
        $score = (float)($input['score'] ?? 0);

        $activityTypeModel->update($id, ['score_per_unit' => $score]);
        Helper::auditLog('activity_type_score_updated', 'activity_type', $id, $existing, ['score_per_unit' => $score]);

        $this->json(['success' => true, 'message' => 'امتیاز با موفقیت بروزرسانی شد']);
    }

    public function toggleStatus($id) {
        if (!Middleware::admin()) return;

        $activityTypeModel = new ActivityType();
        $existing = $activityTypeModel->findById($id);
        if (!$existing) {
            $this->json(['success' => false, 'message' => 'نوع فعالیت یافت نشد'], 404);
            return;
        }

        $input = $this->getInput();
        $status = !empty($input['is_active']) ? 'active' : 'inactive';

        $activityTypeModel->update($id, ['status' => $status]);
        Helper::auditLog('activity_type_status_toggled', 'activity_type', $id, $existing, ['status' => $status]);

        $this->json(['success' => true, 'message' => 'وضعیت با موفقیت بروزرسانی شد']);
    }

    public function delete($id) {
        if (!Middleware::admin()) return;

        $activityTypeModel = new ActivityType();
        $existing = $activityTypeModel->findById($id);
        if (!$existing) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'message' => 'نوع فعالیت یافت نشد'], 404);
                return;
            }
            Helper::flash('error', 'نوع فعالیت یافت نشد');
            $this->redirect('/admin/activity-types');
            return;
        }

        $activityTypeModel->update($id, ['status' => 'inactive']);
        Helper::auditLog('activity_type_deactivated', 'activity_type', $id, $existing, ['status' => 'inactive']);

        if ($this->isAjax()) {
            $this->json(['success' => true, 'message' => 'نوع فعالیت غیرفعال شد']);
            return;
        }

        Helper::flash('success', 'نوع فعالیت غیرفعال شد');
        $this->redirect('/admin/activity-types');
    }

    private function isAjax(): bool {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}
