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
            if (preg_match('/^weekly-(\d{4}-\d{2}-\d{2})-(\d{4}-\d{2}-\d{2})$/', $type, $m)) {
                $this->exportWeekly($m[1], $m[2]);
            } else {
                $this->json(['success' => false, 'message' => 'فرمت تاریخ نامعتبر'], 400);
            }
        } elseif (strpos($type, 'monthly-') === 0) {
            if (preg_match('/^monthly-(\d{4})-(\d{1,2})$/', $type, $m)) {
                $this->exportMonthly((int)$m[1], (int)$m[2]);
            } else {
                $this->json(['success' => false, 'message' => 'فرمت تاریخ نامعتبر'], 400);
            }
        } elseif (strpos($type, 'ranking-') === 0) {
            if (preg_match('/^ranking-(\d{4})-(\d{1,2})$/', $type, $m)) {
                $this->exportRanking((int)$m[1], (int)$m[2]);
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
                (int)$user['news_count'],
                (int)($user['news_exclusive'] ?? 0),
                (int)($user['interview_count'] ?? 0),
                (int)($user['special_report_count'] ?? 0),
                (int)$user['photo_count'],
                (int)($user['gallery_count'] ?? 0),
                (int)$user['video_count'],
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
                (int)$user['news_count'],
                (int)$user['photo_count'],
                (int)$user['video_count'],
                0,
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
                (int)$user['news_count'],
                (int)$user['photo_count'],
                (int)$user['video_count'],
                0,
                $user['score_target'] !== null ? (float)$user['score_target'] : '-',
                $user['score_target'] > 0 && (float)$user['total_score'] > 0
                    ? round(((float)$user['total_score'] / (float)$user['score_target']) * 100, 1) . '%'
                    : '-',
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
                $person['score_target'] !== null ? (float)$person['score_target'] : '-',
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
            fputcsv($output, array_map([$this, 'neutralizeFormula'], $row));
        }

        fclose($output);
        exit;
    }

    private function neutralizeFormula(mixed $cell): mixed
    {
        if (is_string($cell) && $cell !== '' && str_contains('=+-@', $cell[0])) {
            return "'" . $cell;
        }
        return $cell;
    }
}
