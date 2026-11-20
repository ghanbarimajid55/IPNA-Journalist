<?php
/**
 * Plugin Name:       IPNA Report
 * Description:       اتصال مستقیم به دیتابیس سامانه مدیریت عملکرد IPNA و تحلیل تعداد اخبار و عملکرد هر کاربر با مقایسه با اهداف و ارسال هشدار خودکار.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            IPNA
 * License:           GPL-2.0-or-later
 * Text Domain:       ipna-report
 */

if (!defined('ABSPATH')) {
    exit;
}

define('IPNA_REPORT_VERSION', '1.0.0');
define('IPNA_REPORT_DIR', plugin_dir_path(__FILE__));
define('IPNA_REPORT_URL', plugin_dir_url(__FILE__));

require_once IPNA_REPORT_DIR . 'includes/class-ipna-pms.php';
require_once IPNA_REPORT_DIR . 'includes/class-ipna-wp.php';
require_once IPNA_REPORT_DIR . 'includes/class-ipna-analyzer.php';
require_once IPNA_REPORT_DIR . 'includes/class-ipna-alerts.php';
require_once IPNA_REPORT_DIR . 'includes/class-ipna-admin.php';

function ipna_report_defaults(): array
{
    return [
        'source'          => 'wordpress',
        'post_types'      => 'post,gallery',
        'post_status'     => 'publish',
        'goal_news'       => 30,
        'cat_days'        => 14,
        'pms_host'        => 'localhost',
        'pms_dbname'      => 'ipna_pms',
        'pms_dbuser'      => '',
        'pms_dbpass'      => '',
        'pms_prefix'      => '',
        'alert_enabled'   => 0,
        'alert_threshold' => 0,
        'alert_email'     => get_option('admin_email'),
        'alert_caption'   => 'کاربرانی که به آستانه عملکرد رسیده‌اند',
    ];
}

function ipna_report_source(array $cfg)
{
    $source = $cfg['source'] ?? 'wordpress';
    return $source === 'pms' ? new IPNA_PMS($cfg) : new IPNA_Report_WP($cfg);
}

function ipna_report_g2j(int $gy, int $gm, int $gd): array
{
    $_d = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
    $jy = ($gy <= 1600) ? 0 : 979;
    $gy -= ($gy <= 1600) ? 621 : 1600;
    $gy2 = ($gm > 2) ? ($gy + 1) : $gy;
    $days = (365 * $gy) + intdiv($gy2 + 3, 4) - intdiv($gy2 + 99, 100) + intdiv($gy2 + 399, 400) - 80 + $gd + $_d[$gm - 1];
    $jy += 33 * intdiv($days, 12053);
    $days %= 12053;
    $jy += 4 * intdiv($days, 1461);
    $days %= 1461;
    if ($days > 365) {
        $jy += intdiv($days - 1, 365);
        $days = ($days - 1) % 365;
    }
    if ($days < 186) {
        $jm = 1 + intdiv($days, 31);
        $jd = 1 + ($days % 31);
    } else {
        $jm = 7 + intdiv($days - 186, 30);
        $jd = 1 + (($days - 186) % 30);
    }
    return [$jy, $jm, $jd];
}

function ipna_report_jalali(string $ymd): array
{
    $parts = array_map('intval', explode('-', $ymd));
    if (count($parts) !== 3) {
        return [0, 0, 0];
    }
    return ipna_report_g2j($parts[0], $parts[1], $parts[2]);
}

function ipna_report_jmonths(): array
{
    static $m = [
        1 => 'فروردین', 2 => 'اردیبهشت', 3 => 'خرداد', 4 => 'تیر',
        5 => 'مرداد', 6 => 'شهریور', 7 => 'مهر', 8 => 'آبان',
        9 => 'آذر', 10 => 'دی', 11 => 'بهمن', 12 => 'اسفند',
    ];
    return $m;
}

function ipna_report_jalali_short(string $ymd): string
{
    [$jy, $jm, $jd] = ipna_report_jalali($ymd);
    if ($jy === 0) {
        return $ymd;
    }
    $months = ipna_report_jmonths();
    return $jd . ' ' . ($months[$jm] ?? '');
}

function ipna_report_jalali_full(string $ymd): string
{
    [$jy, $jm, $jd] = ipna_report_jalali($ymd);
    if ($jy === 0) {
        return $ymd;
    }
    $months = ipna_report_jmonths();
    return $jd . ' ' . ($months[$jm] ?? '') . ' ' . $jy;
}

function ipna_report_settings(): array
{
    $saved = get_option('ipna_report_settings', []);
    $merged = array_merge(ipna_report_defaults(), is_array($saved) ? $saved : []);
    $merged['alert_email'] = $merged['alert_email'] ?: get_option('admin_email');
    return $merged;
}

add_action('plugins_loaded', function (): void {
    IPNA_Report_Admin::register();
    IPNA_Report_Alerts::register();
});

register_activation_hook(__FILE__, ['IPNA_Report_Alerts', 'activate']);
register_deactivation_hook(__FILE__, ['IPNA_Report_Alerts', 'deactivate']);
