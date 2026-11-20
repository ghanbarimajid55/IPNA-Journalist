<?php
if (!defined('ABSPATH')) {
    exit;
}

class IPNA_Report_Admin
{
    public static function register(): void
    {
        add_action('admin_menu', [__CLASS__, 'menu']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'assets']);
        add_action('admin_post_ipna_report_save', [__CLASS__, 'save']);
    }

    public static function menu(): void
    {
        add_menu_page(
            'IPNA Report',
            'IPNA Report',
            'manage_options',
            'ipna-report',
            [__CLASS__, 'render_dashboard'],
            'dashicons-chart-area',
            26
        );
        add_submenu_page(
            'ipna-report',
            'تنظیمات',
            'تنظیمات',
            'manage_options',
            'ipna-report-settings',
            [__CLASS__, 'render_settings']
        );
    }

    public static function assets(string $hook): void
    {
        if (strpos($hook, 'ipna-report') === false) {
            return;
        }
        wp_enqueue_style('ipna-report', IPNA_REPORT_URL . 'assets/ipna-report.css', [], IPNA_REPORT_VERSION);
        wp_enqueue_script('chartjs', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js', [], '4.4.1', true);
        wp_enqueue_script('ipna-report', IPNA_REPORT_URL . 'assets/ipna-report.js', ['chartjs'], IPNA_REPORT_VERSION, true);
    }

    protected static function validDate(?string $v, string $default): string
    {
        if ($v && preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) {
            return $v;
        }
        return $default;
    }

    public static function render_dashboard(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('دسترسی غیرمجاز');
        }

        $cfg = ipna_report_settings();
        $source = ipna_report_source($cfg);
        $connected = $source->connect();

        $tab = isset($_GET['tab']) && $_GET['tab'] === 'targets' ? 'targets' : 'report';

        echo '<div class="wrap ipna-report-wrap">';
        echo '<h1>IPNA Report — داشبورد عملکرد</h1>';

        $sourceName = ($cfg['source'] ?? 'wordpress') === 'pms' ? 'دیتابیس خارجی PMS' : 'خودِ وردپرس (کاربران + پست‌ها)';
        echo '<p class="ipna-source-note"><strong>منبع فعال:</strong> ' . esc_html($sourceName) . ($cfg['source'] === 'pms' ? '' : ' — پست‌تیپ‌ها: <code>' . esc_html($cfg['post_types']) . '</code>') . '</p>';

        echo '<nav class="nav-tab-wrapper">';
        echo '<a href="?page=ipna-report&tab=report" class="nav-tab' . ($tab === 'report' ? ' nav-tab-active' : '') . '">گزارش</a>';
        echo '<a href="?page=ipna-report&tab=targets" class="nav-tab' . ($tab === 'targets' ? ' nav-tab-active' : '') . '">تحقق اهداف</a>';
        echo '</nav>';

        if (!$connected) {
            echo '<div class="notice notice-error"><p>اتصال به دیتابیس PMS برقرار نشد. لطفاً تنظیمات اتصال را بررسی کنید.</p><p><a class="button" href="' . esc_url(admin_url('admin.php?page=ipna-report-settings')) . '">رفتن به تنظیمات</a></p></div>';
            echo '</div>';
            return;
        }

        $now = current_time('timestamp');
        $to  = self::validDate($_GET['ipna_to'] ?? '', date('Y-m-d', $now));
        $from = self::validDate($_GET['ipna_from'] ?? '', date('Y-m-d', $now - 30 * 86400));

        if ($tab === 'targets') {
            $year  = (int)($_GET['ipna_year'] ?? date('Y', $now));
            $month = (int)($_GET['ipna_month'] ?? date('n', $now));
            self::render_targets($cfg, $source, $year, $month, $from, $to);
        } else {
            self::render_report($cfg, $source, $from, $to);
        }

        echo '</div>';
    }

    protected static function render_report(array $cfg, $source, string $from, string $to): void
    {
        $analyzer = new IPNA_Report_Analyzer($source);
        $users = $source->usersWithStats($from, $to);
        $types = $source->typeTotals($from, $to);
        $daily = $analyzer->fillDaily($source->dailySeries($from, $to), $from, $to);
        $summary = $analyzer->summary($users);

        self::range_form($from, $to, 'report');

        echo '<div class="ipna-cards">';
        self::card('کاربران فعال', (string)$summary['users']);
        self::card('کل خبر/عکس/ویدئو', (string)$summary['quantity']);
        self::card('کل امتیاز', number_format_i18n($summary['score']));
        self::card('میانگین امتیاز هر کاربر', number_format_i18n($summary['avg'], 1));
        echo '</div>';

        echo '<div class="ipna-grid">';
        echo '<div class="ipna-chart-card"><h2>تولید روزانه (امتیاز)</h2><canvas id="ipna-line"></canvas></div>';
        echo '<div class="ipna-chart-card"><h2>سهم دسته‌ها</h2><canvas id="ipna-doughnut"></canvas></div>';
        echo '</div>';

        echo '<div class="ipna-chart-card"><h2>امتیاز به تفکیک کاربر</h2><canvas id="ipna-bar"></canvas></div>';

        if (empty($users)) {
            echo '<div class="notice notice-warning"><p>هیچ کاربرِ فعال با نقش <b>گزارشگر</b> یا <b>عکاس</b> در دیتابیس پیدا نشد. شاید فقط کاربر <code>admin</code> ثبت شده است. برای نمایش گزارش، از سامانه‌ی PMS کاربرانی با این نقش‌ها اضافه کنید و برای آنان فعالیت ثبت کنید.</p></div>';
        } else {
            echo '<h2>فهرست کاربران</h2>';
            echo '<table class="widefat striped ipna-table"><thead><tr>';
            echo '<th>کاربر</th><th>نقش</th><th>اخبار</th><th>عکس</th><th>ویدئو</th><th>تعداد کل</th><th>امتیاز</th>';
            echo '</tr></thead><tbody>';
            foreach ($users as $u) {
                echo '<tr>';
                echo '<td>' . esc_html($u['name']) . '</td>';
                echo '<td>' . esc_html($u['role']) . '</td>';
                echo '<td>' . esc_html($u['news_qty']) . '</td>';
                echo '<td>' . esc_html($u['photo_qty']) . '</td>';
                echo '<td>' . esc_html($u['video_qty']) . '</td>';
                echo '<td>' . esc_html($u['total_quantity']) . '</td>';
                echo '<td><strong>' . esc_html($u['total_score']) . '</strong></td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        }

        self::render_category_daily($source, $from, $to, $cfg);

        wp_localize_script('ipna-report', 'ipnaReport', [
            'daily'    => array_map(static function ($r) {
                $r['label'] = ipna_report_jalali_short((string)$r['d']);
                return $r;
            }, $daily),
            'category' => $analyzer->categoryBreakdown($users),
            'users'    => array_map(static function ($u) {
                return ['name' => $u['name'], 'score' => (float)$u['total_score']];
            }, $users),
        ]);
    }

    protected static function render_category_daily($source, string $from, string $to, array $cfg): void
    {
        if (!method_exists($source, 'userCategoryDaily')) {
            return;
        }
        $rows = $source->userCategoryDaily($from, $to);
        if (!$rows) {
            return;
        }

        $allDates = [];
        foreach ($rows as $r) {
            if (!in_array($r['d'], $allDates, true)) {
                $allDates[] = $r['d'];
            }
        }
        sort($allDates);
        $days = max(3, min(31, (int)($cfg['cat_days'] ?? 14)));
        $dates = array_slice($allDates, -$days);

        $visible = array_values(array_filter($rows, static function ($r) use ($dates) {
            return in_array($r['d'], $dates, true);
        }));

        $userOrder = [];
        foreach ($visible as $r) {
            if (!in_array($r['name'], $userOrder, true)) {
                $userOrder[] = $r['name'];
            }
        }

        $today = [];
        foreach ($rows as $r) {
            if ($r['d'] === $to) {
                $today[$r['name']][] = ['cat' => $r['category'], 'cnt' => (int)$r['cnt']];
            }
        }

        echo '<h2>تفکیک روزانه‌ی هر کاربر به تفکیک دسته‌بندی</h2>';

        if ($today) {
            echo '<div class="ipna-today"><strong>امروز (' . esc_html(ipna_report_jalali_full($to)) . '):</strong>';
            foreach ($today as $uname => $items) {
                $parts = [];
                foreach ($items as $it) {
                    $parts[] = '<b>' . esc_html($it['cat']) . ':</b> ' . esc_html((string)$it['cnt']) . ' خبر';
                }
                echo '<div class="ipna-today-user">' . esc_html($uname) . ' — ' . implode('، ', $parts) . '</div>';
            }
            echo '</div>';
        }

        $palette = ['#1a73e8', '#d9480f', '#2f9e44', '#c2255c', '#7048e8', '#0c8599', '#e03131', '#5f3dc4'];
        $idx = 0;

        echo '<div class="ipna-acc-list">';

        foreach ($userOrder as $uname) {
            $color = $palette[$idx % count($palette)];
            $idx++;
            $catOfUser = [];
            $map = [];
            $userTotal = 0;
            foreach ($visible as $r) {
                if ($r['name'] === $uname) {
                    if (!in_array($r['category'], $catOfUser, true)) {
                        $catOfUser[] = $r['category'];
                    }
                    $map[$r['category']][$r['d']] = (int)$r['cnt'];
                    $userTotal += (int)$r['cnt'];
                }
            }

            echo '<details class="ipna-acc"><summary class="ipna-acc-summary"><span class="ipna-plus"></span><b class="ipna-user-name" style="color:' . esc_attr($color) . '">' . esc_html($uname) . '</b><span class="ipna-acc-name">' . esc_html((string)count($catOfUser)) . ' دسته</span><span class="ipna-acc-count">' . esc_html((string)$userTotal) . ' خبر</span></summary>';
            echo '<div class="ipna-acc-body">';
            echo '<table class="widefat ipna-catdaily"><thead><tr><th>دسته‌بندی</th>';
            foreach ($dates as $d) {
                $lbl = ipna_report_jalali_short($d);
                echo '<th>' . ($d === $to ? '<span class="ipna-today-h"><b>' . esc_html($lbl) . '</b></span>' : esc_html($lbl)) . '</th>';
            }
            echo '<th>جمع</th></tr></thead><tbody>';
            foreach ($catOfUser as $cat) {
                echo '<tr><td><span class="ipna-cat-badge">' . esc_html($cat) . '</span></td>';
                $tot = 0;
                foreach ($dates as $d) {
                    $c = (int)($map[$cat][$d] ?? 0);
                    $tot += $c;
                    $cls = ($d === $to) ? ' class="ipna-col-today"' : '';
                    echo '<td' . $cls . '>' . ($c > 0 ? esc_html((string)$c) : '—') . '</td>';
                }
                echo '<td><strong class="ipna-cat-total">' . esc_html((string)$tot) . '</strong></td></tr>';
            }
            echo '</tbody></table>';
            echo '</div></details>';
        }

        echo '</div>';
    }

    protected static function render_targets(array $cfg, $source, int $year, int $month, string $from, string $to): void
    {
        $analyzer = new IPNA_Report_Analyzer($source);

        echo '<form method="get" class="ipna-range">';
        echo '<input type="hidden" name="page" value="ipna-report">';
        echo '<input type="hidden" name="tab" value="targets">';
        echo '<label>سال <input type="number" name="ipna_year" value="' . esc_attr($year) . '"></label> ';
        echo '<label>ماه <input type="number" name="ipna_month" min="1" max="12" value="' . esc_attr($month) . '"></label> ';
        echo '<button class="button">نمایش</button>';
        echo '</form>';

        $users  = $source->usersWithStats($from, $to);
        $targets = $analyzer->makeTargetMap($source->targets($year, $month));

        if (empty($users)) {
            echo '<div class="notice notice-warning"><p>کاربرِ فعال با نقش گزارشگر/عکاس برای مقایسه با اهداف یافت نشد. ابتدا کاربر بسازید و اهداف ماهانه را در PMS تعیین کنید.</p></div>';
            return;
        }

        echo '<table class="widefat striped ipna-table"><thead><tr>';
        echo '<th>کاربر</th><th>نقش</th><th>امتیاز</th><th>هدف امتیاز</th><th>درصد تحقق</th><th>اهداف تعدد</th>';
        echo '</tr></thead><tbody>';
        foreach ($users as $u) {
            $t     = $targets[(int)$u['id']] ?? null;
            $score = (float)$u['total_score'];
            $targetScore = (float)($t['score_target'] ?? 0);
            $pct   = $analyzer->achievement($score, $targetScore);
            $color = $pct >= 100 ? '#2f9e44' : ($pct >= 50 ? '#e8890c' : '#c92a2a');
            $detail = '';
            if ($t) {
                $pieces = [];
                foreach (['news_target', 'exclusive_news_target', 'interview_target', 'special_report_target'] as $k) {
                    $fields = ['news_target' => 'خبر', 'exclusive_news_target' => 'خبر اختصاصی', 'interview_target' => 'مصاحبه', 'special_report_target' => 'گزارش ویژه'];
                    if ((int)$t[$k] > 0) {
                        $pieces[] = $fields[$k] . ' ' . (int)$t[$k];
                    }
                }
                $detail = implode(' / ', $pieces);
            }
            echo '<tr>';
            echo '<td>' . esc_html($u['name']) . '</td>';
            echo '<td>' . esc_html($u['role']) . '</td>';
            echo '<td>' . esc_html($score) . '</td>';
            echo '<td>' . esc_html($targetScore ?: '—') . '</td>';
            echo '<td><span style="color:' . esc_attr($color) . ';font-weight:600">' . esc_html($pct . '%') . '</span></td>';
            echo '<td>' . esc_html($detail ?: '—') . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    }

    protected static function range_form(string $from, string $to, string $tab): void
    {
        echo '<form method="get" class="ipna-range">';
        echo '<input type="hidden" name="page" value="ipna-report">';
        echo '<input type="hidden" name="tab" value="' . esc_attr($tab) . '">';
        echo '<label>از <input type="date" name="ipna_from" value="' . esc_attr($from) . '"></label> ';
        echo '<label>تا <input type="date" name="ipna_to" value="' . esc_attr($to) . '"></label> ';
        echo '<button class="button">فیلتر</button>';
        echo '</form>';
    }

    protected static function card(string $label, string $value): void
    {
        echo '<div class="ipna-card"><span class="ipna-card-label">' . esc_html($label) . '</span><span class="ipna-card-value">' . esc_html($value) . '</span></div>';
    }

    public static function render_settings(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('دسترسی غیرمجاز');
        }
        $cfg = ipna_report_settings();
        $msg = isset($_GET['ipna_msg']) ? sanitize_text_field($_GET['ipna_msg']) : '';

        echo '<div class="wrap ipna-report-wrap">';
        echo '<h1>تنظیمات IPNA Report</h1>';

        if ($msg) {
            echo '<div class="notice notice-info"><p>' . esc_html($msg) . '</p></div>';
        }

        self::render_diagnostics($cfg);

        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        echo '<input type="hidden" name="action" value="ipna_report_save">';
        wp_nonce_field('ipna_report_save');

        echo '<h2>منبع داده</h2>';
        echo '<table class="form-table">';
        echo '<tr><th>منبع تحلیل</th><td>';
        echo '<label><input type="radio" name="source" value="wordpress" ' . checked('wordpress', $cfg['source'], false) . '> خودِ وردپرس (کاربران + پست‌های خبر در این سایت)</label><br>';
        echo '<label><input type="radio" name="source" value="pms" ' . checked('pms', $cfg['source'], false) . '> دیتابیس خارجی سامانه‌ی PMS</label>';
        echo '</td></tr>';
        echo '</table>';

        echo '<h2>تحلیل وردپرس (post types)</h2>';
        echo '<table class="form-table">';
        echo self::input_row('post_types', 'پست‌تیپِ «خبر» (با کاما جدا کنید)', $cfg['post_types'], 'post,gallery');
        echo self::input_row('post_status', 'وضعیت پست (با کاما جدا کنید)', $cfg['post_status'], 'publish');
        echo self::input_row('goal_news', 'هدف ماهانه خبر برای هر کاربر (عدد)', $cfg['goal_news'], '30', 'number');
        echo self::input_row('cat_days', 'تعداد روزهای ستون‌های جدول تفکیک (۳ تا ۳۱)', $cfg['cat_days'], '14', 'number');
        echo '</table>';

        echo '<h2>اتصال به دیتابیس PMS (وقتی «منبع» = PMS)</h2>';
        echo '<table class="form-table">';
        echo self::input_row('pms_host', 'سرویس‌دهنده (Host)', $cfg['pms_host'], 'localhost');
        echo self::input_row('pms_dbname', 'نام دیتابیس', $cfg['pms_dbname'], 'ipna_pms');
        echo self::input_row('pms_dbuser', 'نام کاربری', $cfg['pms_dbuser'], '');
        echo self::input_row('pms_dbpass', 'رمز عبور', $cfg['pms_dbpass'], '', 'password');
        echo self::input_row('pms_prefix', 'پیشوند جدول‌ها (در صورت وجود)', $cfg['pms_prefix'], '');
        echo '</table>';

        echo '<h2>هشدار خودکار</h2>';
        echo '<table class="form-table">';
        echo '<tr><th>فعال بودن</th><td><label><input type="checkbox" name="alert_enabled" value="1" ' . checked(1, (int)$cfg['alert_enabled'], false) . '> فعال‌سازی هشدار (اجرای روزانه)</label></td></tr>';
        echo self::input_row('alert_threshold', 'آستانه‌ی امتیاز (0 = ارسال برای همه)', $cfg['alert_threshold'], '0', 'number');
        echo self::input_row('alert_email', 'ایمیل دریافت‌کننده', $cfg['alert_email'], '', 'email');
        echo '<tr><th>متن هشدار</th><td><textarea name="alert_caption" rows="2" class="large-text">' . esc_textarea($cfg['alert_caption']) . '</textarea></td></tr>';
        echo '</table>';

        submit_button('ذخیره تنظیمات');
        echo '<p><a class="button" href="' . esc_url(admin_url('admin.php?page=ipna-report')) . '">بازگشت به داشبورد</a></p>';
        echo '</form>';
        echo '</div>';
    }

    protected static function render_diagnostics(array $cfg): void
    {
        if (($cfg['source'] ?? 'wordpress') === 'pms') {
            $pms = new IPNA_PMS($cfg);
            if (!$pms->connect()) {
                echo '<div class="notice notice-error"><p>اتصال به دیتابیس PMS برقرار نیست.</p></div>';
                return;
            }

            $tables = $pms->listTables();
            $expected = ['users', 'activities', 'activity_types', 'monthly_targets'];
            $finder = [];

            foreach ($tables as $t) {
                $trimmed = (string)$t;
                foreach ($expected as $base) {
                    if ($trimmed === $base || substr($trimmed, -1 - strlen($base)) === '_' . $base) {
                        $finder[$base] = $trimmed;
                    }
                }
            }

            echo '<hr><h3>تشخیص جدول‌ها</h3>';

            $prefixMissing = [];
            foreach ($expected as $base) {
                $name = $finder[$base] ?? null;
                echo '<p><b>' . esc_html($base) . '</b>: ';
                if ($name) {
                    $count = $pms->countTable($name);
                    echo 'جدول <code>' . esc_html($name) . '</code> — ' . esc_html($count) . ' ردیف';
                } else {
                    echo '<span style="color:#c92a2a">جدول پیدا نشد</span>';
                    $prefixMissing[] = $base;
                }
                echo '</p>';
            }

            if (!empty($prefixMissing)) {
                $sample = 'ipna_' . $prefixMissing[0];
                echo '<div class="notice notice-warning"><p>جدول‌های <code>' . esc_html(implode('، ', $prefixMissing)) . '</code> با پیشوند فعلی (خالی) پیدا نشدند. در فیلد <b>«پیشوند جدول‌ها»</b> پیشوند را بنویسید.</p></div>';
            }

            echo '<p>کل جدول‌های دیتابیس: ' . esc_html(count($tables)) . '</p>';
        } else {
            global $wpdb;
            $totalUsers = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users}");
            $postTypes  = array_values(array_filter(array_map('trim', explode(',', (string)($cfg['post_types'] ?? 'post,gallery')))));
            $statuses   = array_values(array_filter(array_map('trim', explode(',', (string)($cfg['post_status'] ?? 'publish')))));
            $typesIn = implode(',', array_map(static function ($v) {
                return "'" . esc_sql((string)$v) . "'";
            }, $postTypes));
            $statusIn = implode(',', array_map(static function ($v) {
                return "'" . esc_sql((string)$v) . "'";
            }, $statuses));
            $totalPosts = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type IN ($typesIn) AND post_status IN ($statusIn)");

            echo '<hr><h3>وضعیت منبع وردپرس</h3>';
            echo '<p>کاربران وردپرس: <code>' . esc_html($totalUsers) . '</code></p>';
            echo '<p>کل پست‌های مطابق پیکربندی: <code>' . esc_html($totalPosts) . '</code></p>';
            echo '<p>پست‌تیپ‌های شمرده‌شده: <code>' . esc_html($cfg['post_types']) . '</code> — وضعیت: <code>' . esc_html($cfg['post_status']) . '</code></p>';

            $ptRows = (array)$wpdb->get_results(
                "SELECT p.post_type, COUNT(*) AS c FROM {$wpdb->posts} p WHERE p.post_status IN ($statusIn) GROUP BY p.post_type ORDER BY c DESC",
                ARRAY_A
            );
            echo '<p>انواع پست موجود در سایت (برای انتخاب صحیح):</p><ul>';
            foreach ($ptRows as $pt) {
                echo '<li><code>' . esc_html($pt['post_type']) . '</code> — ' . esc_html($pt['c']) . ' پست</li>';
            }
            echo '</ul>';
        }
    }

    protected static function input_row(string $name, string $label, string $value, string $placeholder = '', string $type = 'text'): string
    {
        $html  = '<tr><th><label for="' . esc_attr($name) . '">' . esc_html($label) . '</label></th><td>';
        $html .= '<input type="' . esc_attr($type) . '" name="' . esc_attr($name) . '" id="' . esc_attr($name) . '" value="' . esc_attr($value) . '" placeholder="' . esc_attr($placeholder) . '" class="regular-text">';
        $html .= '</td></tr>';
        return $html;
    }

    public static function save(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('دسترسی غیرمجاز');
        }
        check_admin_referer('ipna_report_save');

        $cfg = [
            'source'          => isset($_POST['source']) && $_POST['source'] === 'pms' ? 'pms' : 'wordpress',
            'post_types'      => isset($_POST['post_types']) ? sanitize_text_field($_POST['post_types']) : 'post,gallery',
            'post_status'     => isset($_POST['post_status']) ? sanitize_text_field($_POST['post_status']) : 'publish',
            'goal_news'       => isset($_POST['goal_news']) ? (int)$_POST['goal_news'] : 30,
            'cat_days'        => isset($_POST['cat_days']) ? max(3, min(31, (int)$_POST['cat_days'])) : 14,
            'pms_host'        => isset($_POST['pms_host']) ? sanitize_text_field($_POST['pms_host']) : 'localhost',
            'pms_dbname'      => isset($_POST['pms_dbname']) ? sanitize_text_field($_POST['pms_dbname']) : '',
            'pms_dbuser'      => isset($_POST['pms_dbuser']) ? sanitize_text_field($_POST['pms_dbuser']) : '',
            'pms_dbpass'      => isset($_POST['pms_dbpass']) ? (string)$_POST['pms_dbpass'] : '',
            'pms_prefix'      => isset($_POST['pms_prefix']) ? sanitize_text_field($_POST['pms_prefix']) : '',
            'alert_enabled'   => !empty($_POST['alert_enabled']) ? 1 : 0,
            'alert_threshold' => isset($_POST['alert_threshold']) ? (float)$_POST['alert_threshold'] : 0,
            'alert_email'     => isset($_POST['alert_email']) ? sanitize_email($_POST['alert_email']) : get_option('admin_email'),
            'alert_caption'   => isset($_POST['alert_caption']) ? sanitize_textarea_field($_POST['alert_caption']) : '',
        ];

        update_option('ipna_report_settings', $cfg);

        $source = ipna_report_source($cfg);
        $ok     = $source->connect();
        $msg    = $ok ? 'تنظیمات ذخیره شد و منبع داده با موفقیت آماده است.' : 'تنظیمات ذخیره شد، اما برقراری اتصال شکست خورد. مقادیر را بررسی کنید.';

        wp_safe_redirect(add_query_arg('ipna_msg', urlencode($msg), admin_url('admin.php?page=ipna-report-settings')));
        exit;
    }
}
