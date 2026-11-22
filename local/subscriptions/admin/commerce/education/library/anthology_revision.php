<?php
// D2.4.2a — private immutable editorial revision preview.
declare(strict_types=1);
require_once(__DIR__ . '/_bootstrap.php');
use local_subscriptions\crm\library\ReaderAnthologyRevisionService as Revs;
use local_subscriptions\crm\library\ReaderEditorialAnthologyService as Anth;
$id = required_param('id', PARAM_INT);
$rev = required_param('rev', PARAM_INT);
$anth = Anth::anthology($id);
$record = Revs::get($id, $rev);
$data = Revs::snapshot($id, $rev);
$trail = [
    ['label' => get_string('cfr_d24_anthologies', 'local_subscriptions'),
        'url' => cfr_d21_url('anthologies.php')],
    ['label' => $anth->title, 'url' => cfr_d21_url('anthology.php', ['id' => $id])],
    ['label' => get_string('cfr_d242_rev', 'local_subscriptions') . ' ' . (int)$record->revno, 'url' => null],
];
$flipaction = html_writer::link(cfr_d21_url('anthology_flipbook.php', ['id' => $id, 'rev' => $rev]),
    get_string('cfr_d242b_flipversion', 'local_subscriptions'),
    ['class' => 'btn btn-primary', 'target' => '_blank', 'rel' => 'noopener noreferrer']);
cfr_d21_start(get_string('cfr_d242_rev', 'local_subscriptions') . ' ' . (int)$record->revno . ' · ' . $data['anthology']['title'],
    get_string('cfr_d242_privatenotice', 'local_subscriptions'), $trail, $flipaction);
echo '<div class="cfr-d24-warning">' . s(get_string('cfr_d242_privatenotice', 'local_subscriptions')) . '</div>';
echo '<section class="cfr-reader-editor__panel"><div class="cfr-d24-hero"><h3>' . s($data['anthology']['title']) .
    '</h3><span class="cfr-reader-editor__pill">' . s(get_string($record->state === 'approved' ? 'cfr_d242_approvedlabel' : 'cfr_d242_reviewlabel', 'local_subscriptions')) .
    '</span></div><p>' . s($data['anthology']['subtitle']) . '</p>';
echo '<p class="cfr-reader-editor__hint">SHA-256 : <code>' . s($record->digest) . '</code></p></section>';
$indexes = [];
foreach ($data['media'] as $idx => $mf) {
    $indexes[$mf['chapter']][$mf['filepath']][$mf['filename']] = $idx;
}
foreach ($data['chapters'] as $chapter) {
    $ordinal = $chapter['position'];
    echo '<section class="cfr-reader-editor__panel cfr-d242-chapter"><h3>' . (int)$ordinal . '. ' . s($chapter['title']) . '</h3>';
    foreach ($chapter['pages'] as $page) {
        echo '<article class="cfr-d24-preview-page"><h4>' . s($page['title']) . '</h4>';
        $filepath = '/story' . $ordinal . '/pageimage/p' . $page['index'] . '/';
        $name = $page['imagefile'];
        if ($name !== '' && isset($indexes[$ordinal][$filepath][$name])) {
            $url = cfr_d21_url('anthology_revision_media.php', ['id' => $id, 'rev' => $rev,
                'media' => $indexes[$ordinal][$filepath][$name]]);
            echo '<img loading="lazy" src="' . s($url->out(false)) . '" alt="' . s($page['imagealt']) . '">';
        }
        foreach ($page['paragraphs'] as $paragraph) {
            echo '<p>' . nl2br(s($paragraph)) . '</p>';
        }
        echo '</article>';
    }
    $audios = [];
    foreach ($data['media'] as $i => $mf) {
        if ($mf['chapter'] === $ordinal && $mf['area'] === 'narration') { $audios[] = $i; }
    }
    foreach ($audios as $i) {
        $url = cfr_d21_url('anthology_revision_media.php', ['id' => $id, 'rev' => $rev, 'media' => $i]);
        echo '<div class="cfr-d242-audio"><span>' . s(get_string('cfr_d242_narration', 'local_subscriptions')) .
            '</span><audio controls preload="none" src="' . s($url->out(false)) . '"></audio></div>';
    }
    echo '</section>';
}
cfr_d21_end();
