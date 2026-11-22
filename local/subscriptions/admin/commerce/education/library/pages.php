<?php
// CampusFR D2.2.1 — ergonomic editor, unchanged D2.2 persistence contract.
declare(strict_types=1);
require_once(__DIR__ . '/_bootstrap.php');

use local_subscriptions\crm\library\ReaderEditorialService as Editorial;
use local_subscriptions\crm\library\ReaderEditorialPageService as Pages;

$id = required_param('id', PARAM_INT);
$book = Editorial::book($id);
$collection = Editorial::collection((int)$book->collectionid);
$locked = (int)$book->published !== 0 || (int)$collection->published !== 0;
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();
    try {
        $saved = Pages::save($id, required_param('page', PARAM_INT),
            required_param('kind', PARAM_ALPHA), required_param('title', PARAM_RAW_TRIMMED),
            required_param('paragraphs', PARAM_RAW), optional_param('imagealt', '', PARAM_RAW_TRIMMED));
        redirect(cfr_d21_url('pages.php', ['id' => $id, 'edit' => $saved]), get_string('changessaved'));
    } catch (\moodle_exception $exception) {
        $error = $exception->getMessage();
    }
}
$pages = Pages::pages($id);
$count = count($pages);
// An existing book opens on page 1, not on a blank "add" form.
$edit = optional_param('edit', 0, PARAM_INT);
if ($edit < 0 || $edit > $count) { throw new moodle_exception('invalidparameter'); }
$existing = $edit < $count ? $pages[$edit] : null;
$kind = $existing->kind ?? ($count === 0 ? 'cover' : 'text');
$paragraphs = '';
if ($existing) {
    $decoded = json_decode($existing->paragraphsjson, true);
    $paragraphs = is_array($decoded) ? implode("\n\n", $decoded) : '';
}
if ($error !== '') {
    $kind = required_param('kind', PARAM_ALPHA);
    $paragraphs = required_param('paragraphs', PARAM_RAW);
}
$trail = [
    ['label' => $collection->title, 'url' => cfr_d21_url('collection.php', ['id' => $collection->id])],
    ['label' => $book->title, 'url' => cfr_d21_url('book.php', ['id' => $id])],
    ['label' => get_string('cfr_d22_pages', 'local_subscriptions'), 'url' => null],
];
cfr_d21_start(get_string('cfr_d22_pages', 'local_subscriptions') . ' · ' . $book->title,
    get_string('cfr_d22_pages_help', 'local_subscriptions'), $trail);
if ($error !== '') { echo '<div class="alert alert-danger" role="alert">' . s($error) . '</div>'; }
if ($locked) { echo '<div class="cfr-reader-editor__info">' . s(get_string('cfr_d21_readonly', 'local_subscriptions')) . '</div>'; }

echo '<div class="cfr-reader-editor__toolbar">';
echo '<strong>' . (int)$count . ' ' . s(get_string('cfr_d21_pages', 'local_subscriptions')) . '</strong>';
echo '<div class="cfr-reader-editor__actions">';
echo html_writer::link(cfr_d21_url('book.php', ['id' => $id]),
    get_string('cfr_d221_bookback', 'local_subscriptions'), ['class' => 'btn btn-outline-secondary']);
echo html_writer::link(cfr_d21_url('media.php', ['id' => $id]),
    get_string('cfr_d22_media', 'local_subscriptions'), ['class' => 'btn btn-outline-secondary']);
echo html_writer::link(cfr_d21_url('layout_studio.php', ['id' => $id]),
    'Atelier visuel · PDF 16:9', ['class' => 'btn btn-outline-secondary']);
if (!$locked && $count < 100 && (!$count || $pages[$count - 1]->kind !== 'back')) {
    echo html_writer::link(cfr_d21_url('pages.php', ['id' => $id, 'edit' => $count]),
        get_string('cfr_d22_addpage', 'local_subscriptions'), ['class' => 'btn btn-primary']);
}
echo '</div></div>';

echo '<div class="cfr-reader-editor__workbench">';
echo '<nav class="cfr-reader-editor__panel cfr-reader-editor__page-nav" aria-label="' .
    s(get_string('cfr_d221_pagenav', 'local_subscriptions')) . '">';
echo '<h3>' . s(get_string('cfr_d221_pagenav', 'local_subscriptions')) . '</h3>';
if (!$pages) { echo '<p class="cfr-reader-editor__hint">' . s(get_string('cfr_d22_nopages', 'local_subscriptions')) . '</p>'; }
foreach ($pages as $page) {
    $index = (int)$page->pageindex;
    $url = cfr_d21_url('pages.php', ['id' => $id, 'edit' => $index]);
    $active = $index === $edit;
    $decoded = json_decode($page->paragraphsjson, true);
    $excerpt = is_array($decoded) && !empty($decoded[0]) ? \core_text::substr((string)$decoded[0], 0, 125) : '';
    $illustrated = !empty($page->imagefile);
    $type = get_string('cfr_d22_kind_' . $page->kind, 'local_subscriptions');
    echo '<a class="cfr-reader-editor__page-card' . ($active ? ' is-active' : '') . '" href="' .
        s($url->out(false)) . '"' . ($active ? ' aria-current="page"' : '') . '>';
    echo '<div class="cfr-reader-editor__page-thumb">';
    if ($illustrated) {
        $preview = cfr_d21_url('preview.php', ['id' => $id, 'area' => 'pageimage', 'page' => $index]);
        echo '<img loading="lazy" src="' . s($preview->out(false)) . '" alt="">';
    } else {
        echo '<span aria-hidden="true">' . ($page->kind === 'cover' ? '📖' : '✎') . '</span>';
    }
    echo '</div><div class="cfr-reader-editor__page-info"><strong>' . ($index + 1) . '. ' . s($page->title) . '</strong>';
    echo '<small>' . s($type) . ($illustrated ? ' · ' . s(get_string('cfr_d221_imageyes', 'local_subscriptions')) : '') . '</small>';
    if ($excerpt !== '') { echo '<span class="cfr-reader-editor__excerpt">' . s($excerpt) . '</span>'; }
    echo '</div></a>';
}
if (!$locked && $count < 100 && (!$count || $pages[$count - 1]->kind !== 'back')) {
    echo html_writer::link(cfr_d21_url('pages.php', ['id' => $id, 'edit' => $count]),
        '+ ' . get_string('cfr_d22_addpage', 'local_subscriptions'),
        ['class' => 'cfr-reader-editor__page-card cfr-reader-editor__page-add' . ($edit === $count ? ' is-active' : '')]);
}
echo '</nav>';

echo '<section class="cfr-reader-editor__panel cfr-reader-editor__editor"><h3>' .
    s(get_string($existing ? 'cfr_d22_editpage' : 'cfr_d22_addpage', 'local_subscriptions')) .
    ' #' . ($edit + 1) . '</h3>';
if ($existing && !empty($existing->imagefile)) {
    $preview = cfr_d21_url('preview.php', ['id' => $id, 'area' => 'pageimage', 'page' => $edit]);
    echo '<div class="cfr-reader-editor__image-inline"><img loading="lazy" src="' .
        s($preview->out(false)) . '" alt="' . s((string)$existing->imagealt) . '"></div>';
}
if ($locked) {
    if ($existing) {
        echo '<p><strong>' . s($existing->title) . '</strong></p>';
        foreach (json_decode($existing->paragraphsjson, true) ?: [] as $p) {
            echo '<p>' . s((string)$p) . '</p>';
        }
    }
} else if ($edit === $count && $count > 0 && $pages[$count - 1]->kind === 'back') {
    echo '<div class="cfr-reader-editor__info">' . s(get_string('cfr_d22_backlast', 'local_subscriptions')) . '</div>';
} else if ($edit < 100) {
    $inputtitle = $error ? required_param('title', PARAM_RAW_TRIMMED) : (string)($existing->title ?? '');
    $inputalt = $error ? optional_param('imagealt', '', PARAM_RAW_TRIMMED) : (string)($existing->imagealt ?? '');
    echo '<form method="post" action="' . s(cfr_d21_url('pages.php', ['id' => $id, 'edit' => $edit])->out(false)) . '">';
    echo '<input type="hidden" name="sesskey" value="' . s(sesskey()) . '">';
    echo '<input type="hidden" name="page" value="' . $edit . '">';
    echo '<div class="cfr-reader-editor__fields">';
    cfr_d21_field('title', get_string('name'), $inputtitle);
    echo '<div class="cfr-reader-editor__field"><label for="cfr-d22-kind">' .
        s(get_string('cfr_d22_kind', 'local_subscriptions')) . '</label>';
    echo '<select name="kind" id="cfr-d22-kind" class="form-control" required>';
    foreach (['cover', 'text', 'image', 'mixed', 'back'] as $option) {
        echo '<option value="' . $option . '"' . ($kind === $option ? ' selected' : '') . '>' .
            s(get_string('cfr_d22_kind_' . $option, 'local_subscriptions')) . '</option>';
    }
    echo '</select></div>';
    cfr_d21_field('imagealt', get_string('cfr_d22_alt', 'local_subscriptions'), $inputalt,
        false, 'text', '', false);
    echo '</div><div class="cfr-reader-editor__field"><label for="cfr-d22-paragraphs">' .
        s(get_string('cfr_d22_paragraphs', 'local_subscriptions')) . '</label>';
    echo '<textarea class="form-control cfr-reader-editor__textarea" id="cfr-d22-paragraphs" ' .
        'name="paragraphs" rows="11" required>' . s($paragraphs) . '</textarea>';
    echo '<small>' . s(get_string('cfr_d22_paragraphs_help', 'local_subscriptions')) . '</small></div>';
    echo '<div class="cfr-reader-editor__actions"><button class="btn btn-primary" type="submit">' .
        s(get_string('savechanges')) . '</button>';
    echo html_writer::link(cfr_d21_url('media.php', ['id' => $id]),
        get_string('cfr_d221_manageimages', 'local_subscriptions'), ['class' => 'btn btn-outline-secondary']);
    echo '</div></form>';
}
echo '</section></div>';
cfr_d21_end();
