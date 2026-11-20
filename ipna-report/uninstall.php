<?php
if (!defined('ABSPATH')) {
    exit;
}

delete_option('ipna_report_settings');
delete_option('ipna_report_alert_sent');
wp_clear_scheduled_hook('ipna_report_daily');
