<?php

class Api_DashboardController extends BaseController
{
    private User $userModel;
    private Activity $activityModel;
    private Report $reportModel;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new User();
        $this->activityModel = new Activity();
        $this->reportModel = new Report();
    }

    public function stats()
    {
        $auth = new Auth();
        if (!$auth->check()) {
            $this->json(['success' => false, 'message' => 'غير مجاز'], 401);
            return;
        }

        $currentUser = $auth->user();
        $date = $_GET['date'] ?? date('Y-m-d');

        $userStats = $this->userModel->getDashboardStats($currentUser['id'], $date);
        $dailyProduction = $this->activityModel->getDailyProduction($date);

        $totalTeamScore = 0;
        $totalTeamCount = 0;
        $activeUserCount = 0;

        $dailyReport = $this->reportModel->getDailyReport($date);
        foreach ($dailyReport['users'] as $user) {
            $score = (float)$user['total_score'];
            if ($score > 0) {
                $activeUserCount++;
            }
            $totalTeamScore += $score;
            $totalTeamCount += (int)$user['total_quantity'];
        }

        $this->json([
            'success' => true,
            'user' => $userStats,
            'daily_production' => $dailyProduction,
            'team' => [
                'active_users' => $activeUserCount,
                'total_score' => $totalTeamScore,
                'total_quantity' => $totalTeamCount,
            ],
            'summary' => $dailyReport['summary'],
            'date' => $date,
        ]);
    }
}
