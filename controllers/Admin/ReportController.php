<?php

class Admin_ReportController extends BaseController
{
    private User $userModel;
    private Activity $activityModel;
    private ActivityType $activityTypeModel;
    private MonthlyTarget $targetModel;
    private Report $reportModel;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new User();
        $this->activityModel = new Activity();
        $this->activityTypeModel = new ActivityType();
        $this->targetModel = new MonthlyTarget();
        $this->reportModel = new Report();
    }

    public function daily()
    {
        $auth = new Auth();
        if (!$auth->requireRole('admin')) {
            return;
        }

        $date = $_GET['date'] ?? date('Y-m-d');
        $filters = [];
        if (!empty($_GET['user_id'])) {
            $filters['user_id'] = (int)$_GET['user_id'];
        }
        if (!empty($_GET['role'])) {
            $filters['role'] = $_GET['role'];
        }
        if (!empty($_GET['activity_type_id'])) {
            $filters['activity_type_id'] = (int)$_GET['activity_type_id'];
        }

        $report = $this->reportModel->getDailyReport($date, $filters);
        $users = $this->userModel->getTeamMembers();
        $activityTypes = $this->activityTypeModel->findAll();

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            $this->json([
                'success' => true,
                'report' => $report,
            ]);
            return;
        }

        $this->view('admin.reports.daily', [
            'report' => $report,
            'date' => $date,
            'users' => $users,
            'activityTypes' => $activityTypes,
            'filters' => $filters,
        ]);
    }

    public function weekly()
    {
        $auth = new Auth();
        if (!$auth->requireRole('admin')) {
            return;
        }

        $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('monday this week'));
        $endDate = $_GET['end_date'] ?? date('Y-m-d', strtotime('sunday this week'));
        $filters = [];
        if (!empty($_GET['role'])) {
            $filters['role'] = $_GET['role'];
        }

        $report = $this->reportModel->getWeeklyReport($startDate, $endDate, $filters);
        $users = $this->userModel->getTeamMembers();

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            $this->json([
                'success' => true,
                'report' => $report,
            ]);
            return;
        }

        $this->view('admin.reports.weekly', [
            'report' => $report,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'users' => $users,
            'filters' => $filters,
        ]);
    }

    public function monthly()
    {
        $auth = new Auth();
        if (!$auth->requireRole('admin')) {
            return;
        }

        $year = (int)($_GET['year'] ?? date('Y'));
        $month = (int)($_GET['month'] ?? date('n'));

        $report = $this->reportModel->getMonthlyReport($year, $month);
        $summary = $this->reportModel->getMonthlySummary($year, $month);
        $ranking = $this->reportModel->getTeamRanking($year, $month);

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            $this->json([
                'success' => true,
                'report' => $report,
                'summary' => $summary,
                'ranking' => $ranking,
            ]);
            return;
        }

        $this->view('admin.reports.monthly', [
            'report' => $report,
            'summary' => $summary,
            'ranking' => $ranking,
            'year' => $year,
            'month' => $month,
        ]);
    }
}
