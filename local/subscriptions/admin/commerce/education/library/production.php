<?php
// CampusFR D2.10 — DEV editorial production cockpit for active calendar slots only.
// All operations are GET and read-only; publication, purchases, rights and slot dates unchanged.
declare(strict_types=1);
require_once(__DIR__ . '/_bootstrap.php');
// D2.10.1: fresh classes may not be in Moodle's autoload map until the next purge.
// Local, fixed-path include; independent of request parameters or dynamic class discovery.
require_once($CFG->dirroot . '/local/subscriptions/classes/crm/library/ReaderProductionReadiness.php');
// D2.13.1: fixed-path helper (new classes may not yet be registered by Moodle autoload).
require_once($CFG->dirroot . '/local/subscriptions/classes/crm/library/ReaderLaunchReadiness.php');

use local_subscriptions\crm\library\ReaderEditorialService as Editorial;
use local_subscriptions\crm\library\ReaderEditorialCalendarService as Calendar;
use local_subscriptions\crm\library\ReaderEditorialPageService as Pages;
use local_subscriptions\crm\library\ReaderEditorialMediaService as Media;
use local_subscriptions\crm\library\ReaderProductionReadiness as Readiness;
use local_subscriptions\crm\library\ReaderLaunchReadiness as Launch;
use local_subscriptions\crm\library\ReaderEditorialAnthologyService as Anthologies;
use local_subscriptions\crm\library\ReaderAnthologyRevisionService as Revisions;
use local_subscriptions\crm\library\ReaderCommercePublicationService as Publications;
use local_subscriptions\crm\library\ReaderNarrationAlignmentService as Alignment;

$id = required_param('id', PARAM_INT);
$collection = Editorial::collection($id);
$count = Calendar::count($collection);
if ($count < 1 || $count > 31) { throw new moodle_exception('invalidparameter'); }
$filter = optional_param('filter', 'all', PARAM_ALPHA);
if (!in_array($filter, ['all', 'unassigned', 'todo', 'textready', 'needsnarration', 'needsalignment', 'needsfrozen'], true)) {
    throw new moodle_exception('invalidparameter');
}
$slots = Calendar::slots($id);
$books = Editorial::books($id);
$bookmap = [];
foreach ($books as $book) { $bookmap[(int)$book->id] = $book; }
$zone = new DateTimeZone((string)$collection->timezone);
$stats = ['assigned' => 0, 'text' => 0, 'visuals' => 0, 'audio' => 0, 'pdf' => 0];
$rows = [];
for ($day = 1; $day <= $count; $day++) {
    $slot = $slots[$day] ?? null;
    $bid = $slot ? (int)($slot->bookid ?? 0) : 0;
    // A foreign book ID or missing record is not silently treated as a valid assigned story.
    $book = $bid > 0 ? ($bookmap[$bid] ?? null) : null;
    $status = null;
    if ($book) {
        $stats['assigned']++;
        $status = Readiness::summarize(Pages::pages($bid), Media::inventory($bid));
        foreach (['text', 'visuals', 'audio', 'pdf'] as $k) {
            $ok = $k === 'audio' ? $status['audio_unique'] : ($k === 'pdf' ? $status['pdf_unique'] : $status[$k]);
            if ($ok) { $stats[$k]++; }
        }
    }
    $rows[] = ['day'=>$day, 'slot'=>$slot, 'book'=>$book, 'status'=>$status, 'bookid'=>$bid];
}
// D2.13.1: observational indicators only. This endpoint never changes publications or access.
$collectionduplicates = array_values(array_filter(Editorial::collections(),
    static fn($item) => (string)$item->title === (string)$collection->title));
$anthologyready = Anthologies::ready();
$publicationready = Publications::ready() && Revisions::ready();
$alignmentpath = $CFG->dirroot . '/local/subscriptions/classes/crm/library/ReaderNarrationAlignmentService.php';
if (is_file($alignmentpath)) {
    require_once($alignmentpath);
}
$alignmentavailable = class_exists(Alignment::class) && method_exists(Alignment::class, 'approved_snapshot');
$publicationcontexts = [];
if ($publicationready) {
    foreach (Publications::list_all() as $publication) {
        if ((string)$publication->kind !== 'calendar' || (int)$publication->collectionid !== $id) {
            continue;
        }
        $chapters = [];
        $frozen = json_decode((string)($publication->calendarjson ?? ''), true);
        try {
            $revision = Revisions::get((int)$publication->anthologyid, (int)$publication->revisionid);
            if ((string)$revision->state === 'approved') {
                $snapshot = Revisions::snapshot((int)$publication->anthologyid, (int)$publication->revisionid);
                $chapters = is_array($snapshot['chapters'] ?? null) ? $snapshot['chapters'] : [];
            }
        } catch (\Throwable $ignored) {
            // An unreadable/missing snapshot is never labelled published-ready.
        }
        $publicationcontexts[] = ['record' => $publication, 'chapters' => $chapters,
            'calendar' => is_array($frozen) ? $frozen : []];
    }
}
$launchstats = ['alignment' => 0, 'anthology' => 0, 'frozen' => 0, 'ready' => 0];
foreach ($rows as &$row) {
    $row['aligned'] = false;
    $row['alignmentknown'] = $alignmentavailable;
    $row['anthologies'] = [];
    $row['frozen'] = false;
    $row['publication'] = null;
    $row['ready'] = false;
    if (!$row['book']) { continue; }
    $bookid = (int)$row['book']->id;
    if ($anthologyready) {
        $row['anthologies'] = Anthologies::for_story($bookid);
    }
    if ($alignmentavailable && !empty($row['status']['audio_unique'])) {
        try {
            $approved = Alignment::approved_snapshot($bookid);
            $row['aligned'] = is_array($approved) && !empty($approved);
        } catch (\Throwable $ignored) {
            $row['alignmentknown'] = false; // No false "approved" on lookup errors.
        }
    }
    if ($row['slot']) {
        foreach ($publicationcontexts as $entry) {
            $pub = $entry['record'];
            if (!Launch::frozen_matches($entry['chapters'], $entry['calendar'],
                    (int)$row['day'], $bookid, (int)$row['slot']->unlockat)) {
                continue;
            }
            if ($row['publication'] === null || (string)$pub->status === 'published') {
                $row['publication'] = $pub;
            }
            if ((string)$pub->status === 'published') {
                $row['frozen'] = true;
                break;
            }
        }
    }
    $row['ready'] = Launch::launch_ready($row['status'], $row['aligned'],
        count($row['anthologies']) > 0, $row['frozen']);
    if ($row['aligned']) { $launchstats['alignment']++; }
    if ($row['anthologies']) { $launchstats['anthology']++; }
    if ($row['frozen']) { $launchstats['frozen']++; }
    if ($row['ready']) { $launchstats['ready']++; }
}
unset($row); // Do not retain a reference into the result array.
$trail = [
    ['label' => get_string('cfr_d27_calendars', 'local_subscriptions'), 'url' => cfr_d21_url('calendars.php')],
    ['label' => $collection->title, 'url' => cfr_d21_url('collection.php', ['id'=>$id])],
    ['label' => get_string('cfr_d210_dashboard', 'local_subscriptions'), 'url' => null],
];
$actions = html_writer::link(cfr_d21_url('calendar.php', ['id'=>$id]),
    get_string('cfr_d27_edit_calendar', 'local_subscriptions'), ['class'=>'btn btn-outline-secondary']);
cfr_d21_start(get_string('cfr_d210_dashboard', 'local_subscriptions') . ' · ' . $collection->title,
    get_string('cfr_d210_intro', 'local_subscriptions'), $trail, $actions);
echo '<section class="cfr-d210-intro"><h2>' . s(get_string('cfr_d210_headline', 'local_subscriptions')) . '</h2>';
echo '<p>' . s(get_string('cfr_d210_explain', 'local_subscriptions')) . '</p></section>';
if ((int)$collection->published) {
    echo '<div class="cfr-d210-warning">' . s(get_string('cfr_d210_published', 'local_subscriptions')) . '</div>';
}
if (str_contains(strtolower((string)$collection->slug), 'qa') || str_starts_with(strtolower((string)$collection->slug), 'test')) {
    echo '<div class="cfr-d210-warning">' . s(get_string('cfr_d210_testcollection', 'local_subscriptions')) . '</div>';
}
// D2.13.1: selection is always scoped to the explicit collection ID, never its title.
echo '<div class="cfr-d2131-scope"><strong>' . s(get_string('cfr_d2131_scoped', 'local_subscriptions')) .
    ' #' . $id . '</strong><span>' . s((string)$collection->slug) . '</span><span>' .
    s((string)$collection->timezone) . '</span></div>';
if (count($collectionduplicates) > 1) {
    echo '<div class="cfr-d210-warning" role="status">' .
        s(get_string('cfr_d2131_duplicate', 'local_subscriptions')) . ' ';
    foreach ($collectionduplicates as $duplicate) {
        echo html_writer::link(cfr_d21_url('production.php', ['id' => (int)$duplicate->id]),
            '#' . (int)$duplicate->id . ' · ' . (string)$duplicate->slug,
            ['class' => 'cfr-d2131-duplicate']);
    }
    echo '</div>';
}
if ($count !== 24) {
    echo '<p class="cfr-reader-editor__hint">' .
        s(get_string('cfr_d2131_not24', 'local_subscriptions')) . ' (' . $count . ').</p>';
}
if (!$alignmentavailable || !$anthologyready || !$publicationready) {
    echo '<div class="cfr-d210-warning" role="status">' .
        s(get_string('cfr_d2131_unknown', 'local_subscriptions')) . '</div>';
}
echo '<div class="cfr-d210-stats" aria-label="' . s(get_string('cfr_d210_summary','local_subscriptions')) . '">';
foreach ([
  [$stats['assigned'], $count, 'cfr_d210_assigned'],
  [$stats['text'], $count, 'cfr_d210_text'],
  [$stats['visuals'], $count, 'cfr_d210_visual'],
  [$stats['audio'], $count, 'cfr_d210_audio'],
  [$stats['pdf'], $count, 'cfr_d210_pdf'],
] as [$value, $denominator, $key]) {
  echo '<div class="cfr-d210-stat"><strong>' . (int)$value . ' / ' . (int)$denominator . '</strong><span>' .
      s(get_string($key, 'local_subscriptions')) . '</span></div>';
}
echo '</div>';
echo '<div class="cfr-d2131-stats" aria-label="' . s(get_string('cfr_d2131_launchsummary', 'local_subscriptions')) . '">';
foreach ([
    [$launchstats['alignment'], 'cfr_d2131_alignment'],
    [$launchstats['anthology'], 'cfr_d2131_anthology'],
    [$launchstats['frozen'], 'cfr_d2131_frozen'],
    [$launchstats['ready'], 'cfr_d2131_ready'],
] as [$value, $key]) {
    echo '<div class="cfr-d210-stat"><strong>' . $value . ' / ' . $count . '</strong><span>' .
        s(get_string($key, 'local_subscriptions')) . '</span></div>';
}
echo '</div>';
echo '<p class="cfr-reader-editor__hint">' . s(get_string('cfr_d2131_disclaimer', 'local_subscriptions')) . '</p>';
echo '<nav class="cfr-d210-filters" aria-label="' . s(get_string('cfr_d210_filterlabel', 'local_subscriptions')) . '">';
foreach (['all'=>'cfr_d210_all','unassigned'=>'cfr_d210_unassigned',
    'todo'=>'cfr_d210_todo','textready'=>'cfr_d210_textready',
    'needsnarration'=>'cfr_d2131_neednarration','needsalignment'=>'cfr_d2131_needalignment',
    'needsfrozen'=>'cfr_d2131_needfrozen'] as $value=>$key) {
    $attrs = ['class' => $value === $filter ? 'is-active' : ''];
    if ($value === $filter) { $attrs['aria-current'] = 'page'; }
    echo html_writer::link(cfr_d21_url('production.php', ['id'=>$id, 'filter'=>$value]),
        get_string($key, 'local_subscriptions'), $attrs);
}
echo '</nav>';
echo '<div class="cfr-d210-list">';
$shown = 0;
foreach ($rows as $row) {
    $day = $row['day']; $slot = $row['slot']; $book = $row['book']; $status = $row['status'];
    if ($filter === 'unassigned' && $book) { continue; }
    if ($filter === 'textready' && (!$book || !$status['text'] || !$status['visuals'])) { continue; }
    if ($filter === 'todo' && $book && $row['ready']) { continue; }
    if ($filter === 'needsnarration' && (!$book || $status['audio_unique'])) { continue; }
    if ($filter === 'needsalignment' && (!$book || !$status['audio_unique'] || $row['aligned'])) { continue; }
    if ($filter === 'needsfrozen' && (!$book || $row['frozen'])) { continue; }
    $shown++;
    echo '<article class="cfr-d210-row"><div class="cfr-d210-row__day"><small>' .
        s(get_string('cfr_d210_slot', 'local_subscriptions')) . '</small><strong>' . $day . '</strong></div>';
    echo '<div class="cfr-d210-main"><strong>' . s($book ? $book->title :
        get_string('cfr_d210_notassigned', 'local_subscriptions')) . '</strong>';
    if ($slot) {
        $date = (new DateTimeImmutable('@' . (int)$slot->unlockat))->setTimezone($zone)->format('d/m/Y H:i');
        echo '<small>' . s(get_string('cfr_d210_unlock', 'local_subscriptions')) . ': ' . s($date) .
            ' · ' . s((string)$collection->timezone) . '</small>';
        if ($book) { echo '<small>' . s((string)$book->slug) . '</small>'; }
    } else {
        echo '<small>' . s(get_string('cfr_d210_slotmissing', 'local_subscriptions')) . '</small>';
    }
    echo '</div><div class="cfr-d210-row__status">';
    if ($book) {
        $chips = [
            [$status['text'], (int)$status['pages'] . ' ' . get_string('cfr_d210_pages','local_subscriptions')],
            [$status['visuals'], $status['illustrated'] . '/' . $status['visualpages'] . ' ' . get_string('cfr_d210_illustrations','local_subscriptions')],
            [$status['audio_unique'], get_string('cfr_d210_audio','local_subscriptions') . ' (' . (int)$status['audio'] . ')'],
            [$row['aligned'], get_string('cfr_d2131_alignment', 'local_subscriptions') .
                (!$row['alignmentknown'] ? ' · ' . get_string('cfr_d2131_unknownshort', 'local_subscriptions') : '')],
            [count($row['anthologies']) > 0, get_string('cfr_d2131_anthology', 'local_subscriptions') .
                ' (' . count($row['anthologies']) . ')'],
            [$row['frozen'], get_string('cfr_d2131_frozen', 'local_subscriptions')],
        ];
        foreach ($chips as [$ok,$text]) {
            echo '<span class="cfr-d210-chip ' . ($ok ? 'cfr-d210-chip--yes' : 'cfr-d210-chip--pending') . '">' .
                s((string)$text) . '</span>';
        }
    } else {
        echo '<span class="cfr-d210-chip cfr-d210-chip--pending">' .
            s(get_string('cfr_d210_needstory','local_subscriptions')) . '</span>';
    }
    echo '</div><div class="cfr-d210-row__actions">';
    if ($book) {
        echo html_writer::link(cfr_d21_url('book.php',['id'=>$book->id]),
            get_string('cfr_d210_openstory','local_subscriptions'),['class'=>'btn btn-sm btn-outline-secondary']);
        echo html_writer::link(cfr_d21_url('pages.php',['id'=>$book->id]),
            get_string('cfr_d210_editpages','local_subscriptions'),['class'=>'btn btn-sm btn-outline-secondary']);
        echo html_writer::link(cfr_d21_url('media.php',['id'=>$book->id]),
            get_string('cfr_d210_media','local_subscriptions'),['class'=>'btn btn-sm btn-outline-secondary']);
        if ($status['pages'] > 0) {
            echo html_writer::link(cfr_d21_url('story_flipbook.php',['id'=>$book->id]),
                get_string('cfr_d210_preview','local_subscriptions'),['class'=>'btn btn-sm btn-outline-secondary',
                'target'=>'_blank','rel'=>'noopener noreferrer']);
        }
        echo html_writer::link(cfr_d21_url('audio_studio.php', ['id' => $book->id]),
            get_string('cfr_d2131_studio', 'local_subscriptions'), ['class' => 'btn btn-sm btn-outline-secondary']);
        if ($row['anthologies']) {
            $anthology = reset($row['anthologies']);
            echo html_writer::link(cfr_d21_url('anthology.php', ['id' => (int)$anthology->id]),
                get_string('cfr_d2131_openanthology', 'local_subscriptions'),
                ['class' => 'btn btn-sm btn-outline-secondary']);
        } else {
            echo html_writer::link(cfr_d21_url('anthologies.php'),
                get_string('cfr_d2131_openanthology', 'local_subscriptions'),
                ['class' => 'btn btn-sm btn-outline-secondary']);
        }
        $publicationurl = $row['publication'] ? cfr_d21_url('publication.php',
            ['id' => (int)$row['publication']->id]) : cfr_d21_url('publications.php');
        echo html_writer::link($publicationurl, get_string('cfr_d2131_openpublication', 'local_subscriptions'),
            ['class' => 'btn btn-sm btn-outline-secondary']);
    } else {
        echo html_writer::link(cfr_d21_url('calendar.php',['id'=>$id,'day'=>$day]),
            get_string('cfr_d210_assign','local_subscriptions'),['class'=>'btn btn-sm btn-primary']);
    }
    echo '</div></article>';
}
echo '</div>';
if (!$shown) {echo '<p class="cfr-d210-empty">' . s(get_string('cfr_d210_nofilter','local_subscriptions')) . '</p>';}
echo '<p class="cfr-reader-editor__hint">' . s(get_string('cfr_d210_disclaimer','local_subscriptions')) . '</p>';
cfr_d21_end();
