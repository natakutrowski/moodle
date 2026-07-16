<?php

require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\work\rendering\WorkItemRenderer;
use local_subscriptions\crm\work\repositories\WorkItemReadRepository;
use local_subscriptions\subscription_config;

$context = AdminSecurity::require(Capabilities::VIEW_WORK_ITEMS);
$itemid = required_param('id', PARAM_INT);
$repository = new WorkItemReadRepository();
$item = $repository->get_detail($itemid);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url(subscription_config::admin_work_item_view_page(), ['id' => $itemid]));
$PAGE->set_pagelayout('admin');
$PAGE->set_title($item->reference . ' — ' . format_string($item->title));
$PAGE->set_heading(get_string('crm_work_title', 'local_subscriptions'));
$PAGE->requires->js_call_amd('local_subscriptions/work_items', 'init');

echo $OUTPUT->header();
echo html_writer::link(
    new moodle_url(subscription_config::admin_work_items_page()),
    '← ' . get_string('crm_work_back', 'local_subscriptions'),
    ['class' => 'btn btn-outline-secondary mb-3']
);
echo WorkItemRenderer::render_detail(
    $item,
    $repository->get_teams(),
    $repository->get_assignees()
);
echo $OUTPUT->footer();