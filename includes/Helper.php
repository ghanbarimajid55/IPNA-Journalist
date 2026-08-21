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
        $g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];

        if ($gy > 1600) {
            $jy = 979;
            $gy -= 1600;
        } else {
            $jy = 0;
            $gy -= 621;
        }

        $gy2 = ($gm > 2) ? ($gy + 1) : $gy;
        $days = (365 * $gy) + (int)(($gy2 + 3) / 4) - (int)(($gy2 + 99) / 100) + (int)(($gy2 + 399) / 400)
                + $gd + $g_d_m[$gm - 1] - 1;

        $jy += 33 * (int)($days / 12053);
        $days %= 12053;
        $jy += 4 * (int)($days / 1461);
        $days %= 1461;
        if ($days > 365) {
            $jy += (int)(($days - 1) / 365);
            $days = ($days - 1) % 365;
        }

        if ($days < 186) {
            $jm = 1 + (int)($days / 31);
            $jd = 1 + ($days % 31);
        } else {
            $days -= 186;
            $jm = 7 + (int)($days / 30);
            $jd = 1 + ($days % 30);
        }

        return [$jy, $jm, $jd];
    }

    public static function jalaliToGregorian(int $jy, int $jm, int $jd): array
    {
        $jy += 79;
        $jm--;
        $days = -355668 + (365 * $jy) + ((int)($jy / 33)) * 8 + (int)(($jy % 33 + 1) / 4)
                + $jd + (($jm < 7) ? ($jm - 1) * 31 : (($jm - 7) * 30 + 6));

        $gy = 1600 + 400 * (int)($days / 146097);
        $days %= 146097;
        if ($days < 36525) {
            $gy += 100 * (int)($days / 36524);
            $days %= 36524;
            if ($days >= 365) {
                $gy++;
            }
        }

        $gy += (int)($days / 365);
        $days %= 365;
        if ($days > 218) {
            $gy++;
            $days -= 219;
        } elseif ($days == 218) {
            $gy++;
            $days = 0;
        }

        $gm = 0;
        if ($days >= 0) {
            $remaining = $days;
            $monthDays = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];

            if ($gy % 4 == 0 && ($gy % 100 != 0 || $gy % 400 == 0)) {
                $monthDays[1] = 29;
            }

            foreach ($monthDays as $index => $md) {
                if ($remaining < $md) {
                    $gm = $index + 1;
                    $gd = $remaining + 1;
                    break;
                }
                $remaining -= $md;
            }
        }

        return [$gy, $gm, $gd];
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
