<?php

class Helper
{
    public static function url(string $path = ''): string
    {
        return BASE_URL . '/' . ltrim($path, '/');
    }

    public static function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    public static function sanitize(mixed $input): string
    {
        if (is_array($input)) {
            return array_map([self::class, 'sanitize'], $input);
        }
        return htmlspecialchars((string) $input, ENT_QUOTES, 'UTF-8');
    }

    public static function redirect(string $url): void
    {
        if (strpos($url, 'http') !== 0 && defined('BASE_URL')) {
            $url = BASE_URL . $url;
        }
        header('Location: ' . $url);
        exit;
    }

    public static function jsonResponse(mixed $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    public static function jsonError(string $message, int $code = 400): void
    {
        self::jsonResponse(['success' => false, 'message' => $message], $code);
    }

    public static function flash(string $key, ?string $value = null): ?string
    {
        if ($value !== null) {
            $_SESSION['flash_' . $key] = $value;
            return null;
        }

        $sessionKey = 'flash_' . $key;
        if (isset($_SESSION[$sessionKey])) {
            $val = $_SESSION[$sessionKey];
            unset($_SESSION[$sessionKey]);
            return $val;
        }

        return null;
    }

    public static function jalaliDate(string $date): string
    {
        $parts = explode('-', $date);
        if (count($parts) < 3) {
            return $date;
        }
        [$gy, $gm, $gd] = $parts;
        [$jy, $jm, $jd] = self::gregorianToJalali((int)$gy, (int)$gm, (int)$gd);
        return sprintf('%04d/%02d/%02d', $jy, $jm, $jd);
    }

    public static function gregorianToJalali(int $gy, int $gm, int $gd): array
    {
        return self::jdnToJalali(self::gregorianToJdn($gy, $gm, $gd));
    }

    public static function jalaliToGregorian(int $jy, int $jm, int $jd): array
    {
        return self::jdnToGregorian(self::jalaliToJdn($jy, $jm, $jd));
    }

    // ── Jalaali calendar helpers (ported from the verified jalaali-js algorithm) ──

    private const JALALI_BREAKS = [
        -61, 9, 38, 199, 426, 686, 756, 818, 1111, 1181, 1210,
        1635, 2060, 2097, 2192, 2262, 2324, 2394, 2456, 3178,
    ];

    private static function jalIdiv(int $a, int $b): int
    {
        return intdiv($a - ($a % $b), $b);
    }

    private static function jalMod(int $a, int $b): int
    {
        return $a - self::jalIdiv($a, $b) * $b;
    }

    /** @return array{leap:int,gy:int,march:int}|null */
    private static function jalCal(int $jy): ?array
    {
        $breaks = self::JALALI_BREAKS;
        $bl = count($breaks);
        if ($jy < $breaks[0] || $jy >= $breaks[$bl - 1]) {
            return null;
        }
        $gy = $jy + 621;
        $leapJ = -14;
        $jp = $breaks[0];
        $jump = 0;
        for ($i = 1; $i < $bl; $i++) {
            $jm = $breaks[$i];
            $jump = $jm - $jp;
            if ($jy < $jm) {
                break;
            }
            $leapJ += self::jalIdiv($jump, 33) * 8 + self::jalIdiv(self::jalMod($jump, 33), 4);
            $jp = $jm;
        }
        $n = $jy - $jp;
        $leapJ += self::jalIdiv($n, 33) * 8 + self::jalIdiv(self::jalMod($n, 33) + 3, 4);
        if (self::jalMod($jump, 33) === 4 && $jump - $n === 4) {
            $leapJ++;
        }
        $leapG = self::jalIdiv($gy, 4) - self::jalIdiv((self::jalIdiv($gy, 100) + 1) * 3, 4) - 150;
        $march = 20 + $leapJ - $leapG;
        if ($jump - $n < 6) {
            $n = $n - $jump + self::jalIdiv($jump + 4, 33) * 33;
        }
        $leap = self::jalMod(self::jalMod($n + 1, 33) - 1, 4);
        if ($leap === -1) {
            $leap = 4;
        }
        return ['leap' => $leap, 'gy' => $gy, 'march' => $march];
    }

    private static function gregorianToJdn(int $gy, int $gm, int $gd): int
    {
        $d = self::jalIdiv(($gy + self::jalIdiv($gm - 8, 6) + 100100) * 1461, 4)
            + self::jalIdiv(153 * self::jalMod($gm + 9, 12) + 2, 5)
            + $gd - 34840408;
        return $d - self::jalIdiv(self::jalIdiv($gy + 100100 + self::jalIdiv($gm - 8, 6), 100) * 3, 4) + 752;
    }

    private static function jalaliToJdn(int $jy, int $jm, int $jd): ?int
    {
        $r = self::jalCal($jy);
        if ($r === null) {
            return null;
        }
        return self::gregorianToJdn($r['gy'], 3, $r['march'])
            + ($jm - 1) * 31
            - self::jalIdiv($jm, 7) * ($jm - 7)
            + $jd - 1;
    }

    private static function jdnToGregorian(int $jdn): array
    {
        $j = 4 * $jdn + 139361631;
        $j = $j + self::jalIdiv(self::jalIdiv(4 * $jdn + 183187720, 146097) * 3, 4) * 4 - 3908;
        $i = self::jalIdiv(self::jalMod($j, 1461), 4) * 5 + 308;
        $gd = self::jalIdiv(self::jalMod($i, 153), 5) + 1;
        $gm = self::jalMod(self::jalIdiv($i, 153), 12) + 1;
        $gy = self::jalIdiv($j, 1461) - 100100 + self::jalIdiv(8 - $gm, 6);
        return [$gy, $gm, $gd];
    }

    private static function jdnToJalali(int $jdn): array
    {
        [$gy] = self::jdnToGregorian($jdn);
        $jy = $gy - 621;
        $r = self::jalCal($jy);
        if ($r === null) {
            return [$gy, 1, 1];
        }
        $jdn1f = self::gregorianToJdn($gy, 3, $r['march']);
        $k = $jdn - $jdn1f;
        if ($k >= 0) {
            if ($k <= 185) {
                return [$jy, 1 + self::jalIdiv($k, 31), self::jalMod($k, 31) + 1];
            }
            $k -= 186;
        } else {
            $jy -= 1;
            $k += 179;
            if ($r['leap'] === 1) {
                $k += 1;
            }
        }
        return [$jy, 7 + self::jalIdiv($k, 30), self::jalMod($k, 30) + 1];
    }

    public static function formatNumber(float|int $num): string
    {
        $persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $formatted = number_format($num, 0, '.', ',');
        return str_replace(range('0', '9'), $persianDigits, $formatted);
    }

    public static function timeAgo(string $datetime): string
    {
        $now = new DateTime();
        $past = new DateTime($datetime);
        $diff = $now->diff($past);

        if ($diff->y > 0) {
            return $diff->y . ' سال پیش';
        }
        if ($diff->m > 0) {
            return $diff->m . ' ماه پیش';
        }
        if ($diff->d > 0) {
            if ($diff->d >= 7) {
                $weeks = (int) floor($diff->d / 7);
                return $weeks . ' هفته پیش';
            }
            return $diff->d . ' روز پیش';
        }
        if ($diff->h > 0) {
            return $diff->h . ' ساعت پیش';
        }
        if ($diff->i > 0) {
            return $diff->i . ' دقیقه پیش';
        }
        if ($diff->s > 0) {
            return $diff->s . ' ثانیه پیش';
        }

        return 'همین الان';
    }

    public static function slug(string $string): string
    {
        $string = mb_strtolower(trim($string), 'UTF-8');
        $string = preg_replace('/[^a-z0-9\s-]/', '', $string);
        $string = preg_replace('/[\s-]+/', '-', $string);
        $string = trim($string, '-');
        return $string;
    }

    public static function auditLog(
        string $action,
        string $entity,
        ?int $entityId = null,
        mixed $oldValue = null,
        mixed $newValue = null
    ): void {
        try {
            $db = Database::getInstance();
            $userId = $_SESSION['pms_user']['id'] ?? null;
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';

            $db->insert('audit_logs', [
                'action'     => $action,
                'entity'     => $entity,
                'entity_id'  => $entityId,
                'user_id'    => $userId,
                'old_value'  => $oldValue !== null ? json_encode($oldValue, JSON_UNESCAPED_UNICODE) : null,
                'new_value'  => $newValue !== null ? json_encode($newValue, JSON_UNESCAPED_UNICODE) : null,
                'ip_address' => $ip,
                'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
            ]);
        } catch (\Throwable $e) {
            error_log('Audit log failed: ' . $e->getMessage());
        }
    }
}
