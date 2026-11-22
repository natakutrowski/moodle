<?php
// CampusFR D2.4.3 — Native Commerce protected, immutable Reader edition.
declare(strict_types=1);
require_once(__DIR__ . '/../../config.php');
require_login();
use local_campusreader\local\publication_access as Access;

$slug = required_param('slug', PARAM_ALPHANUMEXT);
$chapter = optional_param('chapter', 0, PARAM_INT);
$pub = Access::publication($slug);
header('Cache-Control: private, no-store, max-age=0');
header('X-Robots-Tag: noindex, nofollow');
header('X-Content-Type-Options: nosniff');
// Every request fails closed; locked chapter names and page bytes are never returned.
if (!$pub || !Access::can_view($pub)) {
    http_response_code(404);
    echo '<!doctype html><html lang="' . s(current_language()) . '"><meta charset="utf-8"><title>' . s(get_string('cfr_launch_pub_pageunavailable', 'local_campusreader')) . '</title><p>' . s(get_string('cfr_launch_pub_unavailable', 'local_campusreader')) . '</p></html>';
    exit;
}
try { $snapshot = Access::frozen($pub); }
catch (\Throwable $e) { http_response_code(404); exit; }
$chapters = $snapshot['chapters'];
$calendar = $pub->kind === 'calendar' ? json_decode((string)$pub->calendarjson, true) : null;
if ($pub->kind === 'calendar' && (!is_array($calendar) || !is_array($calendar['slots'] ?? null))) {
    http_response_code(404); exit;
}
if ($chapter !== 0 && ($chapter < 1 || $chapter > count($chapters) ||
        !Access::chapter_allowed($pub, $chapter))) {
    http_response_code(404); exit;
}
$puburl = static fn(int $ch): \moodle_url => new moodle_url('/local/campusreader/publication.php',
    ['slug' => $slug] + ($ch > 0 ? ['chapter' => $ch] : []));
$PAGE->set_url($puburl($chapter));
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$title = (string)$snapshot['anthology']['title'];
$subtitle = trim((string)$snapshot['anthology']['subtitle']);
if ($pub->kind === 'calendar' && $chapter === 0) {
    $display = \local_campusreader\local\calendar_presentation::resolve($snapshot, $calendar);
    $title = $display['title'];
    $subtitle = trim($display['subtitle']);
}
$PAGE->set_title($title . ' — CampusFR');
$PAGE->set_heading($title);
$PAGE->navbar->add(get_string('cfr_launch_pub_home', 'local_campusreader'), new moodle_url('/local/subscriptions/mon_campus.php'));
$PAGE->navbar->add(get_string('cfr_launch_pub_library', 'local_campusreader'), new moodle_url('/local/campusreader/my.php'));
$PAGE->navbar->add($title);
$PAGE->requires->css(new moodle_url('/local/campusreader/styles/reader.css',
    ['v' => filemtime(__DIR__ . '/styles/reader.css')]));
$PAGE->requires->css(new moodle_url('/local/campusreader/styles/publication.css',
    ['v' => filemtime(__DIR__ . '/styles/publication.css')]));
// D2.12.3 — PDF actions appear only while viewing an authorized published chapter.
if ($chapter > 0) {
    $PAGE->requires->css(new moodle_url('/local/campusreader/styles/publication_downloads.css',
        ['v' => filemtime(__DIR__ . '/styles/publication_downloads.css')]));
}
if ($pub->kind === 'calendar' && $chapter === 0) {
    foreach (['library.css', 'advent-illustrated.css', 'advent-magic.css'] as $style) {
        $PAGE->requires->css(new moodle_url('/local/campusreader/styles/' . $style,
            ['v' => filemtime(__DIR__ . '/styles/' . $style)]));
    }
    $PAGE->requires->js_call_amd('local_campusreader/library', 'init');
}
if ($chapter > 0) {
    // D2.9b: only a frozen, approved narration alignment bound to this released snapshot.
    if (isset($chapters[$chapter - 1]['alignment'])) {
        $PAGE->requires->js_call_amd('local_campusreader/phrase_sync', 'init');
    }
    $vendor = __DIR__ . '/vendor/stpageflip/page-flip.browser.js';
    if (is_file($vendor)) {
        $PAGE->requires->js_call_amd('local_campusreader/reader', 'init', [
            (new moodle_url('/local/campusreader/vendor/stpageflip/page-flip.browser.js',
                ['v' => filemtime($vendor)]))->out(false),
        ]);
    }
}
echo $OUTPUT->header();
$ui = static fn(string $key): string => s(get_string('cfr_c_' . $key, 'local_campusreader'));
echo '<main class="cfr-pub-shell">';
// D2.14.0: Moodle already renders the collection heading and breadcrumb.
if ($subtitle !== '') {
    echo '<div class="cfr-pub-head"><p>' . s($subtitle) . '</p></div>';
}
if ($chapter === 0) {
    if ($pub->kind !== 'calendar') { echo '<section class="cfr-pub-grid" aria-label="' . s(get_string('cfr_c_pub_toc', 'local_campusreader')) . '">'; }
    if ($pub->kind === 'calendar') {
        echo \local_campusreader\local\publication_calendar_renderer::render($pub, $snapshot, $calendar);
    } else {
        foreach ($chapters as $i => $story) {
            echo '<a class="cfr-pub-door" href="' . s($puburl($i + 1)->out(false)) . '"><strong>' .
                ($i + 1) . '</strong><span>' . s((string)$story['title']) . '</span></a>';
        }
    }
    if ($pub->kind !== 'calendar') { echo '</section>'; }
} else {
    $ch = $chapters[$chapter - 1];
    $sync = isset($ch['alignment']) && is_array($ch['alignment']) ? $ch['alignment'] : null;
    $total = count($chapters);
    echo '<nav class="cfr-pub-chapternav"><a href="' . s($puburl(0)->out(false)) . '">← ' . s(get_string('cfr_launch_pub_summary', 'local_campusreader')) . '</a>';
    echo '<span>' . s(get_string('cfr_launch_pub_story', 'local_campusreader', (object)['chapter'=>$chapter, 'total'=>$total])) . '</span>';
    if ($chapter > 1 && Access::chapter_allowed($pub, $chapter - 1)) {
        echo '<a href="' . s($puburl($chapter - 1)->out(false)) . '">← ' . s(get_string('cfr_launch_pub_previous', 'local_campusreader')) . '</a>';
    }
    if ($chapter < $total && Access::chapter_allowed($pub, $chapter + 1)) {
        echo '<a href="' . s($puburl($chapter + 1)->out(false)) . '">' . s(get_string('cfr_launch_pub_next', 'local_campusreader')) . ' →</a>';
    }
    echo '</nav>';
    echo '<section id="cfr-reader" class="cfr-reader cfr-pub-reader" data-cfr-book-id="pub-' .
        s($slug) . '-' . $chapter . '" data-cfr-tts-enabled="' . (\local_campusreader\local\word_pronunciation::server_available() ? '1' : '0') .'"' .
        ' data-cfr-tts-scope="publication" data-cfr-vocab-enabled="1" data-cfr-ui="' . \local_campusreader\local\ui_translations::attr() . '" data-cfr-translation-enabled="' .
        (\local_campusreader\local\word_translation::enabled() ? '1' : '0') . '" data-cfr-target-lang="' .
        s(\local_campusreader\local\word_translation::preferred_language()) . '" data-cfr-pub-slug="' . s($slug) .
        '" data-cfr-chapter="' . $chapter . '" data-cfr-tts-sesskey="' . s(sesskey()) . '">';
    echo '<header class="cfr-reader__intro"><div class="cfr-reader__eyebrow">' . $ui('pub_brand') . '</div>';
    if (trim((string)$ch['title']) !== trim($title)) {
        echo '<h2>' . s((string)$ch['title']) . '</h2>';
    }
    if (trim((string)$ch['subtitle']) !== '' && trim((string)$ch['subtitle']) !== $subtitle) {
        echo '<p>' . s((string)$ch['subtitle']) . '</p>';
    }
    echo '</header>';
    echo '<p class="cfr-reader__boot" data-cfr-boot role="status">' . $ui('pub_boot') . '</p>';
    echo '<div class="cfr-reader__stage"><div id="cfr-book" class="cfr-book">';
    $mediamap = [];
    foreach ($snapshot['media'] as $mi => $m) {
        if ((int)$m['chapter'] === $chapter) {
            $mediamap[(string)$m['filepath'] . (string)$m['filename']] = $mi;
        }
    }
    foreach ($ch['pages'] as $i => $page) {
        $phraseno = 0;
        $kind = (string)$page['kind'];
        echo '<article class="cfr-book-page cfr-book-page--' . s($kind) . '" data-page-index="' . $i . '"' .
            (in_array($kind, ['cover', 'back'], true) ? ' data-density="hard"' : '') . '><div class="cfr-book-page__inside">';
        if (in_array($kind, ['cover', 'back', 'image', 'mixed'], true)) {
            if (!empty($page['imagefile'])) {
                $mediaref = '/story' . $chapter . '/pageimage/p' . $i . '/' . (string)$page['imagefile'];
                if (!isset($mediamap[$mediaref])) { http_response_code(404); exit; }
                $mediaurl = new moodle_url('/local/campusreader/publication_media.php',
                    ['slug' => $slug, 'chapter' => $chapter, 'media' => $mediamap[$mediaref]]);
                echo '<div class="cfr-art cfr-art--image"><img loading="lazy" decoding="async" src="' .
                    s($mediaurl->out(false)) . '" alt="' . s((string)($page['imagealt'] ?? '')) . '"></div>';
            } else {
                echo '<div class="cfr-art" aria-hidden="true"><span class="cfr-art__moon"></span>' .
                    '<span class="cfr-art__star cfr-art__star--one">✦</span><span class="cfr-art__star cfr-art__star--two">✧</span>' .
                    '<span class="cfr-art__star cfr-art__star--three">✦</span><span class="cfr-art__hill"></span>' .
                    '<span class="cfr-art__house"></span></div>';
            }
        }
        echo '<div class="cfr-book-page__copy"><div class="cfr-book-page__kicker">' . s((string)$ch['title']) .
            '</div><h3>' . s((string)$page['title']) . '</h3>';
        foreach ($page['paragraphs'] as $paragraphindex => $p) {
            if ($sync === null || trim((string)$p) === '') {
                echo '<p class="cfr-text" lang="fr" data-cfr-paragraph="' . (int)$paragraphindex . '">' . s((string)$p) . '</p>';
                continue;
            }
            // Wrap only frozen phrase fragments; CampusReader still tokenizes each fragment.
            // The page number and phrase sequence match the editorial segmenter.
            $fragments = preg_split('/(?<=[.!?…])(\s+)(?=[«“"\p{Lu}\p{N}])/u',
                trim((string)$p), -1, PREG_SPLIT_DELIM_CAPTURE);
            if (!is_array($fragments)) { echo '<p class="cfr-text" lang="fr" data-cfr-paragraph="' . (int)$paragraphindex . '">' . s((string)$p) . '</p>'; continue; }
            echo '<p class="cfr-text" lang="fr" data-cfr-paragraph="' . (int)$paragraphindex . '">';
            foreach ($fragments as $fragment) {
                if (trim($fragment) === '') { echo s($fragment); continue; }
                echo '<span class="cfr-sync-phrase" data-cfr-sync-id="p' . $i . 's' . $phraseno++ . '">' .
                    s($fragment) . '</span>';
            }
            echo '</p>';
        }
        echo '<span class="cfr-book-page__number">' . ($i + 1) . ' / ' . count($ch['pages']) .
            '</span></div></div></article>';
    }
    echo '</div></div><nav class="cfr-reader__controls" aria-label="' . $ui('pub_navigation') . '">' .
        '<button type="button" class="cfr-btn cfr-btn--secondary" data-cfr-prev>← ' . $ui('pub_previous') . '</button>' .
        '<span class="cfr-reader__counter" data-cfr-counter>' . $ui('pub_page') . ' 1 / ' . count($ch['pages']) . '</span>' .
        '<button type="button" class="cfr-btn cfr-btn--primary" data-cfr-next>' . $ui('pub_next') . ' →</button></nav>' .
        '<div class="cfr-reader__options"><label><input type="checkbox" data-cfr-reduced> ' . $ui('pub_reduced') . '</label></div>';
    // D2.12.3 — links use D2.12.2's existing Commerce-authorized PDF endpoint.
    // The publication and Advent slot were already authorized above.
    // D2.12.3.1 : no dependence on Moodle discovery caches for a newly added class.
    require_once(__DIR__ . '/classes/local/publication_downloads.php');
    echo \local_campusreader\local\publication_downloads::render($slug, $chapter);
    $mediaextras = [];
    foreach ($snapshot['media'] as $idx => $m) {
        if ((int)$m['chapter'] !== $chapter || !in_array($m['area'], ['narration', 'video', 'pdf'], true)) { continue; }
        $area = $m['area'];
        // A book-level media must be unique per area; ambiguous cases render nothing.
        $mediaextras[$area][] = (new moodle_url('/local/campusreader/publication_media.php',
            ['slug' => $slug, 'chapter' => $chapter, 'media' => $idx]))->out(false);
    }
    foreach (['narration', 'video', 'pdf'] as $area) {
        if (!isset($mediaextras[$area]) || count($mediaextras[$area]) !== 1) { continue; }
        $src = s($mediaextras[$area][0]);
        echo '<div class="cfr-pub-media">';
        if ($area === 'narration') {
            echo '<h3>' . $ui('pub_audio') . '</h3>';
            if ($sync !== null) {
                echo '<p class="cfr-pub-sync-head">📖 ' . $ui('pub_sync') . ' <span data-cfr-sync-current role="status" aria-live="polite">' .
                    '' . $ui('pub_sync_intro') . '</span></p>';
            }
            echo '<audio controls preload="metadata"' . ($sync !== null ? ' data-cfr-sync-player' : '') .
                ' src="' . $src . '"></audio>';
        }
        if ($area === 'video') { echo '<h3>' . $ui('pub_video') . '</h3><video controls playsinline preload="none" src="' . $src . '"></video>'; }
        if ($area === 'pdf') { echo '<a href="' . $src . '">' . $ui('pub_pdf') . '</a>'; }
        echo '</div>';
    }
    if ($sync !== null && count($mediaextras['narration'] ?? []) === 1) {
        // JSON_HEX_TAG and its companions prevent inline HTML/script termination.
        // Times are taken exclusively from the authenticated immutable editorial snapshot.
        $safejson = json_encode($sync['cues'], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG |
            JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR);
        echo '<script type="application/json" id="cfr-pub-sync-data">' . $safejson . '</script>';
    }
    echo '</section>';
}
echo '</main>';
echo $OUTPUT->footer();
