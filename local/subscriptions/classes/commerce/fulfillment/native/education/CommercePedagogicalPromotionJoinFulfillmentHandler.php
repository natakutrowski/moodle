<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\fulfillment\native\education;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\education\promotionjoin\CommercePedagogicalPromotionJoinGrant;
use local_subscriptions\commerce\entitlement\domain\CommerceEntitlementGrant;
use local_subscriptions\commerce\fulfillment\native\CommerceNativeFulfillmentContext;
use local_subscriptions\commerce\fulfillment\native\CommerceNativeFulfillmentHandler;
use local_subscriptions\commerce\fulfillment\native\CommerceNativeFulfillmentResult;

/**
 * Validates the dedicated promotion_join entitlement without mutating Moodle course access.
 *
 * The actual pedagogical participation/group/reservation bridge runs immediately after
 * successful Native fulfillment in CommercePedagogicalPurchaseOrchestrator.
 */
final class CommercePedagogicalPromotionJoinFulfillmentHandler implements CommerceNativeFulfillmentHandler {
    public function get_grant_type(): string {
        return CommercePedagogicalPromotionJoinGrant::GRANT_TYPE;
    }

    public function fulfill(
        CommerceEntitlementGrant $grant,
        CommerceNativeFulfillmentContext $context
    ): CommerceNativeFulfillmentResult {
        $join = CommercePedagogicalPromotionJoinGrant::from_grant($grant);

        $payload = [
            'idempotencykey' => $grant->get_idempotency_key(),
            'userid' => $join->get_user_id(),
            'promotionid' => $join->get_promotion_id(),
            'courseid' => $join->get_course_id(),
            'productid' => $join->get_product_id(),
            'productsku' => $join->get_product_sku(),
            'ownershipsource' => $join->get_ownership_source(),
            'courseaccessmutation' => false,
        ];

        if ($context->is_dry_run()) {
            return CommerceNativeFulfillmentResult::skipped(
                $grant,
                'Dry-run: promotion join grant was validated without Moodle mutation.',
                $payload + ['dryrun' => true]
            );
        }

        return CommerceNativeFulfillmentResult::completed(
            $grant,
            $payload,
            'Promotion join entitlement was validated for pedagogical orchestration.'
        );
    }
}
