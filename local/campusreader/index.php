<?php
// CampusFR — interactive reader B2. Demo content ONLY, admin access only.
// @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
declare(strict_types=1);

require_once(__DIR__ . '/../../config.php');
// Explicitly load the B2 demo repository until the Moodle autoload issue is resolved.
require_once(__DIR__ . '/classes/local/demo_book_repository.php');
require_login();
require_capability('moodle/site:config', context_system::instance());

use local_campusreader\local\demo_book_repository;

global $PAGE, $OUTPUT;
$id = optional_param('book', 'noel-des-etoiles', PARAM_ALPHANUMEXT);
$books = demo_book_repository::all();
if (!isset($books[$id])) {
    throw new moodle_exception('invalidparameter');
}
$book = $books[$id];
$pages = $book['pages'];
$bookurl = new moodle_url('/local/campusreader/index.php', ['book' => $id]);
$PAGE->set_url($bookurl);
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('demotitle', 'local_campusreader') . ' — ' . $book['title']);
$PAGE->set_heading(get_string('demotitle', 'local_campusreader'));
$PAGE->requires->css(new moodle_url('/local/campusreader/styles/reader.css', [
    'v' => (string) filemtime(__DIR__ . '/styles/reader.css'),
]));
$vendor = __DIR__ . '/vendor/stpageflip/page-flip.browser.js';
if (is_file($vendor)) {
    $PAGE->requires->js_call_amd('local_campusreader/reader', 'init', [
        (new moodle_url('/local/campusreader/vendor/stpageflip/page-flip.browser.js', [
            'v' => (string) filemtime($vendor),
        ]))->out(false),
    ]);
}

// This sandbox renders plain text escaped server-side, never raw HTML from JSON.
echo $OUTPUT->header();
echo '<div id="cfr-reader" class="cfr-reader" data-cfr-book-id="' . s($id) . '">';
echo '<header class="cfr-reader__intro"><div class="cfr-reader__eyebrow">CAMPUSFR · AVENT 2026</div>';
echo '<h2>' . s(get_string('cfr_fix_demo_heading', 'local_subscriptions')) . '</h2>';
echo '<p>' . s(get_string('cfr_fix_demo_intro', 'local_subscriptions')) . '</p>';
echo '<div class="cfr-reader__book-choice"><label for="cfr-select-book">' . s(get_string('cfr_fix_demo_select', 'local_subscriptions')) . '</label>';
echo '<select id="cfr-select-book" data-cfr-book-select aria-label="' . s(get_string('cfr_fix_demo_aria', 'local_subscriptions')) . '">';
foreach ($books as $bid => $item) {
    $url = (new moodle_url('/local/campusreader/index.php', ['book' => $bid]))->out(false);
    echo '<option value="' . s($url) . '"' . ($bid === $id ? ' selected' : '') . '>' . s($item['title']) . '</option>';
}
echo '</select></div></header>';
echo '<p class="cfr-reader__boot" data-cfr-boot role="status">' . s(get_string('cfr_lotc2_reader_common_05', 'local_campusreader')) . '</p>';
echo '<div class="cfr-reader__stage"><div id="cfr-book" class="cfr-book">';
foreach ($pages as $index => $page) {
    $kind = $page['kind'];
    echo '<article class="cfr-book-page cfr-book-page--' . s($kind) . '" data-page-index="' . $index . '"';
    if ($kind === 'cover' || $kind === 'back') {
        echo ' data-density="hard"';
    }
    echo '><div class="cfr-book-page__inside">';
    if ($kind === 'cover' || $kind === 'back' || $kind === 'image' || $kind === 'mixed') {
        if (isset($page['image'])) {
            $imgurl = (new moodle_url('/local/campusreader/demo/assets/' . $page['image']))->out(false);
            echo '<div class="cfr-art cfr-art--image"><img src="' . s($imgurl) . '" alt="' . s($page['image_alt']) . '" loading="lazy" decoding="async"></div>';
        } else {
            echo '<div class="cfr-art" aria-hidden="true"><span class="cfr-art__moon"></span>';
            echo '<span class="cfr-art__star cfr-art__star--one">✦</span>';
            echo '<span class="cfr-art__star cfr-art__star--two">✧</span>';
            echo '<span class="cfr-art__star cfr-art__star--three">✦</span>';
            echo '<span class="cfr-art__hill"></span><span class="cfr-art__house"></span></div>';
        }
    }
    echo '<div class="cfr-book-page__copy"><div class="cfr-book-page__kicker">' . s($book['title']) . '</div>';
    echo '<h3>' . s($page['title']) . '</h3>';
    foreach ($page['paragraphs'] as $paragraph) {
        echo '<p class="cfr-text" lang="fr">' . s($paragraph) . '</p>';
    }
    echo '<span class="cfr-book-page__number">' . ($index + 1) . ' / ' . count($pages) . '</span>';
    echo '</div></div></article>';
}
echo '</div></div>';
echo '<nav class="cfr-reader__controls" aria-label="' . s(get_string('cfr_lotc2_reader_common_06', 'local_campusreader')) . '">';
echo '<button type="button" class="cfr-btn cfr-btn--secondary" data-cfr-prev>' . s(get_string('cfr_lotc2_reader_common_07', 'local_campusreader')) . '</button>';
echo '<span class="cfr-reader__counter" data-cfr-counter aria-live="polite">' . s(get_string('cfr_fix_page_prefix_demo', 'local_campusreader')) . '' . count($pages) . '</span>';
echo '<button type="button" class="cfr-btn cfr-btn--primary" data-cfr-next>' . s(get_string('cfr_lotc2_reader_common_08', 'local_campusreader')) . '</button>';
echo '</nav>';
echo '<div class="cfr-reader__options"><label><input type="checkbox" data-cfr-reduced>' . s(get_string('cfr_lotc2_reader_common_09', 'local_campusreader')) . '</label>';
echo '<span data-cfr-progress-note>' . s(get_string('cfr_fix_demo_saved', 'local_subscriptions')) . '</span></div>';
echo '<p class="cfr-reader__notice">' . s(get_string('cfr_fix_demo_warning', 'local_subscriptions')) . '</p>';
if (!is_file($vendor)) {
    echo '<p class="alert alert-danger">' . s(get_string('cfr_lotc2_reader_common_10', 'local_campusreader')) . '</p>';
}
echo '</div>';
echo $OUTPUT->footer();
