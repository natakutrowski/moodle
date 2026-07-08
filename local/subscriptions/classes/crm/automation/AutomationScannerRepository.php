<?php

namespace local_subscriptions\crm\automation;

use local_subscriptions\constants\Status;

defined('MOODLE_INTERNAL') || die();

final class AutomationScannerRepository {

    /**
     * Retourne les trials expirés qui n'ont pas encore déclenché l'automation trial_expired.
     */
    public function get_unprocessed_expired_trials(int $now, int $limit = 100): array {
        global $DB;

        return array_values($DB->get_records_sql("
            SELECT s.id,
                   s.userid,
                   s.planid,
                   s.start_date,
                   s.end_date,
                   s.status
              FROM {user_subscription} s
              JOIN {subscription_plan} p ON p.id = s.planid
             WHERE p.is_trial = 1
               AND s.end_date > 0
               AND s.end_date < :now
               AND s.status IN (:active, :expired)
               AND NOT EXISTS (
                    SELECT 1
                      FROM {local_subscriptions_automation_history} h
                     WHERE h.triggerkey = :triggerkey
                       AND h.entitytype = :entitytype
                       AND h.entityid = s.id
                       AND h.status IN ('".AutomationStatuses::SUCCESS."', '".AutomationStatuses::SKIPPED."')
               )
          ORDER BY s.end_date ASC, s.id ASC
        ", [
            'now' => $now,
            'active' => Status::ACTIVE,
            'expired' => Status::EXPIRED,
            'triggerkey' => AutomationTriggerKeys::TRIAL_EXPIRED,
            'entitytype' => AutomationEntityTypes::USER_SUBSCRIPTION,
        ], 0, $limit));
    }

    public function get_unprocessed_failed_payment_requests(int $limit = 100): array {
        global $DB;

        return array_values($DB->get_records_sql("
            SELECT pr.id,
                pr.userid,
                pr.planid,
                pr.price,
                pr.amount_minor,
                pr.currency,
                pr.status,
                pr.creation_date
            FROM {subscription_payment_request} pr
            WHERE pr.status = :failed
            AND NOT EXISTS (
                    SELECT 1
                    FROM {local_subscriptions_automation_history} h
                    WHERE h.triggerkey = :triggerkey
                    AND h.entitytype = :entitytype
                    AND h.entityid = pr.id
                    AND h.status IN ('".AutomationStatuses::SUCCESS."', '".AutomationStatuses::SKIPPED."')
            )
        ORDER BY pr.creation_date ASC, pr.id ASC
        ", [
            'failed' => Status::FAILED,
            'triggerkey' => AutomationTriggerKeys::PAYMENT_FAILED,
            'entitytype' => AutomationEntityTypes::PAYMENT_REQUEST,
        ], 0, $limit));
    }

    public function get_unprocessed_paid_digital_purchases(int $limit = 100): array {
        global $DB;

        return array_values($DB->get_records_sql("
            SELECT pr.id,
                pr.productid,
                pr.userid,
                pr.email,
                pr.currency,
                pr.price,
                pr.amount_minor,
                pr.status,
                pr.payment_date,
                pr.creation_date
            FROM {subscription_digital_payment_request} pr
            WHERE pr.userid IS NOT NULL
            AND pr.userid > 0
            AND pr.status IN ('paid', 'completed', 'PAID', 'COMPLETED')
            AND NOT EXISTS (
                    SELECT 1
                    FROM {local_subscriptions_automation_history} h
                    WHERE h.triggerkey = :triggerkey
                    AND h.entitytype = :entitytype
                    AND h.entityid = pr.id
                    AND h.status IN ('".AutomationStatuses::SUCCESS."', '".AutomationStatuses::SKIPPED."')
            )
        ORDER BY COALESCE(pr.payment_date, pr.creation_date) ASC, pr.id ASC
        ", [
            'triggerkey' => AutomationTriggerKeys::DIGITAL_PURCHASE_PAID,
            'entitytype' => AutomationEntityTypes::DIGITAL_PAYMENT_REQUEST,
        ], 0, $limit));
    }

    public function get_unprocessed_expired_subscriptions(int $now, int $limit = 100): array {
        global $DB;

        return array_values($DB->get_records_sql("
            SELECT s.id,
                s.userid,
                s.planid,
                s.pricepaid,
                s.currency,
                s.start_date,
                s.end_date,
                s.status
            FROM {user_subscription} s
            JOIN {subscription_plan} p ON p.id = s.planid
            WHERE COALESCE(p.is_trial, 0) = 0
            AND s.end_date > 0
            AND s.end_date < :now
            AND s.status IN ('active', 'expired')
            AND NOT EXISTS (
                    SELECT 1
                    FROM {local_subscriptions_automation_history} h
                    WHERE h.triggerkey = :triggerkey
                    AND h.entitytype = :entitytype
                    AND h.entityid = s.id
                    AND h.status IN ('".AutomationStatuses::SUCCESS."', '".AutomationStatuses::SKIPPED."')
            )
        ORDER BY s.end_date ASC, s.id ASC
        ", [
            'now' => $now,
            'triggerkey' => AutomationTriggerKeys::SUBSCRIPTION_EXPIRED,
            'entitytype' => AutomationEntityTypes::USER_SUBSCRIPTION,
        ], 0, $limit));
    }

}