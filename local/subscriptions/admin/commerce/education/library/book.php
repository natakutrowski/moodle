<?php
// CampusFR D2.1 — book metadata editing; publication prohibited in this delta.
declare(strict_types=1);
require_once(__DIR__ . '/_bootstrap.php');

use local_subscriptions\crm\library\ReaderEditorialService as Editorial;
use local_subscriptions\crm\library\ReaderEditorialMediaService as Media;

$id = optional_param('id', 0, PARAM_INT);
$collectionid = optional_param('collectionid', 0, PARAM_INT);
$error = '';
$book = $id ? Editorial::book($id) : null;
if ($book) { $collectionid = (int)$book->collectionid; }
$collection = Editorial::collection($collectionid);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();
    try {
        // D2.2.1: never accept a forged POST against a published parent collection.
        if ((int)$collection->published !== 0) {
            throw new \moodle_exception('invalidparameter');
        }
        $saved = Editorial::save_book(
            $id, $collectionid,
            required_param('slug', PARAM_RAW_TRIMMED),
            required_param('title', PARAM_RAW_TRIMMED),
            optional_param('subtitle', '', PARAM_RAW_TRIMMED)
        );
        redirect(cfr_d21_url('book.php', ['id' => $saved]), get_string('changessaved'));
    } catch (\moodle_exception $exception) {
        $error = $exception->getMessage();
    }
}
$locked = $book && ((int)$book->published !== 0 || (int)$collection->published !== 0);
$title = $book ? $book->title : get_string('cfr_d21_newbook', 'local_subscriptions');
$trail = [
    ['label' => $collection->title, 'url' => cfr_d21_url('collection.php', ['id' => $collectionid])],
    ['label' => $title, 'url' => null],
];
cfr_d21_start($title, get_string('cfr_d21_book_help', 'local_subscriptions'), $trail);
if ($error !== '') { echo '<div role="alert" class="alert alert-danger">' . s($error) . '</div>'; }
if ($locked) { echo '<div class="cfr-reader-editor__info">' . s(get_string('cfr_d21_readonly', 'local_subscriptions')) . '</div>'; }
echo '<section class="cfr-reader-editor__panel">';
if (!$locked) {
    echo '<form method="post" action="' . s(cfr_d21_url('book.php', $id ? ['id' => $id] : ['collectionid' => $collectionid])->out(false)) . '">';
    echo '<input type="hidden" name="sesskey" value="' . s(sesskey()) . '">';
    echo '<div class="cfr-reader-editor__fields">';
    cfr_d21_field('title', get_string('name'), (string)($book->title ?? ''));
    cfr_d21_field('slug', 'Slug', (string)($book->slug ?? ''), (bool)$book, 'text',
        get_string('cfr_d21_slug_help', 'local_subscriptions'));
    cfr_d21_field('subtitle', get_string('cfr_d21_subtitle_field', 'local_subscriptions'),
        (string)($book->subtitle ?? ''), false, 'text', '', false);
    echo '</div><button class="btn btn-primary" type="submit">' . s(get_string('savechanges')) . '</button></form>';
} else {
    echo '<h3>' . s($book->title) . '</h3><p>' . s($book->subtitle) . '</p>';
}
echo '</section>';
if ($book) {
    $pagecount = Editorial::page_count($id);
    $slotcount = Editorial::slot_count($id);
    $media = Media::inventory($id);
    $imagecount = count($media['pageimage']) + count($media['cover']);
    $narrationcount = count($media['narration']);
    $pdfcount = count($media['pdf']);
    echo '<h3 class="cfr-reader-editor__sectiontitle">' .
        s(get_string('cfr_d221_progress', 'local_subscriptions')) . '</h3>';
    echo '<div class="cfr-reader-editor__grid cfr-reader-editor__status-grid">';
    foreach ([
        [$pagecount, get_string('cfr_d21_pages', 'local_subscriptions')],
        [$imagecount, get_string('cfr_d221_images', 'local_subscriptions')],
        [$narrationcount, get_string('cfr_d22_area_narration', 'local_subscriptions')],
        [$pdfcount, get_string('cfr_d22_area_pdf', 'local_subscriptions')],
        [$slotcount, $slotcount === 1 ? get_string('cfr_d221_slot_one', 'local_subscriptions') :
            get_string('cfr_d21_slots', 'local_subscriptions')],
    ] as [$metric, $label]) {
        echo '<div class="cfr-reader-editor__card"><div class="cfr-reader-editor__metric">' . (int)$metric .
            '</div><p>' . s($label) . '</p></div>';
    }
    echo '</div>';
    echo '<p class="cfr-reader-editor__hint">' .
        s(get_string('cfr_d221_progressnote', 'local_subscriptions')) . '</p>';
    echo '<div class="cfr-reader-editor__actions">';
    echo html_writer::link(cfr_d21_url('transfers.php'),
        get_string('cfr_d25_transfer', 'local_subscriptions'), ['class' => 'btn btn-outline-secondary']);
    echo html_writer::link(cfr_d21_url('pages.php', ['id' => $id]),
        get_string('cfr_d22_pages', 'local_subscriptions'), ['class' => 'btn btn-primary']);
    echo html_writer::link(cfr_d21_url('story_flipbook.php', ['id' => $id]),
        get_string('cfr_d242b_storypreview', 'local_subscriptions'),
        ['class' => 'btn btn-outline-secondary', 'target' => '_blank', 'rel' => 'noopener noreferrer']);
    echo html_writer::link(cfr_d21_url('media.php', ['id' => $id]),
        get_string('cfr_d22_media', 'local_subscriptions'), ['class' => 'btn btn-outline-secondary']);
    echo html_writer::link(cfr_d21_url('audio_studio.php', ['id' => $id]),
        'Studio audio', ['class' => 'btn btn-outline-secondary']);
    echo html_writer::link(cfr_d21_url('layout_studio.php', ['id' => $id]),
        'Atelier visuel · PDF 16:9', ['class' => 'btn btn-outline-secondary']);
    echo html_writer::link(cfr_d21_url('calendar.php', ['id' => $collectionid]),
        get_string('cfr_d23_calendar', 'local_subscriptions'), ['class' => 'btn btn-outline-secondary']);
    echo '</div>';
    // One canonical story can be referenced by several independent anthologies.
    if (\local_subscriptions\crm\library\ReaderEditorialAnthologyService::ready()) {
        $linked = \local_subscriptions\crm\library\ReaderEditorialAnthologyService::for_story($id);
        if ($linked) {
            echo '<section class="cfr-reader-editor__panel"><h3>' .
                s(get_string('cfr_d24_used_in', 'local_subscriptions')) . '</h3>';
            foreach ($linked as $anth) {
                echo '<p>' . html_writer::link(cfr_d21_url('anthology.php', ['id' => $anth->id]),
                    s($anth->title)) . '</p>';
            }
            echo '</section>';
        }
    }
    echo '<div class="cfr-reader-editor__info">'
        . s(get_string('cfr_d22_next', 'local_subscriptions')) . '</div>';
}
cfr_d21_end();
