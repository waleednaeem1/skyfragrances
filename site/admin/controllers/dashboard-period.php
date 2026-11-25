<?php
defined('SKYFR') || exit;

const DASHBOARD_REVENUE_STATUSES = "('confirmed', 'packing', 'shipped', 'delivered')";
const DASHBOARD_RANGES = ['day' => 'Day', 'week' => 'Week', 'month' => 'Month', 'year' => 'Year'];
const DASHBOARD_RANGE_FORMATS = ['day' => 'Y-m-d', 'week' => 'Y-m-d', 'month' => 'Y-m', 'year' => 'Y'];
const DASHBOARD_CUSTOM_MAX_DAYS = 3660;

function dashboard_parse_date(string $value, string $format, DateTimeZone $tz): ?DateTimeImmutable
{
    $date = DateTimeImmutable::createFromFormat('!' . $format, $value, $tz);
    return $date !== false && $date->format($format) === $value ? $date : null;
}

function dashboard_range_start(string $range, DateTimeImmutable $day): DateTimeImmutable
{
    return match ($range) {
        'week' => $day->modify('monday this week'),
        'month' => $day->modify('first day of this month'),
        'year' => $day->setDate((int) $day->format('Y'), 1, 1),
        default => $day,
    };
}

function dashboard_range_step(string $range, DateTimeImmutable $start, int $direction): DateTimeImmutable
{
    return $start->modify(($direction > 0 ? '+1 ' : '-1 ') . $range);
}

function dashboard_query(string $range, DateTimeImmutable $start): array
{
    return ['range' => $range, 'at' => $start->format(DASHBOARD_RANGE_FORMATS[$range])];
}

function dashboard_custom_query(DateTimeImmutable $from, DateTimeImmutable $to): array
{
    return ['range' => 'custom', 'from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d')];
}

function dashboard_url(array $query, string $base = '/admin'): string
{
    return $base . '?' . http_build_query($query);
}

function dashboard_span_label(DateTimeImmutable $first, DateTimeImmutable $last): string
{
    if ($first->format('Y-m-d') === $last->format('Y-m-d')) {
        return $first->format('D, j M Y');
    }
    if ($first->format('Y') !== $last->format('Y')) {
        return $first->format('j M Y') . ' – ' . $last->format('j M Y');
    }
    if ($first->format('m') !== $last->format('m')) {
        return $first->format('j M') . ' – ' . $last->format('j M Y');
    }
    return $first->format('j') . ' – ' . $last->format('j M Y');
}

function dashboard_range_label(string $range, DateTimeImmutable $start): string
{
    return match ($range) {
        'day' => $start->format('D, j M Y'),
        'week' => dashboard_span_label($start, $start->modify('+6 days')),
        'month' => $start->format('F Y'),
        default => $start->format('Y'),
    };
}

function dashboard_first_day(DateTimeImmutable $today): DateTimeImmutable
{
    $first = db_fetch_column('SELECT MIN(created_at) FROM orders');
    return is_string($first) ? min($today, new DateTimeImmutable(substr($first, 0, 10), $today->getTimezone())) : $today;
}

function dashboard_period(array $get, DateTimeImmutable $now, DateTimeImmutable $firstDay): array
{
    $tz = $now->getTimezone();
    $today = $now->setTime(0, 0);
    $range = (string) ($get['range'] ?? '');
    if ($range === 'custom') {
        $from = dashboard_parse_date((string) ($get['from'] ?? ''), 'Y-m-d', $tz);
        $to = dashboard_parse_date((string) ($get['to'] ?? ''), 'Y-m-d', $tz);
        if ($from !== null && $to !== null) {
            [$from, $to] = $from <= $to ? [$from, $to] : [$to, $from];
            $to = min($to, $today);
            $from = max(min($from, $to), $to->modify('-' . (DASHBOARD_CUSTOM_MAX_DAYS - 1) . ' days'));
            return dashboard_custom_period($from, $to, $now, $firstDay);
        }
    }
    $range = isset(DASHBOARD_RANGES[$range]) ? $range : 'month';
    $anchor = dashboard_parse_date((string) ($get['at'] ?? ''), DASHBOARD_RANGE_FORMATS[$range], $tz) ?? $today;
    return dashboard_calendar_period($range, dashboard_range_start($range, min($anchor, $today)), $now, $firstDay);
}

function dashboard_calendar_period(string $range, DateTimeImmutable $start, DateTimeImmutable $now, DateTimeImmutable $firstDay): array
{
    $end = dashboard_range_step($range, $start, 1);
    $prevStart = dashboard_range_step($range, $start, -1);
    $current = $now < $end;
    $sameTime = ['day' => 'same time yesterday', 'week' => 'same time last week', 'month' => 'same time last month', 'year' => 'same time last year'];
    return [
        'range' => $range,
        'from' => $start,
        'to' => $end,
        'label' => dashboard_range_label($range, $start),
        'current' => $current,
        'compare_from' => $prevStart,
        'compare_to' => $current ? min($prevStart->add($start->diff($now)), $start) : $start,
        'compare_label' => $current ? $sameTime[$range] : ($range === 'day' ? 'on ' : 'in ') . dashboard_range_label($range, $prevStart),
        'query' => dashboard_query($range, $start),
        'prev_query' => $prevStart >= dashboard_range_start($range, $firstDay) ? dashboard_query($range, $prevStart) : null,
        'next_query' => $current ? null : dashboard_query($range, $end),
        'unit' => ['day' => 'hour', 'week' => 'day', 'month' => 'day', 'year' => 'month'][$range],
    ];
}

function dashboard_custom_period(DateTimeImmutable $from, DateTimeImmutable $to, DateTimeImmutable $now, DateTimeImmutable $firstDay): array
{
    $end = $to->modify('+1 day');
    $days = (int) $from->diff($end)->days;
    $prevStart = $from->modify('-' . $days . ' days');
    $current = $now < $end;
    $lastStart = $now->setTime(0, 0)->modify('-' . ($days - 1) . ' days');
    $nextStart = min($end, $lastStart);
    return [
        'range' => 'custom',
        'from' => $from,
        'to' => $end,
        'label' => dashboard_span_label($from, $to),
        'current' => $current,
        'compare_from' => $prevStart,
        'compare_to' => $current ? min($prevStart->add($from->diff($now)), $from) : $from,
        'compare_label' => 'in the previous ' . ($days === 1 ? 'day' : number_format($days) . ' days'),
        'query' => dashboard_custom_query($from, $to),
        'prev_query' => $from > $firstDay ? dashboard_custom_query($prevStart, $from->modify('-1 day')) : null,
        'next_query' => $current ? null : dashboard_custom_query($nextStart, $nextStart->modify('+' . ($days - 1) . ' days')),
        'unit' => $days <= 62 ? 'day' : ($days <= 1100 ? 'month' : 'year'),
    ];
}

function dashboard_presets(DateTimeImmutable $now): array
{
    $today = $now->setTime(0, 0);
    $lastDays = static fn (int $days): array => dashboard_custom_query($today->modify('-' . ($days - 1) . ' days'), $today);
    return [
        ['label' => 'Today', 'query' => dashboard_query('day', $today)],
        ['label' => 'Yesterday', 'query' => dashboard_query('day', $today->modify('-1 day'))],
        ['label' => 'This week', 'query' => dashboard_query('week', dashboard_range_start('week', $today))],
        ['label' => 'Last week', 'query' => dashboard_query('week', dashboard_range_start('week', $today)->modify('-1 week'))],
        ['label' => 'This month', 'query' => dashboard_query('month', dashboard_range_start('month', $today))],
        ['label' => 'Last month', 'query' => dashboard_query('month', dashboard_range_start('month', $today)->modify('-1 month'))],
        ['label' => 'This year', 'query' => dashboard_query('year', dashboard_range_start('year', $today))],
        ['label' => 'Last year', 'query' => dashboard_query('year', dashboard_range_start('year', $today)->modify('-1 year'))],
        ['label' => 'Last 7 days', 'query' => $lastDays(7)],
        ['label' => 'Last 30 days', 'query' => $lastDays(30)],
        ['label' => 'Last 90 days', 'query' => $lastDays(90)],
    ];
}

function dashboard_picker(array $period, DateTimeImmutable $now, string $base = '/admin'): array
{
    $today = $now->setTime(0, 0);
    $anchor = $period['current'] ? $today : $period['from'];
    $ranges = [];
    foreach (DASHBOARD_RANGES as $range => $label) {
        $ranges[] = ['label' => $label, 'href' => dashboard_url(dashboard_query($range, dashboard_range_start($range, $anchor)), $base), 'active' => $period['range'] === $range];
    }
    $presets = [];
    foreach (dashboard_presets($now) as $preset) {
        $presets[] = ['label' => $preset['label'], 'href' => dashboard_url($preset['query'], $base), 'active' => $preset['query'] === $period['query']];
    }
    return [
        'ranges' => $ranges,
        'presets' => $presets,
        'label' => $period['label'],
        'prev' => $period['prev_query'] === null ? null : dashboard_url($period['prev_query'], $base),
        'next' => $period['next_query'] === null ? null : dashboard_url($period['next_query'], $base),
        'action' => $base,
        'from' => $period['from']->format('Y-m-d'),
        'to' => $period['to']->modify('-1 day')->format('Y-m-d'),
        'max' => $today->format('Y-m-d'),
    ];
}

function dashboard_totals(DateTimeImmutable $from, DateTimeImmutable $to): array
{
    $window = ['from' => $from->format('Y-m-d H:i:s'), 'to' => $to->format('Y-m-d H:i:s')];
    $row = db_fetch('SELECT COUNT(*) AS c, COALESCE(SUM(grand_total), 0) AS v FROM orders WHERE status IN ' . DASHBOARD_REVENUE_STATUSES . ' AND created_at >= :from AND created_at < :to', $window);
    $count = (int) ($row['c'] ?? 0);
    $paisa = money_paisa((string) ($row['v'] ?? '0'));
    return [
        'revenue' => $paisa,
        'orders' => $count,
        'average' => $count > 0 ? intdiv($paisa, $count) : 0,
        'bottles' => (int) db_fetch_column('SELECT COALESCE(SUM(oi.quantity), 0) FROM order_items oi JOIN orders o ON o.id = oi.order_id WHERE o.status IN ' . DASHBOARD_REVENUE_STATUSES . ' AND o.created_at >= :from AND o.created_at < :to', $window),
    ];
}

function dashboard_delta(int $value, int $previous): array
{
    if ($previous === 0) {
        return $value === 0 ? ['text' => 'No change', 'tone' => 'flat'] : ['text' => 'New', 'tone' => $value > 0 ? 'up' : 'down'];
    }
    $percent = ($value - $previous) / abs($previous) * 100;
    if (abs($percent) < 0.5) {
        return ['text' => 'No change', 'tone' => 'flat'];
    }
    $shown = (string) preg_replace('/\.0$/', '', number_format(abs($percent), abs($percent) < 10 ? 1 : 0));
    return ['text' => ($percent > 0 ? '▲ ' : '▼ ') . $shown . '%', 'tone' => $percent > 0 ? 'up' : 'down'];
}

function dashboard_kpis(array $period): array
{
    $current = dashboard_totals($period['from'], $period['to']);
    $previous = dashboard_totals($period['compare_from'], $period['compare_to']);
    $cards = [];
    foreach (['revenue' => ['Revenue', true], 'orders' => ['Orders', false], 'average' => ['Average order', true], 'bottles' => ['Bottles sold', false]] as $key => [$label, $isMoney]) {
        $show = static fn (int $value): string => $isMoney ? money(money_from_paisa($value)) : number_format($value);
        $cards[] = [
            'label' => $label,
            'value' => $show($current[$key]),
            'delta' => dashboard_delta($current[$key], $previous[$key]),
            'compare' => 'vs ' . $show($previous[$key]) . ' ' . $period['compare_label'],
        ];
    }
    return ['cards' => $cards, 'revenue' => $current['revenue'], 'orders' => $current['orders']];
}

function dashboard_axis_step(int $rupees): int
{
    $raw = max(1, $rupees / 4);
    $magnitude = 10 ** (int) floor(log10($raw));
    foreach ([1, 2, 2.5, 5] as $factor) {
        if ($raw <= $factor * $magnitude) {
            return max(1, (int) round($factor * $magnitude));
        }
    }
    return 10 * $magnitude;
}

function dashboard_axis_label(int $rupees): string
{
    if ($rupees >= 1000000) {
        return rtrim(rtrim(number_format($rupees / 1000000, 1), '0'), '.') . 'M';
    }
    if ($rupees >= 1000) {
        return rtrim(rtrim(number_format($rupees / 1000, 1), '0'), '.') . 'K';
    }
    return (string) $rupees;
}

function dashboard_bucket_sql(string $unit, string $column): string
{
    return ['hour' => 'HOUR(' . $column . ')', 'day' => 'DATE(' . $column . ')', 'month' => 'DATE_FORMAT(' . $column . ", '%Y-%m')", 'year' => 'YEAR(' . $column . ')'][$unit];
}

function dashboard_chart(array $period, DateTimeImmutable $now): array
{
    $rows = db_fetch_all(
        'SELECT ' . dashboard_bucket_sql($period['unit'], 'created_at') . ' AS k, COUNT(*) AS c, COALESCE(SUM(grand_total), 0) AS v FROM orders WHERE status IN ' . DASHBOARD_REVENUE_STATUSES . ' AND created_at >= :from AND created_at < :to GROUP BY k',
        ['from' => $period['from']->format('Y-m-d H:i:s'), 'to' => $period['to']->format('Y-m-d H:i:s')]
    );
    $byKey = [];
    foreach ($rows as $row) {
        $byKey[(string) $row['k']] = ['orders' => (int) $row['c'], 'paisa' => money_paisa((string) $row['v'])];
    }
    return dashboard_chart_build($period, $now, $byKey, 'Revenue');
}

function dashboard_chart_build(array $period, DateTimeImmutable $now, array $byKey, string $measure, string $base = '/admin'): array
{
    $unit = $period['unit'];
    $step = ['hour' => '+1 hour', 'day' => '+1 day', 'month' => 'first day of next month', 'year' => '+1 year'][$unit];
    $slots = [];
    for ($at = $period['from']; $at < $period['to']; $at = $at->modify($step)) {
        $slots[] = $at;
    }
    $every = match (true) {
        $unit === 'hour' => 3,
        $unit === 'day' && count($slots) > 14 => (int) ceil(count($slots) / 7),
        $unit === 'month' && count($slots) > 12 => (int) ceil(count($slots) / 12),
        default => 1,
    };
    $bars = [];
    $max = 0;
    foreach ($slots as $index => $at) {
        $key = match ($unit) {
            'hour' => (string) (int) $at->format('G'),
            'day' => $at->format('Y-m-d'),
            'month' => $at->format('Y-m'),
            default => $at->format('Y'),
        };
        $data = ($byKey[$key] ?? []) + ['orders' => 0, 'paisa' => 0];
        $max = max($max, intdiv(max(0, $data['paisa']), 100));
        $bars[] = [
            'key' => $key,
            'paisa' => $data['paisa'],
            'orders' => $data['orders'],
            'value' => money(money_from_paisa($data['paisa'])),
            'axis' => $index % $every === 0 ? match ($unit) {
                'hour' => $at->format('ga'),
                'day' => count($slots) <= 7 ? $at->format('D j') : $at->format('j M'),
                'month' => count($slots) <= 12 ? $at->format('M') : $at->format('M y'),
                default => $at->format('Y'),
            } : '',
            'tip' => match ($unit) {
                'hour' => $at->format('g a') . ' – ' . $at->modify('+1 hour')->format('g a'),
                'day' => $at->format('D, j M Y'),
                'month' => $at->format('F Y'),
                default => $at->format('Y'),
            },
            'href' => $unit === 'hour' ? null : dashboard_url(dashboard_query(['day' => 'day', 'month' => 'month', 'year' => 'year'][$unit], $at), $base),
            'future' => $at > $now,
        ];
    }
    $ticks = [];
    $top = 0;
    if ($max > 0) {
        $stepRupees = dashboard_axis_step($max);
        $intervals = (int) ceil($max / $stepRupees);
        $top = $intervals * $stepRupees;
        for ($i = $intervals; $i >= 0; $i--) {
            $ticks[] = ['label' => dashboard_axis_label($i * $stepRupees), 'pos' => round($i / $intervals * 100, 3)];
        }
    }
    return [
        'bars' => $bars,
        'top_paisa' => $top * 100,
        'ticks' => $ticks,
        'caption' => $measure . ' by ' . $unit . ', ' . $period['label'],
        'unit_label' => ucfirst($unit),
    ];
}
