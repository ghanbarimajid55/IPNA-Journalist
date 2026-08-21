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
            if (in_array('required', $rule) && empty($value)) {
                $errors[$field] = "فیلد {$field} الزامی است";
            }
            if (in_array('email', $rule) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $errors[$field] = 'ایمیل معتبر نیست';
            }
            if (isset($rule['min']) && strlen($value) < $rule['min']) {
                $errors[$field] = "حداقل {$rule['min']} کاراکتر لازم است";
            }
            if (isset($rule['numeric']) && !is_numeric($value)) {
                $errors[$field] = 'باید عدد باشد';
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
}
