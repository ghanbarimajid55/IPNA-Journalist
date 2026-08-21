<?php
class SystemSetting {
    protected $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function get($key, $default = null) {
        $stmt = $this->db->prepare("
            SELECT setting_value FROM system_settings WHERE setting_key = :key
        ");
        $stmt->execute([':key' => $key]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return $default;
        }

        $value = $row['setting_value'];
        $decoded = json_decode($value, true);
        return $decoded !== null ? $decoded : $value;
    }

    public function set($key, $value) {
        $encoded = is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;

        $stmt = $this->db->prepare("
            INSERT INTO system_settings (setting_key, setting_value, updated_at)
            VALUES (:key, :value, NOW())
            ON DUPLICATE KEY UPDATE
                setting_value = VALUES(setting_value),
                updated_at = NOW()
        ");
        return $stmt->execute([
            ':key'   => $key,
            ':value' => $encoded,
        ]);
    }

    public function getAll() {
        $stmt = $this->db->prepare("
            SELECT setting_key, setting_value, updated_at
            FROM system_settings
            ORDER BY setting_key
        ");
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $settings = [];
        foreach ($rows as $row) {
            $decoded = json_decode($row['setting_value'], true);
            $settings[$row['setting_key']] = [
                'value'      => $decoded !== null ? $decoded : $row['setting_value'],
                'updated_at' => $row['updated_at'],
            ];
        }
        return $settings;
    }

    public function delete($key) {
        $stmt = $this->db->prepare("
            DELETE FROM system_settings WHERE setting_key = :key
        ");
        return $stmt->execute([':key' => $key]);
    }
}
