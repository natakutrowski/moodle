<?php

namespace local_subscriptions\commerce\catalog\accessscope;

use moodle_database;

/**
 * Safe admin helper for attaching an unused Legacy plan to an access scope.
 */
final class CommerceAccessScopePlanAttachmentService {
    public function __construct(private readonly moodle_database $db) {
    }

    /**
     * Returns plans that can be safely reassigned to the target scope.
     *
     * Eligible plans are inactive, have never been subscribed to, and are not
     * mapped to a Native Commerce product.
     *
     * @return array<int, object>
     */
    public function list_attachable(int $scopeid): array {
        $this->require_scope($scopeid);

        $sql = "SELECT p.id, p.name, p.accessscopeid
                  FROM {subscription_plan} p
                 WHERE p.is_active = 0
                   AND (p.accessscopeid IS NULL OR p.accessscopeid <> :scopeid)
                   AND NOT EXISTS (
                       SELECT 1
                         FROM {user_subscription} us
                        WHERE us.planid = p.id
                   )
                   AND NOT EXISTS (
                       SELECT 1
                         FROM {local_subs_commerce_prod_map} pm
                        WHERE pm.legacytable = :legacytable
                          AND pm.legacyid = p.id
                   )
              ORDER BY p.name ASC";

        return $this->db->get_records_sql($sql, [
            'scopeid' => $scopeid,
            'legacytable' => 'subscription_plan',
        ]);
    }

    /**
     * Safely reassigns an unused plan to the requested access scope.
     */
    public function attach(int $scopeid, int $planid): void {
        $this->require_scope($scopeid);
        $plan = $this->db->get_record('subscription_plan', ['id' => $planid], '*', MUST_EXIST);

        if ((int)$plan->accessscopeid === $scopeid) {
            return;
        }
        if ((int)$plan->is_active !== 0) {
            throw new \RuntimeException('Only inactive plans can be attached from an access scope.');
        }
        if ($this->db->record_exists('user_subscription', ['planid' => $planid])) {
            throw new \RuntimeException('A plan that has subscription history cannot be reassigned from an access scope.');
        }
        if ($this->db->record_exists('local_subs_commerce_prod_map', [
            'legacytable' => 'subscription_plan',
            'legacyid' => $planid,
        ])) {
            throw new \RuntimeException('A plan linked to a Native Commerce product cannot be reassigned from an access scope.');
        }

        $this->db->set_field('subscription_plan', 'accessscopeid', $scopeid, ['id' => $planid]);
        $this->db->set_field('subscription_plan', 'last_update', time(), ['id' => $planid]);
    }

    private function require_scope(int $scopeid): void {
        $this->db->get_record('subscription_access_scope', ['id' => $scopeid], 'id', MUST_EXIST);
    }
}
