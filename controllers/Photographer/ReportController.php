<?php

class Photographer_ReportController extends BaseController
{
    private Activity $activityModel;
    private MonthlyTarget $targetModel;

    public function __construct()
    {
        parent::__construct();
        $this->activityModel = new Activity();
        $this->targetModel = new MonthlyTarget();
    }

    public function weekly()
    {
        $auth = new Auth();
        if (!$auth->requireRole('photographer', 'admin')) {
            return;
        }

        $currentUser = $auth->user();
        $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('monday this week'));
        $endDate = date('Y-m-d', strtotime($startDate . ' +6 days'));

        $activities = $this->activityModel->findByUser($currentUser['id'], $startDate, $endDate);

        $dailyStats = [];
        $totalScore = 0;
        $totalCount = 0;
        foreach ($activities as $activity) {
            $day = date('Y-m-d', strtotime($activity['created_at']));
            if (!isset($dailyStats[$day])) {
                $dailyStats[$day] = [
                    'date' => $day,
                    'activities' => [],
                    'total_score' => 0,
                    'total_quantity' => 0,
                ];
            }
            $dailyStats[$day]['activities'][] = $activity;
            $dailyStats[$day]['total_score'] += (float)$activity['score'];
            $dailyStats[$day]['total_quantity'] += (int)$activity['quantity'];
            $totalScore += (float)$activity['score'];
            $totalCount += (int)$activity['quantity'];
        }

        ksort($dailyStats);

        $this->view('photographer.reports.weekly', [
            'userId' => $currentUser['id'],
            'startDate' => $startDate,
            'endDate' => $endDate,
            'dailyStats' => array_values($dailyStats),
            'totalScore' => $totalScore,
            'totalCount' => $totalCount,
            'activityCount' => count($activities),
        ]);
    }

    public function monthly()
    {
        $auth = new Auth();
        if (!$auth->requireRole('photographer', 'admin')) {
            return;
        }

        $currentUser = $auth->user();
        $year = (int)($_GET['year'] ?? date('Y'));
        $month = (int)($_GET['month'] ?? date('n'));

        $userStats = $this->activityModel->getUserStats($currentUser['id'], $year, $month);
        $achievement = $this->targetModel->getAchievement($currentUser['id'], $year, $month);

        $totalScore = 0;
        $totalCount = 0;
        foreach ($userStats as $stat) {
            $totalScore += (float)$stat['total_score'];
            $totalCount += (int)$stat['total_quantity'];
        }

        $this->view('photographer.reports.monthly', [
            'userId' => $currentUser['id'],
            'year' => $year,
            'month' => $month,
            'stats' => $userStats,
            'achievement' => $achievement,
            'totalScore' => $totalScore,
            'totalCount' => $totalCount,
        ]);
    }
}
