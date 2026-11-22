<?php
// D2.4.1 — Private manuscript preview; protected by same CRM ACL as the editor.
declare(strict_types=1);
require_once(__DIR__ . '/_bootstrap.php');
use local_subscriptions\crm\library\ReaderEditorialAnthologyService as Anth;
$id = required_param('id', PARAM_INT);
$anth = Anth::anthology($id);
$items = Anth::chapters($id);
$trail = [
    ['label' => get_string('cfr_d24_anthologies', 'local_subscriptions'),
        'url' => cfr_d21_url('anthologies.php')],
    ['label' => $anth->title, 'url' => cfr_d21_url('anthology.php', ['id' => $id])],
    ['label' => get_string('cfr_d24_preview', 'local_subscriptions'), 'url' => null],
];
$action = html_writer::link(cfr_d21_url('anthology.php', ['id' => $id]),
    get_string('cfr_d24_previewback', 'local_subscriptions'), ['class' => 'btn btn-outline-secondary']);
cfr_d21_start(get_string('cfr_d24_preview', 'local_subscriptions') . ' · ' . $anth->title,
    get_string('cfr_d24_previewhint', 'local_subscriptions'), $trail, $action);
echo '<div class="cfr-d24-preview">';
echo '<div class="cfr-d24-warning">' . s(get_string('cfr_d24_previewhint', 'local_subscriptions')) . '</div>';
echo '<header><h2>' . s($anth->title) . '</h2><p>' . s($anth->subtitle) . '</p>';
echo '<div class="cfr-d24-metadata"><span>' . s(get_string('cfr_d24_draft', 'local_subscriptions')) . '</span><span>' .
    count($items) . ' ' . s(get_string('cfr_d24_chapters', 'local_subscriptions')) . '</span></div></header>';
if (!$items) { echo '<p>' . s(get_string('cfr_d24_nochapters', 'local_subscriptions')) . '</p>'; }
foreach ($items as $item) {
    echo '<article class="cfr-d24-preview-story"><h3>' . (int)$item->position . '. ' . s($item->title) . '</h3>';
    $pages = $DB->get_records('local_cr_page', ['bookid' => $item->bookid], 'pageindex ASC', '*', 0, 100);
    foreach ($pages as $page) {
        echo '<section class="cfr-d24-preview-page"><h4>' . s($page->title) . '</h4>';
        if (!empty($page->imagefile)) {
            $url = cfr_d21_url('preview.php', ['id' => $item->bookid, 'area' => 'pageimage',
                'page' => (int)$page->pageindex]);
            echo '<img loading="lazy" alt="' . s((string)($page->imagealt ?? '')) . '" src="' .
                s($url->out(false)) . '">';
        }
        $paragraphs = json_decode($page->paragraphsjson, true);
        if (is_array($paragraphs)) {
            foreach ($paragraphs as $paragraph) {
                if (is_string($paragraph)) { echo '<p>' . nl2br(s($paragraph)) . '</p>'; }
            }
        }
        echo '</section>';
    }
    echo '</article>';
}
echo '</div>';
cfr_d21_end();
