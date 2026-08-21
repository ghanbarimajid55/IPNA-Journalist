<?php

class Api_TeamController extends BaseController
{
    private Report $reportModel;

    public function __construct()
    {
        parent::__construct();
        $this->reportModel = new Report();
    }

    public function ranking()
    {
        $auth = new Auth();
        if (!$auth->check()) {
            $this->json(['success' => false, 'message' => 'غير مجاز'], 401);
            return;
        }

        $year = (int)($_GET['year'] ?? date('Y'));
        $month = (int)($_GET['month'] ?? date('n'));

        $ranking = $this->reportModel->getTeamRanking($year, $month);
        $summary = $this->reportModel->getMonthlySummary($year, $month);

        $this->json([
            'success' => true,
            'ranking' => $ranking,
            'summary' => [
                'total_users' => $summary['total_users'],
                'total_score' => $summary['total_score'],
                'total_quantity' => $summary['total_quantity'],
                'avg_score' => $summary['avg_score'],
            ],
            'year' => $year,
            'month' => $month,
        ]);
    }
}
