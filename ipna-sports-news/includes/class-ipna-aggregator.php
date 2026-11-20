<?php
if (!defined('ABSPATH')) {
    exit;
}

class IPNA_Sports_Aggregator
{
    public static function register(): void
    {
        add_action('ipna_sports_cron', [__CLASS__, 'run']);
        add_filter('cron_schedules', [__CLASS__, 'schedules']);
        add_action('admin_menu', [__CLASS__, 'menu']);
        add_action('admin_post_ipna_sports_save', [__CLASS__, 'save']);
        add_action('admin_post_ipna_sports_run', [__CLASS__, 'run_now']);
        add_action('admin_post_ipna_sports_test', [__CLASS__, 'test_feed']);
    }

    public static function schedules(array $s): array
    {
        $s['ipna_hourly'] = ['interval' => 3600, 'display' => 'هر ساعت'];
        $s['ipna_6h']     = ['interval' => 21600, 'display' => 'هر ۶ ساعت'];
        $s['ipna_12h']    = ['interval' => 43200, 'display' => 'هر ۱۲ ساعت'];
        return $s;
    }

    public static function activate(): void
    {
        if (!wp_next_scheduled('ipna_sports_cron')) {
            wp_schedule_event(time(), 'ipna_hourly', 'ipna_sports_cron');
        }
    }

    public static function deactivate(): void
    {
        wp_clear_scheduled_hook('ipna_sports_cron');
    }

    public static function is_seen(string $hash): bool
    {
        $q = get_posts([
            'post_type'   => 'any',
            'post_status' => 'any',
            'numberposts' => 1,
            'meta_key'    => '_ipna_src_hash',
            'meta_value'  => $hash,
            'fields'      => 'ids',
        ]);
        return !empty($q);
    }

    public static function matches(array $article): bool
    {
        $s = ipna_sports_settings();
        $haystack = mb_strtolower(implode(' ', array_merge(
            [$article['title'], $article['subtitle']],
            (array)$article['tags']
        )) . "\n" . mb_substr(mb_strtolower((string)$article['body']), 0, 3000));

        $terms = array_values(array_filter(array_map('trim', preg_split('/[,،]+/u', (string)$s['keywords']))));
        $categories = array_values(array_filter(array_map('trim', preg_split('/[,،]+/u', (string)$s['categories']))));
        $all = array_merge($terms, $categories);

        if (empty($all)) {
            return true;
        }

        foreach ($all as $t) {
            if ($t !== '' && mb_strpos($haystack, mb_strtolower($t)) !== false) {
                return true;
            }
        }
        return false;
    }

    public static function run(): array
    {
        $s = ipna_sports_settings();
        if (empty($s['enabled'])) {
            return ['created' => 0, 'skipped' => 0, 'error' => ''];
        }

        $sources = ipna_sports_sources();
        $limit   = max(1, (int)$s['max_items']);
        $made    = 0;
        $skipped = 0;
        $lastError = '';

        foreach ($sources as $source) {
            if (!$source['enabled']) {
                continue;
            }
            $items = IPNA_Sports_RSS::fetch($source['feed']);
            if (!$items) {
                continue;
            }

            foreach ($items as $item) {
                if ($made >= $limit) {
                    break;
                }
                if (self::is_seen($item['hash'])) {
                    $skipped++;
                    continue;
                }

                $article = IPNA_Sports_Extract::article($item, $source['selector']);
                $article['sports_category'] = self::detect_category($article);

                if (!self::matches($article)) {
                    $skipped++;
                    continue;
                }

                $rewritten = IPNA_Sports_AI::rewrite($article['title'], $article['body'], $s);
                $article['title'] = $rewritten['title'];
                $article['body']  = $rewritten['body'];

                $post_id = IPNA_Sports_Create::create($article, $source, $s);
                if ($post_id) {
                    update_post_meta($post_id, '_ipna_rewritten', '1');
                    $made++;
                    if ($made >= $limit) {
                        break;
                    }
                }
            }
            if ($made >= $limit) {
                break;
            }
        }

        return ['created' => $made, 'skipped' => $skipped, 'error' => $lastError];
    }

    protected static function detect_category(array $article): string
    {
        $text = mb_strtolower($article['title'] . ' ' . implode(' ', (array)$article['tags']));
        $categories = array_values(array_filter(array_map('trim', preg_split('/[,،]+/u', (string)ipna_sports_settings()['categories']))));
        foreach ($categories as $c) {
            if ($c !== '' && mb_strpos($text, mb_strtolower($c)) !== false) {
                return $c;
            }
        }
        return '';
    }

    public static function menu(): void
    {
        add_menu_page(
            'IPNA Sports News',
            'اخبار ورزشی',
            'manage_options',
            'ipna-sports',
            [__CLASS__, 'render_settings'],
            'dashicons-megaphone',
            27
        );
    }

    public static function render_settings(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('دسترسی غیرمجاز');
        }
        $s = ipna_sports_settings();
        $sources = ipna_sports_sources();
        $msg = isset($_GET['ipna_msg']) ? sanitize_text_field($_GET['ipna_msg']) : '';
        $last = get_option('ipna_sports_last_result', []);

        echo '<div class="wrap"><h1>موتور جمع‌آوری اخبار ورزشی</h1>';
        if ($msg) {
            echo '<div class="notice notice-info"><p>' . esc_html($msg) . '</p></div>';
        }
        if (is_array($last) && (isset($last['created']) || isset($last['skipped']))) {
            echo '<div class="notice notice-info"><p>آخرین اجرا: ساخته‌شده ' . esc_html((string)($last['created'] ?? 0)) . ' — نادیده‌گرفته ' . esc_html((string)($last['skipped'] ?? 0)) . '</p></div>';
        }

        echo '<h2>منابع خبری</h2>';
        echo '<p class="description">هر خط یک منبع: <code>نام | آدرس RSS | (اختیاری) انتخابگر بدنه </code>. خطی که با <code>#</code> یا <code>//</code> شروع شود نادیده گرفته می‌شود.</p>';

        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        echo '<input type="hidden" name="action" value="ipna_sports_save">';
        wp_nonce_field('ipna_sports_save');

        $sourcesText = self::sources_to_text($sources);
        if ($sourcesText === '') {
            $sourcesText = self::default_sources_text();
        }
        echo '<textarea name="sources_text" rows="10" class="large-text">' . esc_textarea($sourcesText) . '</textarea>';

        echo '<h2>کلمات کلیدی و دسته‌بندی</h2>';
        echo '<table class="form-table">';
        echo '<tr><th>کلمات کلیدی (جدا شده با کاما)</th><td><input type="text" name="keywords" value="' . esc_attr($s['keywords']) . '" class="regular-text"></td></tr>';
        echo '<tr><th>دسته‌های ورزشی</th><td><input type="text" name="categories" value="' . esc_attr($s['categories']) . '" class="regular-text"></td></tr>';
        echo '</table>';

        echo '<h2>هوش مصنوعی</h2>';
        echo '<table class="form-table">';
        echo '<tr><th>سرویس</th><td>';
        echo '<label><input type="radio" name="provider" value="openai" ' . checked('openai', $s['provider'], false) . '> OpenAI</label> &nbsp; ';
        echo '<label><input type="radio" name="provider" value="gemini" ' . checked('gemini', $s['provider'], false) . '> Google Gemini</label>';
        echo '</td></tr>';
        echo '<tr><th>کلید API</th><td><input type="password" name="api_key" value="' . esc_attr($s['api_key']) . '" class="regular-text" autocomplete="off"></td></tr>';
        echo '<tr><th>مدل</th><td><input type="text" name="model" value="' . esc_attr($s['model']) . '" class="regular-text"> <span class="description">پیش‌فرض: gpt-4o-mini یا gemini-1.5-flash</span></td></tr>';
        echo '<tr><th>زبان بازنویسی</th><td><select name="language"><option value="persian" ' . selected('persian', $s['language'], false) . '>فارسی</option><option value="english" ' . selected('english', $s['language'], false) . '>English</option></select></td></tr>';
        echo '</table>';

        echo '<h2>خروجی</h2>';
        echo '<table class="form-table">';
        echo '<tr><th>فعال</th><td><label><input type="checkbox" name="enabled" value="1" ' . checked(1, (int)$s['enabled'], false) . '> فعال‌سازی جمع‌آوری زمان‌بندی‌شده</label></td></tr>';
        echo '<tr><th>زمان‌بندی</th><td><select name="interval"><option value="hourly">هر ساعت</option><option value="ipna_6h" ' . selected('ipna_6h', $s['interval'], false) . '>هر ۶ ساعت</option><option value="ipna_12h" ' . selected('ipna_12h', $s['interval'], false) . '>هر ۱۲ ساعت</option></select></td></tr>';
        echo '<tr><th>حداکثر در هر اجرا</th><td><input type="number" name="max_items" min="1" max="100" value="' . esc_attr($s['max_items']) . '"></td></tr>';
        echo '<tr><th>نوع پست</th><td><input type="text" name="post_type" value="' . esc_attr($s['post_type']) . '" class="regular-text"></td></tr>';
        echo '<tr><th>وضعیت</th><td><select name="post_status"><option value="draft" ' . selected('draft', $s['post_status'], false) . '>پیش‌نویس</option><option value="pending" ' . selected('pending', $s['post_status'], false) . '>در انتظار</option></select></td></tr>';
        echo '<tr><th>نویسنده</th><td><input type="number" name="post_author" min="1" value="' . esc_attr($s['post_author']) . '"></td></tr>';
        echo '<tr><th>تصویر شاخص</th><td><label><input type="checkbox" name="set_featured" value="1" ' . checked(1, (int)$s['set_featured'], false) . '> دریافت و تنظیم تصویر شاخص</label></td></tr>';
        echo '</table>';

        submit_button('ذخیره تنظیمات');
        echo '</form>';

        echo '<hr><h2>اجرای آزمایشی</h2>';
        echo '<p>یک دور جمع‌آوری را همان‌جا اجرا کنید (بدون انتظار برای کرون).</p>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        echo '<input type="hidden" name="action" value="ipna_sports_run">';
        wp_nonce_field('ipna_sports_save');
        submit_button('اجرای همین حالا', 'secondary', 'run_now');
        echo '</form>';

        echo '<hr><h2>تست فید</h2>';
        echo '<p>آدرس یک فید را وارد کنید تا چند عنوان اول خوانده‌شده را ببینید (برای بررسی صحت هر منبع).</p>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        echo '<input type="hidden" name="action" value="ipna_sports_test">';
        wp_nonce_field('ipna_sports_save');
        echo '<input type="url" name="test_url" placeholder="https://site.ir/rss" class="regular-text"> ';
        submit_button('تست فید', 'secondary', 'test_feed');
        echo '</form>';

        echo '</div>';
    }

    protected static function sources_to_text(array $sources): string
    {
        $lines = [];
        foreach ($sources as $src) {
            $line = $src['name'] . ' | ' . $src['feed'];
            if ($src['selector'] !== '') {
                $line .= ' | ' . $src['selector'];
            }
            $lines[] = $line;
        }
        return implode("\n", $lines);
    }

    protected static function default_sources_text(): string
    {
        return implode("\n", [
            'فرهیختگان (ورزش) | https://farhikhteganonline.ir/rss | article',
            'ایرنا | https://www.irna.ir/rss | article',
            'ایسنا | https://www.isna.ir/rss | article',
            'مهر (ورزش) | https://www.mehrnews.com/rss | article',
            'تسنیم | https://www.tasnimnews.com/rss | article',
        ]);
    }

    public static function save(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('دسترسی غیرمجاز');
        }
        check_admin_referer('ipna_sports_save');

        $raw = isset($_POST['sources_text']) ? (string)$_POST['sources_text'] : '';
        $sources = [];
        foreach (preg_split('/\r?\n+/', $raw) as $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, '#') === 0 || strpos($line, '//') === 0) {
                continue;
            }
            $parts = array_map('trim', explode('|', $line));
            if (count($parts) < 2 || $parts[0] === '' || $parts[1] === '') {
                continue;
            }
            $sources[] = [
                'name'     => sanitize_text_field($parts[0]),
                'feed'     => esc_url_raw($parts[1]),
                'selector' => count($parts) > 2 ? sanitize_text_field($parts[2]) : '',
                'enabled'  => 1,
            ];
        }
        update_option('ipna_sports_sources', $sources);

        $s = ipna_sports_settings();
        $s['enabled']      = !empty($_POST['enabled']) ? 1 : 0;
        $s['interval']     = isset($_POST['interval']) ? sanitize_text_field($_POST['interval']) : 'hourly';
        $s['max_items']    = isset($_POST['max_items']) ? max(1, min(100, (int)$_POST['max_items'])) : 10;
        $s['provider']     = isset($_POST['provider']) && $_POST['provider'] === 'gemini' ? 'gemini' : 'openai';
        $s['api_key']      = isset($_POST['api_key']) ? trim(sanitize_text_field($_POST['api_key'])) : '';
        $s['model']        = isset($_POST['model']) ? trim(sanitize_text_field($_POST['model'])) : '';
        $s['language']     = isset($_POST['language']) && $_POST['language'] === 'english' ? 'english' : 'persian';
        $s['keywords']     = isset($_POST['keywords']) ? sanitize_textarea_field($_POST['keywords']) : '';
        $s['categories']   = isset($_POST['categories']) ? sanitize_textarea_field($_POST['categories']) : '';
        $s['post_type']    = isset($_POST['post_type']) ? sanitize_text_field($_POST['post_type']) : 'post';
        $s['post_status']  = isset($_POST['post_status']) && $_POST['post_status'] === 'pending' ? 'pending' : 'draft';
        $s['post_author']  = isset($_POST['post_author']) ? max(1, (int)$_POST['post_author']) : 1;
        $s['set_featured'] = !empty($_POST['set_featured']) ? 1 : 0;
        update_option('ipna_sports_settings', $s);

        wp_clear_scheduled_hook('ipna_sports_cron');
        wp_schedule_event(time(), $s['interval'], 'ipna_sports_cron');

        wp_safe_redirect(add_query_arg('ipna_msg', urlencode('تنظیمات ذخیره شد.'), admin_url('admin.php?page=ipna-sports')));
        exit;
    }

    public static function run_now(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('دسترسی غیرمجاز');
        }
        check_admin_referer('ipna_sports_save');

        $result = self::run();
        update_option('ipna_sports_last_result', $result);

        $msg = 'اجرا انجام شد. ساخته‌شده: ' . $result['created'] . ' — نادیده‌گرفته: ' . $result['skipped'];
        wp_safe_redirect(add_query_arg('ipna_msg', urlencode($msg), admin_url('admin.php?page=ipna-sports')));
        exit;
    }

    public static function test_feed(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('دسترسی غیرمجاز');
        }
        check_admin_referer('ipna_sports_save');

        $url = isset($_POST['test_url']) ? esc_url_raw((string)$_POST['test_url']) : '';

        echo '<div class="wrap"><h1>تست فید</h1>';
        echo '<p><a href="' . esc_url(admin_url('admin.php?page=ipna-sports')) . '">&larr; بازگشت به تنظیمات</a></p>';

        if ($url === '') {
            echo '<div class="notice notice-error"><p>آدرس فید خالی است.</p></div></div>';
            exit;
        }

        echo '<p>آدرس: <code>' . esc_html($url) . '</code></p>';
        $items = IPNA_Sports_RSS::fetch($url);

        if (!$items) {
            echo '<div class="notice notice-error"><p>فید خوانده نشد. احتمالاً آدرس اشتباه است، یا سایت ریدایرکت/JS دارد، یا فید XML معتبر برنمی‌گرداند.</p></div>';
            echo '</div>';
            exit;
        }

        echo '<div class="notice notice-success"><p>✔ تعداد آیتم‌های فید: ' . esc_html((string)count($items)) . '</p></div>';
        echo '<ol>';
        foreach (array_slice($items, 0, 6) as $it) {
            echo '<li>' . esc_html($it['title']) . '<br><small>' . esc_html($it['link']) . ' — عکس: ' . ($it['image'] !== '' ? 'دارد' : 'ندارد') . '</small></li>';
        }
        echo '</ol>';

        echo '</div>';
        exit;
    }
}
