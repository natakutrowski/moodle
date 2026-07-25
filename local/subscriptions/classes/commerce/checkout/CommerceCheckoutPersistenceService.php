<?php

namespace local_subscriptions\commerce\checkout;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\payment\legacy\LegacyPaymentRequestContext;
use local_subscriptions\commerce\dualwrite\CommerceDualWriteBridge;
use local_subscriptions\digital\product_manager;

/**
 * Persists provider-neutral checkout initialization data in Legacy tables.
 */
final class CommerceCheckoutPersistenceService {

    private const ALLOWED_TABLES = [
        LegacyPaymentRequestContext::TABLE_SUBSCRIPTION,
        product_manager::TABLE_PAYMENT_REQUEST,
    ];

    public function persist(string $table, int $paymentrequestid, CommerceCheckoutResult $result): void {
        global $DB;

        $table = strtolower(trim($table));
        if (!in_array($table, self::ALLOWED_TABLES, true)) {
            throw new \coding_exception('Unsupported checkout persistence table: ' . $table);
        }
        if ($paymentrequestid <= 0) {
            throw new \coding_exception('A positive payment request id is required.');
        }

        $record = (object)[
            'id' => $paymentrequestid,
            'payment_link' => $result->get_redirect_url(),
            'last_error' => null,
            'last_update' => time(),
        ];

        $providerpaymentid = $result->get_provider_payment_id();
        if ($providerpaymentid !== null) {
            $record->sessionid = $providerpaymentid;
        }

        $DB->update_record($table, $record);

        if ($table === product_manager::TABLE_PAYMENT_REQUEST) {
            CommerceDualWriteBridge::digital($paymentrequestid, 'digital_checkout_persisted');
        }
    }
}
