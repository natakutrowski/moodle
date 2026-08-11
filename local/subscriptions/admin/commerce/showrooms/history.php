<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\commerce\showroom\cms\CommerceShowroomCmsRepository;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomPublicationService;

require_login();
require_capability('local/subscriptions:manage_showrooms', context_system::instance());

$id = required_param('id', PARAM_INT);
$restore = optional_param('restore', 0, PARAM_INT);
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/subscriptions/admin/commerce/showrooms/history.php', ['id' => $id]));
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('commerce_showroom_history', 'local_subscriptions'));
$PAGE->set_heading(get_string('commerce_showroom_history', 'local_subscriptions'));

$repository = new CommerceShowroomCmsRepository($DB);
$showroom = $repository->get($id);
if ($showroom === null) {
    throw new moodle_exception('invalidrecord');
}
$service = new CommerceShowroomPublicationService($DB, $repository);
if ($restore > 0 && confirm_sesskey()) {
    $service->restore($id, $restore, (int)$USER->id);
    redirect(new moodle_url('/local/subscriptions/admin/commerce/showrooms/edit.php', ['id' => $id]),
        get_string('commerce_showroom_revision_restored', 'local_subscriptions'));
}

$revisions = $service->revisions($id);
echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($showroom->name) . ' — ' . get_string('commerce_showroom_history', 'local_subscriptions'));
echo html_writer::div(html_writer::link(
    new moodle_url('/local/subscriptions/admin/commerce/showrooms/edit.php', ['id' => $id]),
    get_string('back'),
    ['class' => 'btn btn-outline-secondary']
), 'mb-4');

$table = new html_table();
$table->head = [
    get_string('commerce_showroom_revision', 'local_subscriptions'),
    get_string('commerce_showroom_revision_action', 'local_subscriptions'),
    get_string('date'),
    get_string('user'),
    get_string('commerce_showroom_revision_note', 'local_subscriptions'),
    get_string('actions'),
];
foreach ($revisions as $revision) {
    $user = $revision->usercreated ? core_user::get_user((int)$revision->usercreated) : null;
    $restoreurl = new moodle_url($PAGE->url, [
        'restore' => (int)$revision->id,
        'sesskey' => sesskey(),
    ]);
    $table->data[] = [
        '#' . (int)$revision->revisionno,
        s((string)$revision->action),
        userdate((int)$revision->timecreated),
        $user ? fullname($user) : '—',
        format_text((string)$revision->note, FORMAT_PLAIN),
        html_writer::link($restoreurl, get_string('commerce_showroom_restore_revision', 'local_subscriptions'), [
            'class' => 'btn btn-sm btn-outline-primary',
        ]),
    ];
}
echo $revisions ? html_writer::table($table) : $OUTPUT->notification(
    get_string('commerce_showroom_no_revisions', 'local_subscriptions'),
    'info'
);
echo $OUTPUT->footer();
