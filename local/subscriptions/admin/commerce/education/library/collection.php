<?php
// CampusFR D2.1 — collection metadata editing, draft-only; immutable course and slug.
declare(strict_types=1);
require_once(__DIR__ . '/_bootstrap.php');

use local_subscriptions\crm\library\ReaderEditorialService as Editorial;

$id = optional_param('id', 0, PARAM_INT);
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();
    try {
        $saved = Editorial::save_collection(
            $id,
            required_param('slug', PARAM_RAW_TRIMMED),
            required_param('title', PARAM_RAW_TRIMMED),
            required_param('courseid', PARAM_INT)
        );
        redirect(cfr_d21_url('collection.php', ['id' => $saved]), get_string('changessaved'));
    } catch (\moodle_exception $exception) {
        $error = $exception->getMessage();
    }
}
$collection = $id ? Editorial::collection($id) : null;
$books = $collection ? Editorial::books($id) : [];
$locked = $collection && (int)$collection->published !== 0;
$title = $collection ? $collection->title : get_string('cfr_d21_newcollection', 'local_subscriptions');
$trail = [['label' => $title, 'url' => null]];
$action = $collection && !$locked ? html_writer::link(cfr_d21_url('book.php', ['collectionid' => $id]),
    get_string('cfr_d21_newbook', 'local_subscriptions'), ['class' => 'btn btn-primary']) : null;
cfr_d21_start($title, get_string('cfr_d21_collection_help', 'local_subscriptions'), $trail, $action);
if ($error !== '') { echo '<div role="alert" class="alert alert-danger">' . s($error) . '</div>'; }
if ($locked) { echo '<div class="cfr-reader-editor__info">' . s(get_string('cfr_d21_readonly', 'local_subscriptions')) . '</div>'; }
echo '<section class="cfr-reader-editor__panel"><h3>' . s(get_string('cfr_d21_collection_details', 'local_subscriptions')) . '</h3>';
if (!$locked) {
    echo '<form method="post" action="' . s(cfr_d21_url('collection.php', $id ? ['id' => $id] : [])->out(false)) . '">';
    echo '<input type="hidden" name="sesskey" value="' . s(sesskey()) . '">';
    echo '<div class="cfr-reader-editor__fields">';
    cfr_d21_field('title', get_string('name'), (string)($collection->title ?? ''), false);
    cfr_d21_field('slug', 'Slug', (string)($collection->slug ?? ''), (bool)$collection, 'text',
        get_string('cfr_d21_slug_help', 'local_subscriptions'));
    cfr_d21_field('courseid', get_string('course'), (string)($collection->courseid ?? ''),
        (bool)$collection, 'number', get_string('cfr_d21_course_help', 'local_subscriptions'));
    echo '</div><button class="btn btn-primary" type="submit">' . s(get_string('savechanges')) . '</button></form>';
} else {
    echo '<p><strong>Slug :</strong> ' . s($collection->slug) . ' · <strong>' . s(get_string('course'))
        . ' :</strong> #' . (int)$collection->courseid . '</p>';
}
echo '</section>';
if ($collection) {
    echo '<div class="cfr-reader-editor__actions cfr-d23-collection-actions">';
    echo html_writer::link(cfr_d21_url('transfers.php'),
        get_string('cfr_d25_transfer', 'local_subscriptions'), ['class' => 'btn btn-outline-secondary']);
    echo html_writer::link(cfr_d21_url('anthologies.php'), get_string('cfr_d24_anthologies', 'local_subscriptions'),
        ['class' => 'btn btn-outline-secondary']);
    echo html_writer::link(cfr_d21_url('calendar.php', ['id' => $id]),
        get_string('cfr_d23_calendar', 'local_subscriptions'), ['class' => 'btn btn-primary']);
    // These open the actual phase B public reader in admin-only layout preview mode.
    if (has_capability('moodle/site:config', context_system::instance())) {
        foreach (['advent' => 'cfr_d242b_calendarpreview', 'library' => 'cfr_d242b_librarypreview'] as $mode => $label) {
            $publicurl = new moodle_url('/local/campusreader/library.php',
                ['collection' => $collection->slug, 'view' => $mode]);
            echo html_writer::link($publicurl, get_string($label, 'local_subscriptions'),
                ['class' => 'btn btn-outline-secondary', 'target' => '_blank', 'rel' => 'noopener noreferrer']);
        }
    }
    echo '</div>';
    echo '<section class="cfr-reader-editor__panel"><h3>' . s(get_string('cfr_d21_books', 'local_subscriptions')) . '</h3>';
    if (!$books) { echo '<p>' . s(get_string('cfr_d21_no_books', 'local_subscriptions')) . '</p>'; }
    foreach ($books as $book) {
        $pages = Editorial::page_count((int)$book->id);
        $status = (int)$book->published ? 'cfr_d21_live' : ($pages ? 'cfr_d21_draft' : 'cfr_d21_unfinished');
        echo '<div class="cfr-reader-editor__book">';
        echo '<div><strong>' . s($book->title) . '</strong><small>' . s($book->slug) . ' · '
            . $pages . ' ' . s(get_string('cfr_d21_pages', 'local_subscriptions')) . '</small></div>';
        echo '<span class="cfr-reader-editor__pill">' . s(get_string($status, 'local_subscriptions')) . '</span>';
        echo html_writer::link(cfr_d21_url('book.php', ['id' => $book->id]), get_string('cfr_d21_open', 'local_subscriptions'),
            ['class' => 'btn btn-sm btn-outline-secondary']);
        echo '</div>';
    }
    echo '</section>';
}
cfr_d21_end();
