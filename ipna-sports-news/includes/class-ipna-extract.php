<?php
if (!defined('ABSPATH')) {
    exit;
}

class IPNA_Sports_Extract
{
    public static function article(array $item, string $bodySelector = ''): array
    {
        $html = self::fetch_html($item['link']);

        $ogImage = self::meta($html, 'property', 'og:image');
        $ogTitle = self::meta($html, 'property', 'og:title');
        $ogDesc  = self::meta($html, 'property', 'og:description');
        $mkWords = self::meta($html, 'name', 'keywords');

        $body = self::body_text($html, $bodySelector);

        $title  = $ogTitle !== '' ? self::clean($ogTitle) : $item['title'];
        $subtitle = $ogDesc !== '' ? self::clean($ogDesc) : $item['desc'];
        $image  = $ogImage !== '' ? self::clean($ogImage) : $item['image'];
        $body   = $body !== '' ? $body : $item['content'];

        $tags = $item['tags'];
        if ($mkWords !== '') {
            foreach (preg_split('/[,،]+/u', $mkWords) as $t) {
                $t = trim($t);
                if ($t !== '' && !in_array($t, $tags, true)) {
                    $tags[] = $t;
                }
            }
        }

        return [
            'title'    => $title,
            'subtitle' => $subtitle,
            'body'     => $body,
            'image'    => $image,
            'date'     => $item['date'],
            'tags'     => array_slice($tags, 0, 20),
            'link'     => $item['link'],
            'hash'     => $item['hash'],
        ];
    }

    protected static function fetch_html(string $url): string
    {
        $response = wp_remote_get($url, [
            'timeout'     => 20,
            'redirection' => 3,
            'user-agent'  => 'Mozilla/5.0 (compatible; IPNA/1.0)',
        ]);
        if (is_wp_error($response)) {
            return '';
        }
        return (string)wp_remote_retrieve_body($response);
    }

    protected static function meta(string $html, string $attr, string $value): string
    {
        $patterns = [
            '#<meta[^>]+' . $attr . '=["\']' . preg_quote($value, '#') . '["\'][^>]+content=["\']([^"\']+)["\']#iu',
            '#<meta[^>]+content=["\']([^"\']+)["\'][^>]+' . $attr . '=["\']' . preg_quote($value, '#') . '["\']#iu',
        ];
        foreach ($patterns as $p) {
            if (preg_match($p, $html, $m)) {
                return html_entity_decode($m[1], ENT_QUOTES, 'UTF-8');
            }
        }
        return '';
    }

    protected static function body_text(string $html, string $selector): string
    {
        if ($html === '') {
            return '';
        }
        $html = preg_replace('#<(script|style|noscript|iframe|svg|form|nav|header|footer)[^>]*>.*?</\1>#is', ' ', $html);
        $html = preg_replace('/<!--.*?-->/s', ' ', $html);

        $block = '';
        if ($selector !== '') {
            if (preg_match('#<' . preg_quote($selector, '#') . '[^>]*>(.*?)</' . preg_quote($selector, '#') . '>#is', $html, $m)) {
                $block = $m[1];
            }
        }
        if ($block === '' && preg_match('#<article[^>]*>(.*?)</article>#is', $html, $m)) {
            $block = $m[1];
        }
        if ($block === '') {
            $block = $html;
        }

        $text = wp_strip_all_tags($block);
        $text = preg_replace('/[ \t]+/u', ' ', $text);
        $text = preg_replace('/\n[ \t]*\n+/u', "\n\n", $text);
        return trim($text);
    }

    protected static function clean(string $s): string
    {
        return trim(preg_replace('/\s+/u', ' ', $s));
    }
}
