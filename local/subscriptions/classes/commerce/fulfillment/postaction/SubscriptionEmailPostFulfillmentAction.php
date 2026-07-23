<?php

namespace local_subscriptions\commerce\fulfillment\postaction;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\fulfillment\CommerceFulfillmentContext;
use local_subscriptions\commerce\fulfillment\CommerceFulfillmentResult;
use local_subscriptions\commerce\fulfillment\subscription\SubscriptionEnrolmentFulfillmentHandler;
use local_subscriptions\service\UserSubscriptionEmailService;

/**
 * Sends subscription welcome, access and receipt emails.
 */
final class SubscriptionEmailPostFulfillmentAction
    implements CommercePostFulfillmentAction {

    public function get_key(): string {
        return 'subscription_emails';
    }

    public function supports(
        CommerceFulfillmentResult $result
    ): bool {
        return $result->get_operation()->get_key()
            === SubscriptionEnrolmentFulfillmentHandler::KEY;
    }

    public function execute(
        CommerceFulfillmentResult $result,
        CommerceFulfillmentContext $context
    ): CommercePostFulfillmentActionResult {
        $metadata = $result->get_metadata();
        $userid = (int)($metadata['userid'] ?? 0);
        $subscriptionid = (int)($metadata['subscriptionid'] ?? 0);

        if ($userid <= 0 || $subscriptionid <= 0) {
            return new CommercePostFulfillmentActionResult(
                $this->get_key(),
                CommercePostFulfillmentActionResult::STATUS_SKIPPED,
                'Subscription email context is incomplete.'
            );
        }

        UserSubscriptionEmailService::resend_welcome_email(
            $userid,
            $subscriptionid
        );
        UserSubscriptionEmailService::resend_access_email(
            $userid,
            $subscriptionid
        );
        UserSubscriptionEmailService::resend_receipt(
            $userid,
            $subscriptionid
        );

        return new CommercePostFulfillmentActionResult(
            $this->get_key(),
            CommercePostFulfillmentActionResult::STATUS_COMPLETED,
            'Subscription emails were sent.',
            [
                'userid' => $userid,
                'subscriptionid' => $subscriptionid,
            ]
        );
    }
}
