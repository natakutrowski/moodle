<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\commerce\showroom\cms\CommerceShowroomCmsRepository;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomStatus;

require_login();
require_capability('local/subscriptions:manage_showrooms', context_system::instance());

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/subscriptions/admin/commerce/showrooms/index.php'));
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('commerce_showroom_cms_title', 'local_subscriptions'));
$PAGE->set_heading(get_string('commerce_showroom_cms_title', 'local_subscriptions'));

$repository = new CommerceShowroomCmsRepository($DB);
$delete = optional_param('delete', 0, PARAM_INT);
if ($delete > 0 && confirm_sesskey()) {
    $repository->delete($delete);
    redirect($PAGE->url, get_string('changessaved'));
}

$showrooms = $repository->all();

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('commerce_showroom_cms_title', 'local_subscriptions'));
echo html_writer::div(
    html_writer::link(
        new moodle_url('/local/subscriptions/admin/commerce/showrooms/edit.php'),
        '<i class="fa-solid fa-plus" aria-hidden="true"></i> '
            . get_string('commerce_showroom_cms_create', 'local_subscriptions'),
        ['class' => 'btn btn-primary']
    )
    . ' '
    . html_writer::link(
        new moodle_url('/local/subscriptions/admin/commerce/showrooms/import.php'),
        '<i class="fa-solid fa-file-import" aria-hidden="true"></i> '
            . get_string('commerce_showroom_import_create', 'local_subscriptions'),
        ['class' => 'btn btn-outline-primary']
    ),
    'mb-4 d-flex flex-wrap gap-2'
);

$table = new html_table();
$table->head = [
    get_string('name'),
    get_string('commerce_showroom_cms_key', 'local_subscriptions'),
    get_string('status'),
    get_string('commerce_showroom_cms_slugs', 'local_subscriptions'),
    get_string('actions'),
];
foreach ($showrooms as $showroom) {
    $editurl = new moodle_url('/local/subscriptions/admin/commerce/showrooms/edit.php', ['id' => $showroom->id]);
    $deleteurl = new moodle_url($PAGE->url, ['delete' => $showroom->id, 'sesskey' => sesskey()]);
    $table->data[] = [
        format_string($showroom->name),
        s($showroom->showroomkey),
        html_writer::tag(
            'span',
            CommerceShowroomStatus::label((string)$showroom->status),
            ['class' => 'badge bg-' . CommerceShowroomStatus::badge_class((string)$showroom->status)]
        ),
        s(implode(' · ', array_filter([$showroom->slugfr, $showroom->slugen, $showroom->slugru]))),
        html_writer::link($editurl, get_string('edit')) . ' · ' .
            html_writer::link(new moodle_url('/local/subscriptions/admin/commerce/showrooms/history.php', ['id' => $showroom->id]), get_string('commerce_showroom_history', 'local_subscriptions')) . ' · ' .
            html_writer::link($deleteurl, get_string('delete'), ['class' => 'text-danger']),
    ];
}
echo html_writer::table($table);
echo $OUTPUT->footer();
