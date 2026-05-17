<?php
require_once(__DIR__ . '/../../../config.php');

require_login();

$context = context_system::instance();
require_capability('moodle/site:config', $context);

$days = optional_param('days', 30, PARAM_INT);
$fromdate = optional_param('fromdate', '', PARAM_RAW_TRIMMED);

if ($days !== -1) {
    $days = max(1, min($days, 3650));
}

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/subscriptions/admin/digital_sales_stats.php', ['days' => $days]));
$PAGE->set_title(get_string('digital_sales_stats_title', 'local_subscriptions'));
$PAGE->set_heading(get_string('digital_sales_stats_title', 'local_subscriptions'));

if ($fromdate !== '') {
    $fromtimestamp = strtotime($fromdate . ' 00:00:00');

    if ($fromtimestamp !== false) {
        $from = $fromtimestamp;
    } else {
        $from = time() - (30 * DAYSECS);
    }
} else if ($days === -1) {
    $from = 0;
} else {
    $from = time() - ($days * DAYSECS);
}

$sql = "
    SELECT id, payment_date, price, currency
      FROM {subscription_digital_payment_request}
     WHERE status IN ('paid', 'completed')
       AND payment_date IS NOT NULL
       AND payment_date >= :from
  ORDER BY payment_date ASC
";

$records = $DB->get_records_sql($sql, ['from' => $from]);

$points = array_values($records);
$count = count($points);

$bins = [];
$cumulative = [];

if ($count > 0) {
    $min = min(array_map(fn($r) => (int)$r->payment_date, $points));
    $max = max(array_map(fn($r) => (int)$r->payment_date, $points));

    $range = max(1, $max - $min);

    $bincount = min(100, max(1, $count));
    $binsize = max(3600, (int)ceil($range / $bincount));

    foreach ($points as $r) {
        $binstart = $min + (int)(floor(((int)$r->payment_date - $min) / $binsize) * $binsize);

        if (!isset($bins[$binstart])) {
            $bins[$binstart] = 0;
        }

        $bins[$binstart]++;
    }

    ksort($bins);

    $running = 0;
    foreach ($bins as $time => $value) {
        $running += $value;
        $cumulative[$time] = $running;
    }
}

echo $OUTPUT->header();

echo html_writer::start_div('container my-4');

echo html_writer::start_div('mb-4 d-flex gap-2 flex-wrap');

foreach ([1, 7, 30, 90, 365, -1] as $d) {
    echo html_writer::link(
        new moodle_url('/local/subscriptions/admin/digital_sales_stats.php', ['days' => $d]),
        $d === -1
            ? get_string('always')
            : ($d > 1
                ? get_string('digital_sales_stats_days_plural', 'local_subscriptions', $d)
                : get_string('digital_sales_stats_days', 'local_subscriptions', $d)),
        ['class' => $days === $d ? 'btn btn-primary' : 'btn btn-outline-primary']
    );
}

echo html_writer::link(
    new moodle_url('/local/subscriptions/admin/digital_purchases.php'),
    get_string('digital_sales_stats_back_to_purchases', 'local_subscriptions'),
    ['class' => 'btn btn-outline-secondary']
);


echo html_writer::end_div();


echo html_writer::start_tag('form', [
    'method' => 'get',
    'class' => 'd-flex gap-2 align-items-end mb-4 flex-wrap',
]);

echo html_writer::start_div();

echo html_writer::tag('label', get_string('digital_sales_stats_show_from', 'local_subscriptions'), [
    'for' => 'fromdate',
    'class' => 'form-label mb-1',
]);

echo html_writer::empty_tag('input', [
    'type' => 'date',
    'name' => 'fromdate',
    'id' => 'fromdate',
    'value' => s($fromdate),
    'class' => 'form-control',
]);

echo html_writer::end_div();

echo html_writer::tag('button', get_string('apply'), [
    'type' => 'submit',
    'class' => 'btn btn-primary',
]);

echo html_writer::end_tag('form');

echo html_writer::tag('p', get_string('digital_sales_stats_sales_found', 'local_subscriptions', $count), ['class' => 'text-muted']);

if ($count === 0) {
    echo $OUTPUT->notification(get_string('digital_sales_stats_no_sales', 'local_subscriptions'), 'info');
    echo html_writer::end_div();
    echo $OUTPUT->footer();
    exit;
}

echo html_writer::tag('h2', get_string('digital_sales_stats_histogram', 'local_subscriptions'), ['class' => 'h4 mt-4']);
echo local_subscriptions_render_bar_chart($bins);

echo html_writer::tag('h2', get_string('digital_sales_stats_cumulative', 'local_subscriptions'), ['class' => 'h4 mt-5']);
echo local_subscriptions_render_line_chart($cumulative);

echo html_writer::end_div();

echo $OUTPUT->footer();


function local_subscriptions_render_bar_chart(array $bins): string {
    $width = 1000;
    $height = 360;

    $paddingLeft = 70;
    $paddingRight = 20;
    $paddingTop = 20;
    $paddingBottom = 60;

    $chartWidth = $width - $paddingLeft - $paddingRight;
    $chartHeight = $height - $paddingTop - $paddingBottom;

    $max = max($bins);
    $barcount = count($bins);

    $barwidth = max(2, $chartWidth / max(1, $barcount));

    $svg = '<svg viewBox="0 0 '.$width.' '.$height.'" 
        style="width:100%;height:auto;background:#fff;border:1px solid #ddd;border-radius:12px;">';

    // Horizontal grid.
    $gridlines = 5;

    for ($i = 0; $i <= $gridlines; $i++) {
        $y = $paddingTop + ($chartHeight * $i / $gridlines);

        $svg .= '<line 
            x1="'.$paddingLeft.'" 
            y1="'.$y.'" 
            x2="'.($width - $paddingRight).'" 
            y2="'.$y.'" 
            stroke="#eee"
            stroke-width="1"
        />';

        $value = round($max - (($max / $gridlines) * $i));

        $svg .= '<text
            x="'.($paddingLeft - 10).'"
            y="'.($y + 5).'"
            text-anchor="end"
            font-size="12"
            fill="#666"
        >'.$value.'</text>';
    }

    // Axes.
    $svg .= '<line 
        x1="'.$paddingLeft.'" 
        y1="'.($height - $paddingBottom).'" 
        x2="'.($width - $paddingRight).'" 
        y2="'.($height - $paddingBottom).'" 
        stroke="#bbb"
    />';

    $svg .= '<line 
        x1="'.$paddingLeft.'" 
        y1="'.$paddingTop.'" 
        x2="'.$paddingLeft.'" 
        y2="'.($height - $paddingBottom).'" 
        stroke="#bbb"
    />';

    // Bars.
    $i = 0;
    $labelEvery = max(1, (int)ceil($barcount / 10));

    foreach ($bins as $time => $value) {
        $barheight = $chartHeight * ($value / max(1, $max));

        $x = $paddingLeft + ($i * $barwidth);
        $y = ($height - $paddingBottom) - $barheight;

        $svg .= '<rect
            x="'.$x.'"
            y="'.$y.'"
            width="'.max(1, $barwidth - 2).'"
            height="'.$barheight.'"
            rx="2"
            fill="#F4197D"
        >
            <title>'.s(userdate($time, '%d/%m/%y %H:%M')).' : '.$value.' vente(s)</title>
        </rect>';

        // X labels.
        if ($i % $labelEvery === 0) {
            $svg .= '<text
                x="'.($x + ($barwidth / 2)).'"
                y="'.($height - 20).'"
                text-anchor="middle"
                font-size="11"
                fill="#666"
                transform="rotate(-35 '.($x + ($barwidth / 2)).','.($height - 20).')"
            >'.s(userdate($time, '%d/%m %H:%M')).'</text>';
        }

        $i++;
    }

    $svg .= '</svg>';

    return $svg;
}


function local_subscriptions_render_line_chart(array $points): string {
    $width = 1000;
    $height = 360;

    $paddingLeft = 70;
    $paddingRight = 20;
    $paddingTop = 20;
    $paddingBottom = 60;

    $chartWidth = $width - $paddingLeft - $paddingRight;
    $chartHeight = $height - $paddingTop - $paddingBottom;

    $max = max($points);
    $count = count($points);

    $svg = '<svg viewBox="0 0 '.$width.' '.$height.'" 
        style="width:100%;height:auto;background:#fff;border:1px solid #ddd;border-radius:12px;">';

    // Horizontal grid.
    $gridlines = 5;

    for ($i = 0; $i <= $gridlines; $i++) {
        $y = $paddingTop + ($chartHeight * $i / $gridlines);

        $svg .= '<line
            x1="'.$paddingLeft.'"
            y1="'.$y.'"
            x2="'.($width - $paddingRight).'"
            y2="'.$y.'"
            stroke="#eee"
        />';

        $value = round($max - (($max / $gridlines) * $i));

        $svg .= '<text
            x="'.($paddingLeft - 10).'"
            y="'.($y + 5).'"
            text-anchor="end"
            font-size="12"
            fill="#666"
        >'.$value.'</text>';
    }

    // Axes.
    $svg .= '<line
        x1="'.$paddingLeft.'"
        y1="'.($height - $paddingBottom).'"
        x2="'.($width - $paddingRight).'"
        y2="'.($height - $paddingBottom).'"
        stroke="#bbb"
    />';

    $svg .= '<line
        x1="'.$paddingLeft.'"
        y1="'.$paddingTop.'"
        x2="'.$paddingLeft.'"
        y2="'.($height - $paddingBottom).'"
        stroke="#bbb"
    />';

    // Build coords.
    $coords = [];
    $dots = [];

    $i = 0;
    $labelEvery = max(1, (int)ceil($count / 10));

    foreach ($points as $time => $value) {
        $x = $paddingLeft + ($i * ($chartWidth / max(1, $count - 1)));

        $y = ($height - $paddingBottom)
            - ($chartHeight * ($value / max(1, $max)));

        $coords[] = $x . ',' . $y;

        $dots[] = [
            'x' => $x,
            'y' => $y,
            'time' => $time,
            'value' => $value,
            'showlabel' => ($i % $labelEvery === 0),
        ];

        $i++;
    }

    // Line.
    $svg .= '<polyline
        points="'.implode(' ', $coords).'"
        fill="none"
        stroke="#3b2b63"
        stroke-width="4"
        stroke-linecap="round"
        stroke-linejoin="round"
    />';

    // Dots + labels.
    foreach ($dots as $dot) {
        $svg .= '<circle
            cx="'.$dot['x'].'"
            cy="'.$dot['y'].'"
            r="4"
            fill="#F4197D"
        >
            <title>'.s(userdate($dot['time'], '%d/%m/%y %H:%M')).' : '.$dot['value'].' ventes cumulées</title>
        </circle>';

        if ($dot['showlabel']) {
            $svg .= '<text
                x="'.$dot['x'].'"
                y="'.($height - 20).'"
                text-anchor="middle"
                font-size="11"
                fill="#666"
                transform="rotate(-35 '.$dot['x'].','.($height - 20).')"
            >'.s(userdate($dot['time'], '%d/%m %H:%M')).'</text>';
        }
    }

    $svg .= '</svg>';

    return $svg;
}