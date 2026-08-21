<?php
class Admin_TargetController extends BaseController {
    public function index() {
        if (!Middleware::admin()) return;

        $targetModel = new MonthlyTarget();
        $userModel = new User();

        $year = (int)($_GET['year'] ?? date('Y'));
        $month = (int)($_GET['month'] ?? date('n'));

        $teamTargets = $targetModel->getTeamTargets($year, $month);
        $teamMembers = $userModel->getTeamMembers();

        $existingTargetUserIds = array_column($teamTargets, 'user_id');
        $membersWithoutTarget = array_filter($teamMembers, function ($m) use ($existingTargetUserIds) {
            return !in_array($m['id'], $existingTargetUserIds);
        });

        $data = [
            'targets' => $teamTargets,
            'members' => $teamMembers,
            'membersWithoutTarget' => array_values($membersWithoutTarget),
            'year' => $year,
            'month' => $month,
        ];

        if ($this->isAjax()) {
            $this->json($data);
            return;
        }

        $this->view('admin.targets.index', $data);
    }

    public function updateTarget($userId) {
        if (!Middleware::admin()) return;

        $input = $this->getInput();
        $year = (int)($_GET['year'] ?? date('Y'));
        $month = (int)($_GET['month'] ?? date('n'));

        $targetModel = new MonthlyTarget();
        $existing = $targetModel->findByUser((int)$userId, $year, $month);

        $updateData = [
            'target_score' => (float)($input['target_score'] ?? 0),
        ];
        if (isset($input['target_articles'])) $updateData['target_articles'] = (int)$input['target_articles'];
        if (isset($input['target_photos'])) $updateData['target_photos'] = (int)$input['target_photos'];
        if (isset($input['notes'])) $updateData['notes'] = $input['notes'];

        if ($existing) {
            $targetModel->update($existing['id'], $updateData);
            Helper::auditLog('target_updated', 'monthly_target', $existing['id'], $existing, $updateData);
        } else {
            $targetModel->create(array_merge($updateData, [
                'user_id' => (int)$userId,
                'year' => $year,
                'month' => $month,
                'created_by' => (new Auth())->user()['id'] ?? null,
            ]));
            Helper::auditLog('target_created', 'monthly_target', null, null, $updateData);
        }

        $this->json(['success' => true, 'message' => 'هدف ذخیره شد']);
    }

    public function bulkUpdate() {
        if (!Middleware::admin()) return;

        $input = $this->getInput();
        $targets = $input['targets'] ?? [];
        $year = (int)($_GET['year'] ?? date('Y'));
        $month = (int)($_GET['month'] ?? date('n'));

        $targetModel = new MonthlyTarget();

        foreach ($targets as $t) {
            $userId = (int)($t['user_id'] ?? 0);
            if (!$userId) continue;

            $existing = $targetModel->findByUser($userId, $year, $month);

            $updateData = [
                'target_score' => (float)($t['target_score'] ?? 0),
            ];
            if (isset($t['target_articles'])) $updateData['target_articles'] = (int)$t['target_articles'];
            if (isset($t['target_photos'])) $updateData['target_photos'] = (int)$t['target_photos'];
            if (isset($t['notes'])) $updateData['notes'] = $t['notes'];

            if ($existing) {
                $targetModel->update($existing['id'], $updateData);
            } else {
                $targetModel->create(array_merge($updateData, [
                    'user_id' => $userId,
                    'year' => $year,
                    'month' => $month,
                    'created_by' => (new Auth())->user()['id'] ?? null,
                ]));
            }
        }

        $this->json(['success' => true, 'message' => 'تمام اهداف ذخیره شد']);
    }

    public function store() {
        if (!Middleware::admin()) return;

        $input = $this->getInput();

        $errors = $this->validate($input, [
            'user_id' => ['required', 'numeric'],
            'year' => ['required', 'numeric'],
            'month' => ['required', 'numeric'],
            'target_score' => ['required', 'numeric'],
        ]);

        if (!empty($errors)) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'errors' => $errors], 422);
                return;
            }
            Helper::flash('error', 'لطفاً خطاهای فرم را بررسی کنید');
            $this->redirect('/admin/targets');
            return;
        }

        $year = (int)$input['year'];
        $month = (int)$input['month'];
        if ($month < 1 || $month > 12) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'errors' => ['month' => 'ماه نامعتبر است']], 422);
                return;
            }
            Helper::flash('error', 'ماه نامعتبر است');
            $this->redirect('/admin/targets');
            return;
        }

        $currentUser = (new Auth())->user();

        $targetModel = new MonthlyTarget();
        $existing = $targetModel->findByUser((int)$input['user_id'], $year, $month);

        $targetId = $targetModel->create([
            'user_id' => (int)$input['user_id'],
            'year' => $year,
            'month' => $month,
            'target_score' => (float)$input['target_score'],
            'target_articles' => !empty($input['target_articles']) ? (int)$input['target_articles'] : null,
            'target_photos' => !empty($input['target_photos']) ? (int)$input['target_photos'] : null,
            'notes' => $input['notes'] ?? null,
            'created_by' => $currentUser['id'] ?? null,
        ]);

        $action = $existing ? 'target_updated' : 'target_created';
        Helper::auditLog($action, 'monthly_target', $targetId, $existing, $input);

        if ($this->isAjax()) {
            $this->json(['success' => true, 'message' => 'هدف با موفقیت ذخیره شد', 'id' => $targetId]);
            return;
        }

        Helper::flash('success', 'هدف با موفقیت ذخیره شد');
        $this->redirect("/admin/targets?year={$year}&month={$month}");
    }
}
