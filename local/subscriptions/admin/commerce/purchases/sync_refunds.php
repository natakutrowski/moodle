<?php
declare(strict_types=1);

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\payment\refund\CommercePaymentRefundImportService;
use local_subscriptions\commerce\payment\refund\CommercePaymentRefundRepository;
use local_subscriptions\commerce\payment\repository\CommercePaymentRepository;
use local_subscriptions\commerce\runtime\CommerceRuntimeFactory;

AdminSecurity::require(Capabilities::MANAGE_SUBSCRIPTIONS);
require_sesskey();

$purchaseid = required_param('id', PARAM_INT);
$paymentid = required_param('paymentid', PARAM_INT);

$returnurl = new moodle_url(
    '/local/subscriptions/admin/commerce/purchases/view.php',
    ['id' => $purchaseid]
);

try {
    $service = new CommercePaymentRefundImportService(
        new CommercePaymentRepository($DB),
        new CommercePaymentRefundRepository($DB),
        CommerceRuntimeFactory::create()->payment_providers()
    );

    $count = $service->import_for_payment(
        $paymentid,
        (int)$USER->id
    );

    redirect(
        $returnurl,
        get_string(
            'commerce_refund_sync_success',
            'local_subscriptions',
            $count
        ),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
} catch (\Throwable $exception) {
    redirect(
        $returnurl,
        get_string(
            'commerce_refund_sync_failed',
            'local_subscriptions',
            $exception->getMessage()
        ),
        null,
        \core\output\notification::NOTIFY_ERROR
    );
}
