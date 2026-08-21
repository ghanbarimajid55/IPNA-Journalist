<?php
class Admin_DashboardController extends BaseController {
    public function index() {
        if (!Middleware::admin()) return;

        $activityModel = new Activity();
        $userModel = new User();
        $targetModel = new MonthlyTarget();
        $reportModel = new Report();

        $today = date('Y-m-d');
        $year = (int)date('Y');
        $month = (int)date('n');
        $weekStart = date('Y-m-d', strtotime('monday this week'));
        $weekEnd = date('Y-m-d', strtotime('sunday this week'));

        $todayStats = $activityModel->getDailyStats($today);
        $dailyProduction = $activityModel->getDailyProduction($today);

        $weeklyStats = $activityModel->getWeeklyStats($weekStart, $weekEnd);

        $monthlyStats = $activityModel->getMonthlyStats($year, $month);
        $monthlyProduction = $activityModel->getMonthlyProduction($year, $month);

        $topPerformers = $activityModel->getTopPerformers($year, $month, 10);

        $activityDistribution = $reportModel->getMonthlyReport($year, $month)['by_type'];

        $dailyProductionChart = $this->getDailyProductionLast30Days();

        $teamTargets = $targetModel->getTeamTargets($year, $month);
        $teamTargetsWithAchievement = [];
        foreach ($teamTargets as $target) {
            $achievement = $targetModel->getAchievement($target['user_id'], $year, $month);
            $teamTargetsWithAchievement[] = array_merge($target, $achievement);
        }

        $monthScore = 0;
        if (!empty($todayStats)) $monthScore = (float)($monthlyStats[0]['total_score'] ?? 0);

        $data = [
            'todayStats' => $todayStats,
            'dailyProduction' => $dailyProduction,
            'weeklyStats' => $weeklyStats,
            'monthlyStats' => $monthlyStats,
            'monthlyProduction' => $monthlyProduction,
            'topPerformers' => $topPerformers,
            'activityDistribution' => $activityDistribution,
            'dailyProductionChart' => $dailyProductionChart,
            'teamTargets' => $teamTargetsWithAchievement,
            'year' => $year,
            'month' => $month,
        ];

        $this->view('admin.dashboard', $data);
    }

    private function getDailyProductionLast30Days(): array {
        $db = Database::getInstance();
        $startDate = date('Y-m-d', strtotime('-29 days'));
        $endDate = date('Y-m-d');

        $stmt = $db->prepare("
            SELECT
                DATE(a.created_at) as date,
                COALESCE(SUM(a.score), 0) as total_score,
                COUNT(*) as total_activities
            FROM activities a
            WHERE DATE(a.activity_date) BETWEEN :start_date AND :end_date
                AND a.status = 'active'
            GROUP BY DATE(a.created_at)
            ORDER BY date
        ");
        $stmt->execute([':start_date' => $startDate, ':end_date' => $endDate]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $dataByDate = [];
        foreach ($results as $row) {
            $dataByDate[$row['date']] = $row;
        }

        $chart = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $chart[] = [
                'date' => $date,
                'total_score' => (float)($dataByDate[$date]['total_score'] ?? 0),
                'total_activities' => (int)($dataByDate[$date]['total_activities'] ?? 0),
            ];
        }

        return $chart;
    }
}
