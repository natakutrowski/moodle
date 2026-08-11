<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../config.php');

use local_subscriptions\commerce\customer\hub\CommerceCustomerHubService;
use local_subscriptions\url\UrlFactory;

require_login();
if (isguestuser()) {
    redirect(new moodle_url('/login/index.php'));
}

global $OUTPUT, $PAGE, $USER;

$context = context_user::instance((int)$USER->id);
$PAGE->set_context($context);
$PAGE->set_url(UrlFactory::my_campus());
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('commerce_customer_hub_title', 'local_subscriptions'));
$PAGE->set_heading(get_string('commerce_customer_hub_title', 'local_subscriptions'));
$PAGE->requires->css(new moodle_url('/local/subscriptions/styles/customer_hub.css'));
$PAGE->navbar->add(get_string('commerce_customer_hub_title', 'local_subscriptions'));

$data = CommerceCustomerHubService::create()->build($USER, $PAGE);
$data['title'] = get_string('commerce_customer_hub_title', 'local_subscriptions');
$data['welcome'] = get_string(
    'commerce_customer_hub_welcome',
    'local_subscriptions',
    $data['firstname']
);

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_subscriptions/customer/hub', $data);
echo $OUTPUT->footer();
