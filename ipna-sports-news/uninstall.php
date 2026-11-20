<?php
if (!defined('ABSPATH')) {
    exit;
}

delete_option('ipna_sports_settings');
delete_option('ipna_sports_sources');
delete_option('ipna_sports_last_result');
wp_clear_scheduled_hook('ipna_sports_cron');
