<?php
// CampusFR D2.8 — private, persistent vocabulary independent of purchases and courses.
declare(strict_types=1);
require_once(__DIR__ . '/../../config.php');
require_login();
if (isguestuser() || !empty($USER->suspended) || !empty($USER->deleted)) { redirect(new moodle_url('/login/index.php')); }
use local_campusreader\local\personal_vocabulary;
use local_campusreader\local\word_pronunciation;
use local_campusreader\local\word_translation;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();
    $action = required_param('action', PARAM_ALPHA);
    if ($action === 'export') {
        $items = personal_vocabulary::list_for((int)$USER->id);
        \core\session\manager::write_close();
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="campusfr-mon-vocabulaire.csv"');
        header('Cache-Control: private, no-store');
        header('X-Content-Type-Options: nosniff');
        $stream = fopen('php://output', 'wb');
        personal_vocabulary::write_csv($stream, $items, (int)$USER->id);
        fclose($stream);
        exit;
    }
    if ($action === 'language') {
        $lang = \local_campusreader\local\word_translation::language(required_param('targetlang', PARAM_ALPHA));
        set_user_preference('campusreader_vocab_language', $lang);
        redirect(new moodle_url('/local/campusreader/vocabulary.php'));
    }
    $id = required_param('id', PARAM_INT);
    if ($action === 'update') {
        personal_vocabulary::update_translation((int)$USER->id, $id,
            required_param('translation', PARAM_RAW_TRIMMED));
    } else if ($action === 'delete') {
        personal_vocabulary::remove((int)$USER->id, $id);
    } else { throw new invalid_parameter_exception('Unknown action'); }
    redirect(new moodle_url('/local/campusreader/vocabulary.php'));
}
$PAGE->set_url(new moodle_url('/local/campusreader/vocabulary.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('cfr_c_my_vocab_title', 'local_campusreader') . ' — CampusFR');
$PAGE->set_heading(get_string('cfr_c_my_vocab_title', 'local_campusreader'));
$PAGE->navbar->add(get_string('cfr_launch_pub_home', 'local_campusreader'), new moodle_url('/local/subscriptions/mon_campus.php'));
$PAGE->navbar->add(get_string('cfr_c_my_heading', 'local_campusreader'), new moodle_url('/local/campusreader/my.php'));
$PAGE->navbar->add(get_string('cfr_c_my_vocab_title', 'local_campusreader'));
$PAGE->requires->js_call_amd('local_campusreader/vocab', 'init');
$PAGE->requires->css(new moodle_url('/local/campusreader/styles/reader.css',
    ['v' => (string)filemtime(__DIR__ . '/styles/reader.css')]));
$items = personal_vocabulary::list_for((int)$USER->id);
$fs = personal_vocabulary::sources_for((int)$USER->id, $items);
$collections = []; $stories = [];
foreach ($fs as $sources) {
    foreach ($sources as $source) {
        $collections[$source['slug']] = $source['collection'];
        $stories[$source['key']] = $source;
    }
}
$duplicated = array_count_values($collections);
asort($collections, SORT_NATURAL | SORT_FLAG_CASE);
uasort($stories, static fn($a, $b) => strnatcasecmp($a['title'], $b['title']));
$ttsavailable = word_pronunciation::server_available();
$translationavailable = word_translation::enabled();
header('Cache-Control: private, no-store');
echo $OUTPUT->header();
$ui = static fn(string $key): string => s(get_string('cfr_c_' . $key, 'local_campusreader'));
echo '<main class="cfr-vocab-page" data-cfr-ui="' . \local_campusreader\local\ui_translations::attr() . '"><header class="cfr-vocab-overview">' .
    '<div><p>' . $ui('vocab_intro') . '</p>' .
    '<strong class="cfr-vocab-total">' . count($items) . '</strong> ' . $ui('vocab_saved_count') . '</div>' .
    '<form method="post"><input type="hidden" name="sesskey" value="' . s(sesskey()) . '">' .
    '<input type="hidden" name="action" value="export">' .
    '<button class="cfr-vocab-export" type="submit"' . (!$items ? ' disabled' : '') .
    '>' . $ui('vocab_export') . '</button></form></header>';
$lang = \local_campusreader\local\word_translation::preferred_language();
echo '<form method="post" class="cfr-vocab-language">' .
    '<input type="hidden" name="sesskey" value="' . s(sesskey()) . '">' .
    '<input type="hidden" name="action" value="language">' .
    '<label for="cfr-vocab-lang">' . $ui('vocab_targetlang') . '</label>' .
    '<select id="cfr-vocab-lang" name="targetlang">';
foreach (\local_campusreader\local\word_translation::languages() as $code => $label) {
    echo '<option value="' . s($code) . '"' . ($code === $lang ? ' selected' : '') . '>' . s($label) . '</option>';
}
echo '</select><button type="submit">' . $ui('vocab_langsave') . '</button></form>';

if ($ttsavailable) { echo '<div class="cfr-vocab-neural-voice" data-cfr-neural-voice-panel>' .
    '<label for="cfr-vocab-voice">' . $ui('vocab_voice') . '</label>' .
    '<select id="cfr-vocab-voice" data-cfr-neural-voice aria-label="' . $ui('vocab_voice_choose') . '">' .
    '<option value="Lea">' . $ui('vocab_voice_female') . '</option>' .
    '<option value="Remi">' . $ui('vocab_voice_male') . '</option></select></div>'; }

if (!$items) {
    echo '<p class="cfr-vocab-empty">' . $ui('vocab_empty') . '</p>';
} else {
    echo '<section class="cfr-vocab-tools" aria-label="' . $ui('vocab_tools_aria') . '">' .
        '<div class="cfr-vocab-tools__row"><label for="cfr-vocab-search">' . $ui('vocab_search') . '' .
        '<input id="cfr-vocab-search" data-cfr-vocab-search type="search" autocomplete="off" ' .
        'placeholder="' . $ui('vocab_search_hint') . '"></label>' .
        '<label for="cfr-vocab-sort">' . $ui('vocab_sort') . '' .
        '<select id="cfr-vocab-sort" data-cfr-vocab-sort>' .
        '<option value="az">A → Z</option><option value="za">Z → A</option>' .
         '<option value="recent">' . $ui('vocab_recent') . '</option></select></label></div>' .
        '<div class="cfr-vocab-tools__row cfr-vocab-source-filters"><label for="cfr-vocab-collection">' . $ui('vocab_collections') . '' .
        '<select id="cfr-vocab-collection" data-cfr-vocab-collection><option value="">' . $ui('vocab_all_sources') . '</option>' .
        '<option value="!unavailable">' . $ui('vocab_no_source') . '</option>';
    foreach ($collections as $slug => $name) {
        $label = $name . ($duplicated[$name] > 1 ? ' · ' . $slug : '');
        echo '<option value="' . s($slug) . '">' . s($label) . '</option>';
    }
    echo '</select></label><label for="cfr-vocab-story">' . $ui('vocab_story') . '<select id="cfr-vocab-story" data-cfr-vocab-story>' .
        '<option value="">' . $ui('vocab_all_stories') . '</option>';
    foreach ($stories as $key => $source) {
        echo '<option value="' . s($key) . '" data-collection="' . s($source['slug']) . '">' .
            s($source['title'] . ' · ' . $source['collection'] . ' · ' . $source['slug'] . ' #' . $source['chapter']) . '</option>';
    }
    echo '</select></label><button type="button" data-cfr-vocab-reset class="cfr-vocab-reset">' . $ui('vocab_reset') . '</button></div>' .
        '<div class="cfr-vocab-tools__letters" data-cfr-vocab-alphabet ' .
        'role="group" aria-label="' . $ui('vocab_letters') . '"></div>' .
        '<p class="cfr-vocab-tools__count" data-cfr-vocab-count aria-live="polite"></p>' .
        '</section>';
}
echo '<section class="cfr-vocab-list" data-cfr-vocab-list aria-label="' . $ui('vocab_list_aria') . '">';
foreach ($items as $item) {
    echo '<article class="cfr-vocab-card" data-cfr-vocab-entry data-word="' .
        s((string)$item->word) . '" data-created="' . (int)$item->timecreated .
        '" data-sources="' . s(json_encode(array_values(array_keys($fs[(int)$item->id] ?? [])), JSON_THROW_ON_ERROR)) . '">' .
        '<div class="cfr-vocab-main"><div class="cfr-vocab-term">' .
        '<strong lang="fr">' . s((string)$item->word) . '</strong>' .
        '<span class="cfr-vocab-term__lang">' . $ui('vocab_french') . '</span></div>' .
        '<div class="cfr-vocab-buttons">';
    $sources = $fs[(int)$item->id] ?? [];
    $first = reset($sources);
    if ($first !== false) {
        $attrs = ' data-word="' . s((string)$item->word) . '" data-slug="' . s($first['slug']) .
            '" data-chapter="' . $first['chapter'] . '" data-page="' . $first['page'] .
            '" data-sesskey="' . s(sesskey()) . '"';
        if ($translationavailable) {
            echo '<button type="button" class="cfr-vocab-suggest" data-cfr-suggest' . $attrs .
                ' data-target="' . s($lang) . '">' . $ui('vocab_suggest') . '</button>';
        }
        if ($ttsavailable) {
            echo '<button type="button" class="cfr-vocab-replay" data-cfr-replay' . $attrs .
                '>' . $ui('vocab_listen') . '</button>';
        }
    }
    echo '</div>'; // Fin des actions IA.
    if ($sources) {
        echo '<div class="cfr-vocab-sources"><span>' . $ui('vocab_source') . '</span>';
        foreach ($sources as $source) {
            $url = new moodle_url('/local/campusreader/publication.php',
                ['slug' => $source['slug'], 'chapter' => $source['chapter']]);
            echo '<a href="' . s($url->out(false)) . '">' . s($source['title']) .
                ' <small>· ' . s($source['collection']) . '</small></a>';
        }
        echo '</div>';
    } else {
        echo '<p class="cfr-vocab-unavailable">' . $ui('vocab_source_missing') . '</p>';
    }
    echo '<div data-cfr-translation-status class="cfr-vocab-translation-status" aria-live="polite"></div>';
    echo '<div class="cfr-vocab-audio-status" data-cfr-audio-status aria-live="polite"></div>';
    echo '</div><div class="cfr-vocab-actions">';
    echo '<form method="post"><input type="hidden" name="sesskey" value="' . s(sesskey()) . '">' .
        '<input type="hidden" name="id" value="' . (int)$item->id . '">' .
        '<input type="hidden" name="action" value="update">' .
        '<label for="cfr-vocab-translation-' . (int)$item->id . '">' . $ui('vocab_translation') . '</label>' .
        '<input id="cfr-vocab-translation-' . (int)$item->id . '" type="text" name="translation" ' .
        'maxlength="255" value="' . s((string)$item->translation) . '">' .
        '<button type="submit" class="cfr-vocab-update">' . $ui('vocab_save') . '</button></form>';
    echo '<form method="post" class="cfr-vocab-remove-form"><input type="hidden" name="sesskey" value="' . s(sesskey()) . '">' .
        '<input type="hidden" name="id" value="' . (int)$item->id . '">' .
        '<input type="hidden" name="action" value="delete">' .
        '<button type="submit" class="cfr-vocab-remove" aria-label="' . s(get_string('cfr_c_vocab_remove_aria', 'local_campusreader', (string)$item->word)) . '">' .
        $ui('vocab_remove') . '</button></form></div></article>';
}
echo '</section>';
if ($items) { echo '<p class="cfr-vocab-no-match" data-cfr-vocab-no-match hidden>' . $ui('vocab_no_match') . '</p>'; }
echo '</main>';
echo $OUTPUT->footer();
