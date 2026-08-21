<?php

class Admin_SettingsController extends BaseController
{
    private SystemSetting $settingModel;

    public function __construct()
    {
        parent::__construct();
        $this->settingModel = new SystemSetting();
    }

    public function index()
    {
        $auth = new Auth();
        if (!$auth->requireRole('admin')) {
            return;
        }

        $settings = $this->settingModel->getAll();

        $this->view('admin.settings.index', [
            'settings' => $settings,
        ]);
    }

    public function update()
    {
        $auth = new Auth();
        if (!$auth->requireRole('admin')) {
            return;
        }

        $input = $this->getInput();
        $data = $_POST;

        $allowedKeys = [
            'site_name', 'site_version', 'site_url', 'organization',
            'academic_year_start', 'fiscal_year_start_month',
            'max_login_attempts', 'lockout_duration_minutes',
            'session_timeout_minutes', 'report_page_size',
            'default_news_target', 'default_photo_target', 'default_video_target',
            'score_multiplier', 'maintenance_mode',
        ];

        $updated = 0;
        $errors = [];

        foreach ($allowedKeys as $key) {
            if (array_key_exists($key, $data)) {
                $value = trim($data[$key]);
                if ($value === '') {
                    $errors[$key] = "فیلد {$key} نمی‌تواند خالی باشد";
                    continue;
                }
                try {
                    $this->settingModel->set($key, $value);
                    $updated++;
                } catch (Exception $e) {
                    $errors[$key] = "خطا در ذخیره‌سازی: " . $e->getMessage();
                }
            }
        }

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            if (!empty($errors)) {
                $this->json(['success' => false, 'errors' => $errors], 422);
            } else {
                $this->json(['success' => true, 'message' => "{$updated} تنظیم با موفقیت به‌روزرسانی شد"]);
            }
            return;
        }

        if (!empty($errors)) {
            Helper::flash('error', 'برخی تنظیمات ذخیره نشدند');
        } else {
            Helper::flash('success', "{$updated} تنظیم با موفقیت به‌روزرسانی شد");
        }

        $this->redirect('/admin/settings');
    }
}
