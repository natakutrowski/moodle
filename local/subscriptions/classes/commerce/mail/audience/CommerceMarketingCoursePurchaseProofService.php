<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail\audience;

defined('MOODLE_INTERNAL') || die();

/**
 * Positive commercial proof for marketing segmentation (not a marketing-consent check).
 *
 * Derives standard plan references from Moodle's plan/course entitlements. A1
 * historical plan 30 and the two verified bundled products have been audited
 * separately because earlier catalog migrations do not consistently expose
 * their product/plan relationships in one table.
 *
 * Fail closed for courses with no known references. Never treat enrolment,
 * trial, pending payment or an unfulfilled purchase as proof of purchase.
 */
final class CommerceMarketingCoursePurchaseProofService {
    public function __construct(private readonly \moodle_database $db) {
    }

    /** @return string[] Uppercase commercial purchase-item references. */
    public function references_for_course(int $courseid): array {
        $sql = "SELECT DISTINCT entitlement.planid
                  FROM {subscription_plan_entitlement} entitlement
                  JOIN {subscription_plan} plan ON plan.id = entitlement.planid
                 WHERE entitlement.courseid = :courseid
                   AND entitlement.roleshortname = :role
                   AND plan.is_trial = 0";
        $planids = $this->db->get_fieldset_sql($sql, [
            'courseid' => $courseid, 'role' => 'student',
        ]);
        // Only A1 (#13) has completed historical SKU mapping. Plan 30 is
        // included explicitly even if its old plan-entitlement row is absent.
        if ($courseid === 13) {
            $planids[] = 30;
        }
        $references = [];
        foreach ($planids as $planid) {
            $planid = (int)$planid;
            if ($planid <= 0) {
                continue;
            }
            $references['SUB.PLAN.' . $planid] = true;
            $references['SUBSCRIPTION-PLAN:' . $planid] = true;
        }
        if ($courseid === 13) {
            $references['BUNDLE.PACK_A1_A2'] = true;
            $references['BUNDLEA1VERBES'] = true;
        }
        $result = array_keys($references);
        sort($result, SORT_STRING);
        return $result;
    }

    /**
     * @param string[] $references Approved uppercase course-purchase references.
     */
    public function has_verified_purchase(int $userid, string $email, array $references): bool {
        if ($userid <= 0 || $references === []) {
            return false;
        }
        [$insql, $params] = $this->db->get_in_or_equal($references, SQL_PARAMS_NAMED, 'courseref');
        $identity = 'purchase.userid = :userid';
        $params['userid'] = $userid;
        $email = trim(\core_text::strtolower($email));
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $identity .= ' OR ' . $this->db->sql_equal('purchase.customeremail', ':customeremail', false, false);
            $params['customeremail'] = $email;
        }
        // Fulfilled is the only confirmed state in the A1 audit. Captured,
        // fulfillment_pending and payment_pending are deliberately excluded.
        // Refunded orders are excluded, even if the parent retained 'fulfilled'.
        $sql = "SELECT 1
                  FROM {local_subscriptions_commerce_purchase} purchase
                  JOIN {local_subscriptions_commerce_purchase_item} item
                    ON item.purchaseid = purchase.id
                 WHERE ($identity)
                   AND purchase.status = :fulfilled
                   AND UPPER(item.itemreference) $insql
                   AND NOT EXISTS (
                       SELECT 1
                         FROM {local_subscriptions_commerce_payment} payment
                         JOIN {local_subscriptions_commerce_refund} refund
                           ON refund.paymentid = payment.id
                        WHERE payment.purchaseid = purchase.id
                          AND refund.status IN ('succeeded','completed','success','refunded')
                   )";
        $params['fulfilled'] = 'fulfilled';
        return $this->db->record_exists_sql($sql, $params);
    }
}
