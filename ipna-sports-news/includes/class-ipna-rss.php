<?php
if (!defined('ABSPATH')) {
    exit;
}

class IPNA_Sports_RSS
{
    public static function fetch(string $url): array
    {
        $feed = fetch_feed($url);
        if (is_wp_error($feed)) {
            return [];
        }

        $items = [];
        foreach ($feed->get_items() as $item) {
            $title = $item->get_title() ? html_entity_decode($item->get_title(), ENT_QUOTES, 'UTF-8') : '';
            $link  = $item->get_permalink();
            if ($title === '' || $link === '') {
                continue;
            }

            $content   = $item->get_content() ?: '';
            $desc      = $item->get_description() ?: '';
            $enclosure = $item->get_enclosure();
            $image     = $enclosure ? (string)$enclosure->get_link() : '';
            $date      = $item->get_date('Y-m-d H:i:s') ?: '';
            $cats      = [];
            foreach ((array)$item->get_categories() as $c) {
                $n = $c->get_name();
                if ($n !== '') {
                    $cats[] = $n;
                }
            }

            $items[] = [
                'title'   => trim($title),
                'link'    => trim($link),
                'desc'    => trim($desc),
                'content' => trim($content),
                'image'   => $image,
                'date'    => $date,
                'tags'    => $cats,
                'hash'    => md5((string)trim($link)),
            ];
        }
        return $items;
    }
}
