<?php
if (!defined('ABSPATH')) {
    exit;
}

class IPNA_Report_Analyzer
{
    protected $pms;

    public function __construct($pms)
    {
        $this->pms = $pms;
    }

    public function summary(array $users): array
    {
        $n      = count($users);
        $score  = 0.0;
        $qty    = 0;
        $news   = 0;
        $photo  = 0;
        $video  = 0;
        foreach ($users as $u) {
            $score += (float)$u['total_score'];
            $qty   += (int)$u['total_quantity'];
            $news  += (int)$u['news_qty'];
            $photo += (int)$u['photo_qty'];
            $video += (int)$u['video_qty'];
        }
        return [
            'users'    => $n,
            'quantity' => $qty,
            'score'    => $score,
            'news'     => $news,
            'photo'    => $photo,
            'video'    => $video,
            'avg'      => $n > 0 ? round($score / $n, 1) : 0,
        ];
    }

    public function categoryBreakdown(array $users): array
    {
        return [
            ['label' => 'اخبار', 'value' => $this->sum($users, 'news_qty')],
            ['label' => 'عکس',   'value' => $this->sum($users, 'photo_qty')],
            ['label' => 'ویدئو', 'value' => $this->sum($users, 'video_qty')],
        ];
    }

    public function achievement(float $score, float $target): float
    {
        if ($target <= 0) {
            return 0;
        }
        return round($score / $target * 100, 1);
    }

    public function makeTargetMap(array $targets): array
    {
        $map = [];
        foreach ($targets as $t) {
            $map[(int)$t['user_id']] = $t;
        }
        return $map;
    }

    public function fillDaily(array $series, string $from, string $to): array
    {
        $byDate = [];
        foreach ($series as $row) {
            $byDate[$row['d']] = $row;
        }
        $out = [];
        $step = 86400;
        $start = strtotime($from);
        $end = strtotime($to);
        for ($t = $start; $t <= $end; $t += $step) {
            $d = date('Y-m-d', $t);
            $out[] = [
                'd'     => $d,
                'score' => (float)($byDate[$d]['score'] ?? 0),
                'qty'   => (int)($byDate[$d]['qty'] ?? 0),
            ];
        }
        return $out;
    }

    protected function sum(array $users, string $key): int
    {
        $s = 0;
        foreach ($users as $u) {
            $s += (int)$u[$key];
        }
        return $s;
    }
}
