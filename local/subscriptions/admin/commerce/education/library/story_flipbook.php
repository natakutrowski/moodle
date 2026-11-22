<?php
// D2.4.2b — interactive preview of a DRAFT/working story; CRM access only.
declare(strict_types=1);
require_once(__DIR__ . '/_bootstrap.php');
require_once(__DIR__ . '/_private_flipbook.php');
use local_subscriptions\crm\library\ReaderEditorialService as Editorial;
use local_subscriptions\crm\library\ReaderPrivatePreviewRenderer as Renderer;

$id = required_param('id', PARAM_INT);
if ($id < 1) { throw new moodle_exception('invalidparameter'); }
$story = Editorial::book($id);
$collection = Editorial::collection((int)$story->collectionid);
$pages = Renderer::draft_pages($id);
$trail = [
    ['label' => $collection->title,
        'url' => cfr_d21_url('collection.php', ['id' => $collection->id])],
    ['label' => $story->title, 'url' => cfr_d21_url('book.php', ['id' => $id])],
    ['label' => get_string('cfr_d242b_storypreview', 'local_subscriptions'), 'url' => null],
];
$actions = html_writer::link(cfr_d21_url('book.php', ['id' => $id]),
    get_string('cfr_d242b_backedit', 'local_subscriptions'), ['class' => 'btn btn-outline-secondary']);
header('Cache-Control: private, no-store, max-age=0');
header('X-Robots-Tag: noindex, nofollow');
header('X-Content-Type-Options: nosniff');
cfr_d242b_require_reader();
cfr_d242c_require_ui();
cfr_d21_start(get_string('cfr_d242b_storypreview', 'local_subscriptions') . ' · ' . $story->title,
    get_string('cfr_d242b_workingnotice', 'local_subscriptions'), $trail, $actions);
$image = static fn(array $page): string => cfr_d21_url('preview.php', [
    'id' => $id, 'area' => 'pageimage', 'page' => $page['index'],
])->out(false);
echo '<section class="cfr-d242c-workspace" data-cfr-preview-shell>';
echo '<div class="cfr-d242c-toolbar cfr-d242c-toolbar--story">';
echo '<div class="cfr-d242c-context"><span class="cfr-d242c-mode">' .
    s(get_string('cfr_d242b_working', 'local_subscriptions')) . '</span>' .
    '<strong class="cfr-d242c-storytitle">' . s((string)$story->title) . '</strong></div>';
echo '<div class="cfr-d242c-toolbar__actions">';
echo '<button type="button" class="cfr-d242c-btn cfr-d242c-btn--immersive" '
    . 'data-cfr-immersive-toggle aria-pressed="false" data-cfr-open="' .
    s(get_string('cfr_d242c_immersive', 'local_subscriptions')) . '" data-cfr-close="' .
    s(get_string('cfr_d242c_exitimmersive', 'local_subscriptions')) . '">' .
    s(get_string('cfr_d242c_immersive', 'local_subscriptions')) . '</button>';
echo html_writer::link(cfr_d21_url('book.php', ['id' => $id]),
    get_string('cfr_d242b_backedit', 'local_subscriptions'),
    ['class' => 'cfr-d242c-btn cfr-d242c-btn--back']);
echo '</div></div><div class="cfr-d242c-flip-area">';
echo Renderer::render((string)$story->title, (string)$story->subtitle, $pages,
    $image, cfr_d242b_draft_extras($id), 'crm-story-' . $id,
    get_string('cfr_d242b_workingnotice', 'local_subscriptions'));
echo '</div></section>';
cfr_d21_end();
