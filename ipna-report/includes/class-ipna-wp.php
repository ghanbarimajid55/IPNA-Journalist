<?php
if (!defined('ABSPATH')) {
    exit;
}

class IPNA_Report_WP
{
    protected array $postTypes;
    protected array $postStatuses;

    public function __construct(array $cfg)
    {
        $types = array_values(array_filter(array_map('trim', explode(',', (string)($cfg['post_types'] ?? 'post,gallery')))));
        $statuses = array_values(array_filter(array_map('trim', explode(',', (string)($cfg['post_status'] ?? 'publish')))));

        $this->postTypes = $types ?: ['post'];
        $this->postStatuses = $statuses ?: ['publish'];
    }

    public function connect(): bool
    {
        return true;
    }

    protected function inList(array $arr): string
    {
        return implode(',', array_map(static function ($v) {
            return "'" . esc_sql((string)$v) . "'";
        }, $arr));
    }

    protected function rangeDates(string $from, string $to): array
    {
        return [$from . ' 00:00:00', $to . ' 23:59:59'];
    }

    public function usersWithStats(string $from, string $to): array
    {
        global $wpdb;
        $types = $this->inList($this->postTypes);
        $statuses = $this->inList($this->postStatuses);
        list($fd, $td) = $this->rangeDates($from, $to);

        $sql = "SELECT u.ID AS id, u.display_name AS name, u.user_login AS username,
                       COUNT(p.ID) AS activity_count,
                       SUM(CASE WHEN p.post_type = 'post' THEN 1 ELSE 0 END) AS news_qty,
                       SUM(CASE WHEN p.post_type <> 'post' THEN 1 ELSE 0 END) AS photo_qty,
                       0 AS video_qty
                FROM {$wpdb->users} u
                LEFT JOIN {$wpdb->posts} p
                       ON p.post_author = u.ID
                       AND p.post_type IN ($types)
                       AND p.post_status IN ($statuses)
                       AND p.post_date >= %s AND p.post_date <= %s
                GROUP BY u.ID, u.display_name, u.user_login
                ORDER BY activity_count DESC, u.display_name ASC";

        $rows = (array)$wpdb->get_results($wpdb->prepare($sql, $fd, $td), ARRAY_A);

        foreach ($rows as &$r) {
            $r['total_quantity'] = (int)($r['activity_count'] ?? 0);
            $r['total_score']    = (int)($r['activity_count'] ?? 0);
            $r['position']       = '';
            $u = get_userdata((int)$r['id']);
            $r['role'] = ($u && !empty($u->roles)) ? implode(', ', $u->roles) : '';
        }
        unset($r);

        return $rows;
    }

    public function typeTotals(string $from, string $to): array
    {
        global $wpdb;
        $types = $this->inList($this->postTypes);
        $statuses = $this->inList($this->postStatuses);
        list($fd, $td) = $this->rangeDates($from, $to);

        $sql = "SELECT p.post_type AS code,
                       p.post_type AS name,
                       (CASE WHEN p.post_type = 'post' THEN 'news' ELSE 'photo' END) AS category,
                       COUNT(*) AS cnt, COUNT(*) AS qty, COUNT(*) AS score
                FROM {$wpdb->posts} p
                WHERE p.post_type IN ($types) AND p.post_status IN ($statuses)
                  AND p.post_date >= %s AND p.post_date <= %s
                GROUP BY p.post_type
                ORDER BY cnt DESC";

        return (array)$wpdb->get_results($wpdb->prepare($sql, $fd, $td), ARRAY_A);
    }

    public function dailySeries(string $from, string $to): array
    {
        global $wpdb;
        $types = $this->inList($this->postTypes);
        $statuses = $this->inList($this->postStatuses);
        list($fd, $td) = $this->rangeDates($from, $to);

        $sql = "SELECT DATE(p.post_date) AS d, COUNT(*) AS qty, COUNT(*) AS score
                FROM {$wpdb->posts} p
                WHERE p.post_type IN ($types) AND p.post_status IN ($statuses)
                  AND p.post_date >= %s AND p.post_date <= %s
                GROUP BY DATE(p.post_date)
                ORDER BY d ASC";

        return (array)$wpdb->get_results($wpdb->prepare($sql, $fd, $td), ARRAY_A);
    }

    public function targets(int $year, int $month): array
    {
        global $wpdb;
        $goal = (float)(ipna_report_settings()['goal_news'] ?? 30);

        $rows = (array)$wpdb->get_results("SELECT u.ID AS user_id, u.display_name AS name, u.user_login AS username FROM {$wpdb->users} u", ARRAY_A);

        $out = [];
        foreach ($rows as $r) {
            $meta = get_user_meta((int)$r['user_id'], 'ipna_report_goal', true);
            $target = ($meta !== '' && $meta !== false) ? (float)$meta : $goal;
            $u = get_userdata((int)$r['user_id']);
            $out[] = [
                'user_id'               => (int)$r['user_id'],
                'name'                  => $r['name'],
                'role'                  => ($u && !empty($u->roles)) ? implode(', ', $u->roles) : '',
                'news_target'           => (int)$target,
                'exclusive_news_target' => 0,
                'interview_target'      => 0,
                'special_report_target' => 0,
                'photo_target'          => (int)$target,
                'gallery_target'        => (int)$target,
                'video_target'          => 0,
                'score_target'          => $target,
            ];
        }
        return $out;
    }

    public function activeUserIds(): array
    {
        global $wpdb;
        $rows = (array)$wpdb->get_results("SELECT ID FROM {$wpdb->users}", ARRAY_A);
        return array_map(static function ($r) {
            return (int)$r['ID'];
        }, $rows);
    }

    public function userCategoryDaily(string $from, string $to): array
    {
        global $wpdb;
        $types = $this->inList($this->postTypes);
        $statuses = $this->inList($this->postStatuses);
        list($fd, $td) = $this->rangeDates($from, $to);

        $sql = "SELECT u.ID AS id, u.display_name AS name, t.name AS category,
                       DATE(p.post_date) AS d, COUNT(*) AS cnt
                FROM {$wpdb->posts} p
                JOIN {$wpdb->term_relationships} tr ON tr.object_id = p.ID
                JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id AND tt.taxonomy = 'category'
                JOIN {$wpdb->terms} t ON t.term_id = tt.term_id
                JOIN {$wpdb->users} u ON u.ID = p.post_author
                WHERE p.post_type IN ($types) AND p.post_status IN ($statuses)
                  AND p.post_date >= %s AND p.post_date <= %s
                GROUP BY u.ID, u.display_name, t.name, DATE(p.post_date)
                ORDER BY u.display_name ASC, t.name ASC, d DESC";

        return (array)$wpdb->get_results($wpdb->prepare($sql, $fd, $td), ARRAY_A);
    }
}
