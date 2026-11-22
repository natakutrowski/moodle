<?php
// CampusFR D2.11 — private read-only visual workshop, 16:9 book preview.
declare(strict_types=1);
require_once(__DIR__ . '/_bootstrap.php');

use local_subscriptions\crm\library\ReaderEditorialService as Editorial;
use local_subscriptions\crm\library\ReaderEditorialPageService as Pages;

header('Cache-Control: private, no-store');
header('X-Robots-Tag: noindex, nofollow');
$id = required_param('id', PARAM_INT);
if ($id < 1) { throw new moodle_exception('invalidparameter'); }
$book = Editorial::book($id);
$collection = Editorial::collection((int)$book->collectionid);
$pages = Pages::pages($id);
if (count($pages) > 100) { throw new moodle_exception('invalidparameter'); }
$fs = get_file_storage();
$context = context_system::instance();
$cover = array_values(array_filter($fs->get_area_files((int)$context->id,
    'local_campusreader', 'cover', $id, 'id ASC', false),
    static fn($file): bool => !$file->is_directory()));
$coverpresent = count($cover) === 1 && in_array($cover[0]->get_mimetype(),
    ['image/jpeg', 'image/png', 'image/webp'], true);
$trail = [
    ['label' => $collection->title, 'url' => cfr_d21_url('collection.php', ['id' => $collection->id])],
    ['label' => $book->title, 'url' => cfr_d21_url('book.php', ['id' => $id])],
    ['label' => get_string('cfr_d211_title', 'local_subscriptions'), 'url' => null],
];
cfr_d21_start(get_string('cfr_d211_title', 'local_subscriptions') . ' · ' . $book->title,
    get_string('cfr_d211_subtitle', 'local_subscriptions'), $trail);
echo '<div class="cfr-reader-editor__info">' . s(get_string('cfr_d211_notice', 'local_subscriptions')) . '</div>';
echo '<div class="cfr-d211-toolbar"><strong>' . count($pages) . ' ' . s(get_string('cfr_d211_pages', 'local_subscriptions')) . '</strong><div class="cfr-d211-toolbar__actions">';
echo html_writer::link(cfr_d21_url('book.php', ['id' => $id]), get_string('cfr_d211_story', 'local_subscriptions'),
    ['class' => 'btn btn-outline-secondary']);
echo html_writer::link(cfr_d21_url('pages.php', ['id' => $id]), get_string('cfr_d211_edittext', 'local_subscriptions'),
    ['class' => 'btn btn-outline-secondary']);
echo html_writer::link(cfr_d21_url('media.php', ['id' => $id]), get_string('cfr_d211_images', 'local_subscriptions'),
    ['class' => 'btn btn-outline-secondary']);
if ($pages && count($pages) <= 60) {
    foreach (['reading', 'booklet'] as $mode) {
        $label = $mode === 'reading' ? 'cfr_d2112_reading' : 'cfr_d2112_booklet';
        $css = $mode === 'reading' ? 'btn btn-outline-primary' : 'btn btn-primary';
        echo '<form action="' . s(cfr_d21_url('layout_pdf.php', ['id' => $id])->out(false)) .
             '" method="post" target="_blank">';
        echo '<input type="hidden" name="sesskey" value="' . s(sesskey()) . '">';
        echo '<input type="hidden" name="mode" value="' . $mode . '">';
        echo '<button type="submit" class="' . $css . '">' .
            s(get_string($label, 'local_subscriptions')) . '</button></form>';
    }
}
echo '</div></div>';
if ($pages && count($pages) <= 60) {
    require_once(__DIR__ . '/../../../../classes/crm/library/ReaderBookletImposition.php');
    $plan = \local_subscriptions\crm\library\ReaderBookletImposition::plan($pages);
    $imposition = $plan['sheets'][0];
    echo '<section class="cfr-d2112-printbox" aria-labelledby="cfr-d2112-print-title">';
    echo '<div><strong id="cfr-d2112-print-title">' .
        s(get_string('cfr_d2112_printtitle', 'local_subscriptions')) . '</strong><p>' .
        s(get_string('cfr_d2112_instructions', 'local_subscriptions')) . '</p><small>' .
        count($plan['items']) . ' ' . s(get_string('cfr_d2112_paperpages', 'local_subscriptions')) . ' · ' .
        count($plan['sheets']) . ' ' . s(get_string('cfr_d2112_sheets', 'local_subscriptions')) .
        ' · ' . s(get_string('cfr_d2112_blanknote', 'local_subscriptions')) . '</small></div>';
    echo '<div class="cfr-d2112-imposition" aria-label="' .
        s(get_string('cfr_d2112_imposition', 'local_subscriptions')) . '">';
    foreach (['front', 'back'] as $side) {
        echo '<div><span>' . s(get_string('cfr_d2112_' . $side, 'local_subscriptions')) .
            '</span><div class="cfr-d2112-imposition__sheet"><b>' .
            (int)$imposition[$side][0] . '</b><b>' . (int)$imposition[$side][1] . '</b></div></div>';
    }
    echo '</div></section>';
}
if (!$pages) {
    echo '<div class="cfr-d211-empty">' . s(get_string('cfr_d211_empty', 'local_subscriptions')) . ' ' .
        html_writer::link(cfr_d21_url('pages.php', ['id' => $id]), get_string('cfr_d211_create', 'local_subscriptions')) . '</div>';
    cfr_d21_end();
    exit;
}
echo '<div class="cfr-d211-workspace" data-cfr-d211><nav class="cfr-d211-sidebar" aria-label="' . s(get_string('cfr_d211_previewpages', 'local_subscriptions')) . '">';
foreach ($pages as $n => $page) {
    if ((int)$page->pageindex !== $n) { throw new moodle_exception('invalidparameter'); }
    $hasimage = !empty($page->imagefile) || ($n === 0 && $coverpresent);
    $area = !empty($page->imagefile) ? 'pageimage' : 'cover';
    $imgurl = cfr_d21_url('preview.php', ['id' => $id, 'area' => $area, 'page' => $area === 'pageimage' ? $n : 0]);
    echo '<button type="button" class="cfr-d211-item' . ($n === 0 ? ' is-current' : '') .
       '" data-cfr-d211-select="' . $n . '" aria-current="' . ($n === 0 ? 'page' : 'false') . '">';
    if ($hasimage) {
        echo '<img class="cfr-d211-item__thumb" src="' . s($imgurl->out(false)) . '" alt="" loading="lazy">';
    } else {
        echo '<span class="cfr-d211-item__thumb cfr-d211-spread__missing" aria-hidden="true">✎</span>';
    }
    echo '<span class="cfr-d211-item__info"><strong>' . ($n+1) . '. ' . s((string)$page->title) .
         '</strong><br><small>' . s((string)$page->kind) . ($hasimage ? ' · ' . get_string('cfr_d211_illustrated', 'local_subscriptions') : ' · ' . get_string('cfr_d211_unillustrated', 'local_subscriptions')) . '</small></span></button>';
}
echo '</nav><div class="cfr-d211-preview">';
foreach ($pages as $n => $page) {
    $kind = in_array((string)$page->kind, ['cover', 'back', 'image', 'mixed', 'text'], true)
        ? (string)$page->kind : 'text';
    $hasimage = !empty($page->imagefile) || ($n === 0 && $coverpresent);
    $area = !empty($page->imagefile) ? 'pageimage' : 'cover';
    $imgurl = cfr_d21_url('preview.php', ['id' => $id, 'area' => $area, 'page' => $area === 'pageimage' ? $n : 0]);
    $paragraphs = json_decode((string)$page->paragraphsjson, true);
    if (!is_array($paragraphs)) { $paragraphs = []; }
    $pageclass = $hasimage ? $kind : 'text';
    echo '<article class="cfr-d211-spread cfr-d211-spread--' . s($pageclass) .
        ($n === 0 ? '' : ' cfr-d211-hidden') . '" data-cfr-d211-page="' . $n . '"' .
        ($n === 0 ? '' : ' hidden') . ' aria-label="' . s(get_string('cfr_fix_layout_page_aria', 'local_subscriptions')) . ' ' . ($n + 1) . '">';
    if ($hasimage) {
        echo '<div class="cfr-d211-spread__picture"><img src="' . s($imgurl->out(false)) .
            '" loading="lazy" alt="' . s((string)($page->imagealt ?? '')) . '"></div>';
    }
    echo '<div class="cfr-d211-spread__copy" data-cfr-d211-copy><h3>' . s((string)$page->title) . '</h3>';
    foreach ($paragraphs as $paragraph) {
        if (is_string($paragraph)) { echo '<p>' . s($paragraph) . '</p>'; }
    }
    echo '</div></article>';
}
echo '<div class="cfr-d211-caption"><div><button class="btn btn-outline-secondary" type="button" data-cfr-d211-prev>' . s(get_string('cfr_d211_prev', 'local_subscriptions')) . '</button> ' .
    '<button class="btn btn-outline-secondary" type="button" data-cfr-d211-next>' . s(get_string('cfr_d211_next', 'local_subscriptions')) . '</button> ' .
    '<strong data-cfr-d211-counter>1 / ' . count($pages) . '</strong></div>' .
    '<span class="cfr-d211-caption__notice" data-cfr-d211-notice role="status" aria-live="polite"' .
    ' data-cfr-d211-draft="' . s(get_string('cfr_d211_draft', 'local_subscriptions')) . '"' .
    ' data-cfr-d211-overflow="' . s(get_string('cfr_d211_overflow', 'local_subscriptions')) . '">' .
    s(get_string('cfr_d211_draft', 'local_subscriptions')) . '</span></div>';
echo '</div></div>';
$PAGE->requires->js_init_code(<<<'JS'
(function () {
  const root = document.querySelector('[data-cfr-d211]');
  if (!root) { return; }
  const pages = Array.from(root.querySelectorAll('[data-cfr-d211-page]'));
  const buttons = Array.from(root.querySelectorAll('[data-cfr-d211-select]'));
  const counter = root.querySelector('[data-cfr-d211-counter]');
  const notice = root.querySelector('[data-cfr-d211-notice]');
  let selected = 0;
  function render(n) {
    selected = Math.max(0, Math.min(pages.length - 1, n));
    pages.forEach((p, i) => {
      p.hidden = i !== selected;
      p.classList.toggle('cfr-d211-hidden', i !== selected);
    });
    buttons.forEach((b, i) => {
      b.classList.toggle('is-current', i === selected);
      b.setAttribute('aria-current', i === selected ? 'page' : 'false');
    });
    counter.textContent = (selected + 1) + ' / ' + pages.length;
    const text = pages[selected].querySelector('[data-cfr-d211-copy]');
    if (text) {
      // The preview warns about content that will need visual checking.
      text.classList.toggle('is-overfull', text.scrollHeight > text.clientHeight + 3);
      notice.textContent = text.classList.contains('is-overfull')
        ? notice.dataset.cfrD211Overflow
        : notice.dataset.cfrD211Draft;
    }
  }
  buttons.forEach((b, i) => b.addEventListener('click', () => render(i)));
  root.querySelector('[data-cfr-d211-prev]').addEventListener('click', () => render(selected - 1));
  root.querySelector('[data-cfr-d211-next]').addEventListener('click', () => render(selected + 1));
  render(0);
}());
JS);
cfr_d21_end();
