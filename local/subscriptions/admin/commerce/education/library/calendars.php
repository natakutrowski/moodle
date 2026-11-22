<?php
// CampusFR D2.7 — calendar overview; no changes to scheduling, grants or the Reader.
declare(strict_types=1);
require_once(__DIR__ . '/_bootstrap.php');

use local_subscriptions\crm\library\ReaderEditorialService as Editorial;
use local_subscriptions\crm\library\ReaderEditorialCalendarService as Calendar;

$calendars = [];
foreach (Editorial::collections() as $collection) {
    $slots = Calendar::slots((int)$collection->id);
    if (!$slots) { continue; }
    $count = Calendar::count($collection);
    $assigned = 0;
    foreach ($slots as $position => $slot) {
        if ($position <= $count && !empty($slot->bookid)) { $assigned++; }
    }
    $calendars[] = [$collection, $count, $assigned, count($slots) - count(array_filter($slots,
        static fn($slot) => (int)$slot->position <= $count))];
}
cfr_d21_start(get_string('cfr_d27_calendars', 'local_subscriptions'),
    get_string('cfr_d27_calendars_help', 'local_subscriptions'), [
        ['label' => get_string('cfr_d27_calendars', 'local_subscriptions'), 'url' => null],
    ]);
echo '<div class="cfr-reader-editor__grid cfr-d27-calendar-list">';
foreach ($calendars as [$collection, $count, $assigned, $inactive]) {
    echo '<article class="cfr-reader-editor__card"><div class="cfr-reader-editor__top"><span class="cfr-reader-editor__pill">' .
        s(get_string((int)$collection->published ? 'cfr_d21_live' : 'cfr_d21_draft', 'local_subscriptions')) . '</span>';
    echo '<span class="cfr-reader-editor__dim">' . s((string)$collection->slug) . '</span></div>';
    echo '<h3>' . s($collection->title) . '</h3>';
    echo '<div class="cfr-d27-progress" role="progressbar" aria-valuenow="' . $assigned . '" aria-valuemin="0" aria-valuemax="' . max(1,$count) . '" aria-label="' . s(get_string('cfr_d27_assigned', 'local_subscriptions')) . '">';
    echo '<span style="width:' . ($count > 0 ? round(100 * $assigned / $count) : 0) . '%"></span></div>';
    echo '<p class="cfr-reader-editor__meta">' . $assigned . ' / ' . $count . ' ' .
        s(get_string('cfr_d27_assigned', 'local_subscriptions')) .
        ($inactive ? ' · ' . $inactive . ' ' . s(get_string('cfr_d27_inactive', 'local_subscriptions')) : '') . '</p>';
    echo '<div class="cfr-reader-editor__actions">' .
        html_writer::link(cfr_d21_url('production.php', ['id' => $collection->id]),
            get_string('cfr_d210_dashboard', 'local_subscriptions'), ['class' => 'btn btn-primary']) .
        html_writer::link(cfr_d21_url('calendar.php', ['id' => $collection->id]),
            get_string('cfr_d27_edit_calendar', 'local_subscriptions'), ['class' => 'btn btn-primary']) .
        html_writer::link(cfr_d21_url('collection.php', ['id' => $collection->id]),
            get_string('cfr_d27_view_collection', 'local_subscriptions'), ['class' => 'btn btn-outline-secondary']) .
        '</div></article>';
}
echo '</div>';
if (!$calendars) { echo '<p class="cfr-reader-editor__info">' . s(get_string('cfr_d27_no_calendars', 'local_subscriptions')) . '</p>'; }
echo '<p class="cfr-reader-editor__hint">' . s(get_string('cfr_d27_technical_note', 'local_subscriptions')) . '</p>';
cfr_d21_end();
