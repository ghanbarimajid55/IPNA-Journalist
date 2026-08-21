<?php

class Photographer_TargetController extends BaseController
{
    private MonthlyTarget $targetModel;
    private Activity $activityModel;

    public function __construct()
    {
        parent::__construct();
        $this->targetModel = new MonthlyTarget();
        $this->activityModel = new Activity();
    }

    public function index()
    {
        $auth = new Auth();
        if (!$auth->requireRole('photographer', 'admin')) {
            return;
        }

        $currentUser = $auth->user();
        $year = (int)($_GET['year'] ?? date('Y'));
        $month = (int)($_GET['month'] ?? date('n'));

        $achievement = $this->targetModel->getAchievement($currentUser['id'], $year, $month);
        $userStats = $this->activityModel->getUserStats($currentUser['id'], $year, $month);

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            $this->json([
                'success' => true,
                'achievement' => $achievement,
                'stats' => $userStats,
            ]);
            return;
        }

        $this->view('photographer.targets.index', [
            'achievement' => $achievement,
            'stats' => $userStats,
            'year' => $year,
            'month' => $month,
        ]);
    }
}
