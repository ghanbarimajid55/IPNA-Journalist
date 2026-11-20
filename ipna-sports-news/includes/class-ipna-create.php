<?php
if (!defined('ABSPATH')) {
    exit;
}

class IPNA_Sports_Create
{
    public static function create(array $article, array $source, array $settings): ?int
    {
        $title = (string)($article['title'] ?? '');
        $body  = (string)($article['body'] ?? '');
        if ($title === '') {
            return null;
        }

        $tags = array_values(array_filter(array_map('trim', (array)($article['tags'] ?? []))));
        $category = (string)(($article['sports_category'] ?? '') !== '' ? $article['sports_category'] : '');

        $postarr = [
            'post_title'   => $title,
            'post_content' => self::to_paragraphs($body),
            'post_type'    => (string)$settings['post_type'],
            'post_status'  => (string)$settings['post_status'],
            'post_author'  => (int)$settings['post_author'],
            'post_date'    => (string)$article['date'] !== '' ? (string)$article['date'] : current_time('mysql'),
        ];
        if ($tags) {
            $postarr['tags_input'] = $tags;
        }

        $post_id = wp_insert_post($postarr, true);
        if (is_wp_error($post_id)) {
            return null;
        }

        update_post_meta($post_id, '_ipna_src_url', (string)$article['link']);
        update_post_meta($post_id, '_ipna_src_hash', (string)$article['hash']);
        update_post_meta($post_id, '_ipna_src_name', (string)$source['name']);
        update_post_meta($post_id, '_ipna_keywords', implode(', ', $tags));
        update_post_meta($post_id, '_ipna_date', (string)$article['date']);
        if ($category !== '') {
            update_post_meta($post_id, '_ipna_sports_category', $category);
        }

        if (!empty($settings['set_featured']) && !empty($article['image'])) {
            self::set_featured((int)$post_id, (string)$article['image']);
        }

        return (int)$post_id;
    }

    protected static function set_featured(int $post_id, string $imageUrl): void
    {
        if (!function_exists('media_handle_sideload')) {
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }
        $tmp = download_url($imageUrl);
        if (is_wp_error($tmp)) {
            return;
        }
        $file = [
            'name'     => basename((string)parse_url($imageUrl, PHP_URL_PATH)),
            'tmp_name' => $tmp,
        ];
        $id = media_handle_sideload($file, $post_id, 'Featured image');
        if (!is_wp_error($id)) {
            set_post_thumbnail($post_id, (int)$id);
        }
    }

    protected static function to_paragraphs(string $body): string
    {
        $body = trim((string)$body);
        if ($body === '') {
            return '';
        }
        if (strpos($body, '<p>') !== false || strpos($body, '</p>') !== false) {
            return $body;
        }
        $parts = preg_split('/\n{2,}/u', $body);
        $html = '';
        foreach ($parts as $p) {
            $p = trim($p);
            if ($p !== '') {
                $html .= '<p>' . wp_kses_post($p) . '</p>';
            }
        }
        return $html;
    }
}
