<?php
/**
 * Plugin Name:       IPNA Channel Publisher
 * Description:       با انتشار هر خبر (post) در وردپرس، تیتر، تصویر شاخص و لینک آن را به‌صورت خودکار در کانال تلگرام و کانال بله ارسال می‌کند.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            IPNA
 * License:           GPL-2.0-or-later
 * Text Domain:       ipna-channel
 */

if (!defined('ABSPATH')) {
    exit;
}

define('IPNA_CHAN_VERSION', '1.0.0');

function ipna_chan_defaults(): array
{
    return [
        'enabled'          => 1,
        'send_photo'       => 1,
        'post_types'       => 'post',
        'telegram_enabled' => 0,
        'telegram_token'   => '',
        'telegram_channel' => '',
        'bale_enabled'     => 0,
        'bale_token'       => '',
        'bale_channel'     => '',
        'template'         => "📰 <b>{title}</b>\n🏷️ {category}\n📅 {date}\n{tags}\n🔗 <a href=\"{link}\">مشاهده خبر</a>",
    ];
}

function ipna_chan_settings(): array
{
    $saved = get_option('ipna_chan_settings', []);
    return array_merge(ipna_chan_defaults(), is_array($saved) ? $saved : []);
}

function ipna_chan_persian_date(string $datetime): string
{
    $ts = strtotime($datetime);
    if ($ts === false) {
        $ts = time();
    }

    if (class_exists('IntlCalendar')) {
        try {
            $cal = IntlCalendar::createInstance('Asia/Tehran', 'fa_IR@calendar=persian');
            $cal->setTime($ts * 1000);
            $jy = $cal->get(IntlCalendar::FIELD_YEAR);
            $jm = $cal->get(IntlCalendar::FIELD_MONTH) + 1;
            $jd = $cal->get(IntlCalendar::FIELD_DAY_OF_MONTH);
            if ($jy > 0) {
                return ipna_chan_format_persian($jy, $jm, $jd);
            }
        } catch (Throwable $e) {
            // fall back to manual conversion below
        }
    }

    $g = getdate($ts);
    [$jy, $jm, $jd] = ipna_chan_gregorian_to_jalali((int)$g['year'], (int)$g['mon'], (int)$g['mday']);
    return ipna_chan_format_persian($jy, $jm, $jd);
}

function ipna_chan_format_persian(int $y, int $m, int $d): string
{
    static $months = [
        1 => 'فروردین', 2 => 'اردیبهشت', 3 => 'خرداد', 4 => 'تیر',
        5 => 'مرداد', 6 => 'شهریور', 7 => 'مهر', 8 => 'آبان',
        9 => 'آذر', 10 => 'دی', 11 => 'بهمن', 12 => 'اسفند',
    ];
    $mn = $months[$m] ?? '';
    return $d . ' ' . $mn . ' ' . $y;
}

function ipna_chan_gregorian_to_jalali(int $gy, int $gm, int $gd): array
{
    $gdm = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
    $jy = ($gy <= 1600) ? 0 : 979;
    $gy -= ($gy <= 1600) ? 621 : 1600;
    $gy2 = ($gm > 2) ? ($gy + 1) : $gy;
    $days = (365 * $gy) + intdiv($gy2 + 3, 4) - intdiv($gy2 + 99, 100) + intdiv($gy2 + 399, 400) - 80 + $gd + $gdm[$gm - 1];
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

add_action('transition_post_status', function (string $new, string $old, WP_Post $post): void {
    if ($new !== 'publish' || $old === 'publish') {
        return;
    }
    $settings = ipna_chan_settings();
    if (empty($settings['enabled'])) {
        return;
    }
    if (get_post_meta($post->ID, '_ipna_chan_sent', true) === '1') {
        return;
    }
    wp_schedule_single_event(time(), 'ipna_chan_publish', [$post->ID]);
}, 10, 3);

add_action('ipna_chan_publish', 'ipna_chan_publish_now');

function ipna_chan_publish_now(int $post_id): void
{
    $settings = ipna_chan_settings();
    if (empty($settings['enabled'])) {
        return;
    }
    if (get_post_meta($post_id, '_ipna_chan_sent', true) === '1') {
        return;
    }

    $post = get_post($post_id);
    if (!$post || $post->post_status !== 'publish') {
        return;
    }

    $types = array_values(array_filter(array_map('trim', explode(',', (string)$settings['post_types']))));
    if ($types && !in_array($post->post_type, $types, true)) {
        return;
    }

    $title   = get_the_title($post_id);
    $link    = get_permalink($post_id);
    $image   = !empty($settings['send_photo']) ? (string)get_the_post_thumbnail_url($post_id, 'large') : '';

    $cats     = get_the_category($post_id);
    $category = $cats ? $cats[0]->name : '';
    $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $safeCat   = htmlspecialchars($category, ENT_QUOTES, 'UTF-8');
    $href      = htmlspecialchars($link, ENT_QUOTES, 'UTF-8');
    $date      = ipna_chan_persian_date((string)$post->post_date);

    $tagNames = [];
    $tags = get_the_tags($post_id);
    if ($tags) {
        foreach (array_slice($tags, 0, 5) as $t) {
            $tagNames[] = htmlspecialchars($t->name, ENT_QUOTES, 'UTF-8');
        }
    }

    $tagList = implode('، ', $tagNames);

    $template = (string)($settings['template'] ?? '');
    if ($template === '') {
        $template = ipna_chan_defaults()['template'];
    }

    $vars = [
        '{title}'     => $safeTitle,
        '{category}'  => $safeCat,
        '{date}'      => $date,
        '{tags}'      => $tagList,
        '{link}'      => $href,
        '{link_text}' => 'مشاهده خبر',
    ];
    $caption = strtr($template, $vars);
    $caption = preg_replace('/[ \t]*\n[ \t]*\n+/', "\n", trim($caption));

    if (!empty($settings['telegram_enabled'])) {
        ipna_chan_post_api('telegram', $settings['telegram_token'], $settings['telegram_channel'], $image, $caption);
    }
    if (!empty($settings['bale_enabled'])) {
        ipna_chan_post_api('bale', $settings['bale_token'], $settings['bale_channel'], $image, $caption);
    }

    update_post_meta($post_id, '_ipna_chan_sent', '1');
}

function ipna_chan_post_api(string $provider, string $token, string $channel, string $image, string $caption): bool
{
    if ($token === '' || $channel === '') {
        return false;
    }
    $base = $provider === 'bale'
        ? 'https://tapi.bale.ai/bot' . $token
        : 'https://api.telegram.org/bot' . $token;

    $payload = [
        'chat_id'    => $channel,
        'parse_mode' => 'HTML',
    ];

    if ($image !== '') {
        $url = $base . '/sendPhoto';
        $payload['photo']   = $image;
        $payload['caption'] = $caption;
    } else {
        $url = $base . '/sendMessage';
        $payload['text'] = $caption;
    }

    $response = wp_remote_post($url, [
        'timeout'     => 12,
        'redirection' => 0,
        'body'        => $payload,
    ]);

    if (is_wp_error($response)) {
        error_log('IPNA Channel: ' . $provider . ' error — ' . $response->get_error_message());
        return false;
    }

    $code = (int)wp_remote_retrieve_response_code($response);
    if ($code >= 400) {
        error_log('IPNA Channel: ' . $provider . ' HTTP ' . $code . ' — ' . wp_remote_retrieve_body($response));
        return false;
    }

    return true;
}

add_action('admin_menu', function (): void {
    add_options_page(
        'IPNA انتشار کانال',
        'IPNA انتشار کانال',
        'manage_options',
        'ipna-channel',
        'ipna_chan_render_settings'
    );
});

function ipna_chan_render_settings(): void
{
    if (!current_user_can('manage_options')) {
        wp_die('دسترسی غیرمجاز');
    }
    $cfg = ipna_chan_settings();
    $msg = isset($_GET['ipna_msg']) ? sanitize_text_field($_GET['ipna_msg']) : '';

    echo '<div class="wrap"><h1>انتشار خودکار به تلگرام و بله</h1>';
    if ($msg) {
        echo '<div class="notice notice-info"><p>' . esc_html($msg) . '</p></div>';
    }

    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
    echo '<input type="hidden" name="action" value="ipna_chan_save">';
    wp_nonce_field('ipna_chan_save');

    echo '<table class="form-table">';
    echo '<tr><th>فعال بودن</th><td><label><input type="checkbox" name="enabled" value="1" ' . checked(1, (int)$cfg['enabled'], false) . '> فعال‌سازی ارسال خودکار</label></td></tr>';
    echo '<tr><th>ارسال تصویر شاخص</th><td><label><input type="checkbox" name="send_photo" value="1" ' . checked(1, (int)$cfg['send_photo'], false) . '> همراه با عکس</label></td></tr>';
    echo '<tr><th>پست‌تیپ‌های خبر</th><td><input type="text" name="post_types" value="' . esc_attr($cfg['post_types']) . '" placeholder="post,news" class="regular-text"><p class="description">با کاما جدا کنید؛ به‌طور پیش‌فرض <code>post</code>.</p></td></tr>';
    echo '<tr><th>قالب پیام</th><td><textarea name="template" rows="6" class="large-text">' . esc_textarea($cfg['template']) . '</textarea>';
    echo '<p class="description">جای‌نگه‌ها: <code>{title}</code> تیتر، <code>{category}</code> دسته‌بندی، <code>{date}</code> تاریخ شمسی، <code>{tags}</code> برچسب‌ها، <code>{link}</code> لینک، <code>{link_text}</code> متن لینک. از HTML (مثل <code>&lt;b&gt;</code> و <code>&lt;a&gt;</code>) می‌توانید استفاده کنید.</p></td></tr>';
    echo '</table>';

    echo '<h2>تلگرام</h2>';
    echo '<table class="form-table">';
    echo '<tr><th>فعال</th><td><label><input type="checkbox" name="telegram_enabled" value="1" ' . checked(1, (int)$cfg['telegram_enabled'], false) . '> ارسال به تلگرام</label></td></tr>';
    echo '<tr><th>توکن ربات</th><td><input type="text" name="telegram_token" value="' . esc_attr($cfg['telegram_token']) . '" placeholder="123456:ABC..." class="regular-text" autocomplete="off"></td></tr>';
    echo '<tr><th>شناسه‌ی کانال</th><td><input type="text" name="telegram_channel" value="' . esc_attr($cfg['telegram_channel']) . '" placeholder="مثلاً @mychannel یا -1001234567890" class="regular-text"></td></tr>';
    echo '</table>';

    echo '<h2>بله</h2>';
    echo '<table class="form-table">';
    echo '<tr><th>فعال</th><td><label><input type="checkbox" name="bale_enabled" value="1" ' . checked(1, (int)$cfg['bale_enabled'], false) . '> ارسال به بله</label></td></tr>';
    echo '<tr><th>توکن ربات</th><td><input type="text" name="bale_token" value="' . esc_attr($cfg['bale_token']) . '" placeholder="توکن ربات بله" class="regular-text" autocomplete="off"></td></tr>';
    echo '<tr><th>شناسه‌ی کانال</th><td><input type="text" name="bale_channel" value="' . esc_attr($cfg['bale_channel']) . '" placeholder="مثلاً @mychannel" class="regular-text"></td></tr>';
    echo '</table>';

    submit_button('ذخیره تنظیمات');
    echo '</form>';

    echo '<hr><h2>تست</h2>';
    echo '<p>یک پیام آزمایشی به کانال‌های تنظیم‌شده بفرستید:</p>';
    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
    echo '<input type="hidden" name="action" value="ipna_chan_test">';
    wp_nonce_field('ipna_chan_save');
    submit_button('ارسال پیام آزمایشی', 'secondary', 'send_test');
    echo '</form>';

    echo '</div>';
}

add_action('admin_post_ipna_chan_save', function (): void {
    if (!current_user_can('manage_options')) {
        wp_die('دسترسی غیرمجاز');
    }
    check_admin_referer('ipna_chan_save');

    $cfg = [
        'enabled'          => !empty($_POST['enabled']) ? 1 : 0,
        'send_photo'       => !empty($_POST['send_photo']) ? 1 : 0,
        'post_types'       => isset($_POST['post_types']) ? sanitize_text_field($_POST['post_types']) : 'post',
        'telegram_enabled' => !empty($_POST['telegram_enabled']) ? 1 : 0,
        'telegram_token'   => isset($_POST['telegram_token']) ? sanitize_text_field($_POST['telegram_token']) : '',
        'telegram_channel' => isset($_POST['telegram_channel']) ? sanitize_text_field($_POST['telegram_channel']) : '',
        'bale_enabled'     => !empty($_POST['bale_enabled']) ? 1 : 0,
        'bale_token'       => isset($_POST['bale_token']) ? sanitize_text_field($_POST['bale_token']) : '',
        'bale_channel'     => isset($_POST['bale_channel']) ? sanitize_text_field($_POST['bale_channel']) : '',
        'template'         => isset($_POST['template']) ? sanitize_textarea_field($_POST['template']) : ipna_chan_defaults()['template'],
    ];

    update_option('ipna_chan_settings', $cfg);
    wp_safe_redirect(add_query_arg('ipna_msg', urlencode('تنظیمات ذخیره شد.'), admin_url('options-general.php?page=ipna-channel')));
    exit;
});

add_action('admin_post_ipna_chan_test', function (): void {
    if (!current_user_can('manage_options')) {
        wp_die('دسترسی غیرمجاز');
    }
    check_admin_referer('ipna_chan_save');

    $cfg = ipna_chan_settings();
    $caption = 'پیام آزمایشی از پلاگین IPNA.';
    $telegram = null;
    $bale = null;

    if (!empty($cfg['telegram_enabled'])) {
        $telegram = ipna_chan_post_api('telegram', $cfg['telegram_token'], $cfg['telegram_channel'], '', $caption);
    }
    if (!empty($cfg['bale_enabled'])) {
        $bale = ipna_chan_post_api('bale', $cfg['bale_token'], $cfg['bale_channel'], '', $caption);
    }

    $parts = [];
    $parts[] = $telegram === null ? 'تلگرام: غیرفعال' : ($telegram ? 'تلگرام: ✔ موفق' : 'تلگرام: ✖ ناموفق (لاگ را ببینید)');
    $parts[] = $bale === null ? 'بله: غیرفعال' : ($bale ? 'بله: ✔ موفق' : 'بله: ✖ ناموفق (لاگ را ببینید)');
    $msg = implode(' — ', $parts);

    wp_safe_redirect(add_query_arg('ipna_msg', urlencode($msg), admin_url('options-general.php?page=ipna-channel')));
    exit;
});
