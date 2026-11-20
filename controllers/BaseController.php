<?php
abstract class BaseController {
    protected $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    protected function view($view, $data = []) {
        extract($data);
        $viewPath = BASE_PATH . '/views/' . str_replace('.', '/', $view) . '.php';
        if (!file_exists($viewPath)) {
            throw new Exception("View {$view} not found");
        }
        ob_start();
        require $viewPath;
        $content = ob_get_clean();
        require BASE_PATH . '/views/layouts/main.php';
    }
    
    protected function viewPartial($view, $data = []) {
        extract($data);
        require BASE_PATH . '/views/' . str_replace('.', '/', $view) . '.php';
    }
    
    protected function json($data, $code = 200) {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    protected function redirect($url) {
        if (strpos($url, 'http') !== 0 && defined('BASE_URL')) {
            $url = BASE_URL . $url;
        }
        header("Location: {$url}");
        exit;
    }
    
    protected function validate($data, $rules) {
        $errors = [];
        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;
            $rule = array_map('strval', (array)$rule);
            if (in_array('required', $rule, true) && empty($value)) {
                $errors[$field] = "فیلد {$field} الزامی است";
            }
            if (in_array('email', $rule, true) && $value !== null && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $errors[$field] = 'ایمیل معتبر نیست';
            }
            if (in_array('numeric', $rule, true) && $value !== null && !is_numeric($value)) {
                $errors[$field] = 'باید عدد باشد';
            }
            if (in_array('min', $rule, true) || isset($rule['min'])) {
                $min = isset($rule['min']) ? (int)$rule['min'] : 0;
                if ($value !== null && mb_strlen((string)$value) < $min) {
                    $errors[$field] = "حداقل {$min} کاراکتر لازم است";
                }
            }
        }
        return $errors;
    }
    
    protected function getInput() {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (strpos($contentType, 'application/json') !== false) {
            return json_decode(file_get_contents('php://input'), true) ?: [];
        }
        return $_POST;
    }

    protected function isAjax(): bool {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Preserve submitted form input in the session so the view can repopulate
     * fields with old() after a validation failure.
     *
     * Password fields are intentionally never stored.
     */
    protected function keepInput(array $input): void {
        $sensitive = ['password', 'password_confirmation', 'old_password', 'confirm_password', 'pass', 'pass2'];
        $_SESSION['old'] = array_diff_key($input, array_flip($sensitive));
    }

    protected function clearOldInput(): void {
        unset($_SESSION['old']);
    }

    /**
     * Redirect back to the referring page, preserving submitted input.
     * Falls back to the current request URI if no referer is present.
     */
    protected function redirectBackWithInput(array $input, string $message = 'لطفاً خطاهای فرم را بررسی کنید'): void {
        $this->keepInput($input);
        Helper::flash('error', $message);
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        if ($referer !== '' && str_starts_with($referer, 'http')) {
            $this->redirect($referer);
        }
        $this->redirect($_SERVER['REQUEST_URI'] ?? '/');
    }
}
