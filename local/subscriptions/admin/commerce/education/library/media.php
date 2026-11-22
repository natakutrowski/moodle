<?php
// CampusFR D2.2.1 — visual private media management; same D2.2 upload rules.
declare(strict_types=1);
require_once(__DIR__ . '/_bootstrap.php');

use local_subscriptions\crm\library\ReaderEditorialService as Editorial;
use local_subscriptions\crm\library\ReaderEditorialPageService as Pages;
use local_subscriptions\crm\library\ReaderEditorialMediaService as Media;

$id = required_param('id', PARAM_INT);
$book = Editorial::book($id);
$collection = Editorial::collection((int)$book->collectionid);
$locked = (int)$book->published !== 0 || (int)$collection->published !== 0;
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();
    try {
        $area = required_param('area', PARAM_ALPHA);
        $pageindex = $area === 'pageimage' ? required_param('page', PARAM_INT) - 1 : 0;
        $result = Media::import($id, $area, $pageindex, $_FILES['mediafile'] ?? []);
        redirect(cfr_d21_url('media.php', ['id' => $id]),
            get_string($result === 'already' ? 'cfr_d22_already' : 'cfr_d22_uploaded', 'local_subscriptions'));
    } catch (\moodle_exception $exception) {
        $error = $exception->getMessage();
    }
}
$inventory = Media::inventory($id);
$pages = Pages::pages($id);
$eligible = array_values(array_filter($pages, static fn($page): bool =>
    in_array($page->kind, ['cover', 'back', 'image', 'mixed'], true)));
$trail = [
    ['label' => $collection->title, 'url' => cfr_d21_url('collection.php', ['id' => $collection->id])],
    ['label' => $book->title, 'url' => cfr_d21_url('book.php', ['id' => $id])],
    ['label' => get_string('cfr_d22_media', 'local_subscriptions'), 'url' => null],
];
cfr_d21_start(get_string('cfr_d22_media', 'local_subscriptions') . ' · ' . $book->title,
    get_string('cfr_d22_media_help', 'local_subscriptions'), $trail);
if ($error !== '') { echo '<div class="alert alert-danger" role="alert">' . s($error) . '</div>'; }
if ($locked) { echo '<div class="cfr-reader-editor__info">' . s(get_string('cfr_d21_readonly', 'local_subscriptions')) . '</div>'; }
echo '<div class="cfr-reader-editor__toolbar">';
echo '<strong>' . s(get_string('cfr_d22_inventory', 'local_subscriptions')) . '</strong>';
echo '<div class="cfr-reader-editor__actions">';
echo html_writer::link(cfr_d21_url('book.php', ['id' => $id]),
    get_string('cfr_d221_bookback', 'local_subscriptions'), ['class' => 'btn btn-outline-secondary']);
echo html_writer::link(cfr_d21_url('pages.php', ['id' => $id]),
    get_string('cfr_d22_pages', 'local_subscriptions'), ['class' => 'btn btn-outline-secondary']);
echo '</div></div>';

echo '<section class="cfr-reader-editor__media-grid" aria-label="' .
    s(get_string('cfr_d22_inventory', 'local_subscriptions')) . '">';
foreach ($inventory as $area => $files) {
    echo '<div class="cfr-reader-editor__panel cfr-reader-editor__media-card"><div class="cfr-reader-editor__top">';
    echo '<h3>' . s(get_string('cfr_d22_area_' . $area, 'local_subscriptions')) . '</h3>';
    echo '<span class="cfr-reader-editor__pill">' . count($files) . '</span></div>';
    if (!$files) {
        echo '<p class="cfr-reader-editor__hint">' . s(get_string('cfr_d221_nofile', 'local_subscriptions')) . '</p>';
    }
    foreach ($files as $file) {
        $filepath = (string)$file['filepath'];
        $filename = (string)$file['filename'];
        $pageindex = 0;
        if ($area === 'pageimage' && preg_match('#\A/p([0-9]{1,3})/\z#', $filepath, $match)) {
            $pageindex = (int)$match[1];
        }
        $label = $area === 'pageimage'
            ? get_string('cfr_d221_page', 'local_subscriptions') . ' ' . ($pageindex + 1)
            : get_string('cfr_d22_area_' . $area, 'local_subscriptions');
        if ($area === 'pageimage' && isset($pages[$pageindex])) {
            $label .= ' · ' . $pages[$pageindex]->title;
        }
        $preview = cfr_d21_url('preview.php', ['id' => $id, 'area' => $area, 'page' => $pageindex]);
        echo '<div class="cfr-reader-editor__media-item">';
        if (in_array($area, ['cover', 'pageimage'], true)) {
            echo '<img class="cfr-reader-editor__media-image" loading="lazy" src="' .
                s($preview->out(false)) . '" alt="' . s($label) . '">';
        } else if ($area === 'narration') {
            echo '<audio controls preload="none" src="' . s($preview->out(false)) . '">';
            echo s(get_string('cfr_d221_audiofallback', 'local_subscriptions')) . '</audio>';
        } else if ($area === 'video') {
            echo '<video controls preload="none" class="cfr-reader-editor__media-video" src="' .
                s($preview->out(false)) . '"></video>';
        } else if ($area === 'pdf') {
            echo html_writer::link($preview, get_string('cfr_d221_downloadpdf', 'local_subscriptions'),
                ['class' => 'btn btn-outline-secondary']);
        }
        echo '<strong>' . s($label) . '</strong><small title="' . s($filename) . '">' .
            s($filename) . ' · ' . round((int)$file['size'] / 1024) . ' Ko</small>';
        echo '</div>';
    }
    echo '</div>';
}
echo '</section>';
if (!$locked) {
    echo '<section class="cfr-reader-editor__panel"><h3>' .
        s(get_string('cfr_d22_addmedia', 'local_subscriptions')) . '</h3>';
    echo '<p class="cfr-reader-editor__hint">' . s(get_string('cfr_d221_uploadnote', 'local_subscriptions')) . '</p>';
    echo '<form method="post" enctype="multipart/form-data" action="' .
        s(cfr_d21_url('media.php', ['id' => $id])->out(false)) . '">';
    echo '<input type="hidden" name="sesskey" value="' . s(sesskey()) . '">';
    echo '<div class="cfr-reader-editor__fields"><div class="cfr-reader-editor__field">';
    echo '<label for="cfr-d22-area">' . s(get_string('cfr_d22_area', 'local_subscriptions')) . '</label>';
    echo '<select id="cfr-d22-area" name="area" class="form-control" required>';
    foreach (['cover', 'pageimage', 'narration', 'video', 'pdf'] as $area) {
        echo '<option value="' . $area . '"' . ($area === 'pageimage' && !$eligible ? ' disabled' : '') . '>' .
            s(get_string('cfr_d22_area_' . $area, 'local_subscriptions')) . '</option>';
    }
    echo '</select></div>';
    echo '<div class="cfr-reader-editor__field" id="cfr-d221-page-wrap">';
    echo '<label for="cfr-d22-page">' . s(get_string('cfr_d22_imagepage', 'local_subscriptions')) . '</label>';
    echo '<select id="cfr-d22-page" name="page" class="form-control">';
    foreach ($eligible as $page) {
        echo '<option value="' . ((int)$page->pageindex + 1) . '">' .
            ((int)$page->pageindex + 1) . '. ' . s($page->title) . '</option>';
    }
    echo '</select><small>' . s(get_string('cfr_d22_imagepage_help', 'local_subscriptions')) . '</small></div>';
    echo '<div class="cfr-reader-editor__field"><label for="cfr-d22-file">' .
        s(get_string('cfr_d22_file', 'local_subscriptions')) . '</label>';
    echo '<input id="cfr-d22-file" class="form-control" type="file" name="mediafile" ' .
        'accept=".png,.jpg,.jpeg,.webp" required></div></div>';
    echo '<p class="cfr-reader-editor__hint">' . s(get_string('cfr_d22_upload_limits', 'local_subscriptions')) . '</p>';
    echo '<button class="btn btn-primary" type="submit">' .
        s(get_string('cfr_d22_addmedia', 'local_subscriptions')) . '</button></form></section>';
    // Progressive enhancement: the server validates area/page/file independently of this JS.
    $PAGE->requires->js_init_code(<<<'JS'
(function() {
    var area = document.getElementById('cfr-d22-area');
    var pageWrap = document.getElementById('cfr-d221-page-wrap');
    var page = document.getElementById('cfr-d22-page');
    var file = document.getElementById('cfr-d22-file');
    if (!area || !pageWrap || !page || !file) { return; }
    var accepts = {
        cover: '.png,.jpg,.jpeg,.webp', pageimage: '.png,.jpg,.jpeg,.webp',
        narration: '.mp3,.m4a,.ogg', video: '.mp4,.webm', pdf: '.pdf'
    };
    var update = function() {
        var isPage = area.value === 'pageimage';
        pageWrap.hidden = !isPage;
        page.disabled = !isPage;
        page.required = isPage;
        file.setAttribute('accept', accepts[area.value] || '');
    };
    area.addEventListener('change', update);
    update();
})();
JS);
}
cfr_d21_end();
