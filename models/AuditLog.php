<?php
class AuditLog {
    protected $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO audit_logs (user_id, action, entity, entity_id, old_value, new_value, ip_address, user_agent)
            VALUES (:user_id, :action, :entity, :entity_id, :old_value, :new_value, :ip_address, :user_agent)
        ");
        return $stmt->execute([
            ':user_id'    => $data['user_id'] ?? null,
            ':action'     => $data['action'],
            ':entity'     => $data['entity'] ?? null,
            ':entity_id'  => $data['entity_id'] ?? null,
            ':old_value'  => isset($data['old_value']) ? json_encode($data['old_value'], JSON_UNESCAPED_UNICODE) : null,
            ':new_value'  => isset($data['new_value']) ? json_encode($data['new_value'], JSON_UNESCAPED_UNICODE) : null,
            ':ip_address' => $data['ip_address'] ?? ($_SERVER['REMOTE_ADDR'] ?? ''),
            ':user_agent' => $data['user_agent'] ?? substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
        ]);
    }

    public function findAll($filters = [], $limit = 50) {
        $sql = "
            SELECT al.*, u.name as user_name
            FROM audit_logs al
            LEFT JOIN users u ON al.user_id = u.id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['user_id'])) {
            $sql .= " AND al.user_id = :user_id";
            $params[':user_id'] = $filters['user_id'];
        }
        if (!empty($filters['action'])) {
            $sql .= " AND al.action = :action";
            $params[':action'] = $filters['action'];
        }
        if (!empty($filters['entity'])) {
            $sql .= " AND al.entity = :entity";
            $params[':entity'] = $filters['entity'];
        }
        if (!empty($filters['date_from'])) {
            $sql .= " AND al.created_at >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $sql .= " AND al.created_at <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }

        $sql .= " ORDER BY al.created_at DESC";
        $sql .= " LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($results as &$row) {
            if ($row['old_value']) {
                $row['old_value'] = json_decode($row['old_value'], true);
            }
            if ($row['new_value']) {
                $row['new_value'] = json_decode($row['new_value'], true);
            }
        }

        return $results;
    }

    public function findByUser($userId, $limit = 50) {
        return $this->findAll(['user_id' => $userId], $limit);
    }

    public function findByEntity($entity, $entityId) {
        $stmt = $this->db->prepare("
            SELECT al.*, u.name as user_name
            FROM audit_logs al
            LEFT JOIN users u ON al.user_id = u.id
            WHERE al.entity = :entity AND al.entity_id = :entity_id
            ORDER BY al.created_at DESC
        ");
        $stmt->execute([
            ':entity'    => $entity,
            ':entity_id' => $entityId,
        ]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($results as &$row) {
            if ($row['old_value']) {
                $row['old_value'] = json_decode($row['old_value'], true);
            }
            if ($row['new_value']) {
                $row['new_value'] = json_decode($row['new_value'], true);
            }
        }

        return $results;
    }
}
