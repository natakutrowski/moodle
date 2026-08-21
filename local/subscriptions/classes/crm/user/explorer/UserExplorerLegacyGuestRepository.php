<?php

namespace local_subscriptions\crm\user\explorer;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\business\CrmBusinessSql;

/**
 * Read model for Legacy digital buyers who do not yet have a Moodle account.
 *
 * Email is the canonical temporary CRM identity. As soon as a non-deleted
 * Moodle user exists with the same email, the row disappears from this source
 * and the normal User Explorer/User360 identity wins.
 */
final class UserExplorerLegacyGuestRepository {

    public function count(UserExplorerCriteria $criteria): int {
        [$where, $params] = $this->build_where($criteria);
        if ($where === null) {
            return 0;
        }

        global $DB;
        return (int)$DB->count_records_sql(
            "SELECT COUNT(*) FROM (" . $this->base_sql($where) . ") legacyguests",
            $params
        );
    }

    public function get_records(UserExplorerCriteria $criteria, int $limit): array {
        [$where, $params] = $this->build_where($criteria);
        if ($where === null || $limit <= 0) {
            return [];
        }

        global $DB;
        $records = array_values($DB->get_records_sql(
            $this->base_sql($where),
            $params,
            0,
            $limit
        ));

        foreach ($records as $record) {
            $record->id = 0;
            $record->iscommerceguest = 1;
            $record->firstnamephonetic = '';
            $record->lastnamephonetic = '';
            $record->middlename = '';
            $record->alternatename = '';
            $record->country = '';
            $record->lang = '';
            $record->lastaccess = 0;
            $record->suspended = 0;
            $record->commercialscore = 0;
            $record->engagementscore = 0;
            $record->riskscore = 0;
            $record->globalscore = 0;
            $record->scorelevel = '';
            $record->segmentsjson = '';
            $record->opportunitiesjson = '';
            $record->recommendationsjson = '';
            $record->scoretimecreated = 0;
            $record->subscriptioncount = 0;
            $record->customer_success_open_count = 0;
            $record->customer_success_blocked_count = 0;
            $record->customer_success_highest_priority = null;
            $record->inboxconversationcount = 0;
            $record->inboxopenconversationcount = 0;
            $record->inboxunreadcount = 0;
            $record->inboxurgentcount = 0;
            $record->inboxlastmessageat = null;
        }

        return $records;
    }

    /** @return array{0:?string,1:array} */
    private function build_where(UserExplorerCriteria $criteria): array {
        // Filters that intrinsically require a Moodle account/read-model.
        if (
            $criteria->intelligence !== '' ||
            $criteria->country !== '' ||
            $criteria->tag !== '' ||
            (
                $criteria->accountstatus !== UserExplorerCriteria::ACCOUNT_ALL
                && $criteria->accountstatus !== UserExplorerCriteria::ACCOUNT_NO_MOODLE
            ) ||
            $criteria->scoremin !== null ||
            $criteria->scoremax !== null ||
            $criteria->riskmin !== null ||
            $criteria->riskmax !== null ||
            $criteria->activity !== UserExplorerCriteria::ACTIVITY_ALL ||
            $criteria->customer_success_plan_status !== '' ||
            $criteria->trendfilter->is_active()
        ) {
            return [null, []];
        }

        if (
            $criteria->hassubscription === UserExplorerCriteria::PRESENCE_YES ||
            $criteria->haspurchase === UserExplorerCriteria::PRESENCE_NO ||
            $criteria->hasinbox === UserExplorerCriteria::PRESENCE_YES ||
            $criteria->hasinboxunread === UserExplorerCriteria::PRESENCE_YES ||
            $criteria->hascustomer_success_plan === UserExplorerCriteria::PRESENCE_YES ||
            $criteria->customer_success_plan_blocked === UserExplorerCriteria::PRESENCE_YES
        ) {
            return [null, []];
        }

        if ($criteria->has_funnel_filter() && $criteria->funnelstage !== UserExplorerCriteria::FUNNEL_DIGITAL_BUYERS) {
            return [null, []];
        }

        $conditions = [];
        $params = [];

        if ($criteria->query !== '') {
            global $DB;
            $like = '%' . $DB->sql_like_escape($criteria->query) . '%';
            $conditions[] = '(' .
                $DB->sql_like('LOWER(dpr.email)', ':guestqemail', false) . ' OR ' .
                $DB->sql_like('LOWER(COALESCE(dpr.firstname, \'\'))', ':guestqfirstname', false) . ' OR ' .
                $DB->sql_like('LOWER(COALESCE(dpr.lastname, \'\'))', ':guestqlastname', false) .
            ')';
            $params['guestqemail'] = \core_text::strtolower($like);
            $params['guestqfirstname'] = \core_text::strtolower($like);
            $params['guestqlastname'] = \core_text::strtolower($like);
        }

        if ($criteria->has_funnel_filter()) {
            $date = CrmBusinessSql::digital_payment_date_expression('dpr');
            $conditions[] = "{$date} >= :guestfunnelstart AND {$date} < :guestfunnelend";
            $params['guestfunnelstart'] = $criteria->funnelstart;
            $params['guestfunnelend'] = $criteria->funnelend;
        }

        return [$conditions ? implode(' AND ', $conditions) : '1 = 1', $params];
    }

    private function base_sql(string $extra): string {
        global $DB;
        $success = CrmBusinessSql::successful_digital_payment_condition('dpr');
        $date = CrmBusinessSql::digital_payment_date_expression('dpr');
        $amount = "CASE
            WHEN COALESCE(dpr.locked_final_price, 0) > 0 THEN dpr.locked_final_price
            WHEN COALESCE(dpr.amount_minor, 0) > 0 THEN dpr.amount_minor / 100.0
            ELSE COALESCE(dpr.price, 0)
        END";
        $emailmatch = 'LOWER(' . $DB->sql_compare_text('u.email') . ') = LOWER(' . $DB->sql_compare_text('dpr.email') . ')';

        return "
            SELECT
                LOWER(dpr.email) AS emailkey,
                MAX(COALESCE(NULLIF(TRIM(dpr.firstname), ''), '')) AS firstname,
                MAX(COALESCE(NULLIF(TRIM(dpr.lastname), ''), '')) AS lastname,
                LOWER(dpr.email) AS email,
                MIN(dpr.creation_date) AS timecreated,
                COUNT(DISTINCT dpr.id) AS purchasecount,
                COUNT(DISTINCT dpr.id) AS successfulpurchasecount,
                ROUND(SUM(CASE WHEN UPPER(TRIM(dpr.currency)) = 'EUR' THEN {$amount} * 100 ELSE 0 END)) AS revenueeurminor,
                ROUND(SUM(CASE WHEN UPPER(TRIM(dpr.currency)) = 'RUB' THEN {$amount} * 100 ELSE 0 END)) AS revenuerubminor,
                MAX({$date}) AS lastpurchaseat
            FROM {subscription_digital_payment_request} dpr
            WHERE {$success}
              AND TRIM(COALESCE(dpr.email, '')) <> ''
              AND NOT EXISTS (
                    SELECT 1 FROM {user} u
                    WHERE u.deleted = 0
                      AND {$emailmatch}
              )
              AND {$extra}
            GROUP BY LOWER(dpr.email)
        ";
    }
}
