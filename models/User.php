<?php
class User {
    protected $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function findById($id) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function findByUsername($username) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->execute([':username' => $username]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function findByEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function findAll($filters = [], $orderBy = 'name ASC', $limit = null, $offset = null) {
        $sql = "SELECT * FROM users WHERE 1=1";
        $params = [];

        if (!empty($filters['role'])) {
            $sql .= " AND role = :role";
            $params[':role'] = $filters['role'];
        }
        if (!empty($filters['status'])) {
            $sql .= " AND status = :status";
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['search'])) {
            $sql .= " AND (name LIKE :search OR email LIKE :search2 OR username LIKE :search3)";
            $search = '%' . $filters['search'] . '%';
            $params[':search'] = $search;
            $params[':search2'] = $search;
            $params[':search3'] = $search;
        }

        $allowedOrders = ['name', 'email', 'role', 'created_at', 'status', 'position'];
        $orderCol = preg_replace('/\s+(ASC|DESC)$/i', '', $orderBy);
        $direction = stripos($orderBy, 'DESC') !== false ? ' DESC' : ' ASC';
        $orderCol = in_array($orderCol, $allowedOrders) ? $orderCol : 'name';
        $sql .= " ORDER BY {$orderCol}{$direction}";

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
        $stmt = $this->db->prepare("
            INSERT INTO users (name, username, email, password, role, position, avatar, status)
            VALUES (:name, :username, :email, :password, :role, :position, :avatar, :status)
        ");
        $stmt->execute([
            ':name'     => $data['name'],
            ':username' => $data['username'],
            ':email'    => $data['email'] ?? '',
            ':password' => password_hash($data['password'], PASSWORD_BCRYPT),
            ':role'     => $data['role'] ?? 'reporter',
            ':position' => $data['position'] ?? '',
            ':avatar'   => $data['avatar'] ?? null,
            ':status'   => $data['status'] ?? 'active',
        ]);
        return $this->db->lastInsertId();
    }

    public function update($id, $data) {
        $fields = [];
        $params = [':id' => $id];

        $allowed = ['name', 'username', 'email', 'role', 'position', 'status', 'avatar'];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = :{$field}";
                $params[":{$field}"] = $data[$field];
            }
        }

        if (isset($data['password']) && !empty($data['password'])) {
            $fields[] = "password = :password";
            $params[':password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }

        if (empty($fields)) {
            return false;
        }

        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function count($filters = []) {
        $sql = "SELECT COUNT(*) as total FROM users WHERE 1=1";
        $params = [];

        if (!empty($filters['role'])) {
            $sql .= " AND role = :role";
            $params[':role'] = $filters['role'];
        }
        if (!empty($filters['status'])) {
            $sql .= " AND status = :status";
            $params[':status'] = $filters['status'];
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['total'] ?? 0);
    }

    public function getTeamMembers() {
        $stmt = $this->db->prepare("
            SELECT id, name, username, email, role, position, avatar
            FROM users
            WHERE role IN ('reporter', 'photographer') AND status = 'active'
            ORDER BY name
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDashboardStats($userId, $date = null) {
        if ($date === null) {
            $date = date('Y-m-d');
        }

        $weekStart = date('Y-m-d', strtotime('monday this week', strtotime($date)));
        $weekEnd   = date('Y-m-d', strtotime('sunday this week', strtotime($date)));
        $monthStart = date('Y-m-01', strtotime($date));
        $monthEnd   = date('Y-m-t', strtotime($date));

        $sql = "
            SELECT
                SUM(CASE WHEN a.activity_date = :t1 THEN a.score ELSE 0 END) as today_score,
                SUM(CASE WHEN a.activity_date = :t2 THEN a.quantity ELSE 0 END) as today_count,
                SUM(CASE WHEN a.activity_date BETWEEN :ws1 AND :we1 THEN a.score ELSE 0 END) as week_score,
                SUM(CASE WHEN a.activity_date BETWEEN :ws2 AND :we2 THEN a.quantity ELSE 0 END) as week_count,
                SUM(CASE WHEN a.activity_date BETWEEN :ms1 AND :me1 THEN a.score ELSE 0 END) as month_score,
                SUM(CASE WHEN a.activity_date BETWEEN :ms2 AND :me2 THEN a.quantity ELSE 0 END) as month_count
            FROM activities a
            WHERE a.user_id = :user_id AND a.status = 'active'
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':user_id'     => $userId,
            ':t1'          => $date,
            ':t2'          => $date,
            ':ws1'         => $weekStart,
            ':we1'         => $weekEnd,
            ':ws2'         => $weekStart,
            ':we2'         => $weekEnd,
            ':ms1'         => $monthStart,
            ':me1'         => $monthEnd,
            ':ms2'         => $monthStart,
            ':me2'         => $monthEnd,
        ]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);

        $target = null;
        $monthInt = (int)date('m', strtotime($date));
        $yearInt  = (int)date('Y', strtotime($date));
        $stmt2 = $this->db->prepare("
            SELECT score_target FROM monthly_targets
            WHERE user_id = :user_id AND year = :year AND month = :month
        ");
        $stmt2->execute([
            ':user_id' => $userId,
            ':year'    => $yearInt,
            ':month'   => $monthInt,
        ]);
        $targetRow = $stmt2->fetch(PDO::FETCH_ASSOC);
        if ($targetRow) {
            $target = (int)$targetRow['score_target'];
        }

        return [
            'today' => [
                'score' => (float)($stats['today_score'] ?? 0),
                'count' => (int)($stats['today_count'] ?? 0),
            ],
            'week' => [
                'score' => (float)($stats['week_score'] ?? 0),
                'count' => (int)($stats['week_count'] ?? 0),
            ],
            'month' => [
                'score'       => (float)($stats['month_score'] ?? 0),
                'count'       => (int)($stats['month_count'] ?? 0),
                'target'      => $target,
                'achievement' => $target > 0 ? round(((float)($stats['month_score'] ?? 0) / $target) * 100, 1) : null,
            ],
        ];
    }

    public function getLastLogin($userId) {
        $stmt = $this->db->prepare("
            SELECT created_at FROM audit_logs
            WHERE user_id = :user_id AND action = 'login_success'
            ORDER BY created_at DESC LIMIT 1
        ");
        $stmt->execute([':user_id' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['created_at'] : null;
    }
}
