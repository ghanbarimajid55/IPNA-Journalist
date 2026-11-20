<?php
if (!defined('ABSPATH')) {
    exit;
}

class IPNA_Sports_AI
{
    public static function rewrite(string $title, string $body, array $settings): array
    {
        $provider = $settings['provider'] ?? 'openai';
        $apiKey   = (string)($settings['api_key'] ?? '');
        $model    = (string)($settings['model'] ?? '');
        $lang     = ($settings['language'] ?? 'persian') === 'english' ? 'english' : 'persian';

        if ($apiKey === '') {
            return ['title' => $title, 'body' => $body];
        }

        $bodyText = self::clip($body, 6000);
        $prompt   = self::prompt($title, $bodyText, $lang);

        $raw = $provider === 'gemini'
            ? self::gemini($apiKey, $model ?: 'gemini-1.5-flash', $prompt)
            : self::openai($apiKey, $model ?: 'gpt-4o-mini', $prompt);

        $json = self::extract_json($raw);
        if ($json === null) {
            return ['title' => $title, 'body' => $body];
        }

        $newTitle = trim((string)($json['title'] ?? ''));
        $newBody  = trim((string)($json['body'] ?? ''));
        return [
            'title' => $newTitle !== '' ? $newTitle : $title,
            'body'  => $newBody !== '' ? $newBody : $body,
        ];
    }

    protected static function prompt(string $title, string $body, string $lang): string
    {
        $langName = $lang === 'english' ? 'English' : 'Persian (فارسی)';
        return "تو یک خبرنگار ورزشی حرفه‌ای هستی. خبر ورزشی زیر را بازنویسی کن.\n"
            . "قواعد:\n"
            . "- تیتر را کوتاه، جذاب و خبری دوباره‌سازی کن.\n"
            . "- متن خبر را به سبک خبرنگاری ورزشی به زبان {$langName} بازنویسی کن؛ به همان طول تقریبی.\n"
            . "- همه‌ی حقایق، اعداد، نام تیم‌ها و افراد را حفظ کن.\n"
            . "- بدون HTML، بدون Markdown، فقط متن ساده با پاراگراف‌های جدا با خط خالی.\n"
            . "- فقط یک JSON معتبر بدون هیچ متن اضافه بده به شکل: {\"title\":\"...\",\"body\":\"...\"}\n\n"
            . "تیتر: {$title}\n\nمتن:\n{$body}";
    }

    protected static function openai(string $apiKey, string $model, string $prompt): string
    {
        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
            'timeout' => 60,
            'headers' => [
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type'  => 'application/json',
            ],
            'body' => wp_json_encode([
                'model'       => $model,
                'temperature' => 0.6,
                'messages'    => [
                    ['role' => 'system', 'content' => 'You are a JSON-only assistant.'],
                    ['role' => 'user',   'content' => $prompt],
                ],
            ]),
        ]);
        if (is_wp_error($response)) {
            return '';
        }
        $data = json_decode((string)wp_remote_retrieve_body($response), true);
        return (string)($data['choices'][0]['message']['content'] ?? '');
    }

    protected static function gemini(string $apiKey, string $model, string $prompt): string
    {
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode($model) . ':generateContent?key=' . rawurlencode($apiKey);
        $response = wp_remote_post($url, [
            'timeout' => 60,
            'headers' => ['Content-Type' => 'application/json'],
            'body' => wp_json_encode([
                'contents' => [
                    ['parts' => [['text' => $prompt]]],
                ],
                'generationConfig' => ['temperature' => 0.6],
            ]),
        ]);
        if (is_wp_error($response)) {
            return '';
        }
        $data = json_decode((string)wp_remote_retrieve_body($response), true);
        return (string)($data['candidates'][0]['content']['parts'][0]['text'] ?? '');
    }

    protected static function extract_json(string $raw): ?array
    {
        $raw = trim((string)$raw);
        $raw = preg_replace('/^\s*```(?:json)?\s*\n?/', '', $raw);
        $raw = preg_replace('/\n?\s*```\s*$/', '', $raw);
        $raw = trim($raw);
        $data = json_decode($raw, true);
        if (is_array($data)) {
            return $data;
        }
        if (preg_match('/\{(?:[^{}]|\{[^{}]*\})*\}/s', $raw, $m)) {
            $data = json_decode($m[0], true);
            if (is_array($data)) {
                return $data;
            }
        }
        return null;
    }

    protected static function clip(string $text, int $max): string
    {
        if (mb_strlen($text) <= $max) {
            return $text;
        }
        return mb_substr($text, 0, $max);
    }
}
