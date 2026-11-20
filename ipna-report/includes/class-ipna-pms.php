<?php
if (!defined('ABSPATH')) {
    exit;
}

class IPNA_PMS
{
    protected ?PDO $pdo = null;
    protected array $cfg;

    public function __construct(array $cfg)
    {
        $this->cfg = $cfg;
    }

    protected function q(string $sql, array $params = []): array
    {
        if (!$this->connect()) {
            return [];
        }
        try {
            $st = $this->pdo->prepare($sql);
            $st->execute($params);
            return $st->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    protected function one(string $sql, array $params = []): array
    {
        if (!$this->connect()) {
            return [];
        }
        try {
            $st = $this->pdo->prepare($sql);
            $st->execute($params);
            $row = $st->fetch();
            return $row ?: [];
        } catch (PDOException $e) {
            return [];
        }
    }

    public function connect(): bool
    {
        if ($this->pdo instanceof PDO) {
            return true;
        }
        if (empty($this->cfg['pms_dbname'])) {
            return false;
        }
        try {
            $dsn = 'mysql:host=' . $this->cfg['pms_host'] . ';dbname=' . $this->cfg['pms_dbname'] . ';charset=utf8mb4';
            $this->pdo = new PDO($dsn, $this->cfg['pms_dbuser'], (string)$this->cfg['pms_dbpass'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            return false;
        }
        return true;
    }

    protected function table(string $name): string
    {
        $prefix = (string)($this->cfg['pms_prefix'] ?? '');
        return $prefix === '' ? '`' . $name . '`' : '`' . $prefix . $name . '`';
    }

    public function usersWithStats(string $from, string $to): array
    {
        $sql = "SELECT u.id, u.name, u.username, u.role, u.position,
                       COUNT(a.id) AS activity_count,
                       COALESCE(SUM(a.quantity),0) AS total_quantity,
                       COALESCE(SUM(a.score),0) AS total_score,
                       SUM(CASE WHEN at.category = 'news'  THEN a.quantity ELSE 0 END) AS news_qty,
                       SUM(CASE WHEN at.category = 'photo' THEN a.quantity ELSE 0 END) AS photo_qty,
                       SUM(CASE WHEN at.category = 'video' THEN a.quantity ELSE 0 END) AS video_qty
                FROM " . $this->table('users') . " u
                LEFT JOIN " . $this->table('activities') . " a
                       ON a.user_id = u.id AND a.status = 'active'
                       AND a.activity_date BETWEEN ? AND ?
                LEFT JOIN " . $this->table('activity_types') . " at ON at.id = a.activity_type
                WHERE u.status = 'active' AND u.role IN ('reporter','photographer')
                GROUP BY u.id, u.name, u.username, u.role, u.position
                ORDER BY total_score DESC, total_quantity DESC";
        return $this->q($sql, [$from, $to]);
    }

    public function typeTotals(string $from, string $to): array
    {
        $sql = "SELECT at.name, at.code, at.category,
                       COUNT(a.id) AS cnt,
                       COALESCE(SUM(a.quantity),0) AS qty,
                       COALESCE(SUM(a.score),0) AS score
                FROM " . $this->table('activities') . " a
                JOIN " . $this->table('activity_types') . " at ON at.id = a.activity_type
                WHERE a.status = 'active' AND a.activity_date BETWEEN ? AND ?
                GROUP BY at.id, at.name, at.code, at.category
                ORDER BY score DESC, qty DESC";
        return $this->q($sql, [$from, $to]);
    }

    public function dailySeries(string $from, string $to): array
    {
        $sql = "SELECT a.activity_date AS d,
                       COALESCE(SUM(a.score),0) AS score,
                       COALESCE(SUM(a.quantity),0) AS qty
                FROM " . $this->table('activities') . " a
                WHERE a.status = 'active' AND a.activity_date BETWEEN ? AND ?
                GROUP BY a.activity_date ORDER BY a.activity_date ASC";
        return $this->q($sql, [$from, $to]);
    }

    public function targets(int $year, int $month): array
    {
        $sql = "SELECT t.user_id, t.news_target, t.exclusive_news_target, t.interview_target,
                       t.special_report_target, t.photo_target, t.gallery_target, t.video_target,
                       t.score_target, u.name, u.role
                FROM " . $this->table('monthly_targets') . " t
                JOIN " . $this->table('users') . " u ON u.id = t.user_id
                WHERE t.year = ? AND t.month = ? AND u.status = 'active'
                ORDER BY u.name ASC";
        return $this->q($sql, [$year, $month]);
    }

    public function activeUserIds(): array
    {
        $rows = $this->q(
            'SELECT id FROM ' . $this->table('users') . " WHERE status = 'active' AND role IN ('reporter','photographer')"
        );
        return array_map(static function ($r) {
            return (int)$r['id'];
        }, $rows);
    }

    public function listTables(): array
    {
        if (!$this->connect()) {
            return [];
        }
        try {
            $st = $this->pdo->query('SHOW TABLES');
            return $st->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            return [];
        }
    }

    public function countTable(string $table): int
    {
        if (!$this->connect()) {
            return 0;
        }
        try {
            $st = $this->pdo->query('SELECT COUNT(*) AS c FROM `' . $table . '`');
            $r = $st->fetch(PDO::FETCH_ASSOC);
            return (int)($r['c'] ?? 0);
        } catch (PDOException $e) {
            return 0;
        }
    }
}
