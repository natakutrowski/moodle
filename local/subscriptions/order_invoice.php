<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../config.php');

use local_subscriptions\commerce\order\invoice\CommerceInvoicePdfService;
use local_subscriptions\commerce\order\presentation\CommerceOrderPresentationAccessDeniedException;
use local_subscriptions\commerce\order\presentation\CommerceOrderPresentationService;

\local_subscriptions\subscription_config::guard_public_access();
require_login();

global $PAGE, $USER;
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/subscriptions/order_invoice.php'));
$PAGE->set_pagelayout('embedded');

$reference = required_param('reference', PARAM_ALPHANUMEXT);
$isadmin = has_capability('moodle/site:config', $context);
try {
    $order = CommerceOrderPresentationService::create()->find_for_user(
        $reference,
        (int)$USER->id,
        $isadmin,
        (string)$USER->email
    );
} catch (CommerceOrderPresentationAccessDeniedException $exception) {
    throw new moodle_exception('commerce_public_access_denied', 'local_subscriptions');
}
if ($order === null) {
    throw new moodle_exception('commerce_i2_order_not_found', 'local_subscriptions');
}

$document = CommerceInvoicePdfService::create()->generate($order);
$content = $document->get_content();
$filename = clean_filename($document->get_filename());

if (headers_sent()) {
    throw new coding_exception('Cannot send the invoice PDF because HTTP headers were already sent.');
}

\core\session\manager::write_close();

header('Content-Type: ' . $document->get_mimetype());
header('Content-Length: ' . strlen($content));
header('Content-Disposition: attachment; filename="' . addcslashes($filename, '\"') . '"; filename*=UTF-8\'\'' . rawurlencode($filename));
header('Cache-Control: private, must-revalidate, post-check=0, pre-check=0, max-age=0');
header('Pragma: public');
header('Expires: 0');

echo $content;
exit;
