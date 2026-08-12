<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\customer\merge;

defined('MOODLE_INTERNAL') || die();

use moodle_database;

/** Consolidates Legacy, Native Commerce and customer-owned CRM references. */
final class CommerceCustomerLegacyConsolidationService {
    public function __construct(private readonly moodle_database $database) {
    }

    /** @return array<string,int> */
    public function preview(int $sourceuserid): array {
        return [
            'legacysubscriptions' => $this->count('user_subscription', 'userid', $sourceuserid),
            'legacypaymentrequests' => $this->count('subscription_payment_request', 'userid', $sourceuserid),
            'legacyreminders' => $this->count('subscription_reminder_log', 'userid', $sourceuserid),
            'legacydigital' => $this->count('subscription_digital_payment_request', 'userid', $sourceuserid),
            'purchases' => $this->count('local_subscriptions_commerce_purchase', 'userid', $sourceuserid),
            'grants' => $this->count('local_subs_commerce_grant', 'beneficiaryuserid', $sourceuserid),
            'digitalaccesses' => $this->count('local_subs_commerce_dig_access', 'beneficiaryuserid', $sourceuserid),
            'offers' => $this->count('local_subs_commerce_offer', 'beneficiaryuserid', $sourceuserid),
        ];
    }

    /** @return array<string,int> */
    public function merge(int $sourceuserid, int $targetuserid, string $targetemail): array {
        $out = [
            'legacysubscriptions' => $this->move('user_subscription', 'userid', $sourceuserid, $targetuserid, 'last_update'),
            'legacypaymentrequests' => $this->move('subscription_payment_request', 'userid', $sourceuserid, $targetuserid, 'last_update'),
            'legacyreminders' => $this->move('subscription_reminder_log', 'userid', $sourceuserid, $targetuserid),
            'legacydigital' => $this->move('subscription_digital_payment_request', 'userid', $sourceuserid, $targetuserid, 'last_update'),
            'purchases' => $this->move('local_subscriptions_commerce_purchase', 'userid', $sourceuserid, $targetuserid, 'timemodified'),
            'grants' => $this->move('local_subs_commerce_grant', 'beneficiaryuserid', $sourceuserid, $targetuserid, 'timemodified'),
            'digitalaccesses' => $this->move('local_subs_commerce_dig_access', 'beneficiaryuserid', $sourceuserid, $targetuserid, 'timemodified'),
            'guestsessions' => $this->move('local_subs_commerce_guest', 'userid', $sourceuserid, $targetuserid, 'timemodified'),
            'offers' => $this->move('local_subs_commerce_offer', 'beneficiaryuserid', $sourceuserid, $targetuserid, 'timemodified'),
            'promouses' => $this->move('local_subs_commerce_promouse', 'userid', $sourceuserid, $targetuserid, 'timemodified'),
            'offercampaignmembers' => $this->move('local_subs_commerce_offer_campaign_member', 'userid', $sourceuserid, $targetuserid, 'timemodified'),
            'grantcampaignmembers' => $this->move('local_subs_commerce_grant_campaign_member', 'userid', $sourceuserid, $targetuserid, 'timemodified'),
            'commerceemails' => $this->move('local_subs_commerce_mail', 'userid', $sourceuserid, $targetuserid, 'timemodified'),
            'automationhistory' => $this->move('local_subscriptions_automation_history', 'userid', $sourceuserid, $targetuserid),
            'csplans' => $this->move('local_subscriptions_cs_plan', 'userid', $sourceuserid, $targetuserid, 'timemodified'),
            'worktargets' => $this->move('local_subscriptions_work_item', 'targetuserid', $sourceuserid, $targetuserid, 'timemodified'),
        ];

        // Current entitlement identity must follow the retained account/email.
        $this->rewrite_beneficiary_email('local_subs_commerce_grant', 'beneficiaryuserid', $targetuserid, $targetemail);
        $this->rewrite_beneficiary_email('local_subs_commerce_dig_access', 'beneficiaryuserid', $targetuserid, $targetemail);
        $this->rewrite_beneficiary_email('local_subs_commerce_offer', 'beneficiaryuserid', $targetuserid, $targetemail);
        $this->rewrite_member_email('local_subs_commerce_offer_campaign_member', $targetuserid, $targetemail);
        $this->rewrite_member_email('local_subs_commerce_grant_campaign_member', $targetuserid, $targetemail);

        return $out;
    }

    private function move(string $table, string $field, int $sourceuserid, int $targetuserid, ?string $timefield = null): int {
        if (!$this->has_field($table, $field)) {
            return 0;
        }
        $count = (int)$this->database->count_records($table, [$field => $sourceuserid]);
        if ($count === 0) {
            return 0;
        }
        $sets = [$field . ' = :target'];
        $params = ['target' => $targetuserid, 'source' => $sourceuserid];
        if ($timefield !== null && $this->has_field($table, $timefield)) {
            $sets[] = $timefield . ' = :now';
            $params['now'] = time();
        }
        $this->database->execute(
            'UPDATE {' . $table . '} SET ' . implode(', ', $sets) . ' WHERE ' . $field . ' = :source',
            $params
        );
        return $count;
    }

    private function rewrite_beneficiary_email(string $table, string $userfield, int $targetuserid, string $email): void {
        if ($email !== '' && $this->has_field($table, 'beneficiaryemail') && $this->has_field($table, $userfield)) {
            $this->database->set_field($table, 'beneficiaryemail', $email, [$userfield => $targetuserid]);
        }
    }

    private function rewrite_member_email(string $table, int $targetuserid, string $email): void {
        if ($email !== '' && $this->has_field($table, 'userid') && $this->has_field($table, 'email')) {
            $this->database->set_field($table, 'email', $email, ['userid' => $targetuserid]);
        }
    }

    private function count(string $table, string $field, int $userid): int {
        return $this->has_field($table, $field) ? (int)$this->database->count_records($table, [$field => $userid]) : 0;
    }

    private function has_field(string $table, string $field): bool {
        $manager = $this->database->get_manager();
        $xmldbtable = new \xmldb_table($table);
        return $manager->table_exists($xmldbtable)
            && $manager->field_exists($xmldbtable, new \xmldb_field($field));
    }
}
