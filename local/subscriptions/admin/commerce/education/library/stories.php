<?php
// CampusFR D2.7 — read-only story catalogue within the authenticated editorial CRM.
declare(strict_types=1);
require_once(__DIR__ . '/_bootstrap.php');

use local_subscriptions\crm\library\ReaderEditorialService as Editorial;

$q = trim(optional_param('q', '', PARAM_TEXT));
if (\core_text::strlen($q) > 100) { $q = \core_text::substr($q, 0, 100); }
$collections = Editorial::collections();
$total = 0;
$visible = [];
foreach ($collections as $collection) {
    $books = Editorial::books((int)$collection->id);
    $total += count($books);
    $filtered = [];
    foreach ($books as $book) {
        if ($q !== '' && mb_stripos($book->title . ' ' . $book->slug . ' ' . $collection->title, $q, 0, 'UTF-8') === false) { continue; }
        $filtered[] = $book;
    }
    if ($filtered) { $visible[] = [$collection, $filtered]; }
}
$actions = html_writer::link(cfr_d21_url('index.php'),
    get_string('cfr_d27_collections', 'local_subscriptions'), ['class' => 'btn btn-outline-secondary']);
cfr_d21_start(get_string('cfr_d27_stories', 'local_subscriptions'),
    get_string('cfr_d27_stories_help', 'local_subscriptions'), [
        ['label' => get_string('cfr_d27_stories', 'local_subscriptions'), 'url' => null],
    ], $actions);
echo '<div class="cfr-d27-section-heading"><strong>' . $total . ' ' .
    s(get_string('cfr_d27_stories', 'local_subscriptions')) . '</strong>';
echo '<form method="get" class="cfr-d27-search" action="' . s(cfr_d21_url('stories.php')->out(false)) . '">';
echo '<label for="cfr-d27-story-filter">' . s(get_string('cfr_d27_search', 'local_subscriptions')) . '</label>';
echo '<div class="cfr-d27-search__controls"><input id="cfr-d27-story-filter" class="form-control" name="q" type="search" maxlength="100" value="' .
    s($q) . '" placeholder="' . s(get_string('cfr_d27_search_placeholder', 'local_subscriptions')) . '">';
echo '<button class="btn btn-outline-secondary" type="submit">' . s(get_string('cfr_d27_search', 'local_subscriptions')) . '</button></div></form></div>';
foreach ($visible as [$collection, $books]) {
    echo '<section class="cfr-reader-editor__panel cfr-d27-list-section">';
    echo '<div class="cfr-d27-section-heading"><h3>' . s($collection->title) . '</h3>';
    echo html_writer::link(cfr_d21_url('collection.php', ['id' => $collection->id]),
        get_string('cfr_d27_view_collection', 'local_subscriptions'), ['class' => 'btn btn-sm btn-outline-secondary']) . '</div>';
    foreach ($books as $book) {
        $pages = Editorial::page_count((int)$book->id);
        $status = (int)$book->published !== 0 ? 'cfr_d21_live' : 'cfr_d21_draft';
        echo '<div class="cfr-d27-list-row"><div class="cfr-d27-list-row__main"><strong>' . s($book->title) . '</strong>';
        echo '<small>' . s($book->slug) . ' · ' . $pages . ' ' .
            s(get_string('cfr_d21_pages', 'local_subscriptions')) . '</small></div>';
        echo '<span class="cfr-reader-editor__pill">' . s(get_string($status, 'local_subscriptions')) . '</span>';
        echo '<div class="cfr-d27-list-row__actions">' .
            html_writer::link(cfr_d21_url('book.php', ['id' => $book->id]),
                get_string('cfr_d21_open', 'local_subscriptions'), ['class' => 'btn btn-sm btn-outline-secondary']) .
            html_writer::link(cfr_d21_url('pages.php', ['id' => $book->id]),
                get_string('cfr_d22_pages', 'local_subscriptions'), ['class' => 'btn btn-sm btn-outline-secondary']) .
            html_writer::link(cfr_d21_url('story_flipbook.php', ['id' => $book->id]),
                get_string('cfr_d242b_storypreview', 'local_subscriptions'),
                ['class' => 'btn btn-sm btn-outline-secondary', 'target' => '_blank', 'rel' => 'noopener noreferrer']) .
            '</div></div>';
    }
    echo '</section>';
}
if (!$visible) { echo '<p class="cfr-reader-editor__info">' . s(get_string('cfr_d27_noresults', 'local_subscriptions')) . '</p>'; }
cfr_d21_end();
