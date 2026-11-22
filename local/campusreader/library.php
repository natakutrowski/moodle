<?php
// CampusFR Reader C2.1 — Advent doors and permanent bookshelves. GPL-3.0-or-later.
declare(strict_types=1);
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/classes/local/presentation.php');
require_login();

use local_campusreader\local\collection_access;
use local_campusreader\local\collection_repository;
use local_campusreader\local\presentation;

global $DB, $PAGE, $OUTPUT, $USER;
$slug = optional_param('collection', 'avent-2026', PARAM_ALPHANUMEXT);
$collection = collection_repository::collection($slug);
if (!collection_access::can_view_collection($collection)) {
    // Deny without disclosing whether the resource exists or its publication date.
    // Throwing Moodle's capability exception here produced HTTP 500 in the live QA tests.
    http_response_code(404);
    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: private, no-store, max-age=0');
    header('X-Robots-Tag: noindex, nofollow');
    echo '<!doctype html><html lang="' . s(current_language()) . '"><head><meta charset="utf-8">' .
        '<meta name="viewport" content="width=device-width,initial-scale=1">' .
        '<title>' . s(get_string('cfr_lotc2_reader_common_01', 'local_campusreader')) . '</title></head><body>' .
        '<main><h1>' . s(get_string('cfr_lotc2_reader_common_02', 'local_campusreader')) . '</h1><p>' . s(get_string('cfr_lotc2_reader_common_03', 'local_campusreader')) . '</p></main>' .
        '</body></html>';
    exit;
}
$admin = collection_access::is_admin();
$slots = collection_repository::slots((int) $collection->id);
$books = $DB->get_records('local_cr_book', ['collectionid' => $collection->id]);
$bookmap = [];
foreach ($books as $book) {
    $bookmap[(int) $book->id] = $book;
}
$natural = presentation::mode($collection);
$mode = $natural;
// Administrator-only layout preview: never affects publication, files, or unlock dates.
$previewmode = optional_param('view', '', PARAM_ALPHA);
if ($admin && in_array($previewmode, ['advent', 'library'], true)) {
    $mode = $previewmode;
}
$now = time();

$PAGE->set_url(new moodle_url('/local/campusreader/library.php', ['collection' => $slug]));
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title($collection->title . ' — CampusFR');
$PAGE->set_heading($collection->title);
// CAMPUSFR_C33_READER_NAV: Moodle's native breadcrumb, consistent with My Library.
$PAGE->navbar->add(get_string('cfr_c_my_campus', 'local_campusreader'), new moodle_url('/local/subscriptions/mon_campus.php'));
$PAGE->navbar->add(get_string('cfr_c_my_library', 'local_campusreader'), new moodle_url('/local/campusreader/my.php'));
$PAGE->navbar->add(format_string((string)$collection->title));
$PAGE->requires->css(new moodle_url('/local/campusreader/styles/library.css', [
    'v' => (string) filemtime(__DIR__ . '/styles/library.css'),
]));
if ($mode === 'advent') {
    $PAGE->requires->css(new moodle_url('/local/campusreader/styles/advent-illustrated.css', [
        'v' => (string) filemtime(__DIR__ . '/styles/advent-illustrated.css'),
    ]));
    $PAGE->requires->css(new moodle_url('/local/campusreader/styles/advent-magic.css', [
        'v' => (string) filemtime(__DIR__ . '/styles/advent-magic.css'),
    ]));
    $PAGE->requires->js_call_amd('local_campusreader/library', 'init');
}

// Precompute only permitted URLs and titles. Closed student slots never expose the book identity.
$cards = [];
foreach ($slots as $slot) {
    $book = $bookmap[(int) ($slot->bookid ?? 0)] ?? null;
    $unlocked = collection_access::unlocked($slot, $now);
    $canread = $book && collection_access::can_read_book($book, $collection, $slot, $now);
    $localdate = (new DateTimeImmutable('@' . $slot->unlockat))
        ->setTimezone(new DateTimeZone($collection->timezone));
    $date = $localdate->format('d/m/Y');
    $daylabel = $localdate->format('j F Y');
    $url = $canread && $book ? (new moodle_url('/local/campusreader/read.php', [
        'id' => (int) $book->id,
    ]))->out(false) : null;
    $title = $canread && $book ? $book->title : $slot->teaser;
    $state = $admin && $book && empty($book->published) ? get_string('cfr_lotc2_library_01', 'local_subscriptions') :
        ($canread ? get_string('cfr_lotc2_library_02', 'local_subscriptions') : ($unlocked ? get_string('cfr_lotc2_library_03', 'local_subscriptions') : get_string('cfr_lotc2_library_04', 'local_subscriptions') . $date));
    $cards[] = [
        'day' => (int) $slot->position,
        'date' => $date,
        'daylabel' => $daylabel,
        'title' => (string) $title,
        'state' => $state,
        'url' => $url,
        'open' => (bool) $unlocked,
        'size' => presentation::size($slot),
        'preview' => (bool) ($admin && $book && empty($book->published)),
    ];
}

echo $OUTPUT->header();
echo '<div class="cfr-library cfr-library--' . s($mode) . '" data-cfr-library-mode="' . s($mode) . '"';
echo ' data-cfr-session-key="cfr.advent.open.v1.' . (int) $collection->id . '.' . (int) $USER->id . '">';
echo '<header class="cfr-library__hero">';
echo '<span class="cfr-library__eyebrow">CAMPUSFR · ' . ($mode === 'advent' ? get_string('cfr_lotc2_calendar_caps', 'local_campusreader') : get_string('cfr_lotc2_library_caps', 'local_campusreader')) . '</span>';
echo '<h2>' . s($collection->title) . '</h2>';
echo '<p>' . ($mode === 'advent' ? get_string('cfr_lotc2_library_05', 'local_subscriptions') :
    get_string('cfr_lotc2_library_06', 'local_subscriptions')) . '</p>';
echo '<span class="cfr-library__count">' . count($cards) . '' . s(get_string('cfr_lotc2_library_07', 'local_subscriptions')) . '</span>';
if ($admin) {
    echo '<p class="cfr-library__admin">' . s(get_string('cfr_lotc2_library_08', 'local_subscriptions')) . '' . (int) $collection->courseid .
        ' · collection ' . (empty($collection->published) ? get_string('cfr_lotc2_library_09', 'local_subscriptions') : get_string('cfr_fix_published', 'local_campusreader')) . '</p>';
    echo '<nav class="cfr-library__view-switch" aria-label="' . s(get_string('cfr_lotc2_library_10', 'local_subscriptions')) . '">';
    foreach (['advent' => get_string('cfr_fix_calendar_label', 'local_campusreader'), 'library' => get_string('cfr_fix_library_label', 'local_campusreader')] as $target => $label) {
        $switchurl = (new moodle_url('/local/campusreader/library.php', [
            'collection' => $slug, 'view' => $target,
        ]))->out(false);
        echo '<a class="cfr-library__view-link' . ($target === $mode ? ' is-active' : '') .
            '" href="' . s($switchurl) . '"' . ($target === $mode ? ' aria-current="page"' : '') .
            '>' . s($label) . '</a>';
    }
    echo '</nav>';
    if ($mode === 'advent') {
        // Administrator-only replay: never accepts a URL or bypasses collection/book access gates.
        $replayable = array_values(array_filter($cards, static fn($item) => $item['url'] !== null));
        if ($replayable) {
            echo '<div class="cfr-advent-debug" data-cfr-admin-replay>';
            echo '<label for="cfr-advent-replay-day">' . s(get_string('cfr_lotc2_library_11', 'local_subscriptions')) . '</label>';
            echo '<select id="cfr-advent-replay-day" data-cfr-replay-day aria-label="' . s(get_string('cfr_lotc2_library_12', 'local_subscriptions')) . '">';
            foreach ($replayable as $replaycard) {
                echo '<option value="' . (int) $replaycard['day'] . '">Case ' .
                    (int) $replaycard['day'] . ' · ' . s($replaycard['date']) . '</option>';
            }
            echo '</select>';
            echo '<button type="button" data-cfr-replay-first>' . s(get_string('cfr_lotc2_library_13', 'local_subscriptions')) . '</button>';
            echo '<span class="cfr-advent-debug__hint" data-cfr-replay-status role="status" aria-live="polite"></span>';
            echo '</div>';
        }
    }
    if (($collection->presentationmode ?? 'auto') === 'auto') {
        $switchdate = (int) ($collection->libraryafter ?? 0);
        if ($switchdate) {
            $date = (new DateTimeImmutable('@' . $switchdate))->setTimezone(new DateTimeZone($collection->timezone));
            echo '<p class="cfr-library__switch-note">' . s(get_string('cfr_lotc2_library_14', 'local_subscriptions')) . '' .
                s($date->format('d/m/Y H:i')) . ' (' . s($collection->timezone) . ').</p>';
        } else {
            echo '<p class="cfr-library__switch-note">' . s(get_string('cfr_lotc2_library_15', 'local_subscriptions')) . '</p>';
        }
    }
}
echo '</header>';

if ($mode === 'advent') {
    echo '<section class="cfr-advent" aria-label="' . s(get_string('cfr_lotc2_library_16', 'local_subscriptions')) . '">';
    $firstslot = reset($slots);
    $firstdate = $firstslot ? (new DateTimeImmutable('@' . $firstslot->unlockat))
        ->setTimezone(new DateTimeZone($collection->timezone)) : null;
    $month = $firstdate ? core_text::strtoupper(userdate($firstslot->unlockat, '%B', $collection->timezone)) :
        get_string('cfr_lotc2_library_caps', 'local_campusreader');
    $year = $firstdate ? $firstdate->format('Y') : '';
    echo '<div class="cfr-advent__sky" aria-hidden="true">';
    echo '<span class="cfr-advent__sky-sparks">✧ &nbsp; ✦ &nbsp; ✧</span>';
    echo '<span class="cfr-advent__sky-month">' . s($month) . ' <strong>' . s($year) . '</strong></span>';
    echo '<span class="cfr-advent__sky-caption">' . s(get_string('cfr_lotc2_village', 'local_campusreader', count($cards))) . '</span>';
    echo '</div>';
    echo '<p class="cfr-advent__instruction">' . s(get_string('cfr_lotc2_library_17', 'local_subscriptions')) . '</p>';
    // Stable storybook arrangement, never random on refresh. DB still owns every date and permission.
    // For the 2026 collection the sequence is art-directed; other collections are deterministic too.
    $calendarcards = $cards;
    $curated = [11, 3, 26, 7, 18, 1, 14, 29, 5, 22, 9, 31, 12, 2, 16, 24,
        8, 20, 4, 27, 15, 10, 28, 6, 23, 13, 30, 17, 25, 21, 19];
    $rank = array_flip($curated);
    $curated24 = [11, 3, 7, 18, 1, 14, 5, 22, 9, 12, 2, 16, 24,
        8, 20, 4, 15, 10, 6, 23, 13, 17, 21, 19];
    $iscurated24 = count($calendarcards) === 24 &&
        count(array_intersect(array_column($calendarcards, 'day'), $curated24)) === 24;
    if ($iscurated24) {
        $rank = array_flip($curated24);
        usort($calendarcards, static fn($a, $b) => $rank[$a['day']] <=> $rank[$b['day']]);
    }
    $iscurated = count($calendarcards) === 31 &&
        count(array_intersect(array_column($calendarcards, 'day'), $curated)) === 31;
    if ($iscurated) {
        usort($calendarcards, static fn($a, $b) => $rank[$a['day']] <=> $rank[$b['day']]);
    } else if (!$iscurated24) {
        usort($calendarcards, static fn($a, $b) => strcmp(
            hash('sha256', 'campusfr-advent-' . $collection->id . ':' . $a['day']),
            hash('sha256', 'campusfr-advent-' . $collection->id . ':' . $b['day'])
        ));
    }
    echo '<ol class="cfr-advent__grid">';
    foreach ($calendarcards as $card) {
        $day = $card['day'];
        $haslink = $card['url'] !== null;
        $closed = !$card['open'] && !$haslink;
        $size = $card['size'];
        if ($size === 'normal') {
            // Visual defaults only: custom layoutsize values continue to take precedence.
            $size = in_array($day, [1, 24, 31], true) ? 'feature' :
                (in_array($day, [7, 12, 17, 23], true) ? 'tall' :
                (in_array($day, [3, 8, 11, 15, 20, 26, 29], true) ? 'wide' : 'normal'));
        }
        $revealid = 'cfr-advent-reveal-' . $day;
        echo '<li class="cfr-advent__slot cfr-advent__slot--' . s($size) .
            ($haslink ? ' is-available' : ' is-unavailable') .
            ($card['preview'] ? ' is-preview' : '') . '" data-cfr-day="' . $day . '">';
        echo '<div class="cfr-advent__case">';
        $doorbody = '<span class="cfr-advent__door-front">' .
            '<span class="cfr-advent__door-crest" aria-hidden="true">✦</span>' .
            '<span class="cfr-advent__number">' . $day . '</span>' .
            '<span class="cfr-advent__decoration" aria-hidden="true">✧</span>' .
            '<span class="cfr-advent__date">' . s($card['date']) . '</span>' .
            '<span class="cfr-advent__invitation">' .
                ($haslink ? get_string('cfr_lotc2_library_18', 'local_subscriptions') : ($closed ? get_string('cfr_lotc2_library_19', 'local_subscriptions') : get_string('cfr_fix_coming_soon', 'local_campusreader'))) . '</span>' .
            '<span class="cfr-advent__pull-tab" aria-hidden="true">⌄</span></span>' .
            '<span class="cfr-advent__door-back" aria-hidden="true"><span>✧</span>' .
            '<small>' . s(get_string('cfr_lotc2_library_20', 'local_subscriptions')) . '</small></span>';
        if ($haslink) {
            // No-JS fallback: an ordinary link to a server-authorized book.
            echo '<a class="cfr-advent__door" data-cfr-door href="' . s($card['url']) . '"' .
                ' aria-controls="' . $revealid . '" aria-expanded="false"' .
                ' aria-label="' . s(get_string('cfr_lotc2_library_21', 'local_subscriptions')) . '' . $day . ' du ' . s($card['date']) . '">' . $doorbody . '</a>';
            echo '<div class="cfr-advent__reveal" id="' . $revealid . '" hidden>';
            echo '<span class="cfr-advent__reveal-rays" aria-hidden="true"></span>';
            echo '<span aria-hidden="true" class="cfr-advent__reveal-symbol">✦</span>';
            echo '<span class="cfr-advent__reveal-kicker">' . s(get_string('cfr_lotc2_door_opened', 'local_campusreader', $day)) . '</span>';
            echo '<strong>' . s($card['title']) . '</strong>';
            echo '<button type="button" class="cfr-advent__discover" data-cfr-discover' .
                ' aria-label="' . s(get_string('cfr_lotc2_library_22', 'local_subscriptions')) . '' . $day . '">' . s(get_string('cfr_lotc2_library_23', 'local_subscriptions')) . '</button>';
            echo '<a class="cfr-advent__read-link" href="' . s($card['url']) . '">Lire</a>';
            echo '<button type="button" class="cfr-advent__close" data-cfr-door-close' .
                ' aria-label="' . s(get_string('cfr_lotc2_library_24', 'local_subscriptions')) . '' . $day . '">×</button>';
            echo '</div>';
        } else {
            echo '<div class="cfr-advent__door cfr-advent__door--locked" aria-label="Case ' . $day .
                get_string('cfr_lotc2_library_25', 'local_subscriptions') . s($card['date']) . '">' . $doorbody . '</div>';
        }
        echo '</div>';
        echo '<span class="cfr-advent__status">' . s($card['state']) . '</span>';
        echo '</li>';
    }
    echo '</ol>';
    // Accessible native dialog. Contents are injected only from server-authorized revealed cards.
    // Locked titles / URLs never enter the HTML or the dialog payload.
    echo '<dialog class="cfr-advent-magic" data-cfr-magic-dialog aria-labelledby="cfr-advent-magic-title">';
    echo '<div class="cfr-advent-magic__inner">';
    echo '<button type="button" class="cfr-advent-magic__close" data-cfr-magic-close' .
        ' aria-label="' . s(get_string('cfr_lotc2_library_26', 'local_subscriptions')) . '">×</button>';
    echo '<div class="cfr-advent-magic__art" aria-hidden="true"><span class="cfr-advent-magic__moon">✧</span></div>';
    echo '<div class="cfr-advent-magic__content">';
    echo '<span class="cfr-advent-magic__eyebrow">' . s(get_string('cfr_lotc2_library_27', 'local_subscriptions')) . '</span>';
    echo '<span class="cfr-advent-magic__date" data-cfr-magic-date></span>';
    echo '<h3 id="cfr-advent-magic-title" data-cfr-magic-title></h3>';
    echo '<p>' . s(get_string('cfr_lotc2_library_28', 'local_subscriptions')) . '</p>';
    echo '<a class="cfr-advent-magic__read" data-cfr-magic-link href="#">' . s(get_string('cfr_lotc2_library_29', 'local_subscriptions')) . ' <span aria-hidden="true">↗</span></a>';
    echo '<button type="button" class="cfr-advent-magic__fold" data-cfr-magic-fold>' . s(get_string('cfr_lotc2_library_30', 'local_subscriptions')) . '</button>';
    echo '</div></div></dialog>';
    echo '<p class="cfr-advent__outro">✦ &nbsp; ' . s(get_string('cfr_lotc2_library_31', 'local_subscriptions')) . ' &nbsp; ✦</p></section>';
} else {
    echo '<section class="cfr-shelves" aria-label="' . s(get_string('cfr_lotc2_library_32', 'local_subscriptions')) . '">';
    foreach (array_chunk($cards, 6) as $rowindex => $rowcards) {
        echo '<div class="cfr-shelves__scroll" tabindex="0" aria-label="' . s(get_string('cfr_lotc2_library_33', 'local_subscriptions')) . '' . ($rowindex + 1) . '' . s(get_string('cfr_fix_scroll_hint', 'local_campusreader')) . '">';
        echo '<div class="cfr-shelves__row" role="list">';
        foreach ($rowcards as $card) {
            $day = $card['day'];
            $body = '<span class="cfr-library__number">' . $day . '</span>' .
                '<span class="cfr-library__ornament" aria-hidden="true">✦</span>' .
                '<span class="cfr-library__card-title">' . s($card['title']) . '</span>' .
                '<span class="cfr-library__card-date">' . s($card['date']) . '</span>';
            echo '<div class="cfr-library__item" role="listitem">';
            if ($card['url']) {
                echo '<a class="cfr-library__cover" href="' . s($card['url']) . '"' .
                    ' aria-label="' . s(get_string('cfr_lotc2_library_34', 'local_subscriptions')) . '' . s($card['date']) . ' : ' . s($card['title']) . '">' . $body . '</a>';
            } else {
                echo '<div class="cfr-library__cover cfr-library__cover--locked" aria-label="' . s(get_string('cfr_lotc2_library_35', 'local_subscriptions')) . '">' .
                    $body . '</div>';
            }
            echo '<span class="cfr-library__card-status">' . s($card['state']) . '</span>';
            echo '</div>';
        }
        echo '</div></div>';
    }
    echo '</section>';
}
echo '<footer class="cfr-library__footer">';
echo '<p>' . s(get_string('cfr_lotc2_library_36', 'local_subscriptions')) . '' . s($collection->timezone) . '. ' . s(get_string('cfr_lotc2_library_37', 'local_subscriptions')) . '</p>';
echo '<p>' . s(get_string('cfr_lotc2_library_38', 'local_subscriptions')) . '</p>';
echo '</footer></div>';
echo $OUTPUT->footer();
