<?php
class Admin_TargetController extends BaseController {

    // Target columns that exist in the monthly_targets table (database/schema.sql)
    private const TARGET_FIELDS = [
        'news_target', 'exclusive_news_target', 'interview_target', 'special_report_target',
        'photo_target', 'gallery_target', 'video_target', 'score_target',
    ];

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

        $updateData = $this->extractTargetFields($input);

        if ($existing) {
            if (!empty($updateData)) {
                $targetModel->update($existing['id'], $updateData);
            }
            Helper::auditLog('target_updated', 'monthly_target', $existing['id'], $existing, $updateData);
        } else {
            $targetModel->create(array_merge($updateData, [
                'user_id' => (int)$userId,
                'year' => $year,
                'month' => $month,
            ]));
            Helper::auditLog('target_created', 'monthly_target', null, null,
                array_merge($updateData, ['user_id' => (int)$userId, 'year' => $year, 'month' => $month]));
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
            $updateData = $this->extractTargetFields(is_array($t) ? $t : []);

            if ($existing) {
                if (!empty($updateData)) {
                    $targetModel->update($existing['id'], $updateData);
                }
            } else {
                $targetModel->create(array_merge($updateData, [
                    'user_id' => $userId,
                    'year' => $year,
                    'month' => $month,
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

        $targetModel = new MonthlyTarget();
        $existing = $targetModel->findByUser((int)$input['user_id'], $year, $month);

        $data = array_merge($this->extractTargetFields($input), [
            'user_id' => (int)$input['user_id'],
            'year' => $year,
            'month' => $month,
        ]);

        $targetId = $targetModel->create($data);

        $action = $existing ? 'target_updated' : 'target_created';
        Helper::auditLog($action, 'monthly_target', $targetId, $existing, $data);

        if ($this->isAjax()) {
            $this->json(['success' => true, 'message' => 'هدف با موفقیت ذخیره شد', 'id' => $targetId]);
            return;
        }

        Helper::flash('success', 'هدف با موفقیت ذخیره شد');
        $this->redirect("/admin/targets?year={$year}&month={$month}");
    }

    private function extractTargetFields(array $input): array {
        $data = [];
        foreach (self::TARGET_FIELDS as $field) {
            if (isset($input[$field])) {
                $data[$field] = max(0, (int)$input[$field]);
            }
        }
        return $data;
    }

    protected function isAjax(): bool {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}
