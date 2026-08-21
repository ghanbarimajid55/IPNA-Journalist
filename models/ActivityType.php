<?php
class ActivityType {
    protected $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function findById($id) {
        $stmt = $this->db->prepare("SELECT * FROM activity_types WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function findByCode($code) {
        $stmt = $this->db->prepare("SELECT * FROM activity_types WHERE code = :code");
        $stmt->execute([':code' => $code]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function findAll($status = 'active') {
        $sql = "SELECT * FROM activity_types";
        $params = [];
        if ($status !== null) {
            $sql .= " WHERE status = :status";
            $params[':status'] = $status;
        }
        $sql .= " ORDER BY category, score DESC, name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO activity_types (name, code, score, category, status)
            VALUES (:name, :code, :score, :category, :status)
        ");
        $stmt->execute([
            ':name'     => $data['name'],
            ':code'     => $data['code'],
            ':score'    => $data['score'] ?? 1,
            ':category' => $data['category'] ?? 'news',
            ':status'   => $data['status'] ?? 'active',
        ]);
        return $this->db->lastInsertId();
    }

    public function update($id, $data) {
        $fields = [];
        $params = [':id' => $id];

        $allowed = ['name', 'code', 'score', 'category', 'status'];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = :{$field}";
                $params[":{$field}"] = $data[$field];
            }
        }

        if (empty($fields)) {
            return false;
        }

        $fields[] = "updated_at = NOW()";
        $sql = "UPDATE activity_types SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete($id) {
        $stmt = $this->db->prepare("UPDATE activity_types SET status = 'inactive' WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function getByCategory($category) {
        $stmt = $this->db->prepare("
            SELECT * FROM activity_types
            WHERE category = :category AND status = 'active'
            ORDER BY score DESC, name
        ");
        $stmt->execute([':category' => $category]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
