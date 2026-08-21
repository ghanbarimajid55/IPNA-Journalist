<?php
class Activity {
    protected $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function findById($id) {
        $stmt = $this->db->prepare("
            SELECT a.*, u.name as user_name, u.position, at.name as activity_type_name, at.code as activity_type_code, at.category as activity_category, at.score as unit_score
            FROM activities a
            LEFT JOIN users u ON a.user_id = u.id
            LEFT JOIN activity_types at ON a.activity_type = at.id
            WHERE a.id = :id AND a.status = 'active'
        ");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function findByIdRaw($id) {
        $stmt = $this->db->prepare("SELECT * FROM activities WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function findByUser($userId, $dateFrom = null, $dateTo = null) {
        $sql = "
            SELECT a.*, at.name as activity_type_name, at.code as activity_type_code, at.category as activity_category, at.score as unit_score
            FROM activities a
            LEFT JOIN activity_types at ON a.activity_type = at.id
            WHERE a.user_id = :user_id AND a.status = 'active'
        ";
        $params = [':user_id' => $userId];

        if ($dateFrom !== null) {
            $sql .= " AND a.activity_date >= :date_from";
            $params[':date_from'] = $dateFrom;
        }
        if ($dateTo !== null) {
            $sql .= " AND a.activity_date <= :date_to";
            $params[':date_to'] = $dateTo;
        }

        $sql .= " ORDER BY a.activity_date DESC, a.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findByDate($date) {
        $stmt = $this->db->prepare("
            SELECT a.*, u.name as user_name, u.position, at.name as activity_type_name, at.score as unit_score
            FROM activities a
            LEFT JOIN users u ON a.user_id = u.id
            LEFT JOIN activity_types at ON a.activity_type = at.id
            WHERE a.activity_date = :date AND a.status = 'active'
            ORDER BY a.created_at DESC
        ");
        $stmt->execute([':date' => $date]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findAll($filters = [], $orderBy = 'a.activity_date DESC', $limit = null, $offset = null) {
        $sql = "
            SELECT a.*, u.name as user_name, u.position, at.name as activity_type_name, at.code as activity_type_code, at.category as activity_category, at.score as unit_score
            FROM activities a
            LEFT JOIN users u ON a.user_id = u.id
            LEFT JOIN activity_types at ON a.activity_type = at.id
            WHERE a.status = 'active'
        ";
        $params = [];

        if (!empty($filters['user_id'])) {
            $sql .= " AND a.user_id = :user_id";
            $params[':user_id'] = $filters['user_id'];
        }
        if (!empty($filters['activity_type'])) {
            $sql .= " AND a.activity_type = :activity_type";
            $params[':activity_type'] = $filters['activity_type'];
        }
        if (!empty($filters['date_from'])) {
            $sql .= " AND a.activity_date >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $sql .= " AND a.activity_date <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }
        if (!empty($filters['category'])) {
            $sql .= " AND at.category = :category";
            $params[':category'] = $filters['category'];
        }
        if (!empty($filters['search'])) {
            $sql .= " AND (a.title LIKE :search OR u.name LIKE :search2 OR a.description LIKE :search3)";
            $search = '%' . $filters['search'] . '%';
            $params[':search'] = $search;
            $params[':search2'] = $search;
            $params[':search3'] = $search;
        }
        if (!empty($filters['mission'])) {
            $sql .= " AND a.mission = 1";
        }

        $allowedOrders = [
            'a.activity_date DESC', 'a.activity_date ASC',
            'a.created_at DESC', 'a.created_at ASC',
            'a.score DESC', 'a.score ASC',
            'a.quantity DESC', 'a.quantity ASC',
        ];
        $order = in_array($orderBy, $allowedOrders) ? $orderBy : 'a.activity_date DESC';
        $sql .= " ORDER BY {$order}";

        if ($limit !== null) {
            $sql .= " LIMIT " . (int)$limit;
        }
        if ($offset !== null) {
            $sql .= " OFFSET " . (int)$offset;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        $unitScore = $this->getUnitScore($data['activity_type']);
        $score = $unitScore * (int)($data['quantity'] ?? 1);

        $stmt = $this->db->prepare("
            INSERT INTO activities (user_id, activity_date, activity_type, quantity, title, link, mission, description, score)
            VALUES (:user_id, :activity_date, :activity_type, :quantity, :title, :link, :mission, :description, :score)
        ");
        $stmt->execute([
            ':user_id'       => $data['user_id'],
            ':activity_date' => $data['activity_date'] ?? date('Y-m-d'),
            ':activity_type' => $data['activity_type'],
            ':quantity'      => $data['quantity'] ?? 1,
            ':title'         => $data['title'] ?? '',
            ':link'          => $data['link'] ?? null,
            ':mission'       => !empty($data['mission']) ? 1 : 0,
            ':description'   => $data['description'] ?? null,
            ':score'         => $score,
        ]);
        return $this->db->lastInsertId();
    }

    public function update($id, $data) {
        $fields = [];
        $params = [':id' => $id];

        $allowed = ['user_id', 'activity_date', 'activity_type', 'quantity', 'title', 'link', 'mission', 'description'];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = :{$field}";
                $params[":{$field}"] = $data[$field];
            }
        }

        if (isset($data['quantity']) || isset($data['activity_type'])) {
            $existing = $this->findByIdRaw($id);
            $typeId = $data['activity_type'] ?? $existing['activity_type'];
            $qty = $data['quantity'] ?? $existing['quantity'];
            $fields[] = "score = :score";
            $params[':score'] = $this->getUnitScore($typeId) * (int)$qty;
        }

        if (empty($fields)) {
            return false;
        }

        $sql = "UPDATE activities SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete($id) {
        $stmt = $this->db->prepare("UPDATE activities SET status = 'deleted' WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function count($filters = []) {
        $sql = "SELECT COUNT(*) as total FROM activities a WHERE a.status = 'active'";
        $params = [];

        if (!empty($filters['user_id'])) {
            $sql .= " AND a.user_id = :user_id";
            $params[':user_id'] = $filters['user_id'];
        }
        if (!empty($filters['activity_type'])) {
            $sql .= " AND a.activity_type = :activity_type";
            $params[':activity_type'] = $filters['activity_type'];
        }
        if (!empty($filters['date_from'])) {
            $sql .= " AND a.activity_date >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $sql .= " AND a.activity_date <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['total'] ?? 0);
    }

    public function getDailyStats($date) {
        $stmt = $this->db->prepare("
            SELECT
                COUNT(DISTINCT a.user_id) as active_users,
                COUNT(*) as total_activities,
                COALESCE(SUM(a.quantity), 0) as total_quantity,
                COALESCE(SUM(a.score), 0) as total_score,
                SUM(CASE WHEN at.code = 'news' THEN a.quantity ELSE 0 END) as news,
                SUM(CASE WHEN at.code = 'exclusive_news' THEN a.quantity ELSE 0 END) as exclusive_news,
                SUM(CASE WHEN at.code = 'interview' THEN a.quantity ELSE 0 END) as interview,
                SUM(CASE WHEN at.code = 'special_report' THEN a.quantity ELSE 0 END) as special_report,
                SUM(CASE WHEN at.code = 'news_photo' THEN a.quantity ELSE 0 END) as news_photo,
                SUM(CASE WHEN at.code = 'photo_gallery' THEN a.quantity ELSE 0 END) as photo_gallery,
                SUM(CASE WHEN at.code = 'exclusive_video' THEN a.quantity ELSE 0 END) as exclusive_video
            FROM activities a
            LEFT JOIN activity_types at ON a.activity_type = at.id
            WHERE a.activity_date = :date AND a.status = 'active'
        ");
        $stmt->execute([':date' => $date]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        foreach (['news','exclusive_news','interview','special_report','news_photo','photo_gallery','exclusive_video','total_activities','total_quantity','total_score','active_users'] as $key) {
            $result[$key] = (int)($result[$key] ?? 0);
        }
        return $result;
    }

    public function getWeeklyStats($startDate, $endDate) {
        $stmt = $this->db->prepare("
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
        $stmt->execute([':start_date' => $startDate, ':end_date' => $endDate]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getMonthlyStats($year, $month) {
        $stmt = $this->db->prepare("
            SELECT
                WEEK(a.activity_date, 1) as week_number,
                COUNT(DISTINCT a.user_id) as active_users,
                COUNT(*) as total_activities,
                COALESCE(SUM(a.quantity), 0) as total_quantity,
                COALESCE(SUM(a.score), 0) as total_score
            FROM activities a
            WHERE YEAR(a.activity_date) = :year AND MONTH(a.activity_date) = :month AND a.status = 'active'
            GROUP BY WEEK(a.activity_date, 1)
            ORDER BY week_number
        ");
        $stmt->execute([':year' => $year, ':month' => $month]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUserStats($userId, $year, $month) {
        $stmt = $this->db->prepare("
            SELECT
                a.activity_date as date,
                at.name as activity_type_name,
                at.category,
                SUM(a.quantity) as total_quantity,
                SUM(a.score) as total_score,
                COUNT(*) as activity_count
            FROM activities a
            LEFT JOIN activity_types at ON a.activity_type = at.id
            WHERE a.user_id = :user_id
                AND YEAR(a.activity_date) = :year
                AND MONTH(a.activity_date) = :month
                AND a.status = 'active'
            GROUP BY a.activity_date, at.id
            ORDER BY a.activity_date
        ");
        $stmt->execute([':user_id' => $userId, ':year' => $year, ':month' => $month]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDailyProduction($date) {
        $stmt = $this->db->prepare("
            SELECT
                at.name as activity_type_name,
                at.code as activity_type_code,
                at.category,
                COUNT(*) as count,
                COALESCE(SUM(a.quantity), 0) as total_quantity,
                COALESCE(SUM(a.score), 0) as total_score
            FROM activities a
            LEFT JOIN activity_types at ON a.activity_type = at.id
            WHERE a.activity_date = :date AND a.status = 'active'
            GROUP BY a.activity_type
            ORDER BY total_score DESC
        ");
        $stmt->execute([':date' => $date]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getWeeklyProduction($startDate) {
        $endDate = date('Y-m-d', strtotime($startDate . ' +6 days'));
        $stmt = $this->db->prepare("
            SELECT
                a.activity_date as date,
                at.name as activity_type_name,
                at.category,
                COALESCE(SUM(a.quantity), 0) as total_quantity,
                COALESCE(SUM(a.score), 0) as total_score
            FROM activities a
            LEFT JOIN activity_types at ON a.activity_type = at.id
            WHERE a.activity_date BETWEEN :start_date AND :end_date AND a.status = 'active'
            GROUP BY a.activity_date, a.activity_type
            ORDER BY a.activity_date, total_score DESC
        ");
        $stmt->execute([':start_date' => $startDate, ':end_date' => $endDate]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getMonthlyProduction($year, $month) {
        $stmt = $this->db->prepare("
            SELECT
                at.name as activity_type_name,
                at.code as activity_type_code,
                at.category,
                COUNT(*) as count,
                COALESCE(SUM(a.quantity), 0) as total_quantity,
                COALESCE(SUM(a.score), 0) as total_score
            FROM activities a
            LEFT JOIN activity_types at ON a.activity_type = at.id
            WHERE YEAR(a.activity_date) = :year AND MONTH(a.activity_date) = :month AND a.status = 'active'
            GROUP BY a.activity_type
            ORDER BY total_score DESC
        ");
        $stmt->execute([':year' => $year, ':month' => $month]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTopPerformers($year, $month, $limit = 10) {
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
                SUM(CASE WHEN at.category = 'news' THEN a.quantity ELSE 0 END) as news_count,
                SUM(CASE WHEN at.category = 'photo' THEN a.quantity ELSE 0 END) as photo_count,
                SUM(CASE WHEN at.category = 'video' THEN a.quantity ELSE 0 END) as video_count
            FROM users u
            LEFT JOIN activities a ON u.id = a.user_id
                AND YEAR(a.activity_date) = :year
                AND MONTH(a.activity_date) = :month
                AND a.status = 'active'
            LEFT JOIN activity_types at ON a.activity_type = at.id
            WHERE u.status = 'active' AND u.role IN ('reporter', 'photographer')
            GROUP BY u.id
            ORDER BY total_score DESC, 
                SUM(CASE WHEN at.code = 'exclusive_news' THEN a.quantity ELSE 0 END) DESC,
                SUM(CASE WHEN at.code = 'special_report' THEN a.quantity ELSE 0 END) DESC,
                SUM(CASE WHEN at.code = 'interview' THEN a.quantity ELSE 0 END) DESC,
                total_quantity DESC
            LIMIT " . (int)$limit
        );
        $stmt->execute([':year' => $year, ':month' => $month]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function calculateScore($activityTypeId, $quantity) {
        return $this->getUnitScore($activityTypeId) * (int)$quantity;
    }

    private function getUnitScore($activityTypeId) {
        $stmt = $this->db->prepare("SELECT score FROM activity_types WHERE id = :id AND status = 'active'");
        $stmt->execute([':id' => $activityTypeId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (int)$row['score'] : 0;
    }

    public function search($query, $filters = []) {
        $sql = "
            SELECT a.*, u.name as user_name, u.position, at.name as activity_type_name, at.category as activity_category
            FROM activities a
            LEFT JOIN users u ON a.user_id = u.id
            LEFT JOIN activity_types at ON a.activity_type = at.id
            WHERE a.status = 'active'
                AND (a.title LIKE :query OR u.name LIKE :query2 OR at.name LIKE :query3 OR a.description LIKE :query4)
        ";
        $searchParam = '%' . $query . '%';
        $params = [
            ':query'  => $searchParam,
            ':query2' => $searchParam,
            ':query3' => $searchParam,
            ':query4' => $searchParam,
        ];

        if (!empty($filters['user_id'])) {
            $sql .= " AND a.user_id = :user_id";
            $params[':user_id'] = $filters['user_id'];
        }
        if (!empty($filters['date_from'])) {
            $sql .= " AND a.activity_date >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $sql .= " AND a.activity_date <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }

        $sql .= " ORDER BY a.activity_date DESC, a.created_at DESC";

        if (!empty($filters['limit'])) {
            $sql .= " LIMIT " . (int)$filters['limit'];
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
