<?php

class Admin_ImportController extends BaseController
{
    private User $userModel;
    private Activity $activityModel;
    private ActivityType $activityTypeModel;

    private const COLUMN_MAP = [
        'news'           => 'news',
        'exclusive_news' => 'exclusive_news',
        'interview'      => 'interview',
        'special_report' => 'special_report',
        'photo'          => 'news_photo',
        'gallery'        => 'photo_gallery',
        'video'          => 'exclusive_video',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new User();
        $this->activityModel = new Activity();
        $this->activityTypeModel = new ActivityType();
    }

    public function index()
    {
        $auth = new Auth();
        if (!$auth->requireRole('admin')) {
            return;
        }

        $this->view('admin.import.index', [
            'activityTypes' => $this->activityTypeModel->findAll(),
        ]);
    }

    public function process()
    {
        $auth = new Auth();
        if (!$auth->requireRole('admin')) {
            return;
        }

        if (empty($_FILES['import_file'])) {
            $this->json(['success' => false, 'message' => 'فایلی ارسال نشد'], 400);
            return;
        }

        $file = $_FILES['import_file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, ['csv', 'xlsx', 'xls'])) {
            $this->json(['success' => false, 'message' => 'فرمت فایل پشتیبانی نمی‌شود. فایل CSV یا Excel ارسال کنید'], 400);
            return;
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->json(['success' => false, 'message' => 'خطا در آپلود فایل'], 400);
            return;
        }

        $rows = $this->parseFile($file['tmp_name'], $ext);
        if ($rows === false) {
            $this->json(['success' => false, 'message' => 'خطا در خواندن فایل'], 400);
            return;
        }

        if (empty($rows)) {
            $this->json(['success' => false, 'message' => 'فایل خالی است'], 400);
            return;
        }

        $result = $this->processRows($rows);

        Helper::auditLog('import_activities', 'activity', null, null, [
            'success' => $result['success_count'],
            'errors' => $result['error_count'],
        ]);

        $this->json([
            'success' => true,
            'message' => "پردازش تمام شد. {$result['success_count']} رکورد موفق، {$result['error_count']} رکورد خطا",
            'success_count' => $result['success_count'],
            'error_count' => $result['error_count'],
            'errors' => $result['errors'],
        ]);
    }

    private function parseFile(string $tmpPath, string $ext): ?array
    {
        if ($ext === 'csv') {
            return $this->parseCsv($tmpPath);
        }
        return $this->parseCsv($tmpPath);
    }

    private function parseCsv(string $tmpPath): ?array
    {
        $handle = fopen($tmpPath, 'r');
        if ($handle === false) {
            return false;
        }

        $rows = [];
        $header = null;

        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            if ($header === null) {
                $header = array_map('trim', $row);
                continue;
            }
            $mapped = [];
            foreach ($header as $index => $colName) {
                $mapped[$colName] = $row[$index] ?? '';
            }
            $rows[] = $mapped;
        }

        fclose($handle);
        return $rows;
    }

    private function processRows(array $rows): array
    {
        $successCount = 0;
        $errorCount = 0;
        $errors = [];
        $userCache = [];

        $this->db->beginTransaction();

        try {
            foreach ($rows as $rowIndex => $row) {
                $lineNum = $rowIndex + 2;

                $name = trim($row['Name'] ?? '');
                if ($name === '') {
                    $errors[] = "خطای ردیف {$lineNum}: نام خالی است";
                    $errorCount++;
                    continue;
                }

                $date = trim($row['Date'] ?? '');
                if ($date !== '') {
                    $date = $this->normalizeDate($date);
                    if ($date === null) {
                        $errors[] = "خطای ردیف {$lineNum}: تاریخ نامعتبر";
                        $errorCount++;
                        continue;
                    }
                } else {
                    $date = date('Y-m-d');
                }

                $position = trim($row['Position'] ?? '');

                if (!isset($userCache[$name])) {
                    $userCache[$name] = $this->findOrCreateUser($name, $position);
                }
                $userId = $userCache[$name];

                if (!$userId) {
                    $errors[] = "خطای ردیف {$lineNum}: کاربر {$name} یافت نشد و ایجاد نشد";
                    $errorCount++;
                    continue;
                }

                $mission = (int)($row['Mission'] ?? 0);
                $link = trim($row['Link'] ?? '');
                $description = trim($row['Description'] ?? '');

                $importColumns = [
                    'news'           => 'News',
                    'exclusive_news' => 'Exclusive News',
                    'interview'      => 'Interview',
                    'special_report' => 'Special Report',
                    'news_photo'     => 'Photo',
                    'photo_gallery'  => 'Gallery',
                    'exclusive_video'=> 'Video',
                ];

                $importedAny = false;
                foreach ($importColumns as $typeCode => $csvColumn) {
                    $quantity = (int)($row[$csvColumn] ?? 0);
                    if ($quantity <= 0) {
                        continue;
                    }

                    $activityType = $this->db->fetch(
                        "SELECT id, score FROM activity_types WHERE code = ? AND status = 'active'",
                        [$typeCode]
                    );

                    if (!$activityType) {
                        $errors[] = "خطای ردیف {$lineNum}: نوع فعالیت {$typeCode} یافت نشد";
                        $errorCount++;
                        continue;
                    }

                    $score = (int)$activityType['score'] * $quantity;

                    $this->db->insert('activities', [
                        'user_id'       => $userId,
                        'activity_date' => $date,
                        'activity_type' => $activityType['id'],
                        'quantity'      => $quantity,
                        'title'         => $description ?: $typeCode,
                        'link'          => $link !== '' ? $link : null,
                        'mission'       => $mission,
                        'description'   => $description !== '' ? $description : null,
                        'score'         => $score,
                        'status'        => 'active',
                        'created_at'    => date('Y-m-d H:i:s'),
                        'updated_at'    => date('Y-m-d H:i:s'),
                    ]);

                    $importedAny = true;
                }

                if ($importedAny) {
                    $successCount++;
                } elseif ($errorCount === 0 || !empty($errors)) {
                    if (!$importedAny && empty(array_filter($importColumns, fn($csvCol) => (int)($row[$csvCol] ?? 0) > 0))) {
                        $errors[] = "خطای ردیف {$lineNum}: هیچ فعالیتی ثبت نشد (تعداد همه صفر است)";
                        $errorCount++;
                    }
                }
            }

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            $errors[] = "خطای سیستمی: " . $e->getMessage();
            $errorCount++;
        }

        return [
            'success_count' => $successCount,
            'error_count'   => $errorCount,
            'errors'        => $errors,
        ];
    }

    private function findOrCreateUser(string $name, string $position = ''): ?int
    {
        $existing = $this->db->fetch(
            "SELECT id FROM users WHERE name = ? AND status = 'active'",
            [$name]
        );

        if ($existing) {
            return (int)$existing['id'];
        }

        $username = $this->generateUsername($name);
        $email = $username . '@ipna.local';

        $id = $this->userModel->create([
            'name'       => $name,
            'username'   => $username,
            'email'      => $email,
            'password'   => 'temp_' . bin2hex(random_bytes(8)),
            'role'       => 'reporter',
            'status'     => 'active',
            'position'   => $position,
        ]);

        return $id ? (int)$id : null;
    }

    private function generateUsername(string $name): string
    {
        $base = '';
        if (function_exists('transliterator_transliterate')) {
            $base = preg_replace('/[^a-zA-Z0-9]/', '', transliterator_transliterate('Any-Latin; Latin-ASCII;', $name));
        } else {
            $base = preg_replace('/[^a-zA-Z0-9]/', '', $name);
        }
        $base = strtolower($base);
        if ($base === '') {
            $base = 'user_' . bin2hex(random_bytes(4));
        }

        $username = $base;
        $counter = 1;
        while ($this->userModel->findByUsername($username)) {
            $username = $base . $counter;
            $counter++;
        }

        return $username;
    }

    private function normalizeDate(string $raw): ?string
    {
        $raw = strtr(trim($raw), [
            '۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9',
            '٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9',
        ]);
        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $raw, $m)) {
            return sprintf('%04d-%02d-%02d', (int)$m[1], (int)$m[2], (int)$m[3]);
        }
        if (preg_match('/^(\d{4})\/(\d{1,2})\/(\d{1,2})$/', $raw, $m)) {
            [$gy, $gm, $gd] = Helper::jalaliToGregorian((int)$m[1], (int)$m[2], (int)$m[3]);
            return sprintf('%04d-%02d-%02d', $gy, $gm, $gd);
        }
        return null;
    }
}
