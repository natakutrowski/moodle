<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../config.php');

use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\order\creditnote\CommerceCreditNotePdfService;
use local_subscriptions\commerce\order\document\CommerceOrderDocumentHistoryRepository;
use local_subscriptions\commerce\order\presentation\CommerceOrderPresentationAccessDeniedException;
use local_subscriptions\commerce\order\presentation\CommerceOrderPresentationService;

\local_subscriptions\subscription_config::guard_public_access();
require_login();

global $DB, $PAGE, $USER;

$reference = required_param('reference', PARAM_ALPHANUMEXT);
$creditnoteid = required_param('creditnoteid', PARAM_INT);
$context = context_system::instance();
$isadmin = has_capability('moodle/site:config', $context)
    || has_capability(Capabilities::MANAGE_SUBSCRIPTIONS, $context);

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

$note = (new CommerceOrderDocumentHistoryRepository($DB))
    ->credit_note($creditnoteid);

if ($note === null || $note->purchaseid !== $order->purchaseid) {
    throw new moodle_exception('commerce_credit_note_not_found', 'local_subscriptions');
}

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/subscriptions/order_credit_note.php', [
    'reference' => $reference,
    'creditnoteid' => $creditnoteid,
]));
$PAGE->set_pagelayout('embedded');

$document = (new CommerceCreditNotePdfService())->generate($note);
$content = $document->get_content();
$filename = clean_filename($document->get_filename());

if (headers_sent()) {
    throw new coding_exception(
        'Cannot send the credit-note PDF because HTTP headers were already sent.'
    );
}

\core\session\manager::write_close();

header('Content-Type: ' . $document->get_mimetype());
header('Content-Length: ' . strlen($content));
header(
    'Content-Disposition: attachment; filename="'
    . addcslashes($filename, '\"')
    . '"; filename*=UTF-8\'\''
    . rawurlencode($filename)
);
header('Cache-Control: private, must-revalidate, post-check=0, pre-check=0, max-age=0');
header('Pragma: public');
header('Expires: 0');

echo $content;
exit;
