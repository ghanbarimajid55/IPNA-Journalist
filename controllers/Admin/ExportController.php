<?php

class Admin_ExportController extends BaseController
{
    private Report $reportModel;

    public function __construct()
    {
        parent::__construct();
        $this->reportModel = new Report();
    }

    public function export($type = '')
    {
        $auth = new Auth();
        if (!$auth->requireRole('admin')) {
            return;
        }

        if ($type === '') {
            $type = $_GET['type'] ?? '';
        }

        if ($type === '') {
            $this->json(['success' => false, 'message' => 'نوع گزارش مشخص نشده است'], 400);
            return;
        }

        if (strpos($type, 'daily-') === 0) {
            $date = substr($type, 6);
            $this->exportDaily($date);
        } elseif (strpos($type, 'weekly-') === 0) {
            $parts = explode('-', substr($type, 7));
            if (count($parts) >= 2) {
                $this->exportWeekly($parts[0] . '-' . $parts[1] . '-' . $parts[2], $parts[3] . '-' . $parts[4] . '-' . $parts[5]);
            } else {
                $this->json(['success' => false, 'message' => 'فرمت تاریخ نامعتبر'], 400);
            }
        } elseif (strpos($type, 'monthly-') === 0) {
            $parts = explode('-', substr($type, 8));
            if (count($parts) >= 2) {
                $this->exportMonthly((int)$parts[0], (int)$parts[1]);
            } else {
                $this->json(['success' => false, 'message' => 'فرمت تاریخ نامعتبر'], 400);
            }
        } elseif (strpos($type, 'ranking-') === 0) {
            $parts = explode('-', substr($type, 8));
            if (count($parts) >= 2) {
                $this->exportRanking((int)$parts[0], (int)$parts[1]);
            } else {
                $this->json(['success' => false, 'message' => 'فرمت تاریخ نامعتبر'], 400);
            }
        } else {
            $this->json(['success' => false, 'message' => 'نوع گزارش نامعتبر'], 400);
        }
    }

    private function exportDaily(string $date): void
    {
        $report = $this->reportModel->getDailyReport($date);

        $headers = [
            'ردیف', 'نام', 'سمت', 'تعداد فعالیت', 'مجموع کمیت', 'مجموع امتیاز',
            'خبر', 'خبر اختصاصی', 'مصاحبه', 'گزارش ویژه', 'عکس', 'گالری', 'ویدئو',
        ];

        $rows = [];
        $rank = 1;
        foreach ($report['users'] as $user) {
            if ((int)$user['total_quantity'] === 0 && (float)$user['total_score'] === 0) {
                continue;
            }
            $rows[] = [
                $rank++,
                $user['user_name'],
                $user['role'] === 'reporter' ? 'خبرنگار' : 'عکاس',
                (int)$user['activity_count'],
                (int)$user['total_quantity'],
                (float)$user['total_score'],
                (int)$user['articles'],
                0,
                0,
                0,
                (int)$user['photos'],
                0,
                (int)$user['videos'],
            ];
        }

        $this->sendCsv($headers, $rows, "daily-report-{$date}.csv");
    }

    private function exportWeekly(string $startDate, string $endDate): void
    {
        $report = $this->reportModel->getWeeklyReport($startDate, $endDate);

        $headers = [
            'ردیف', 'نام', 'سمت', 'مجموع امتیاز', 'مجموع کمیت', 'تعداد فعالیت',
            'اخبار', 'عکس', 'ویدئو', 'شبکه اجتماعی',
        ];

        $rows = [];
        $rank = 1;
        foreach ($report['users'] as $user) {
            $rows[] = [
                $rank++,
                $user['user_name'],
                $user['role'] === 'reporter' ? 'خبرنگار' : 'عکاس',
                (float)$user['total_score'],
                (int)$user['total_quantity'],
                (int)$user['activity_count'],
                (int)$user['articles'],
                (int)$user['photos'],
                (int)$user['videos'],
                (int)$user['social_media'],
            ];
        }

        $this->sendCsv($headers, $rows, "weekly-report-{$startDate}-{$endDate}.csv");
    }

    private function exportMonthly(int $year, int $month): void
    {
        $report = $this->reportModel->getMonthlyReport($year, $month);

        $headers = [
            'ردیف', 'نام', 'سمت', 'مجموع امتیاز', 'مجموع کمیت', 'تعداد فعالیت',
            'اخبار', 'عکس', 'ویدئو', 'شبکه اجتماعی',
            'هدف امتیاز', 'تحقق امتیاز %',
        ];

        $rows = [];
        $rank = 1;
        foreach ($report['users'] as $user) {
            $rows[] = [
                $rank++,
                $user['user_name'],
                $user['role'] === 'reporter' ? 'خبرنگار' : 'عکاس',
                (float)$user['total_score'],
                (int)$user['total_quantity'],
                (int)$user['activity_count'],
                (int)$user['articles'],
                (int)$user['photos'],
                (int)$user['videos'],
                (int)$user['social_media'],
                $user['target_score'] !== null ? (float)$user['target_score'] : '-',
                $user['score_achievement'] !== null ? $user['score_achievement'] . '%' : '-',
            ];
        }

        $this->sendCsv($headers, $rows, "monthly-report-{$year}-{$month}.csv");
    }

    private function exportRanking(int $year, int $month): void
    {
        $ranking = $this->reportModel->getTeamRanking($year, $month);

        $headers = [
            'رتبه', 'نام', 'سمت', 'مجموع امتیاز', 'مجموع کمیت', 'تعداد فعالیت',
            'هدف امتیاز', 'درصد تحقق',
        ];

        $rows = [];
        foreach ($ranking as $person) {
            $rows[] = [
                $person['rank'],
                $person['user_name'],
                $person['role'] === 'reporter' ? 'خبرنگار' : 'عکاس',
                (float)$person['total_score'],
                (int)$person['total_quantity'],
                (int)$person['activity_count'],
                $person['target_score'] !== null ? (float)$person['target_score'] : '-',
                $person['achievement_pct'] !== null ? $person['achievement_pct'] . '%' : '-',
            ];
        }

        $this->sendCsv($headers, $rows, "team-ranking-{$year}-{$month}.csv");
    }

    private function sendCsv(array $headers, array $rows, string $filename): void
    {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo "\xEF\xBB\xBF";

        $output = fopen('php://output', 'w');
        fputcsv($output, $headers);

        foreach ($rows as $row) {
            fputcsv($output, $row);
        }

        fclose($output);
        exit;
    }
}
