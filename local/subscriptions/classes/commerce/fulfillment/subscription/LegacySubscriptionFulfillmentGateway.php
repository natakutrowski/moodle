<?php

namespace local_subscriptions\commerce\fulfillment\subscription;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\fulfillment\CommerceFulfillmentContext;
use local_subscriptions\commerce\fulfillment\CommerceFulfillmentOperation;
use local_subscriptions\subscription_manager;

/**
 * Grants subscription access through the current Legacy subscription manager.
 */
final class LegacySubscriptionFulfillmentGateway
    implements SubscriptionFulfillmentGateway {

    public function find_by_transaction(
        string $transactionid
    ): ?\stdClass {
        global $DB;

        if (trim($transactionid) === '') {
            return null;
        }

        $record = $DB->get_record(
            'user_subscription',
            ['transactionid' => $transactionid],
            '*',
            IGNORE_MISSING
        );

        return $record ?: null;
    }

    public function fulfill(
        CommerceFulfillmentOperation $operation,
        CommerceFulfillmentContext $context
    ): array {
        $userid = (int)$operation->get_metadata_value('userid', 0);
        $planid = (int)$operation->get_metadata_value('planid', 0);
        $durationkey = trim((string)$operation->get_metadata_value(
            'duration_key',
            ''
        ));

        if ($userid <= 0 || $planid <= 0 || $durationkey === '') {
            throw new \coding_exception(
                'The subscription fulfillment operation is incomplete.'
            );
        }

        $startdate = (int)$context->get_metadata_value(
            'start_date',
            $context->get_paid_at()
        );

        $enddate = (int)$context->get_metadata_value(
            'end_date',
            0
        );

        if ($enddate <= 0) {
            $enddate = subscription_manager::get_end_date_from_duration_key(
                $durationkey,
                $startdate
            );
        }

        $discount = [
            'percent' => (int)$context->get_metadata_value(
                'discount_percent',
                0
            ),
            'amount' => (float)$context->get_metadata_value(
                'discount_amount',
                0.0
            ),
            'reason' => $context->get_metadata_value(
                'discount_reason'
            ),
        ];

        $result = subscription_manager::create_or_extend_subscription(
            $userid,
            $planid,
            $context->get_provider(),
            $context->get_transaction_id(),
            $startdate,
            $enddate,
            $context->get_amount_major(),
            $context->get_currency(),
            $context->get_paid_at(),
            (bool)$context->get_metadata_value('allow_update', false),
            $discount['percent'],
            $discount['reason'],
            $discount['amount']
        );

        subscription_manager::enrol_user_to_courses(
            $userid,
            $planid,
            $startdate,
            $enddate
        );

        return [
            'status' => $result['status'] ?? 'completed',
            'subscription' => $result['subscription'] ?? null,
            'userid' => $userid,
            'planid' => $planid,
            'start_date' => $startdate,
            'end_date' => $enddate,
        ];
    }
}
