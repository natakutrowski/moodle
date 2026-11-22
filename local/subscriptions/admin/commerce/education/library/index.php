<?php
// CampusFR D2.7 — CRM editorial hub. Displays only; existing operations are unchanged.
declare(strict_types=1);
require_once(__DIR__ . '/_bootstrap.php');

use local_subscriptions\crm\library\ReaderEditorialService as Editorial;
use local_subscriptions\crm\library\ReaderEditorialAnthologyService as Anth;
use local_subscriptions\crm\library\ReaderCommercePublicationService as Publication;

$collections = Editorial::collections();
$storycount = 0;
foreach ($collections as $collection) { $storycount += count(Editorial::books((int)$collection->id)); }
$anthcount = Anth::ready() ? count(Anth::list_all()) : 0;
$pubcount = Publication::ready() ? count(Publication::list_all()) : 0;
$action = html_writer::link(cfr_d21_url('collection.php'),
    get_string('cfr_d21_newcollection', 'local_subscriptions'), ['class' => 'btn btn-primary']);
cfr_d21_start(get_string('cfr_d21_title', 'local_subscriptions'),
    get_string('cfr_d27_intro', 'local_subscriptions'), [], $action);

echo '<section class="cfr-d27-welcome" aria-labelledby="cfr-d27-welcome-title">';
echo '<div><span class="cfr-d27-eyebrow">CampusFR · CRM</span>';
echo '<h2 id="cfr-d27-welcome-title">' . s(get_string('cfr_d27_workflow', 'local_subscriptions')) . '</h2>';
echo '<p>' . s(get_string('cfr_d27_workflow_help', 'local_subscriptions')) . '</p></div>';
echo '</section>';

$metrics = [
    [count($collections), get_string('cfr_d27_collections', 'local_subscriptions')],
    [$storycount, get_string('cfr_d27_stories', 'local_subscriptions')],
    [$anthcount, get_string('cfr_d24_anthologies', 'local_subscriptions')],
    [$pubcount, get_string('cfr_d243_publications', 'local_subscriptions')],
];
echo '<div class="cfr-d27-metrics">';
foreach ($metrics as [$amount, $caption]) {
    echo '<div class="cfr-d27-metric"><strong>' . (int)$amount . '</strong><span>' . s($caption) . '</span></div>';
}
echo '</div>';

$spaces = [
    ['stories.php', 'cfr_d27_stories', 'cfr_d27_stories_help', '01'],
    ['calendars.php', 'cfr_d27_calendars', 'cfr_d27_calendars_help', '02'],
    ['anthologies.php', 'cfr_d24_anthologies', 'cfr_d27_anthologies_help', '03'],
    ['publications.php', 'cfr_d243_publications', 'cfr_d27_commerce_help', '04'],
    ['bundles.php', 'cfr_d27_transfers', 'cfr_d27_transfers_help', '05'],
];
echo '<h3 class="cfr-d27-section-title">' . s(get_string('cfr_d27_myspaces', 'local_subscriptions')) . '</h3>';
echo '<div class="cfr-d27-workflow-grid">';
foreach ($spaces as [$url, $name, $help, $number]) {
    echo '<article class="cfr-d27-tile"><span class="cfr-d27-tile__number" aria-hidden="true">' . s($number) . '</span>';
    echo '<h4>' . s(get_string($name, 'local_subscriptions')) . '</h4>';
    echo '<p>' . s(get_string($help, 'local_subscriptions')) . '</p>';
    echo html_writer::link(cfr_d21_url($url), get_string('cfr_d27_open_space', 'local_subscriptions'),
        ['class' => 'cfr-d27-tile__link']);
    echo '</article>';
}
echo '</div>';

echo '<div class="cfr-d27-section-heading"><h3 class="cfr-d27-section-title">' .
    s(get_string('cfr_d27_collections', 'local_subscriptions')) . '</h3>' .
    '<span class="cfr-reader-editor__hint">' . s(get_string('cfr_d27_technical_note', 'local_subscriptions')) . '</span></div>';
echo '<div class="cfr-reader-editor__grid">';
foreach ($collections as $collection) {
    $books = Editorial::books((int)$collection->id);
    $draft = (int)$collection->published === 0;
    echo '<article class="cfr-reader-editor__card">';
    echo '<div class="cfr-reader-editor__top"><span class="cfr-reader-editor__pill">' .
        s(get_string($draft ? 'cfr_d21_draft' : 'cfr_d21_live', 'local_subscriptions')) . '</span>';
    echo '<span class="cfr-reader-editor__dim">#' . (int)$collection->id . '</span></div>';
    echo '<h3>' . s($collection->title) . '</h3><p class="cfr-d27-slug">' . s($collection->slug) . '</p>';
    echo '<div class="cfr-reader-editor__meta">' . count($books) . ' ' .
        s(get_string('cfr_d21_books', 'local_subscriptions')) . ' · ' . s(get_string('course')) .
        ' #' . (int)$collection->courseid . '</div>';
    echo '<div class="cfr-reader-editor__actions">' .
        html_writer::link(cfr_d21_url('collection.php', ['id' => $collection->id]),
            get_string('cfr_d21_open', 'local_subscriptions'), ['class' => 'btn btn-outline-secondary']) .
        html_writer::link(cfr_d21_url('calendar.php', ['id' => $collection->id]),
            get_string('cfr_d23_calendar', 'local_subscriptions'), ['class' => 'btn btn-outline-secondary']) .
        '</div></article>';
}
if (!$collections) {
    echo '<p class="cfr-reader-editor__info">' . s(get_string('cfr_d21_empty', 'local_subscriptions')) . '</p>';
}
echo '</div>';
cfr_d21_end();
