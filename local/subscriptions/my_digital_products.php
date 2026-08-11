<?php
// local/subscriptions/my_digital_products.php.

declare(strict_types=1);

require_once(__DIR__ . '/../../config.php');

use local_subscriptions\commerce\digital\library\CommerceDigitalLibraryService;
use local_subscriptions\output\my_digital_products\MyDigitalProductsPage;
use local_subscriptions\url\UrlFactory;

require_login();

if (isguestuser()) {
    redirect(new moodle_url('/login/index.php'));
}

global $OUTPUT, $PAGE, $USER;

$requesteduserid = optional_param('userid', 0, PARAM_INT);
$targetuserid = (int)$USER->id;

if ($requesteduserid > 0 && $requesteduserid !== (int)$USER->id) {
    $targetcontext = context_user::instance($requesteduserid, IGNORE_MISSING);
    if ($targetcontext && has_capability('moodle/user:viewdetails', $targetcontext)) {
        $targetuserid = $requesteduserid;
    } else {
        redirect(UrlFactory::my_digital_products());
    }
}

$targetuser = core_user::get_user($targetuserid, '*', MUST_EXIST);
$isadminview = (int)$targetuser->id !== (int)$USER->id;
$params = $isadminview ? ['userid' => $targetuserid] : [];
$title = $isadminview
    ? get_string('digital_library_user_title', 'local_subscriptions', fullname($targetuser))
    : get_string('digital_library_title', 'local_subscriptions');

$PAGE->set_context(context_user::instance($targetuserid));
$PAGE->set_url(UrlFactory::my_digital_products(), $params);
$PAGE->set_pagelayout('standard');
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->navbar->ignore_active();
$PAGE->navbar->add(
    get_string('commerce_customer_hub_title', 'local_subscriptions'),
    UrlFactory::my_campus()
);
$PAGE->navbar->add($title);
$PAGE->requires->css(new moodle_url('/local/subscriptions/styles/my_digital_products.css'));

$library = CommerceDigitalLibraryService::create()->get_for_customer(
    (int)$targetuser->id,
    (string)$targetuser->email
);
$page = new MyDigitalProductsPage($library, $targetuser, $isadminview);

/** @var local_subscriptions\output\renderer $renderer */
$renderer = $PAGE->get_renderer('local_subscriptions');

echo $OUTPUT->header();
echo $renderer->render_my_digital_products_page($page);
echo $OUTPUT->footer();
