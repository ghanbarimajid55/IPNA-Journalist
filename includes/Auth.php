<?php

class Auth
{
    private const MAX_LOGIN_ATTEMPTS = 5;
    private const RATE_LIMIT_WINDOW = 900;
    private const SESSION_KEY = 'pms_user';

    public static function login(string $username, string $password): bool
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $auth = new self();

        if ($auth->isRateLimited($ip)) {
            Helper::auditLog('login_rate_limited', 'auth', null, null, ['ip' => $ip, 'username' => $username]);
            return false;
        }

        $db = Database::getInstance();
        $user = $db->fetch(
            'SELECT id, username, password, name, role, email, status FROM users WHERE username = ? OR email = ?',
            [$username, $username]
        );

        if (!$user) {
            $auth->recordLoginAttempt($username, $ip);
            Helper::auditLog('login_failed', 'auth', null, null, ['username' => $username, 'ip' => $ip]);
            return false;
        }

        if (($user['status'] ?? '') !== 'active') {
            Helper::auditLog('login_inactive', 'auth', $user['id'], null, ['username' => $username]);
            return false;
        }

        if (!password_verify($password, $user['password'])) {
            $auth->recordLoginAttempt($username, $ip);
            Helper::auditLog('login_failed', 'auth', $user['id'], null, ['username' => $username, 'ip' => $ip]);
            return false;
        }

        $auth->clearLoginAttempts($ip);
        session_regenerate_id(true);

        unset($user['password']);
        $_SESSION[self::SESSION_KEY] = $user;

        $db->update('users', ['last_login' => date('Y-m-d H:i:s')], 'id = ?', [$user['id']]);

        $db->insert('audit_logs', [
            'action'      => 'login_success',
            'entity'      => 'user',
            'entity_id'   => $user['id'],
            'user_id'     => $user['id'],
            'ip_address'  => $ip,
            'user_agent'  => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
        ]);

        return true;
    }

    public static function logout(): void
    {
        // Read the id straight from the session. user()/check() must never be
        // called from here: check() invokes logout() on idle timeout, so
        // routing this through check() would recurse until stack overflow.
        $sessionUser = $_SESSION[self::SESSION_KEY] ?? null;
        $userId = is_array($sessionUser) ? ($sessionUser['id'] ?? null) : null;
        if ($userId !== null && $userId !== 0 && $userId !== '0') {
            Helper::auditLog('logout', 'user', $userId);
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }

        session_destroy();
        session_start();
    }

    public static function check(): bool
    {
        return isset($_SESSION[self::SESSION_KEY]) && is_array($_SESSION[self::SESSION_KEY]);
    }

    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }
        return $_SESSION[self::SESSION_KEY];
    }

    public static function role(): ?string
    {
        $user = self::user();
        return $user['role'] ?? null;
    }

    public static function requireRole(string ...$roles): bool
    {
        $role = self::role();

        if (!$role || !in_array($role, $roles, true)) {
            Helper::redirect('/login');
            return false;
        }

        return true;
    }

    public static function register(array $data): int
    {
        $admin = self::user();
        if (!$admin || $admin['role'] !== 'admin') {
            throw new RuntimeException('Only administrators can register new users.');
        }

        $required = ['username', 'password', 'name', 'role'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new InvalidArgumentException("Missing required field: {$field}");
            }
        }

        $validRoles = ['admin', 'reporter', 'photographer'];
        if (!in_array($data['role'], $validRoles, true)) {
            throw new InvalidArgumentException('Invalid role specified.');
        }

        $db = Database::getInstance();

        $existing = $db->fetch('SELECT id FROM users WHERE username = ? OR email = ?', [
            $data['username'],
            $data['email'] ?? '',
        ]);
        if ($existing) {
            throw new InvalidArgumentException('Username or email already exists.');
        }

        return $db->insert('users', [
            'username'   => $data['username'],
            'password'   => password_hash($data['password'], PASSWORD_BCRYPT),
            'name'       => $data['name'],
            'email'      => $data['email'] ?? '',
            'role'       => $data['role'],
            'position'   => $data['position'] ?? '',
            'status'     => 'active',
        ]);
    }

    public static function updatePassword(int $userId, string $newPassword): bool
    {
        $db = Database::getInstance();

        $user = $db->fetch('SELECT id FROM users WHERE id = ?', [$userId]);
        if (!$user) {
            return false;
        }

        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
        $db->update('users', [
            'password' => $hash,
        ], 'id = ?', [$userId]);

        Helper::auditLog('password_updated', 'user', $userId);

        return true;
    }

    public static function isRateLimited(string $ip): bool
    {
        $db = Database::getInstance();
        $cutoff = date('Y-m-d H:i:s', time() - self::RATE_LIMIT_WINDOW);

        $attempts = $db->count(
            'login_attempts',
            'ip_address = ? AND attempted_at > ?',
            [$ip, $cutoff]
        );

        return $attempts >= self::MAX_LOGIN_ATTEMPTS;
    }

    public static function recordLoginAttempt(string $username, string $ip): void
    {
        $db = Database::getInstance();
        $db->insert('login_attempts', [
            'username'     => $username,
            'ip_address'   => $ip,
        ]);
        // Bound table growth: discard attempts older than the rate-limit window.
        $db->execute(
            'DELETE FROM login_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL ? SECOND)',
            [self::RATE_LIMIT_WINDOW]
        );
    }

    public static function clearLoginAttempts(string $ip): void
    {
        $db = Database::getInstance();
        $db->execute('DELETE FROM login_attempts WHERE ip_address = ?', [$ip]);
    }

    public static function generateCsrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function verifyCsrfToken(?string $token): bool
    {
        if (empty($token) || empty($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }
}
