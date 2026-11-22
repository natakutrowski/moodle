<?php
// CampusFR C3.1 — User's Reader collections (rights are checked on every request).
declare(strict_types=1);
require_once(__DIR__ . '/../../config.php');
require_login();
if (isguestuser()) {
    redirect(new moodle_url('/login/index.php'));
}

use local_campusreader\local\entrypoint;
use local_campusreader\local\publication_access;

global $OUTPUT, $PAGE;
$PAGE->set_url(new moodle_url('/local/campusreader/my.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('cfr_c_my_heading', 'local_campusreader') . ' — CampusFR');
$PAGE->set_heading(get_string('cfr_c_my_heading', 'local_campusreader'));
$PAGE->navbar->add(get_string('cfr_launch_pub_home', 'local_campusreader'), new moodle_url('/local/subscriptions/mon_campus.php'));
$PAGE->navbar->add(get_string('cfr_c_my_heading', 'local_campusreader'));
$PAGE->requires->css(new moodle_url('/local/campusreader/styles/my.css',
    ['v' => (string)filemtime(__DIR__ . '/styles/my.css')]));
$collections = entrypoint::for_current_user();
$publications = publication_access::owned();

echo $OUTPUT->header();
$ui = static fn(string $key): string => s(get_string('cfr_c_' . $key, 'local_campusreader'));
// CAMPUSFR_C33_READER_NAV: one navigation system: the native Moodle breadcrumb.
echo '<main class="cfr-reader-home">';
echo '<header class="cfr-reader-home__hero"><span>' . $ui('my_hero_eyebrow') . '</span><h1>' . $ui('my_heading') . '</h1>';
echo '<p>' . $ui('my_intro') . '</p></header>';
// D2.8.6: a proper learning destination, accessible even when the personal lexicon is empty.
$vocabularyurl = (new moodle_url('/local/campusreader/vocabulary.php'))->out(false);
echo '<section class="cfr-reader-home__vocab-feature" aria-label="' . $ui('my_vocab_aria') . '">';
echo '<a class="cfr-reader-home__vocab-link" href="' . s($vocabularyurl) . '">';
echo '<span class="cfr-reader-home__vocab-icon" aria-hidden="true">📖</span>';
echo '<span class="cfr-reader-home__vocab-copy">' .
    '<span class="cfr-reader-home__vocab-eyebrow">' . $ui('my_vocab_eyebrow') . '</span>' .
    '<strong>' . $ui('my_vocab_title') . '</strong>' .
    '<small>' . $ui('my_vocab_desc') . '</small>' .
    '</span>';
echo '<span class="cfr-reader-home__vocab-cta" aria-hidden="true">' . $ui('my_vocab_open') . ' <span>→</span></span>';
echo '</a></section>';
if (!$collections && !$publications) {
    echo '<div class="cfr-reader-home__empty"><h2>' . $ui('my_empty_title') . '</h2>';
    echo '<p>' . $ui('my_empty_desc') . '</p></div>';
} else {
    echo '<div class="cfr-reader-home__grid">';
    foreach ($publications as $edition) {
        echo '<a class="cfr-reader-home__card" href="' . s($edition['url']) . '">';
        echo '<span class="cfr-reader-home__illustration" aria-hidden="true">📚</span>';
        echo '<span class="cfr-reader-home__card-text"><strong>' . s($edition['title']) . '</strong>';
        echo '<small>' . ($edition['kind'] === 'calendar' ? $ui('my_open_calendar') : $ui('my_open_anthology')) . '</small></span>';
        echo '<span aria-hidden="true">→</span></a>';
    }
    foreach ($collections as $collection) {
        echo '<a class="cfr-reader-home__card" href="' . s($collection['url']) . '">';
        echo '<span class="cfr-reader-home__illustration" aria-hidden="true">📖</span>';
        echo '<span class="cfr-reader-home__card-text"><strong>' . s($collection['title']) . '</strong>';
        echo '<small>' . $ui('my_open_collection') . '</small></span>';
        echo '<span aria-hidden="true">→</span></a>';
    }
    echo '</div>';
}
echo '</main>';
echo $OUTPUT->footer();
