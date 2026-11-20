<?php
if (!defined('ABSPATH')) {
    exit;
}

class IPNA_Report_Alerts
{
    public static function register(): void
    {
        add_action('ipna_report_daily', [__CLASS__, 'run']);
    }

    public static function activate(): void
    {
        if (!wp_next_scheduled('ipna_report_daily')) {
            wp_schedule_event(strtotime('tomorrow 08:00'), 'daily', 'ipna_report_daily');
        }
    }

    public static function deactivate(): void
    {
        wp_clear_scheduled_hook('ipna_report_daily');
    }

    public static function run(): void
    {
        $cfg = ipna_report_settings();
        if (empty($cfg['alert_enabled']) || empty($cfg['pms_dbname'])) {
            return;
        }

        $source = ipna_report_source($cfg);
        if (!$source->connect()) {
            return;
        }

        $now   = current_time('timestamp');
        $year  = (int)date('Y', $now);
        $month = (int)date('n', $now);
        $from  = date('Y-m-01', $now);
        $to    = date('Y-m-d', $now);

        $users   = $source->usersWithStats($from, $to);
        $targets = $source->targets($year, $month);

        $threshold = (float)$cfg['alert_threshold'];
        $sent      = get_option('ipna_report_alert_sent', []);
        if (!is_array($sent)) {
            $sent = [];
        }

        $list = [];
        foreach ($users as $u) {
            $score = (float)$u['total_score'];
            if ($threshold > 0 && $score < $threshold) {
                continue;
            }
            $key = (int)$u['id'] . ':' . $year . '-' . $month;
            if (isset($sent[$key])) {
                continue;
            }
            $list[] = sprintf('%s (%s) — امتیاز %s — تعداد %d', $u['name'], $u['role'], $score, (int)$u['total_quantity']);
            $sent[$key] = time();
        }

        if (!$list) {
            return;
        }

        $toEmail = (string)$cfg['alert_email'];
        if (empty($toEmail)) {
            return;
        }

        $subject = 'IPNA Report — هشدار عملکرد ' . $year . '/' . $month;
        $body    = (string)$cfg['alert_caption'] . " (" . $year . "/" . $month . "):\r\n\r\n" . implode("\r\n", $list);
        $headers = ['Content-Type: text/plain; charset=UTF-8'];

        wp_mail($toEmail, $subject, $body, $headers);
        update_option('ipna_report_alert_sent', $sent, false);
    }
}
