<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../config.php');

use local_subscriptions\commerce\education\group\CommerceStudentClanService;
use local_subscriptions\url\UrlFactory;

require_login();
if (isguestuser()) {
    redirect(new moodle_url('/login/index.php'));
}

global $PAGE, $OUTPUT, $USER;
$userid = (int)$USER->id;
$promotionid = optional_param('promotionid', 0, PARAM_INT);
if ($promotionid < 0) {
    throw new moodle_exception('nopermissions', 'error');
}

$url = new moodle_url('/local/subscriptions/my_clan.php',
    $promotionid > 0 ? ['promotionid' => $promotionid] : []);
$PAGE->set_context(context_user::instance($userid));
$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');
$title = get_string('commerce_my_clan_title', 'local_subscriptions');
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->navbar->add(get_string('commerce_customer_hub_title', 'local_subscriptions'), UrlFactory::my_campus());
$PAGE->navbar->add($title);
$PAGE->requires->css(new moodle_url('/local/subscriptions/styles/my_clan.css', ['v' => '20261105b']));

$data = CommerceStudentClanService::create()->get_for_user($userid, $promotionid, $PAGE);
if ($data === null) {
    // No group, ended/revoked relation or a promotion belonging to another student.
    throw new moodle_exception('nopermissions', 'error');
}

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_subscriptions/customer/my_clan', $data);
echo $OUTPUT->footer();
