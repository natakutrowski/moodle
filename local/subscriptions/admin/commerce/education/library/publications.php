<?php
// CampusFR D2.4.3 — CRM publication registry, no customer grants created here.
declare(strict_types=1);
require_once(__DIR__ . '/_bootstrap.php');
use local_subscriptions\crm\library\ReaderCommercePublicationService as Publications;
if (!Publications::ready()) { throw new moodle_exception('invalidparameter'); }
$actions = html_writer::link(cfr_d21_url('publication.php'),
    get_string('cfr_d243_new', 'local_subscriptions'), ['class' => 'btn btn-primary']);
cfr_d21_start(get_string('cfr_d243_publications', 'local_subscriptions'),
    get_string('cfr_d243_intro', 'local_subscriptions'), [
        ['label' => get_string('cfr_d243_publications', 'local_subscriptions'), 'url' => null],
    ], $actions);
echo '<p class="cfr-d24-warning">' . s(get_string('cfr_d243_warning', 'local_subscriptions')) . '</p>';
echo '<div class="cfr-reader-editor__grid">';
foreach (Publications::list_all() as $pub) {
    echo '<article class="cfr-reader-editor__card"><h3>' . s($pub->slug) . '</h3>' .
        '<p>' . s(get_string('cfr_d243_kind_' . $pub->kind, 'local_subscriptions')) .
        ' · ' . s((string)$pub->status) . '</p>' .
        '<p>' . s((string)$pub->productsku ?: get_string('cfr_fix_publications_01', 'local_subscriptions')) . '</p>' .
        html_writer::link(cfr_d21_url('publication.php', ['id' => $pub->id]),
            get_string('cfr_d21_open', 'local_subscriptions'), ['class' => 'btn btn-outline-secondary']) .
        '</article>';
}
echo '</div>';
cfr_d21_end();
