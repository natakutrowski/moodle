<?php
// CampusFR D2.3.1 — CRM configurable 1–31-case editorial calendar, DEV-only.
declare(strict_types=1);
require_once(__DIR__ . '/_bootstrap.php');

use local_subscriptions\crm\library\ReaderEditorialService as Editorial;
use local_subscriptions\crm\library\ReaderEditorialCalendarService as Calendar;

$id = required_param('id', PARAM_INT);
$collection = Editorial::collection($id);
$readonly = (int)$collection->published !== 0;
$slots = Calendar::slots($id);
$count = Calendar::count($collection);
if ($count < 1 || $count > 31) {
    throw new moodle_exception('invalidparameter', 'error', '',
        get_string('cfr_fix_calendar_01', 'local_subscriptions'));
}
$first = reset($slots);
$defaultyear = $first ? (int)(new DateTimeImmutable('@' . $first->unlockat))
    ->setTimezone(new DateTimeZone((string)$collection->timezone))->format('Y') : 2026;
$year = optional_param('year', $defaultyear, PARAM_INT);
$day = optional_param('day', 1, PARAM_INT);
if ($day < 1 || $day > 31 || $year < 2026 || $year > 2035) {
    throw new moodle_exception('invalidparameter');
}
if ($day > $count) { $day = 1; }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();
    if ($readonly) { throw new moodle_exception('invalidparameter'); }
    try {
        $action = required_param('action', PARAM_ALPHA);
        if ($action === 'count') {
            if (optional_param('confirm', 0, PARAM_INT) !== 1) {
                throw new moodle_exception('invalidparameter', 'error', '', get_string('cfr_fix_calendar_02', 'local_subscriptions'));
            }
            Calendar::configure_count($id, required_param('slotcount', PARAM_INT),
                required_param('expectedcount', PARAM_INT));
            $day = 1;
        } else if ($action === 'complete') {
            if (optional_param('confirm', 0, PARAM_INT) !== 1) {
                throw new moodle_exception('invalidparameter', 'error', '', get_string('cfr_fix_calendar_02', 'local_subscriptions'));
            }
            Calendar::complete($id, $year);
        } else if ($action === 'switch') {
            Calendar::configure_switch($id, required_param('switchlocal', PARAM_RAW_TRIMMED));
        } else if ($action === 'save') {
            Calendar::save_slot($id, $day, required_param('teaser', PARAM_RAW_TRIMMED),
                required_param('bookid', PARAM_INT), required_param('layoutsize', PARAM_ALPHA),
                required_param('unlocklocal', PARAM_RAW_TRIMMED), required_param('expectedunlock', PARAM_INT));
        } else {
            throw new moodle_exception('invalidparameter');
        }
        redirect(cfr_d21_url('calendar.php', ['id' => $id, 'day' => $day, 'year' => $year]),
            get_string('changessaved'));
    } catch (\moodle_exception $exception) {
        $error = $exception->getMessage();
    }
    $collection = Editorial::collection($id);
    $slots = Calendar::slots($id);
    $count = Calendar::count($collection);
}
$books = Editorial::books($id);
$bookmap = [];
foreach ($books as $book) { $bookmap[(int)$book->id] = $book; }
$active = array_filter($slots, static fn($slot) => (int)$slot->position <= $count);
$archived = count($slots) - count($active);
$used = [];
foreach ($slots as $slot) {
    if (!empty($slot->bookid)) { $used[(int)$slot->bookid] = (int)$slot->position; }
}
$filled = count($active);
$assigned = count(array_filter($active, static fn($slot) => !empty($slot->bookid)));
$selected = $slots[$day] ?? null;
$trail = [
    ['label' => $collection->title, 'url' => cfr_d21_url('collection.php', ['id' => $id])],
    ['label' => get_string('cfr_d23_calendar', 'local_subscriptions'), 'url' => null],
];
$actions = html_writer::link(cfr_d21_url('collection.php', ['id' => $id]),
    get_string('cfr_d23_back', 'local_subscriptions'), ['class' => 'btn btn-outline-secondary']);
if (has_capability('moodle/site:config', context_system::instance())) {
    $preview = new moodle_url('/local/campusreader/library.php', [
        'collection' => $collection->slug, 'view' => 'advent',
    ]);
    $actions .= ' ' . html_writer::link($preview, get_string('cfr_d242b_calendarpreview', 'local_subscriptions'),
        ['class' => 'btn btn-outline-secondary', 'target' => '_blank', 'rel' => 'noopener noreferrer']);
    $librarypreview = new moodle_url('/local/campusreader/library.php', [
        'collection' => $collection->slug, 'view' => 'library',
    ]);
    $actions .= ' ' . html_writer::link($librarypreview,
        get_string('cfr_d242b_librarypreview', 'local_subscriptions'),
        ['class' => 'btn btn-outline-secondary', 'target' => '_blank', 'rel' => 'noopener noreferrer']);
}
cfr_d21_start(get_string('cfr_d23_calendar', 'local_subscriptions') . ' · ' . $collection->title,
    get_string('cfr_d231_intro', 'local_subscriptions'), $trail, $actions);
if ($error !== '') { echo '<div role="alert" class="alert alert-danger">' . s($error) . '</div>'; }
if ($readonly) { echo '<div class="cfr-reader-editor__info">' .
    s(get_string('cfr_d21_readonly', 'local_subscriptions')) . '</div>'; }
echo '<div class="cfr-d23-summary" aria-label="' . s(get_string('cfr_d23_summary', 'local_subscriptions')) . '">';
foreach ([
    [$count, get_string('cfr_d23_total', 'local_subscriptions')],
    [$filled, get_string('cfr_d23_created', 'local_subscriptions')],
    [$assigned, get_string('cfr_d23_assigned', 'local_subscriptions')],
    [max(0, $count - $assigned), get_string('cfr_d23_unassigned', 'local_subscriptions')],
] as [$value, $label]) {
    echo '<div class="cfr-d23-stat"><strong>' . (int)$value . '</strong><span>' . s($label) . '</span></div>';
}
echo '</div>';
echo '<p class="cfr-reader-editor__hint">' . s(get_string('cfr_d231_datepolicy', 'local_subscriptions')) .
    ' · <strong>' . s((string)$collection->timezone) . '</strong></p>';

// Explicit count selection; no row deletion, no SQL migration, not available on published collection.
echo '<section class="cfr-reader-editor__panel cfr-d231-settings">';
echo '<h3>' . s(get_string('cfr_d231_settings', 'local_subscriptions')) . '</h3>';
echo '<p class="cfr-reader-editor__hint">' . s(get_string('cfr_d231_count_hint', 'local_subscriptions')) . '</p>';
if (!$readonly) {
    echo '<form method="post" action="' . s(cfr_d21_url('calendar.php', ['id' => $id, 'year' => $year])->out(false)) . '">';
    echo '<input type="hidden" name="sesskey" value="' . s(sesskey()) . '">';
    echo '<input type="hidden" name="action" value="count">';
    echo '<input type="hidden" name="expectedcount" value="' . $count . '">';
    echo '<div class="cfr-reader-editor__field"><label for="cfr-d231-count">' .
        s(get_string('cfr_d231_count', 'local_subscriptions')) . '</label>';
    echo '<select class="form-control" name="slotcount" id="cfr-d231-count">';
    for ($candidate = 1; $candidate <= 31; $candidate++) {
        echo '<option value="' . $candidate . '"' . ($candidate === $count ? ' selected' : '') . '>' . $candidate . '</option>';
    }
    echo '</select></div>';
    echo '<label class="cfr-d23-confirm"><input type="checkbox" name="confirm" value="1" required> ' .
        s(get_string('cfr_d231_count_confirm', 'local_subscriptions')) . '</label>';
    echo '<button type="submit" class="btn btn-outline-secondary">' .
        s(get_string('cfr_d231_count_apply', 'local_subscriptions')) . '</button></form>';
} else {
    echo '<p><strong>' . $count . '</strong> ' . s(get_string('cfr_d231_count', 'local_subscriptions')) . '</p>';
}
if ($archived > 0) {
    echo '<p class="cfr-d231-archived" role="status">' . $archived . ' ' .
        s(get_string('cfr_d231_archived', 'local_subscriptions')) . '</p>';
}
echo '</section>';

echo '<div class="cfr-d23-board">';
echo '<section class="cfr-reader-editor__panel cfr-d23-board__calendar">';
echo '<h3>' . s(get_string('cfr_d23_december', 'local_subscriptions')) . ' ' . (int)$year . '</h3>';
echo '<div class="cfr-d23-grid">';
for ($n = 1; $n <= $count; $n++) {
    $slot = $slots[$n] ?? null;
    $book = $slot && !empty($slot->bookid) ? ($bookmap[(int)$slot->bookid] ?? null) : null;
    $label = $book ? $book->title : ($slot ? get_string('cfr_d23_no_book', 'local_subscriptions') :
        get_string('cfr_d23_missing', 'local_subscriptions'));
    $status = $book ? 'assigned' : ($slot ? 'unassigned' : 'missing');
    $url = cfr_d21_url('calendar.php', ['id' => $id, 'day' => $n, 'year' => $year]);
    echo '<a href="' . s($url->out(false)) . '#cfr-d23-editor" class="cfr-d23-day cfr-d23-day--' . $status .
        ($day === $n ? ' is-active' : '') . '"' . ($day === $n ? ' aria-current="true"' : '') . '>';
    echo '<span class="cfr-d23-day__number">' . $n . '</span>';
    echo '<span class="cfr-d23-day__label">' . s((string)$label) . '</span>';
    if ($slot) {
        $dt = (new DateTimeImmutable('@' . $slot->unlockat))->setTimezone(new DateTimeZone((string)$collection->timezone));
        echo '<span class="cfr-d231-day-date">' . s($dt->format('d/m H:i')) . '</span>';
    }
    echo '</a>';
}
echo '</div><p class="cfr-reader-editor__hint">' . s(get_string('cfr_d23_gridnote', 'local_subscriptions')) . '</p>';
echo '</section>';
echo '<section id="cfr-d23-editor" class="cfr-reader-editor__panel cfr-d23-board__editor">';
echo '<h3>' . s(get_string('cfr_d23_day', 'local_subscriptions')) . ' ' . $day . '</h3>';
if ($selected) {
    $date = (new DateTimeImmutable('@' . $selected->unlockat))
        ->setTimezone(new DateTimeZone((string)$collection->timezone));
    $bookid = (int)($selected->bookid ?? 0);
    if (!$readonly) {
        echo '<form method="post" action="' . s(cfr_d21_url('calendar.php',
            ['id' => $id, 'year' => $year, 'day' => $day])->out(false)) . '">';
        echo '<input type="hidden" name="sesskey" value="' . s(sesskey()) . '">';
        echo '<input type="hidden" name="action" value="save">';
        echo '<input type="hidden" name="expectedunlock" value="' . (int)$selected->unlockat . '">';
        echo '<div class="cfr-reader-editor__field"><label for="cfr-d231-unlock">' .
            s(get_string('cfr_d231_unlock_edit', 'local_subscriptions')) . '</label>';
        echo '<input class="form-control" id="cfr-d231-unlock" name="unlocklocal" type="datetime-local" required step="60" value="' .
            s($date->format('Y-m-d\TH:i')) . '">';
        echo '<small>' . s(get_string('cfr_d231_unlock_hint', 'local_subscriptions')) . '</small></div>';
        echo '<div class="cfr-reader-editor__field"><label for="cfr-d23-book">' .
            s(get_string('cfr_d23_book', 'local_subscriptions')) . '</label>';
        echo '<select id="cfr-d23-book" name="bookid" class="form-control">';
        echo '<option value="0">' . s(get_string('cfr_d23_no_book', 'local_subscriptions')) . '</option>';
        foreach ($books as $book) {
            $bid = (int)$book->id;
            if (isset($used[$bid]) && $used[$bid] !== $day) { continue; }
            echo '<option value="' . $bid . '"' . ($bid === $bookid ? ' selected' : '') . '>' .
                s($book->title) . ' (' . (int)Editorial::page_count($bid) . ' ' .
                s(get_string('cfr_d21_pages', 'local_subscriptions')) . ')</option>';
        }
        echo '</select></div><div class="cfr-reader-editor__field">';
        echo '<label for="cfr-d23-teaser">' . s(get_string('cfr_d23_teaser', 'local_subscriptions')) . '</label>';
        echo '<input class="form-control" id="cfr-d23-teaser" name="teaser" type="text" maxlength="191" required value="' .
            s((string)$selected->teaser) . '">';
        echo '<small>' . s(get_string('cfr_d23_teaserhint', 'local_subscriptions')) . '</small></div>';
        echo '<div class="cfr-reader-editor__field"><label for="cfr-d23-size">' .
            s(get_string('cfr_d23_size', 'local_subscriptions')) . '</label>';
        echo '<select id="cfr-d23-size" name="layoutsize" class="form-control">';
        foreach (['normal', 'wide', 'tall', 'feature'] as $size) {
            echo '<option value="' . $size . '"' . ($selected->layoutsize === $size ? ' selected' : '') . '>' .
                s(get_string('cfr_d23_size_' . $size, 'local_subscriptions')) . '</option>';
        }
        echo '</select></div>';
        echo '<button type="submit" class="btn btn-primary">' . s(get_string('savechanges')) . '</button></form>';
    } else {
        echo '<p class="cfr-d23-release">' . s(get_string('cfr_d23_unlock', 'local_subscriptions')) .
            ' : <strong>' . s($date->format('d/m/Y H:i')) . '</strong></p>';
        echo '<p>' . s($selected->teaser) . '</p>';
    }
    if ($bookid > 0 && isset($bookmap[$bookid])) {
        echo '<p class="cfr-d23-booklink">' . html_writer::link(cfr_d21_url('book.php', ['id' => $bookid]),
            get_string('cfr_d23_editbook', 'local_subscriptions'), ['class' => 'btn btn-outline-secondary']) . '</p>';
    }
} else {
    echo '<div class="cfr-reader-editor__info">' . s(get_string('cfr_d23_missinghint', 'local_subscriptions')) . '</div>';
}
echo '</section></div>';

if (!$readonly && $filled < $count) {
    echo '<section class="cfr-reader-editor__panel cfr-d23-setup">';
    echo '<h3>' . s(get_string('cfr_d23_setup', 'local_subscriptions')) . '</h3>';
    echo '<p class="cfr-reader-editor__hint">' . s(get_string('cfr_d231_setuphint', 'local_subscriptions')) . '</p>';
    echo '<form method="post" action="' . s(cfr_d21_url('calendar.php', ['id' => $id, 'year' => $year])->out(false)) . '">';
    echo '<input type="hidden" name="sesskey" value="' . s(sesskey()) . '">';
    echo '<input type="hidden" name="action" value="complete">';
    echo '<div class="cfr-reader-editor__field"><label for="cfr-d23-year">' .
        s(get_string('cfr_d23_year', 'local_subscriptions')) . '</label>';
    if ($filled) {
        echo '<input id="cfr-d23-year" type="number" class="form-control" value="' . (int)$year . '" readonly>';
    } else {
        echo '<select id="cfr-d23-year" class="form-control" name="year">';
        for ($y = 2026; $y <= 2035; $y++) {
            echo '<option value="' . $y . '"' . ($y === $year ? ' selected' : '') . '>' . $y . '</option>';
        }
        echo '</select>';
    }
    echo '</div><label class="cfr-d23-confirm"><input type="checkbox" name="confirm" value="1" required> ' .
        s(get_string('cfr_d23_confirm', 'local_subscriptions')) . '</label>';
    echo '<button type="submit" class="btn btn-outline-secondary">' .
        s(get_string('cfr_d23_complete', 'local_subscriptions')) . '</button></form></section>';
}
if ($filled === $count) {
    $current = (int)$collection->libraryafter ?: Calendar::switch_at($year);
    $switchlocal = (new DateTimeImmutable('@' . $current))
        ->setTimezone(new DateTimeZone((string)$collection->timezone))->format('Y-m-d\TH:i');
    echo '<section class="cfr-reader-editor__panel cfr-d23-switch">';
    echo '<h3>' . s(get_string('cfr_d23_switch', 'local_subscriptions')) . '</h3>';
    echo '<p class="cfr-reader-editor__hint">' . s(get_string('cfr_d231_switchhint', 'local_subscriptions')) . '</p>';
    if (!$readonly) {
        echo '<form method="post" action="' . s(cfr_d21_url('calendar.php', ['id' => $id, 'year' => $year])->out(false)) . '">';
        echo '<input type="hidden" name="sesskey" value="' . s(sesskey()) . '">';
        echo '<input type="hidden" name="action" value="switch">';
        echo '<div class="cfr-reader-editor__field"><label for="cfr-d231-switchlocal">' .
            s(get_string('cfr_d231_switchdate', 'local_subscriptions')) . '</label>';
        echo '<input class="form-control" type="datetime-local" step="60" required id="cfr-d231-switchlocal" ' .
            'name="switchlocal" value="' . s($switchlocal) . '"></div>';
        echo '<button class="btn btn-outline-secondary" type="submit">' .
            s(get_string('cfr_d231_switchsave', 'local_subscriptions')) . '</button></form>';
    } else {
        echo '<p><strong>' . s(str_replace('T', ' ', $switchlocal)) . '</strong> Europe/Paris</p>';
    }
    echo '</section>';
}
echo '<p class="cfr-d243-link">' . html_writer::link(cfr_d21_url('publications.php'), get_string('cfr_fix_anthologies_commerce', 'local_subscriptions'), ['class' => 'btn btn-outline-secondary']) . '</p>';
cfr_d21_end();
