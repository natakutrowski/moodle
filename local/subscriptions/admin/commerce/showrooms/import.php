<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\commerce\showroom\cms\CommerceShowroomCmsRepository;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomPackageService;

require_login();
require_capability('local/subscriptions:manage_showrooms', context_system::instance());
$PAGE->set_context(context_system::instance());
$PAGE->set_url('/local/subscriptions/admin/commerce/showrooms/import.php');
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('commerce_showroom_import', 'local_subscriptions'));
$PAGE->set_heading(get_string('commerce_showroom_import', 'local_subscriptions'));

if (data_submitted() && confirm_sesskey()) {
    $json = required_param('packagejson', PARAM_RAW);
    $service = new CommerceShowroomPackageService(new CommerceShowroomCmsRepository($DB));
    $id = $service->import($json, (int)$USER->id);
    redirect(new moodle_url('/local/subscriptions/admin/commerce/showrooms/edit.php', ['id' => $id]), get_string('changessaved'));
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('commerce_showroom_import', 'local_subscriptions'));
echo html_writer::start_tag('form', ['method' => 'post', 'class' => 'card card-body']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::tag('label', get_string('commerce_showroom_import_help', 'local_subscriptions'), ['for' => 'packagejson', 'class' => 'form-label']);
echo html_writer::tag('textarea', '', ['id' => 'packagejson', 'name' => 'packagejson', 'rows' => 20, 'class' => 'form-control font-monospace mb-3', 'required' => true]);
echo html_writer::tag('button', get_string('commerce_showroom_import', 'local_subscriptions'), ['type' => 'submit', 'class' => 'btn btn-primary align-self-start']);
echo html_writer::end_tag('form');
echo $OUTPUT->footer();
