<?php
class Report {
    protected $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function getDailyReport($date, $filters = []) {
        $sql = "
            SELECT
                u.id as user_id,
                u.name as user_name,
                u.role,
                u.position,
                COALESCE(SUM(a.score), 0) as total_score,
                COALESCE(SUM(a.quantity), 0) as total_quantity,
                COUNT(a.id) as activity_count,
                SUM(CASE WHEN at.category = 'news' THEN a.quantity ELSE 0 END) as news_count,
                SUM(CASE WHEN at.category = 'photo' THEN a.quantity ELSE 0 END) as photo_count,
                SUM(CASE WHEN at.category = 'video' THEN a.quantity ELSE 0 END) as video_count,
                SUM(CASE WHEN at.code = 'news' THEN a.quantity ELSE 0 END) as news_normal,
                SUM(CASE WHEN at.code = 'exclusive_news' THEN a.quantity ELSE 0 END) as news_exclusive,
                SUM(CASE WHEN at.code = 'interview' THEN a.quantity ELSE 0 END) as interview_count,
                SUM(CASE WHEN at.code = 'special_report' THEN a.quantity ELSE 0 END) as special_report_count,
                SUM(CASE WHEN at.code = 'news_photo' THEN a.quantity ELSE 0 END) as photo_news,
                SUM(CASE WHEN at.code = 'photo_gallery' THEN a.quantity ELSE 0 END) as gallery_count,
                SUM(CASE WHEN at.code = 'exclusive_video' THEN a.quantity ELSE 0 END) as exclusive_video_count
            FROM users u
            LEFT JOIN activities a ON u.id = a.user_id AND a.activity_date = :date AND a.status = 'active'
            LEFT JOIN activity_types at ON a.activity_type = at.id
            WHERE u.status = 'active' AND u.role IN ('reporter', 'photographer')
        ";
        $params = [':date' => $date];

        if (!empty($filters['role'])) {
            $sql .= " AND u.role = :role";
            $params[':role'] = $filters['role'];
        }
        if (!empty($filters['user_id'])) {
            $sql .= " AND u.id = :user_id";
            $params[':user_id'] = $filters['user_id'];
        }

        $sql .= " GROUP BY u.id, u.name, u.role, u.position ORDER BY total_score DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt2 = $this->db->prepare("
            SELECT
                COUNT(DISTINCT a.user_id) as active_users,
                COUNT(*) as total_activities,
                COALESCE(SUM(a.quantity), 0) as total_quantity,
                COALESCE(SUM(a.score), 0) as total_score,
                COALESCE(AVG(a.score), 0) as avg_score
            FROM activities a
            WHERE a.activity_date = :date AND a.status = 'active'
        ");
        $stmt2->execute([':date' => $date]);
        $summary = $stmt2->fetch(PDO::FETCH_ASSOC);

        $stmt3 = $this->db->prepare("
            SELECT at.name, at.code, at.category, COUNT(*) as count, COALESCE(SUM(a.quantity), 0) as total_quantity, COALESCE(SUM(a.score), 0) as total_score
            FROM activities a
            LEFT JOIN activity_types at ON a.activity_type = at.id
            WHERE a.activity_date = :date AND a.status = 'active'
            GROUP BY a.activity_type, at.name, at.code, at.category
            ORDER BY total_score DESC
        ");
        $stmt3->execute([':date' => $date]);
        $byType = $stmt3->fetchAll(PDO::FETCH_ASSOC);

        return [
            'date'    => $date,
            'summary' => $summary,
            'users'   => $users,
            'by_type' => $byType,
        ];
    }

    public function getWeeklyReport($startDate, $endDate, $filters = []) {
        $sql = "
            SELECT
                u.id as user_id,
                u.name as user_name,
                u.role,
                u.position,
                COALESCE(SUM(a.score), 0) as total_score,
                COALESCE(SUM(a.quantity), 0) as total_quantity,
                COUNT(a.id) as activity_count,
                SUM(CASE WHEN at.category = 'news' THEN a.quantity ELSE 0 END) as news_count,
                SUM(CASE WHEN at.category = 'photo' THEN a.quantity ELSE 0 END) as photo_count,
                SUM(CASE WHEN at.category = 'video' THEN a.quantity ELSE 0 END) as video_count,
                SUM(CASE WHEN at.code = 'news' THEN a.quantity ELSE 0 END) as news_normal,
                SUM(CASE WHEN at.code = 'exclusive_news' THEN a.quantity ELSE 0 END) as news_exclusive,
                SUM(CASE WHEN at.code = 'interview' THEN a.quantity ELSE 0 END) as interview_count,
                SUM(CASE WHEN at.code = 'special_report' THEN a.quantity ELSE 0 END) as special_report_count,
                SUM(CASE WHEN at.code = 'news_photo' THEN a.quantity ELSE 0 END) as photo_news,
                SUM(CASE WHEN at.code = 'photo_gallery' THEN a.quantity ELSE 0 END) as gallery_count,
                SUM(CASE WHEN at.code = 'exclusive_video' THEN a.quantity ELSE 0 END) as exclusive_video_count
            FROM users u
            LEFT JOIN activities a ON u.id = a.user_id
                AND a.activity_date BETWEEN :start_date AND :end_date
                AND a.status = 'active'
            LEFT JOIN activity_types at ON a.activity_type = at.id
            WHERE u.status = 'active' AND u.role IN ('reporter', 'photographer')
        ";
        $params = [
            ':start_date' => $startDate,
            ':end_date'   => $endDate,
        ];

        if (!empty($filters['role'])) {
            $sql .= " AND u.role = :role";
            $params[':role'] = $filters['role'];
        }
        if (!empty($filters['user_id'])) {
            $sql .= " AND u.id = :user_id";
            $params[':user_id'] = $filters['user_id'];
        }

        $sql .= " GROUP BY u.id, u.name, u.role, u.position ORDER BY total_score DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt2 = $this->db->prepare("
            SELECT
                a.activity_date as date,
                COUNT(DISTINCT a.user_id) as active_users,
                COUNT(*) as total_activities,
                COALESCE(SUM(a.quantity), 0) as total_quantity,
                COALESCE(SUM(a.score), 0) as total_score
            FROM activities a
            WHERE a.activity_date BETWEEN :start_date AND :end_date AND a.status = 'active'
            GROUP BY a.activity_date
            ORDER BY a.activity_date
        ");
        $stmt2->execute([':start_date' => $startDate, ':end_date' => $endDate]);
        $daily = $stmt2->fetchAll(PDO::FETCH_ASSOC);

        return [
            'start_date' => $startDate,
            'end_date'   => $endDate,
            'daily'      => $daily,
            'users'      => $users,
        ];
    }

    public function getMonthlyReport($year, $month) {
        $sql = "
            SELECT
                u.id as user_id,
                u.name as user_name,
                u.role,
                u.position,
                u.avatar,
                COALESCE(SUM(a.score), 0) as total_score,
                COALESCE(SUM(a.quantity), 0) as total_quantity,
                COUNT(a.id) as activity_count,
                SUM(CASE WHEN at.category = 'news' THEN a.quantity ELSE 0 END) as news_count,
                SUM(CASE WHEN at.category = 'photo' THEN a.quantity ELSE 0 END) as photo_count,
                SUM(CASE WHEN at.category = 'video' THEN a.quantity ELSE 0 END) as video_count,
                SUM(CASE WHEN at.code = 'news' THEN a.quantity ELSE 0 END) as news_normal,
                SUM(CASE WHEN at.code = 'exclusive_news' THEN a.quantity ELSE 0 END) as news_exclusive,
                SUM(CASE WHEN at.code = 'interview' THEN a.quantity ELSE 0 END) as interview_count,
                SUM(CASE WHEN at.code = 'special_report' THEN a.quantity ELSE 0 END) as special_report_count,
                SUM(CASE WHEN at.code = 'news_photo' THEN a.quantity ELSE 0 END) as photo_news,
                SUM(CASE WHEN at.code = 'photo_gallery' THEN a.quantity ELSE 0 END) as gallery_count,
                SUM(CASE WHEN at.code = 'exclusive_video' THEN a.quantity ELSE 0 END) as exclusive_video_count,
                mt.score_target,
                mt.news_target,
                mt.photo_target,
                mt.video_target
            FROM users u
            LEFT JOIN activities a ON u.id = a.user_id
                AND YEAR(a.activity_date) = :year
                AND MONTH(a.activity_date) = :month
                AND a.status = 'active'
            LEFT JOIN activity_types at ON a.activity_type = at.id
            LEFT JOIN monthly_targets mt ON u.id = mt.user_id AND mt.year = :year2 AND mt.month = :month2
            WHERE u.status = 'active' AND u.role IN ('reporter', 'photographer')
            GROUP BY u.id, u.name, u.role, u.position, u.avatar, mt.score_target, mt.news_target, mt.photo_target, mt.video_target
            ORDER BY total_score DESC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':year'   => $year,
            ':month'  => $month,
            ':year2'  => $year,
            ':month2' => $month,
        ]);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $byType = [];
        $stmt3 = $this->db->prepare("
            SELECT at.name, at.code, at.category, COUNT(*) as count, COALESCE(SUM(a.quantity), 0) as total_quantity, COALESCE(SUM(a.score), 0) as total_score
            FROM activities a
            LEFT JOIN activity_types at ON a.activity_type = at.id
            WHERE YEAR(a.activity_date) = :year AND MONTH(a.activity_date) = :month AND a.status = 'active'
            GROUP BY a.activity_type, at.name, at.code, at.category
            ORDER BY total_score DESC
        ");
        $stmt3->execute([':year' => $year, ':month' => $month]);
        $byType = $stmt3->fetchAll(PDO::FETCH_ASSOC);

        $summary = [
            'total_users'      => count($users),
            'total_score'      => array_sum(array_column($users, 'total_score')),
            'total_quantity'   => array_sum(array_column($users, 'total_quantity')),
            'total_activities' => array_sum(array_column($users, 'activity_count')),
        ];

        return [
            'year'    => $year,
            'month'   => $month,
            'summary' => $summary,
            'users'   => $users,
            'by_type' => $byType,
        ];
    }

    public function getTeamRanking($year, $month) {
        $stmt = $this->db->prepare("
            SELECT
                u.id as user_id,
                u.name as user_name,
                u.role,
                u.position,
                u.avatar,
                COALESCE(SUM(a.score), 0) as total_score,
                COALESCE(SUM(a.quantity), 0) as total_quantity,
                COUNT(a.id) as activity_count,
                SUM(CASE WHEN at.code = 'news' THEN a.quantity ELSE 0 END) as news_normal,
                SUM(CASE WHEN at.code = 'exclusive_news' THEN a.quantity ELSE 0 END) as news_exclusive,
                SUM(CASE WHEN at.code = 'interview' THEN a.quantity ELSE 0 END) as interview_count,
                SUM(CASE WHEN at.code = 'special_report' THEN a.quantity ELSE 0 END) as special_report_count,
                SUM(CASE WHEN at.code = 'news_photo' THEN a.quantity ELSE 0 END) as photo_news,
                SUM(CASE WHEN at.code = 'photo_gallery' THEN a.quantity ELSE 0 END) as gallery_count,
                SUM(CASE WHEN at.code = 'exclusive_video' THEN a.quantity ELSE 0 END) as exclusive_video_count,
                mt.score_target,
                CASE
                    WHEN mt.score_target > 0 THEN ROUND((COALESCE(SUM(a.score), 0) / mt.score_target) * 100, 1)
                    ELSE NULL
                END as achievement_pct
            FROM users u
            LEFT JOIN activities a ON u.id = a.user_id
                AND YEAR(a.activity_date) = :year
                AND MONTH(a.activity_date) = :month
                AND a.status = 'active'
            LEFT JOIN activity_types at ON a.activity_type = at.id
            LEFT JOIN monthly_targets mt ON u.id = mt.user_id AND mt.year = :year2 AND mt.month = :month2
            WHERE u.status = 'active' AND u.role IN ('reporter', 'photographer')
            GROUP BY u.id, u.name, u.role, u.position, u.avatar, mt.score_target
            ORDER BY total_score DESC, news_exclusive DESC, special_report_count DESC, interview_count DESC, total_quantity DESC
        ");
        $stmt->execute([
            ':year'   => $year,
            ':month'  => $month,
            ':year2'  => $year,
            ':month2' => $month,
        ]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($results as $index => &$row) {
            $row['rank'] = $index + 1;
        }

        return $results;
    }

    public function getMonthlySummary($year, $month) {
        $stmt = $this->db->prepare("
            SELECT
                u.id as user_id,
                u.name as user_name,
                u.role,
                u.position,
                COALESCE(SUM(a.score), 0) as total_score,
                COALESCE(SUM(a.quantity), 0) as total_quantity,
                COUNT(a.id) as total_activities,
                SUM(CASE WHEN at.category = 'news' THEN a.quantity ELSE 0 END) as news_count,
                SUM(CASE WHEN at.category = 'photo' THEN a.quantity ELSE 0 END) as photo_count,
                SUM(CASE WHEN at.category = 'video' THEN a.quantity ELSE 0 END) as video_count,
                COUNT(DISTINCT a.activity_date) as active_days
            FROM users u
            LEFT JOIN activities a ON u.id = a.user_id
                AND YEAR(a.activity_date) = :year
                AND MONTH(a.activity_date) = :month
                AND a.status = 'active'
            LEFT JOIN activity_types at ON a.activity_type = at.id
            WHERE u.status = 'active' AND u.role IN ('reporter', 'photographer')
            GROUP BY u.id, u.name, u.role, u.position
            ORDER BY total_score DESC
        ");
        $stmt->execute([':year' => $year, ':month' => $month]);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'year'             => $year,
            'month'            => $month,
            'total_users'      => count($users),
            'total_score'      => array_sum(array_column($users, 'total_score')),
            'total_quantity'   => array_sum(array_column($users, 'total_quantity')),
            'total_activities' => array_sum(array_column($users, 'total_activities')),
            'avg_score'        => count($users) > 0 ? round(array_sum(array_column($users, 'total_score')) / count($users), 1) : 0,
            'users'            => $users,
        ];
    }

    public function getPerformanceChart($userId, $year, $month) {
        $stmt = $this->db->prepare("
            SELECT
                a.activity_date as date,
                COALESCE(SUM(a.score), 0) as daily_score,
                COALESCE(SUM(a.quantity), 0) as daily_quantity,
                COUNT(*) as activity_count
            FROM activities a
            WHERE a.user_id = :user_id
                AND YEAR(a.activity_date) = :year
                AND MONTH(a.activity_date) = :month
                AND a.status = 'active'
            GROUP BY a.activity_date
            ORDER BY a.activity_date
        ");
        $stmt->execute([
            ':user_id' => $userId,
            ':year'    => $year,
            ':month'   => $month,
        ]);
        $dailyData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $monthStart = sprintf('%04d-%02d-01', $year, $month);
        $daysInMonth = (int)date('t', strtotime($monthStart));

        $scoresByDay = [];
        foreach ($dailyData as $row) {
            $day = (int)date('d', strtotime($row['date']));
            $scoresByDay[$day] = [
                'date'            => $row['date'],
                'score'           => (float)$row['daily_score'],
                'quantity'        => (int)$row['daily_quantity'],
                'activity_count'  => (int)$row['activity_count'],
            ];
        }

        $labels = [];
        $scores = [];
        $cumulativeScores = [];
        $cumulative = 0;

        for ($d = 1; $d <= $daysInMonth; $d++) {
            $labels[] = $d;
            $dayScore = $scoresByDay[$d]['score'] ?? 0;
            $scores[] = $dayScore;
            $cumulative += $dayScore;
            $cumulativeScores[] = $cumulative;
        }

        $stmt2 = $this->db->prepare("
            SELECT score_target FROM monthly_targets
            WHERE user_id = :user_id AND year = :year AND month = :month
        ");
        $stmt2->execute([
            ':user_id' => $userId,
            ':year'    => $year,
            ':month'   => $month,
        ]);
        $targetRow = $stmt2->fetch(PDO::FETCH_ASSOC);
        $targetScore = $targetRow ? (float)$targetRow['score_target'] : null;

        $dailyTarget = $targetScore !== null ? round($targetScore / $daysInMonth, 2) : null;

        return [
            'user_id'           => $userId,
            'year'              => $year,
            'month'             => $month,
            'days_in_month'     => $daysInMonth,
            'target_score'      => $targetScore,
            'daily_target'      => $dailyTarget,
            'labels'            => $labels,
            'scores'            => $scores,
            'cumulative_scores' => $cumulativeScores,
            'daily_data'        => array_values($scoresByDay),
            'total_score'       => $cumulative,
        ];
    }
}
