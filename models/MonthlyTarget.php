<?php
class MonthlyTarget {
    protected $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function findById($id) {
        $stmt = $this->db->prepare("
            SELECT mt.*, u.name as user_name
            FROM monthly_targets mt
            LEFT JOIN users u ON mt.user_id = u.id
            WHERE mt.id = :id
        ");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function findByUser($userId, $year, $month) {
        $stmt = $this->db->prepare("
            SELECT * FROM monthly_targets
            WHERE user_id = :user_id AND year = :year AND month = :month
        ");
        $stmt->execute([
            ':user_id' => $userId,
            ':year'    => $year,
            ':month'   => $month,
        ]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO monthly_targets (user_id, year, month, news_target, exclusive_news_target, interview_target, special_report_target, photo_target, gallery_target, video_target, score_target)
            VALUES (:user_id, :year, :month, :news_target, :exclusive_news_target, :interview_target, :special_report_target, :photo_target, :gallery_target, :video_target, :score_target)
            ON DUPLICATE KEY UPDATE
                news_target = VALUES(news_target),
                exclusive_news_target = VALUES(exclusive_news_target),
                interview_target = VALUES(interview_target),
                special_report_target = VALUES(special_report_target),
                photo_target = VALUES(photo_target),
                gallery_target = VALUES(gallery_target),
                video_target = VALUES(video_target),
                score_target = VALUES(score_target),
                updated_at = NOW()
        ");
        $stmt->execute([
            ':user_id'                 => $data['user_id'],
            ':year'                    => $data['year'],
            ':month'                   => $data['month'],
            ':news_target'             => $data['news_target'] ?? 0,
            ':exclusive_news_target'   => $data['exclusive_news_target'] ?? 0,
            ':interview_target'        => $data['interview_target'] ?? 0,
            ':special_report_target'   => $data['special_report_target'] ?? 0,
            ':photo_target'            => $data['photo_target'] ?? 0,
            ':gallery_target'          => $data['gallery_target'] ?? 0,
            ':video_target'            => $data['video_target'] ?? 0,
            ':score_target'            => $data['score_target'] ?? 0,
        ]);
        return $this->db->lastInsertId() ?: $this->findByUser($data['user_id'], $data['year'], $data['month'])['id'];
    }

    public function update($id, $data) {
        $fields = [];
        $params = [':id' => $id];

        $allowed = ['news_target', 'exclusive_news_target', 'interview_target', 'special_report_target', 'photo_target', 'gallery_target', 'video_target', 'score_target'];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = :{$field}";
                $params[":{$field}"] = (int)$data[$field];
            }
        }

        if (empty($fields)) {
            return false;
        }

        $fields[] = "updated_at = NOW()";
        $sql = "UPDATE monthly_targets SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function getTeamTargets($year, $month) {
        $stmt = $this->db->prepare("
            SELECT mt.*, u.name as user_name, u.role, u.position, u.avatar
            FROM monthly_targets mt
            LEFT JOIN users u ON mt.user_id = u.id
            WHERE mt.year = :year AND mt.month = :month AND u.status = 'active'
            ORDER BY u.name
        ");
        $stmt->execute([':year' => $year, ':month' => $month]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAchievement($userId, $year, $month) {
        $target = $this->findByUser($userId, $year, $month);
        if (!$target) {
            return [
                'target' => null,
                'actual' => null,
                'score_achievement' => null,
                'news_achievement' => null,
                'photo_achievement' => null,
                'video_achievement' => null,
            ];
        }

        $stmt = $this->db->prepare("
            SELECT
                COALESCE(SUM(a.score), 0) as total_score,
                SUM(CASE WHEN at.code = 'news' THEN a.quantity ELSE 0 END) as news_count,
                SUM(CASE WHEN at.code = 'exclusive_news' THEN a.quantity ELSE 0 END) as exclusive_news_count,
                SUM(CASE WHEN at.code = 'interview' THEN a.quantity ELSE 0 END) as interview_count,
                SUM(CASE WHEN at.code = 'special_report' THEN a.quantity ELSE 0 END) as special_report_count,
                SUM(CASE WHEN at.code = 'news_photo' THEN a.quantity ELSE 0 END) as photo_count,
                SUM(CASE WHEN at.code = 'photo_gallery' THEN a.quantity ELSE 0 END) as gallery_count,
                SUM(CASE WHEN at.code = 'exclusive_video' THEN a.quantity ELSE 0 END) as video_count
            FROM activities a
            LEFT JOIN activity_types at ON a.activity_type = at.id
            WHERE a.user_id = :user_id
                AND YEAR(a.activity_date) = :year
                AND MONTH(a.activity_date) = :month
                AND a.status = 'active'
        ");
        $stmt->execute([
            ':user_id' => $userId,
            ':year'    => $year,
            ':month'   => $month,
        ]);
        $actual = $stmt->fetch(PDO::FETCH_ASSOC);

        $scoreAchievement = $target['score_target'] > 0
            ? round(((float)$actual['total_score'] / (float)$target['score_target']) * 100, 1)
            : 0;

        $newsAchievement = $target['news_target'] > 0
            ? round(((int)($actual['news_count'] ?? 0) / (int)$target['news_target']) * 100, 1)
            : null;

        $photoAchievement = $target['photo_target'] > 0
            ? round(((int)($actual['photo_count'] ?? 0) / (int)$target['photo_target']) * 100, 1)
            : null;

        $videoAchievement = $target['video_target'] > 0
            ? round(((int)($actual['video_count'] ?? 0) / (int)$target['video_target']) * 100, 1)
            : null;

        return [
            'target'             => $target,
            'actual'             => $actual,
            'score_achievement'  => $scoreAchievement,
            'news_achievement'   => $newsAchievement,
            'photo_achievement'  => $photoAchievement,
            'video_achievement'  => $videoAchievement,
        ];
    }
}
