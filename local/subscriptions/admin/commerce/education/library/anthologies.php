<?php
// D2.4.1 — private CRM anthology catalogue, draft-only.
declare(strict_types=1);
require_once(__DIR__ . '/_bootstrap.php');
use local_subscriptions\crm\library\ReaderEditorialAnthologyService as Anth;
Anth::assert_ready();
$all = Anth::list_all();
$actions = html_writer::link(cfr_d21_url('anthology.php'), get_string('cfr_d24_create', 'local_subscriptions'),
    ['class' => 'btn btn-primary']);
cfr_d21_start(get_string('cfr_d24_anthologies', 'local_subscriptions'),
    get_string('cfr_d24_listhelp', 'local_subscriptions'), [
        ['label' => get_string('cfr_d24_anthologies', 'local_subscriptions'), 'url' => null],
    ], $actions);
echo '<div class="cfr-d24-warning">' . s(get_string('cfr_d24_protect', 'local_subscriptions')) . '</div>';
echo '<div class="cfr-reader-editor__grid">';
foreach ($all as $a) {
    $items = Anth::chapters((int)$a->id);
    echo '<article class="cfr-reader-editor__card">';
    echo '<div class="cfr-reader-editor__top"><span class="cfr-reader-editor__pill">' .
        s(get_string('cfr_d24_draft', 'local_subscriptions')) . '</span></div>';
    echo '<h3>' . s($a->title) . '</h3><p>' . s($a->subtitle) . '</p>';
    echo '<small>' . s($a->slug) . ' · ' . count($items) . ' ' .
        s(get_string('cfr_d24_chapters', 'local_subscriptions')) . '</small>';
    echo '<div class="cfr-reader-editor__actions">' .
        html_writer::link(cfr_d21_url('anthology.php', ['id' => $a->id]),
            get_string('cfr_d21_open', 'local_subscriptions'), ['class' => 'btn btn-outline-secondary']) .
        html_writer::link(cfr_d21_url('anthology_preview.php', ['id' => $a->id]),
            get_string('cfr_d24_preview', 'local_subscriptions'), ['class' => 'btn btn-outline-secondary']) .
        ($items ? html_writer::link(cfr_d21_url('anthology_flipbook.php', ['id' => $a->id]),
            get_string('cfr_d242b_flipdraft', 'local_subscriptions'),
            ['class' => 'btn btn-outline-secondary', 'target' => '_blank', 'rel' => 'noopener noreferrer']) : '') .
        '</div></article>';
}
if (!$all) {
    echo '<p class="cfr-reader-editor__info">' . s(get_string('cfr_d24_none', 'local_subscriptions')) . '</p>';
}
echo '</div>';
echo '<p class="cfr-d243-link">' . html_writer::link(cfr_d21_url('publications.php'), get_string('cfr_fix_anthologies_commerce', 'local_subscriptions'), ['class' => 'btn btn-outline-secondary']) . '</p>';
cfr_d21_end();
