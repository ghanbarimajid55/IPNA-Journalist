<?php
declare(strict_types=1);

/**
 * IPNA PMS - Development/Test Data Seeder
 *
 * Creates 10 test reporters + 5 test photographers, monthly targets,
 * and realistic activity records across the current and previous month.
 *
 * Safe to re-run: all users with usernames starting with ipna_test_ are
 * removed first (their activities/targets cascade via FK constraints).
 *
 * Run from the project root with:
 *   C:\xampp\php\php.exe database\seed_test_data.php
 */

$basePath = dirname(__DIR__);
$configPath = $basePath . '/config/database.php';

if (!is_file($configPath)) {
    fwrite(STDERR, "Database config not found: {$configPath}\n");
    exit(1);
}

$config = require $configPath;

try {
    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        $config['host'],
        $config['dbname'],
        $config['charset'] ?? 'utf8mb4'
    );

    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (Throwable $e) {
    fwrite(STDERR, "Database connection failed: {$e->getMessage()}\n");
    exit(1);
}

$reporters = [
    ['علی رضایی', 'ali_rezaei'],
    ['محمد احمدی', 'mohammad_ahmadi'],
    ['امیر حسینی', 'amir_hosseini'],
    ['مهدی کریمی', 'mehdi_karimi'],
    ['سعید مرادی', 'saeed_moradi'],
    ['رضا اکبری', 'reza_akbari'],
    ['احسان موسوی', 'ehsan_mousavi'],
    ['حسین نادری', 'hossein_naderi'],
    ['سامان کاظمی', 'saman_kazemi'],
    ['آرش محمدی', 'arash_mohammadi'],
];

$photographers = [
    ['پویا رحیمی', 'pouya_rahimi'],
    ['امیرحسین صادقی', 'amirhossein_sadeghi'],
    ['مهدی حیدری', 'mehdi_heydari'],
    ['سینا رضوانی', 'sina_rezvani'],
    ['نیما شریفی', 'nima_sharifi'],
];

$activityTypes = [];
$stmt = $pdo->query("SELECT id, code, score FROM activity_types WHERE status = 'active'");
foreach ($stmt->fetchAll() as $row) {
    $activityTypes[$row['code']] = $row;
}

$requiredTypes = ['news', 'exclusive_news', 'interview', 'special_report', 'news_photo', 'photo_gallery', 'exclusive_video'];
foreach ($requiredTypes as $code) {
    if (!isset($activityTypes[$code])) {
        throw new RuntimeException("Required activity type is missing: {$code}");
    }
}

$pdo->beginTransaction();

try {
    // Remove only our own test dataset.
    $delete = $pdo->prepare("DELETE FROM users WHERE username LIKE 'ipna_test_%'");
    $delete->execute();

    $passwordHash = password_hash('Test@12345', PASSWORD_BCRYPT);

    $insertUser = $pdo->prepare(<<<SQL
        INSERT INTO users (name, username, email, password, role, position, avatar, status)
        VALUES (:name, :username, :email, :password, :role, :position, NULL, 'active')
    SQL);

    $insertTarget = $pdo->prepare(<<<SQL
        INSERT INTO monthly_targets
            (user_id, year, month, news_target, exclusive_news_target,
             interview_target, special_report_target, photo_target,
             gallery_target, video_target, score_target)
        VALUES
            (:user_id, :year, :month, :news_target, :exclusive_news_target,
             :interview_target, :special_report_target, :photo_target,
             :gallery_target, :video_target, :score_target)
        ON DUPLICATE KEY UPDATE
            news_target = VALUES(news_target),
            exclusive_news_target = VALUES(exclusive_news_target),
            interview_target = VALUES(interview_target),
            special_report_target = VALUES(special_report_target),
            photo_target = VALUES(photo_target),
            gallery_target = VALUES(gallery_target),
            video_target = VALUES(video_target),
            score_target = VALUES(score_target)
    SQL);

    $insertActivity = $pdo->prepare(<<<SQL
        INSERT INTO activities
            (user_id, activity_date, activity_type, quantity, title, link, mission, description, score)
        VALUES
            (:user_id, :activity_date, :activity_type, :quantity, :title,
             :link, :mission, :description, :score)
    SQL);

    $users = [];

    foreach ($reporters as $i => [$name, $slug]) {
        $username = 'ipna_test_' . $slug;
        $insertUser->execute([
            ':name' => $name,
            ':username' => $username,
            ':email' => $username . '@test.ipna.local',
            ':password' => $passwordHash,
            ':role' => 'reporter',
            ':position' => 'خبرنگار تستی',
        ]);
        $users[] = [
            'id' => (int)$pdo->lastInsertId(),
            'name' => $name,
            'role' => 'reporter',
            'slug' => $slug,
            'index' => $i,
        ];
    }

    foreach ($photographers as $i => [$name, $slug]) {
        $username = 'ipna_test_' . $slug;
        $insertUser->execute([
            ':name' => $name,
            ':username' => $username,
            ':email' => $username . '@test.ipna.local',
            ':password' => $passwordHash,
            ':role' => 'photographer',
            ':position' => 'عکاس تستی',
        ]);
        $users[] = [
            'id' => (int)$pdo->lastInsertId(),
            'name' => $name,
            'role' => 'photographer',
            'slug' => $slug,
            'index' => $i,
        ];
    }

    $now = new DateTimeImmutable('today', new DateTimeZone('Asia/Tehran'));
    $currentYear = (int)$now->format('Y');
    $currentMonth = (int)$now->format('m');
    $previous = $now->modify('first day of previous month');

    foreach ($users as $u) {
        $isReporter = $u['role'] === 'reporter';

        // Deliberately varied targets so dashboard achievement percentages differ.
        $targetFactor = 0.75 + (($u['index'] % 5) * 0.10);
        $newsTarget = $isReporter ? (int)round(30 * $targetFactor) : 0;
        $exclusiveTarget = $isReporter ? (int)round(6 * $targetFactor) : 0;
        $interviewTarget = $isReporter ? (int)round(4 * $targetFactor) : 0;
        $specialTarget = $isReporter ? (int)round(3 * $targetFactor) : 0;
        $photoTarget = $isReporter ? 0 : (int)round(20 * $targetFactor);
        $galleryTarget = $isReporter ? 0 : (int)round(5 * $targetFactor);
        $videoTarget = (int)round(5 * $targetFactor);
        $scoreTarget = $isReporter
            ? ($newsTarget + ($exclusiveTarget * 3) + ($interviewTarget * 4) + ($specialTarget * 5) + ($videoTarget * 5))
            : (($photoTarget) + ($galleryTarget * 3) + ($videoTarget * 5));

        foreach ([[$currentYear, $currentMonth], [(int)$previous->format('Y'), (int)$previous->format('m')]] as [$year, $month]) {
            $insertTarget->execute([
                ':user_id' => $u['id'],
                ':year' => $year,
                ':month' => $month,
                ':news_target' => $newsTarget,
                ':exclusive_news_target' => $exclusiveTarget,
                ':interview_target' => $interviewTarget,
                ':special_report_target' => $specialTarget,
                ':photo_target' => $photoTarget,
                ':gallery_target' => $galleryTarget,
                ':video_target' => $videoTarget,
                ':score_target' => $scoreTarget,
            ]);
        }

        // Each person receives a deliberately different activity volume.
        $activityCount = $isReporter ? 18 + ($u['index'] % 6) : 20 + ($u['index'] % 7);

        for ($n = 0; $n < $activityCount; $n++) {
            $daysBack = ($n * 2 + $u['index'] * 3) % 42;
            $date = $now->modify("-{$daysBack} days");

            if ($isReporter) {
                $codes = ['news', 'news', 'exclusive_news', 'interview', 'special_report', 'exclusive_video'];
                $code = $codes[($n + $u['index']) % count($codes)];
                $titles = [
                    'پوشش خبری مسابقات امروز',
                    'خبر اختصاصی از اردوی تیم ملی',
                    'گفت‌وگوی اختصاصی با ورزشکار',
                    'گزارش ویژه از رقابت‌های قهرمانی',
                    'خبر فوری از رویداد ورزشی',
                    'ویدئوی اختصاصی پشت صحنه مسابقات',
                ];
            } else {
                $codes = ['news_photo', 'news_photo', 'photo_gallery', 'exclusive_video'];
                $code = $codes[($n + $u['index']) % count($codes)];
                $titles = [
                    'عکس خبری مسابقه',
                    'پوشش تصویری تمرین تیم',
                    'گزارش تصویری کامل مسابقات',
                    'ویدئوی اختصاصی رویداد',
                ];
            }

            $type = $activityTypes[$code];
            $quantity = 1;
            if ($code === 'news_photo' && $n % 4 === 0) $quantity = 3;
            if ($code === 'photo_gallery') $quantity = 1;
            if ($code === 'news' && $n % 5 === 0) $quantity = 2;

            $title = $titles[$n % count($titles)] . ' - ' . $u['name'];
            $mission = ($n % 7 === 0) ? 1 : 0;
            $description = $mission
                ? 'فعالیت مأموریتی تستی برای بررسی گزارش عملکرد.'
                : 'داده تستی تولیدشده برای ارزیابی داشبورد و گزارش‌های IPNA.';

            $insertActivity->execute([
                ':user_id' => $u['id'],
                ':activity_date' => $date->format('Y-m-d'),
                ':activity_type' => $type['id'],
                ':quantity' => $quantity,
                ':title' => $title,
                ':link' => 'https://ipna.ir/test/' . $u['slug'] . '/' . ($n + 1),
                ':mission' => $mission,
                ':description' => $description,
                ':score' => (int)$type['score'] * $quantity,
            ]);
        }
    }

    $pdo->commit();

    $reporterCount = count($reporters);
    $photographerCount = count($photographers);
    $activityTotal = (18 + 19 + 20 + 21 + 22 + 23 + 18 + 19 + 20 + 21)
                   + (20 + 21 + 22 + 23 + 24);
    echo "SUCCESS\n";
    echo "Test reporters: {$reporterCount}\n";
    echo "Test photographers: {$photographerCount}\n";
    echo "Users total created: " . ($reporterCount + $photographerCount) . "\n";
    echo "Activity rows created: {$activityTotal}\n";
    echo "Test login password: Test@12345\n";
    echo "Username prefix: ipna_test_\n";
    echo "Dataset spans current and previous month, plus the last 42 days.\n";
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, "SEED FAILED: {$e->getMessage()}\n");
    exit(1);
}
