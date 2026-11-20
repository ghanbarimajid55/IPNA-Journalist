<?php
/**
 * Plugin Name:       IPNA Login Notice
 * Description:       نمایش پیام خوش‌آمد (پاپ‌آپ) بعد از ورود کاربر و پیام خداحافظی بعد از خروج.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            IPNA
 * License:           GPL-2.0-or-later
 * Text Domain:       ipna-login-notice
 */

if (!defined('ABSPATH')) {
    exit;
}

define('IPNA_LN_VERSION', '1.0.0');

function ipna_ln_defaults(): array
{
    return [
        'enabled' => 1,
        'login'   => '{name} عزیز، از اینکه شروع به انتشار خبر کردی خیلی خوشحالیم. ایپنا به تو افتخار می‌کند. موفق باشی!',
        'logout'  => '{name} جان خسته نباشی. ممنون از زحمتی که کشیدی.',
    ];
}

function ipna_ln_settings(): array
{
    $saved = get_option('ipna_ln_settings', []);
    return array_merge(ipna_ln_defaults(), is_array($saved) ? $saved : []);
}

add_action('plugins_loaded', function (): void {
    IPNA_Login_Notice::register();
});

class IPNA_Login_Notice
{
    public static function register(): void
    {
        add_action('wp_login', [__CLASS__, 'on_login'], 10, 2);
        add_action('wp_logout', [__CLASS__, 'on_logout']);
        add_filter('wp_logout_redirect', [__CLASS__, 'logout_redirect'], 10, 3);
        add_action('wp_footer', [__CLASS__, 'maybe_show']);
        add_action('admin_footer', [__CLASS__, 'maybe_show']);
        add_action('admin_menu', [__CLASS__, 'menu']);
        add_action('admin_post_ipna_ln_save', [__CLASS__, 'save']);
    }

    public static function on_login(string $login, WP_User $user): void
    {
        $s = ipna_ln_settings();
        if (empty($s['enabled'])) {
            return;
        }
        set_transient('ipna_ln_login_' . $user->ID, self::user_name($user), 300);
    }

    public static function on_logout(): void
    {
        if (empty(ipna_ln_settings()['enabled'])) {
            return;
        }
        $u = wp_get_current_user();
        if ($u && $u->ID) {
            set_transient('ipna_ln_logout', self::user_name($u), 300);
        }
    }

    public static function logout_redirect(string $redirect, string $requested, WP_User $user)
    {
        return add_query_arg('ipna_loggedout', '1', home_url('/'));
    }

    public static function maybe_show(): void
    {
        $s = ipna_ln_settings();
        if (empty($s['enabled'])) {
            return;
        }

        if (!is_admin() && isset($_GET['ipna_loggedout'])) {
            $name = get_transient('ipna_ln_logout');
            if ($name) {
                delete_transient('ipna_ln_logout');
                self::render(str_replace('{name}', $name, (string)$s['logout']));
            }
            return;
        }

        if (!is_user_logged_in()) {
            return;
        }
        $uid = get_current_user_id();
        $key = 'ipna_ln_login_' . $uid;
        $name = get_transient($key);
        if ($name) {
            delete_transient($key);
            self::render(str_replace('{name}', $name, (string)$s['login']));
        }
    }

    protected static function user_name(WP_User $user): string
    {
        $display = trim((string)$user->display_name);
        if ($display !== '') {
            return $display;
        }
        $fn = trim((string)$user->first_name);
        if ($fn !== '') {
            return $fn;
        }
        $login = trim((string)$user->user_login);
        return $login !== '' ? $login : 'کاربر';
    }

    protected static function render(string $message): void
    {
        echo '<style>';
        echo '#ipna-pop-overlay{position:fixed;inset:0;background:rgba(0,0,0,0.55);z-index:999999;display:flex;align-items:center;justify-content:center}';
        echo '#ipna-pop{background:#fff;border-radius:14px;padding:28px 30px;max-width:420px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,0.3);text-align:center;position:relative;font-family:Vazirmatn,Tahoma,sans-serif}';
        echo '#ipna-pop p{margin:0;font-size:15px;line-height:2;color:#1d2327}';
        echo '#ipna-pop-close{position:absolute;top:10px;left:14px;background:none;border:0;font-size:22px;color:#999;cursor:pointer;font-weight:700}';
        echo '</style>';
        echo '<div id="ipna-pop-overlay"><div id="ipna-pop"><button id="ipna-pop-close" type="button">&times;</button><p>' . esc_html($message) . '</p></div></div>';
        echo '<script>';
        echo ';(function(){var o=document.getElementById("ipna-pop-overlay");if(!o)return;';
        echo 'var c=document.getElementById("ipna-pop-close");function h(){o.remove()}';
        echo 'if(c){c.addEventListener("click",h)}';
        echo 'o.addEventListener("click",function(e){if(e.target===o)h()});';
        echo 'setTimeout(h,12000);})();';
        echo '</script>';
    }

    public static function menu(): void
    {
        add_options_page('پیام ورود/خروج', 'پیام ورود/خروج', 'manage_options', 'ipna-login-notice', [__CLASS__, 'render_settings']);
    }

    public static function render_settings(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('دسترسی غیرمجاز');
        }
        $s  = ipna_ln_settings();
        $msg = isset($_GET['ipna_msg']) ? sanitize_text_field($_GET['ipna_msg']) : '';

        echo '<div class="wrap"><h1>پیام ورود / خروج</h1>';
        if ($msg) {
            echo '<div class="notice notice-info"><p>' . esc_html($msg) . '</p></div>';
        }
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        echo '<input type="hidden" name="action" value="ipna_ln_save">';
        wp_nonce_field('ipna_ln_save');
        echo '<table class="form-table">';
        echo '<tr><th>فعال</th><td><label><input type="checkbox" name="enabled" value="1" ' . checked(1, (int)$s['enabled'], false) . '> نمایش پاپ‌آپ</label></td></tr>';
        echo '<tr><th>پیام ورود</th><td><textarea name="login" rows="3" class="large-text">' . esc_textarea($s['login']) . '</textarea><p class="description">از <code>{name}</code> برای نام کاربر استفاده کنید.</p></td></tr>';
        echo '<tr><th>پیام خروج</th><td><textarea name="logout" rows="2" class="large-text">' . esc_textarea($s['logout']) . '</textarea><p class="description">از <code>{name}</code> برای نام کاربر استفاده کنید.</p></td></tr>';
        echo '</table>';
        submit_button('ذخیره');
        echo '</form></div>';
    }

    public static function save(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('دسترسی غیرمجاز');
        }
        check_admin_referer('ipna_ln_save');
        update_option('ipna_ln_settings', [
            'enabled' => !empty($_POST['enabled']) ? 1 : 0,
            'login'   => isset($_POST['login']) ? sanitize_textarea_field($_POST['login']) : ipna_ln_defaults()['login'],
            'logout'  => isset($_POST['logout']) ? sanitize_textarea_field($_POST['logout']) : ipna_ln_defaults()['logout'],
        ]);
        wp_safe_redirect(add_query_arg('ipna_msg', urlencode('تنظیمات ذخیره شد.'), admin_url('options-general.php?page=ipna-login-notice')));
        exit;
    }
}
