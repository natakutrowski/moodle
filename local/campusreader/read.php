<?php
// CampusFR Reader C1: secured DB book; reuse the existing B2 flipbook engine. GPL-3.0-or-later.
declare(strict_types=1);
require_once(__DIR__ . '/../../config.php');
require_login();

use local_campusreader\local\collection_access;
use local_campusreader\local\collection_repository;

global $PAGE, $OUTPUT;
$id = required_param('id', PARAM_INT);
[$book, $collection, $slot] = collection_repository::book_bundle($id);
if (!collection_access::can_read_book($book, $collection, $slot)) {
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
$pages = collection_repository::pages($id);
$PAGE->set_url(new moodle_url('/local/campusreader/read.php', ['id' => $id]));
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title($book->title . ' — CampusFR');
$PAGE->set_heading($book->title);
// CAMPUSFR_C35_READER_FINISH: Moodle breadcrumb shared with collection and My Library.
$libraryurl = new moodle_url('/local/campusreader/library.php', ['collection' => $collection->slug]);
$PAGE->navbar->add(get_string('cfr_c_my_campus', 'local_campusreader'), new moodle_url('/local/subscriptions/mon_campus.php'));
$PAGE->navbar->add(get_string('cfr_c_my_library', 'local_campusreader'), new moodle_url('/local/campusreader/my.php'));
$PAGE->navbar->add(format_string((string)$collection->title), $libraryurl);
$PAGE->navbar->add(format_string((string)$book->title));
$PAGE->requires->css(new moodle_url('/local/campusreader/styles/reader.css', [
    'v' => (string) filemtime(__DIR__ . '/styles/reader.css'),
]));
// CAMPUSFR_C34_READER_UX: enhance audio with controls, retaining native fallback.
$PAGE->requires->js_call_amd('local_campusreader/narration', 'init');
$vendor = __DIR__ . '/vendor/stpageflip/page-flip.browser.js';
if (is_file($vendor)) {
    $PAGE->requires->js_call_amd('local_campusreader/reader', 'init', [
        (new moodle_url('/local/campusreader/vendor/stpageflip/page-flip.browser.js', [
            'v' => (string) filemtime($vendor),
        ]))->out(false),
    ]);
}
echo $OUTPUT->header();
// CAMPUSFR_D1_TTS: no key is ever sent to the browser.
$neuralready = !empty($CFG->campusreader_azure_tts_key);
echo '<div id="cfr-reader" class="cfr-reader" data-cfr-book-id="db-' . $id .
    '" data-cfr-tts-book="' . $id . '" data-cfr-tts-sesskey="' . s(sesskey()) .
    '" data-cfr-tts-enabled="' . ($neuralready ? '1' : '0') . '">';
echo '<header class="cfr-reader__intro"><div class="cfr-reader__eyebrow">' . s(get_string('cfr_lotc2_reader_common_04', 'local_campusreader')) . '</div>';
echo '<h2>' . s($book->title) . '</h2><p>' . s($book->subtitle) . '</p>';
echo '</header>';
// Optional private narration, video and PDF. Never render URLs until the book gate above passed.
$fs = get_file_storage();
$media = [];
foreach (['cover', 'narration', 'video', 'pdf'] as $area) {
    $found = $fs->get_area_files(context_system::instance()->id,
        'local_campusreader', $area, (int)$book->id, 'id ASC', false);
    $found = array_values(array_filter($found, static fn($file): bool => !$file->is_directory()));
    if (count($found) === 1) {
        $stored = $found[0];
        $media[$area] = moodle_url::make_pluginfile_url(context_system::instance()->id,
            'local_campusreader', $area, (int)$book->id, '/', $stored->get_filename())->out(false);
    }
}
echo '<p class="cfr-reader__boot" data-cfr-boot role="status">' . s(get_string('cfr_lotc2_reader_common_05', 'local_campusreader')) . '</p>';
echo '<div class="cfr-reader__stage"><div id="cfr-book" class="cfr-book">';
foreach ($pages as $index => $page) {
    $kind = $page['kind'];
    echo '<article class="cfr-book-page cfr-book-page--' . s($kind) . '" data-page-index="' . $index . '"';
    if ($kind === 'cover' || $kind === 'back') {
        echo ' data-density="hard"';
    }
    echo '><div class="cfr-book-page__inside">';
    if (in_array($kind, ['cover', 'back', 'image', 'mixed'], true)) {
        if (!empty($page['imagefile'])) {
            $filename = $page['imagefile'];
            $url = moodle_url::make_pluginfile_url(context_system::instance()->id,
                'local_campusreader', 'pageimage', $id, '/p' . $index . '/', $filename);
            echo '<div class="cfr-art cfr-art--image"><img src="' . s($url->out(false)) . '" alt="' .
                s($page['imagealt'] ?? '') . '" loading="lazy" decoding="async"></div>';
        } else {
            echo '<div class="cfr-art" aria-hidden="true"><span class="cfr-art__moon"></span>' .
                '<span class="cfr-art__star cfr-art__star--one">✦</span><span class="cfr-art__star cfr-art__star--two">✧</span>' .
                '<span class="cfr-art__star cfr-art__star--three">✦</span><span class="cfr-art__hill"></span>' .
                '<span class="cfr-art__house"></span></div>';
        }
    }
    echo '<div class="cfr-book-page__copy"><div class="cfr-book-page__kicker">' . s($book->title) . '</div>';
    echo '<h3>' . s($page['title']) . '</h3>';
    foreach ($page['paragraphs'] as $paragraph) {
        echo '<p class="cfr-text" lang="fr">' . s($paragraph) . '</p>';
    }
    echo '<span class="cfr-book-page__number">' . ($index + 1) . ' / ' . count($pages) . '</span>';
    echo '</div></div></article>';
}
echo '</div></div><nav class="cfr-reader__controls" aria-label="' . s(get_string('cfr_lotc2_reader_common_06', 'local_campusreader')) . '">';
echo '<button type="button" class="cfr-btn cfr-btn--secondary" data-cfr-prev>' . s(get_string('cfr_lotc2_reader_common_07', 'local_campusreader')) . '</button>';
echo '<span class="cfr-reader__counter" data-cfr-counter aria-live="polite">' . s(get_string('cfr_fix_page_prefix_book', 'local_campusreader')) . '' . count($pages) . '</span>';
echo '<button type="button" class="cfr-btn cfr-btn--primary" data-cfr-next>' . s(get_string('cfr_lotc2_reader_common_08', 'local_campusreader')) . '</button></nav>';
echo '<div class="cfr-reader__options"><label><input type="checkbox" data-cfr-reduced>' . s(get_string('cfr_lotc2_reader_common_09', 'local_campusreader')) . '</label>';
echo '</div>';
// CAMPUSFR_C34_READER_UX: complementary actions come after the complete flipbook.
// Protected File API URLs still originate solely from the existing book-access gate.
if (isset($media['narration']) || isset($media['video']) || isset($media['pdf'])) {
    echo '<section class="cfr-reader-media" aria-label="' . s(get_string('cfr_lotc2_read_01', 'local_subscriptions')) . '">';
    if (isset($media['video'])) {
        echo '<div class="cfr-reader-media__video"><h3>' . s(get_string('cfr_lotc2_read_02', 'local_subscriptions')) . '</h3>';
        echo '<video controls playsinline preload="none"';
        if (isset($media['cover'])) {
            echo ' poster="' . s($media['cover']) . '"';
        }
        echo '><source src="' . s($media['video']) . '">' . s(get_string('cfr_lotc2_read_03', 'local_subscriptions')) . '</video></div>';
    }
    if (isset($media['narration'])) {
        // Native controls are present in HTML; JS enhances them only if all controls are available.
        echo '<div class="cfr-reader-audio" data-cfr-audio-player>';
        echo '<audio class="cfr-reader-audio__native" controls preload="metadata" src="' .
            s($media['narration']) . '">' . s(get_string('cfr_lotc2_read_04', 'local_subscriptions')) . '</audio>';
        echo '<div class="cfr-reader-audio__custom" data-cfr-audio-ui hidden>';
        echo '<button type="button" class="cfr-reader-audio__toggle" data-cfr-audio-toggle ' .
            'aria-label="' . s(get_string('cfr_lotc2_read_05', 'local_subscriptions')) . '" aria-pressed="false"></button>';
        echo '<span class="cfr-reader-audio__label" data-cfr-audio-label>' . s(get_string('cfr_lotc2_read_06', 'local_subscriptions')) . '</span>';
        echo '<div class="cfr-reader-audio__details" data-cfr-audio-details hidden>';
        echo '<span class="cfr-reader-audio__time"><span data-cfr-audio-current>0:00</span>' .
            ' / <span data-cfr-audio-total>--:--</span></span>';
        echo '<input class="cfr-reader-audio__seek" data-cfr-audio-seek type="range" ' .
            'min="0" max="1000" value="0" step="1" aria-label="' . s(get_string('cfr_lotc2_read_07', 'local_subscriptions')) . '" disabled>';
        echo '<button type="button" class="cfr-reader-audio__mute" data-cfr-audio-mute ' .
            'aria-label="' . s(get_string('cfr_lotc2_read_08', 'local_subscriptions')) . '" aria-pressed="false">' .
            '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">' .
            '<path d="M4 9h4l5-4v14l-5-4H4z"/><path d="M17 9c2 2 2 4 0 6"/>' .
            '<path d="M19 6c4 4 4 8 0 12"/></svg></button>';
        echo '<input class="cfr-reader-audio__volume" data-cfr-audio-volume type="range" ' .
            'min="0" max="100" value="100" step="1" aria-label="' . s(get_string('cfr_lotc2_read_09', 'local_subscriptions')) . '">';
        echo '</div></div></div>';
    }
    if (isset($media['pdf'])) {
        echo '<a class="cfr-reader-download" href="' . s($media['pdf']) . '">';
        echo '<span class="cfr-reader-download__icon" aria-hidden="true">' .
            '<svg viewBox="0 0 24 24" focusable="false"><path d="M14 2H6a2 2 0 0 0-2 2v16' .
            'a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>' .
            '<path d="M14 2v6h6"/><path d="M8 13h8M8 17h5"/></svg></span>';
        echo '<span class="cfr-reader-download__copy"><span class="cfr-reader-download__title">' .
            '' . s(get_string('cfr_lotc2_read_10', 'local_subscriptions')) . '</span>' .
            '<span class="cfr-reader-download__sub">' . s(get_string('cfr_lotc2_read_11', 'local_subscriptions')) . '</span></span>';
        echo '<span class="cfr-reader-download__arrow" aria-hidden="true">↓</span></a>';
    }
    echo '</section>';
}
if (collection_access::is_admin()) {
    echo '<p class="cfr-reader__notice">' . s(get_string('cfr_lotc2_read_12', 'local_subscriptions')) . '</p>';
}
if (!is_file($vendor)) {
    echo '<p class="alert alert-danger">' . s(get_string('cfr_lotc2_reader_common_10', 'local_campusreader')) . '</p>';
}
echo '</div>';
echo $OUTPUT->footer();
