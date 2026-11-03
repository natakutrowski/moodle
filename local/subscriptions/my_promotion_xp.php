<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../config.php');

use local_subscriptions\commerce\education\xp\CommercePedagogicalXpHistoryService;
use local_subscriptions\url\UrlFactory;

require_login();
if (isguestuser()) {
    redirect(new moodle_url('/login/index.php'));
}

/** @var stdClass $USER */
global $PAGE, $OUTPUT, $USER;

$promotionid = required_param('promotionid', PARAM_INT);
$page = optional_param('page', 0, PARAM_INT);
$service = CommercePedagogicalXpHistoryService::create();
$data = $service->get_for_user($promotionid, (int)$USER->id, $page);
if ($data === null) {
    throw new moodle_exception('nopermissions', 'error');
}

$url = new moodle_url('/local/subscriptions/my_promotion_xp.php', ['promotionid' => $promotionid]);
$title = get_string('commerce_promo_xp_history_title', 'local_subscriptions');
$PAGE->set_context(context_user::instance((int)$USER->id));
$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->requires->css(new moodle_url('/local/subscriptions/styles/customer_hub.css'));
$PAGE->navbar->add(get_string('commerce_customer_hub_title', 'local_subscriptions'), UrlFactory::my_campus());
$PAGE->navbar->add($title);

$data['title'] = $title;
$data['backurl'] = UrlFactory::my_campus()->out(false);

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_subscriptions/customer/promotion_xp_history', $data);
if ($data['rewardcount'] > CommercePedagogicalXpHistoryService::PAGE_SIZE) {
    echo $OUTPUT->render(new paging_bar($data['rewardcount'], $data['page'],
        CommercePedagogicalXpHistoryService::PAGE_SIZE, $url, 'page'));
}
echo $OUTPUT->footer();
