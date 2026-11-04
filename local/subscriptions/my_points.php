<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../config.php');

use local_subscriptions\commerce\education\xp\CommercePedagogicalXpHistoryService;
use local_subscriptions\commerce\education\xp\CommercePersonalXpHistoryService;
use local_subscriptions\url\UrlFactory;

require_login();
if (isguestuser()) {
    redirect(new moodle_url('/login/index.php'));
}

/** @var stdClass $USER */
global $PAGE, $OUTPUT, $USER;

$promotionid = optional_param('promotionid', 0, PARAM_INT);
$page = optional_param('page', 0, PARAM_INT);
$userid = (int)$USER->id;

$url = new moodle_url('/local/subscriptions/my_points.php',
    $promotionid > 0 ? ['promotionid' => $promotionid] : []);
$PAGE->set_context(context_user::instance($userid));
$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');

$promohistory = CommercePedagogicalXpHistoryService::create();
$promotions = $promohistory->list_for_learner($userid);
$selectedpromotion = null;
$tabs = [];
foreach ($promotions as $promotion) {
    if ((int)$promotion['id'] === $promotionid) {
        $selectedpromotion = $promotion;
    }
    $tabs[] = [
        'name' => $promotion['name'],
        'url' => (new moodle_url('/local/subscriptions/my_points.php',
            ['promotionid' => (int)$promotion['id']]))->out(false),
        'isactive' => (int)$promotion['id'] === $promotionid,
    ];
}

if ($promotionid < 0 || ($promotionid > 0 && $selectedpromotion === null)) {
    // Never let a URL parameter expose another learner's promotion.
    throw new moodle_exception('nopermissions', 'error');
}

$iscurrentpromotion = $selectedpromotion !== null;
if ($iscurrentpromotion) {
    $data = $promohistory->get_for_learner_archive($promotionid, $userid, $page);
    if ($data === null) {
        throw new moodle_exception('nopermissions', 'error');
    }
} else {
    $data = CommercePersonalXpHistoryService::create()->get_for_user($userid, $page);
}

$title = get_string('commerce_points_history_title', 'local_subscriptions');
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->requires->css(new moodle_url('/local/subscriptions/styles/customer_hub.css', ['v' => '2026110404']));
$PAGE->navbar->add(get_string('commerce_customer_hub_title', 'local_subscriptions'), UrlFactory::my_campus());
$PAGE->navbar->add($title);

$data['title'] = $title;
$data['backurl'] = UrlFactory::my_campus()->out(false);
$data['ispersonal'] = !$iscurrentpromotion;
$data['ispromotion'] = $iscurrentpromotion;
$data['hastabs'] = $promotions !== [];
$data['personalactive'] = !$iscurrentpromotion;
$data['personalurl'] = (new moodle_url('/local/subscriptions/my_points.php'))->out(false);
$data['tabs'] = $tabs;
$data['promoiconurl'] = (new moodle_url('/local/subscriptions/pix/pain_au_chocolat.png'))->out(false);

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_subscriptions/customer/points_history', $data);
if ($data['rewardcount'] > CommercePedagogicalXpHistoryService::PAGE_SIZE) {
    echo $OUTPUT->render(new paging_bar($data['rewardcount'], $data['page'],
        CommercePedagogicalXpHistoryService::PAGE_SIZE, $url, 'page'));
}
echo $OUTPUT->footer();
