<?php
/**
 * Plugin Name:       IPNA Sports News
 * Description:       موتور جست‌وجو و جمع‌آوری اخبار ورزشی از سایت‌های معرفی‌شده (RSS + استخراج صفحه) با بازنویسی تیتر و متن با هوش مصنوعی و ذخیره به‌صورت پیش‌نویس وردپرس.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            IPNA
 * License:           GPL-2.0-or-later
 * Text Domain:       ipna-sports
 */

if (!defined('ABSPATH')) {
    exit;
}

define('IPNA_SPORTS_VERSION', '1.0.0');
define('IPNA_SPORTS_DIR', plugin_dir_path(__FILE__));
define('IPNA_SPORTS_URL', plugin_dir_url(__FILE__));

require_once IPNA_SPORTS_DIR . 'includes/class-ipna-rss.php';
require_once IPNA_SPORTS_DIR . 'includes/class-ipna-extract.php';
require_once IPNA_SPORTS_DIR . 'includes/class-ipna-ai.php';
require_once IPNA_SPORTS_DIR . 'includes/class-ipna-create.php';
require_once IPNA_SPORTS_DIR . 'includes/class-ipna-aggregator.php';

function ipna_sports_defaults(): array
{
    return [
        'enabled'      => 0,
        'interval'     => 'hourly',
        'max_items'    => 10,
        'provider'     => 'openai',
        'api_key'      => '',
        'model'        => 'gpt-4o-mini',
        'language'     => 'persian',
        'keywords'     => 'فوتبال, والیبال, کشتی, وزنه‌برداری, ورزش, لیگ برتر',
        'categories'   => 'فوتبال, والیبال, کشتی, وزنه‌برداری, بسکتبال, ورزش',
        'post_type'    => 'post',
        'post_status'  => 'draft',
        'post_author'  => 1,
        'set_featured' => 1,
    ];
}

function ipna_sports_settings(): array
{
    $saved = get_option('ipna_sports_settings', []);
    return array_merge(ipna_sports_defaults(), is_array($saved) ? $saved : []);
}

function ipna_sports_sources(): array
{
    $raw = get_option('ipna_sports_sources', []);
    if (!is_array($raw)) {
        return [];
    }
    $out = [];
    foreach ($raw as $s) {
        if (is_array($s) && !empty($s['name']) && !empty($s['feed'])) {
            $out[] = [
                'name'     => (string)$s['name'],
                'feed'     => (string)$s['feed'],
                'selector' => isset($s['selector']) ? (string)$s['selector'] : '',
                'enabled'  => !empty($s['enabled']) ? 1 : 0,
            ];
        }
    }
    return $out;
}

add_action('plugins_loaded', function (): void {
    IPNA_Sports_Aggregator::register();
});

register_activation_hook(__FILE__, ['IPNA_Sports_Aggregator', 'activate']);
register_deactivation_hook(__FILE__, ['IPNA_Sports_Aggregator', 'deactivate']);
