<?php
// D2.4.2b — interactive anthology preview (working draft or immutable revision), CRM only.
declare(strict_types=1);
require_once(__DIR__ . '/_bootstrap.php');
require_once(__DIR__ . '/_private_flipbook.php');
use local_subscriptions\crm\library\ReaderEditorialAnthologyService as Anth;
use local_subscriptions\crm\library\ReaderAnthologyRevisionService as Revs;
use local_subscriptions\crm\library\ReaderPrivatePreviewRenderer as Renderer;

$id = required_param('id', PARAM_INT);
$rev = optional_param('rev', 0, PARAM_INT);
$position = optional_param('chapter', 1, PARAM_INT);
if ($id < 1 || $rev < 0 || $position < 1 || $position > 100) {
    throw new moodle_exception('invalidparameter');
}
$anth = Anth::anthology($id);
$trail = [
    ['label' => get_string('cfr_d24_anthologies', 'local_subscriptions'), 'url' => cfr_d21_url('anthologies.php')],
    ['label' => $anth->title, 'url' => cfr_d21_url('anthology.php', ['id' => $id])],
];
if ($rev > 0) {
    // The revision ID is looked up within this exact anthology; content and media are SHA checked.
    $version = Revs::get($id, $rev);
    $snapshot = Revs::snapshot($id, $rev);
    Revs::assert_media($rev, $snapshot);
    $chapters = $snapshot['chapters'];
    $mode = get_string('cfr_d242b_immutable', 'local_subscriptions') . ' ' . (int)$version->revno;
    $trail[] = ['label' => $mode,
        'url' => cfr_d21_url('anthology_revision.php', ['id' => $id, 'rev' => $rev])];
} else {
    $chapters = array_values(Anth::chapters($id));
    $mode = get_string('cfr_d242b_working', 'local_subscriptions');
}
if (!$chapters || $position > count($chapters)) { throw new moodle_exception('invalidparameter'); }
$selected = $chapters[$position - 1];
if ($rev > 0) {
    $pages = $selected['pages'];
    $booktitle = (string)$selected['title'];
    $booksubtitle = (string)($selected['subtitle'] ?? '');
    [$images, $extras] = cfr_d242b_snapshot_media($snapshot, $position, $id, $rev);
    $imageurl = static function(array $page) use ($images, $position): string {
        $key = '/story' . $position . '/pageimage/p' . $page['index'] . '/' . (string)$page['imagefile'];
        if (!isset($images[$key])) { throw new \moodle_exception('cfr_d242_mediaerror', 'local_subscriptions'); }
        return $images[$key];
    };
    $localkey = 'crm-anthrev-' . $rev . '-chapter-' . $position;
    $notice = get_string('cfr_d242b_snapshotnotice', 'local_subscriptions');
} else {
    $bookid = (int)$selected->bookid;
    $pages = Renderer::draft_pages($bookid);
    $book = \local_subscriptions\crm\library\ReaderEditorialService::book($bookid);
    $booktitle = (string)$book->title;
    $booksubtitle = (string)$book->subtitle;
    $extras = cfr_d242b_draft_extras($bookid);
    $imageurl = static fn(array $page): string => cfr_d21_url('preview.php', [
        'id' => $bookid, 'area' => 'pageimage', 'page' => $page['index'],
    ])->out(false);
    $localkey = 'crm-anthdraft-' . $id . '-chapter-' . $position;
    $notice = get_string('cfr_d242b_workingnotice', 'local_subscriptions');
}
$trail[] = ['label' => $mode . ' · ' . $position, 'url' => null];
$actions = html_writer::link($rev > 0 ?
    cfr_d21_url('anthology_revision.php', ['id' => $id, 'rev' => $rev]) :
    cfr_d21_url('anthology.php', ['id' => $id]),
    get_string('cfr_d242b_backedit', 'local_subscriptions'), ['class' => 'btn btn-outline-secondary']);
header('Cache-Control: private, no-store, max-age=0');
header('X-Robots-Tag: noindex, nofollow');
header('X-Content-Type-Options: nosniff');
cfr_d242b_require_reader();
cfr_d242c_require_ui();
cfr_d21_start(get_string('cfr_d242b_flipbook', 'local_subscriptions') . ' · ' . $anth->title,
    $notice, $trail, $actions);
// One centered, responsive CRM workspace. Public Reader and its permissions remain unchanged.
$previous = $position > 1 ? cfr_d21_url('anthology_flipbook.php',
    ['id' => $id, 'rev' => $rev, 'chapter' => $position - 1]) : null;
$next = $position < count($chapters) ? cfr_d21_url('anthology_flipbook.php',
    ['id' => $id, 'rev' => $rev, 'chapter' => $position + 1]) : null;
$backurl = $rev > 0 ? cfr_d21_url('anthology_revision.php', ['id' => $id, 'rev' => $rev]) :
    cfr_d21_url('anthology.php', ['id' => $id]);
$currenttitle = $rev > 0 ? (string)$selected['title'] : (string)$selected->title;
echo '<section class="cfr-d242c-workspace" data-cfr-preview-shell>';
echo '<div class="cfr-d242c-toolbar">';
echo '<div class="cfr-d242c-context">';
echo '<span class="cfr-d242c-mode">' . s($mode) . '</span>';
echo '<details class="cfr-d242c-toc"><summary>' .
    s(get_string('cfr_d242c_contents', 'local_subscriptions')) .
    ' <span class="cfr-d242c-toc__selected">' . $position . '/' . count($chapters) .
    ' · ' . s($currenttitle) . '</span></summary>';
echo '<nav class="cfr-d242c-toc__list" aria-label="' .
    s(get_string('cfr_d242b_chapterlist', 'local_subscriptions')) . '">';
foreach ($chapters as $i => $ch) {
    $idx = $i + 1;
    $chaptertitle = $rev > 0 ? (string)$ch['title'] : (string)$ch->title;
    $url = cfr_d21_url('anthology_flipbook.php', ['id' => $id, 'rev' => $rev, 'chapter' => $idx]);
    echo html_writer::link($url, $idx . '. ' . $chaptertitle,
        ['class' => 'cfr-d242c-toc__item' . ($idx === $position ? ' is-active' : ''),
            'aria-current' => $idx === $position ? 'page' : null]);
}
echo '</nav></details></div>';
echo '<nav class="cfr-d242c-toolbar__actions" aria-label="' .
    s(get_string('cfr_d242b_chapterlist', 'local_subscriptions')) . '">';
if ($previous) {
    echo html_writer::link($previous, '← ' . get_string('cfr_d242b_prevchapter', 'local_subscriptions'),
        ['class' => 'cfr-d242c-btn cfr-d242c-btn--secondary']);
} else {
    echo '<span class="cfr-d242c-btn cfr-d242c-btn--disabled" aria-disabled="true">← ' .
        s(get_string('cfr_d242b_prevchapter', 'local_subscriptions')) . '</span>';
}
if ($next) {
    echo html_writer::link($next, get_string('cfr_d242b_nextchapter', 'local_subscriptions') . ' →',
        ['class' => 'cfr-d242c-btn cfr-d242c-btn--secondary']);
} else {
    echo '<span class="cfr-d242c-btn cfr-d242c-btn--disabled" aria-disabled="true">' .
        s(get_string('cfr_d242b_nextchapter', 'local_subscriptions')) . ' →</span>';
}
echo '<button type="button" class="cfr-d242c-btn cfr-d242c-btn--immersive" '
    . 'data-cfr-immersive-toggle aria-pressed="false" data-cfr-open="' .
    s(get_string('cfr_d242c_immersive', 'local_subscriptions')) . '" data-cfr-close="' .
    s(get_string('cfr_d242c_exitimmersive', 'local_subscriptions')) . '">' .
    s(get_string('cfr_d242c_immersive', 'local_subscriptions')) . '</button>';
echo html_writer::link($backurl, get_string('cfr_d242b_backedit', 'local_subscriptions'),
    ['class' => 'cfr-d242c-btn cfr-d242c-btn--back']);
echo '</nav></div>';
echo '<div class="cfr-d242b-guide" role="note">' . s($notice) . '</div>';
echo '<div class="cfr-d242c-flip-area">';
echo Renderer::render($booktitle, $booksubtitle, $pages, $imageurl, $extras, $localkey, $notice);
echo '</div></section>';
cfr_d21_end();
