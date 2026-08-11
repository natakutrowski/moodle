<?php

namespace local_subscriptions\crm\user\explorer;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\user\UserExplorerFilter;
use local_subscriptions\crm\success\plans\domain\CustomerSuccessPlanStatus;
use local_subscriptions\crm\success\plans\domain\CustomerSuccessPlanStepStatus;
use local_subscriptions\crm\business\CrmBusinessSql;

final class UserExplorerRepository {

    private const SCORE_TABLE =
        'local_subscriptions_crm_score';

    private const TAG_TABLE =
        'local_subscriptions_user_tag';

    private const INBOX_CONTACT_TABLE =
        'local_subscriptions_inbox_contact';

    private const INBOX_THREAD_TABLE =
        'local_subscriptions_inbox_thread';

    public function count(
        UserExplorerCriteria $criteria
    ): int {
        global $DB;

        [$joins, $where, $params] =
            $this->build_filter_parts($criteria);

        $sql = "
            SELECT COUNT(DISTINCT u.id)
              FROM {user} u

              {$this->latest_score_join()}

              {$joins}

             WHERE {$where}
        ";

        return (int)$DB->count_records_sql(
            $sql,
            $params
        );
    }

    public function get_records(
        UserExplorerCriteria $criteria,
        bool $includeinbox = false
    ): array {
        return $this->fetch_records(
            $criteria,
            $criteria->offset(),
            $criteria->perpage,
            $includeinbox
        );
    }

    public function get_records_for_export(
        UserExplorerCriteria $criteria,
        int $limit = 5000,
        bool $includeinbox = false
    ): array {
        return $this->fetch_records(
            $criteria,
            0,
            max(
                1,
                min(5000, $limit)
            ),
            $includeinbox
        );
    }

    private function fetch_records(
        UserExplorerCriteria $criteria,
        int $offset,
        int $limit,
        bool $includeinbox
    ): array {
        global $DB;

        if (!$includeinbox) {
            $criteria =
                $criteria->without_inbox();
        }

        [$joins, $where, $params] =
            $this->build_filter_parts(
                $criteria
            );

        $order = UserExplorerSort::sql(
            $criteria->sort
        );

        $inboxselect = '';
        $inboxjoin = '';

        if ($includeinbox) {
            $inboxselect = "
                ,
                COALESCE(
                    inbox.conversationcount,
                    0
                ) AS inboxconversationcount,

                COALESCE(
                    inbox.openconversationcount,
                    0
                ) AS inboxopenconversationcount,

                COALESCE(
                    inbox.unreadcount,
                    0
                ) AS inboxunreadcount,

                COALESCE(
                    inbox.urgentcount,
                    0
                ) AS inboxurgentcount,

                inbox.lastmessageat
                    AS inboxlastmessageat
            ";

            $inboxjoin =
                $this->inbox_summary_join();
        }

        $sql = "
            SELECT
                u.id,
                u.firstname,
                u.lastname,
                u.firstnamephonetic,
                u.lastnamephonetic,
                u.middlename,
                u.alternatename,
                u.email,
                u.country,
                u.lang,
                u.timecreated,
                u.lastaccess,
                u.suspended,

                COALESCE(
                    score.commercialscore,
                    0
                ) AS commercialscore,

                COALESCE(
                    score.engagementscore,
                    0
                ) AS engagementscore,

                COALESCE(
                    score.riskscore,
                    0
                ) AS riskscore,

                COALESCE(
                    score.globalscore,
                    0
                ) AS globalscore,

                score.level AS scorelevel,
                score.segmentsjson,
                score.opportunitiesjson,
                score.recommendationsjson,
                score.timecreated
                    AS scoretimecreated,

                COALESCE(
                    subscriptions.subscriptioncount,
                    0
                ) AS subscriptioncount,

                COALESCE(
                    purchases.purchasecount,
                    0
                ) AS purchasecount,

                COALESCE(
                    purchases.successfulpurchasecount,
                    0
                ) AS successfulpurchasecount,

                COALESCE(
                    purchases.revenueeurminor,
                    0
                ) AS revenueeurminor,

                COALESCE(
                    purchases.revenuerubminor,
                    0
                ) AS revenuerubminor,

                purchases.lastpurchaseat AS lastpurchaseat,

                COALESCE(
                    successplans.openplancount,
                    0
                ) AS customer_success_open_count,

                COALESCE(
                    successplans.blockedstepcount,
                    0
                ) AS customer_success_blocked_count,

                successplans.highestpriority
                    AS customer_success_highest_priority

                {$inboxselect}

            FROM {user} u

            {$this->latest_score_join()}

            LEFT JOIN (
                    SELECT
                        userid,
                        COUNT(*) AS subscriptioncount
                    FROM {user_subscription}
                GROUP BY userid
            ) subscriptions
                ON subscriptions.userid = u.id

            {$this->digital_purchase_count_join()}

            {$inboxjoin}

            {$this->customer_success_plan_summary_join()}

            {$joins}

            WHERE {$where}

            ORDER BY {$order}
        ";

        return array_values(
            $DB->get_records_sql(
                $sql,
                $params,
                $offset,
                $limit
            )
        );
    }

    public function get_tags_by_userids(
        array $userids
    ): array {
        global $DB;

        $userids = array_values(array_unique(array_map(
            'intval',
            $userids
        )));

        if (!$userids) {
            return [];
        }

        [$insql, $params] = $DB->get_in_or_equal(
            $userids,
            SQL_PARAMS_NAMED,
            'taguserid'
        );

        $records = $DB->get_records_select(
            self::TAG_TABLE,
            "userid {$insql}",
            $params,
            'userid ASC, tag ASC, id ASC'
        );

        $result = [];

        foreach ($records as $record) {
            $userid = (int)$record->userid;

            if (!isset($result[$userid])) {
                $result[$userid] = [];
            }

            $result[$userid][] = (string)$record->tag;
        }

        return $result;
    }

    public function get_available_countries(): array {
        global $DB;

        $records = $DB->get_records_sql("
            SELECT DISTINCT country
              FROM {user}
             WHERE deleted = 0
               AND country IS NOT NULL
               AND country <> ''
          ORDER BY country ASC
        ");

        return array_values(array_map(
            static fn(\stdClass $record): string =>
                (string)$record->country,
            $records
        ));
    }

    public function get_available_tags(): array {
        global $DB;

        return array_values($DB->get_fieldset_sql("
            SELECT DISTINCT tag
              FROM {" . self::TAG_TABLE . "}
          ORDER BY tag ASC
        "));
    }

    private function build_filter_parts(
        UserExplorerCriteria $criteria
    ): array {
        global $DB;

        $joins = '';
        $conditions = [
            'u.deleted = 0',
            'u.id <> :guestid',
        ];

        $params = [
            'guestid' => guest_user()->id,
        ];

        if ($criteria->query !== '') {
            $like = '%' .
                $DB->sql_like_escape(
                    $criteria->query
                ) .
                '%';

            $conditions[] = '(' .
                $DB->sql_like(
                    'u.firstname',
                    ':firstnamequery',
                    false,
                    false
                ) .
                ' OR ' .
                $DB->sql_like(
                    'u.lastname',
                    ':lastnamequery',
                    false,
                    false
                ) .
                ' OR ' .
                $DB->sql_like(
                    'u.email',
                    ':emailquery',
                    false,
                    false
                ) .
                ')';

            $params['firstnamequery'] = $like;
            $params['lastnamequery'] = $like;
            $params['emailquery'] = $like;
        }

        if ($criteria->country !== '') {
            $conditions[] = 'u.country = :country';
            $params['country'] = $criteria->country;
        }

        if ($criteria->accountstatus ===
            UserExplorerCriteria::ACCOUNT_ACTIVE
        ) {
            $conditions[] = 'u.suspended = 0';
        }

        if ($criteria->accountstatus ===
            UserExplorerCriteria::ACCOUNT_SUSPENDED
        ) {
            $conditions[] = 'u.suspended = 1';
        }

        if ($criteria->tag !== '') {
            $joins .= "
                JOIN {" . self::TAG_TABLE . "} filteredtag
                  ON filteredtag.userid = u.id
                 AND filteredtag.tag = :filteredtag
            ";

            $params['filteredtag'] = $criteria->tag;
        }

        if (
            UserExplorerFilter::is_segment(
                $criteria->intelligence
            )
        ) {
            $conditions[] = $DB->sql_like(
                'score.segmentsjson',
                ':segment',
                false,
                false
            );

            $params['segment'] =
                '%"' .
                $DB->sql_like_escape(
                    $criteria->intelligence
                ) .
                '"%';
        }

        if (
            UserExplorerFilter::is_opportunity(
                $criteria->intelligence
            )
        ) {
            $conditions[] = $DB->sql_like(
                'score.opportunitiesjson',
                ':opportunity',
                false,
                false
            );

            $params['opportunity'] =
                '%"' .
                $DB->sql_like_escape(
                    $criteria->intelligence
                ) .
                '"%';
        }

        if ($criteria->scoremin !== null) {
            $conditions[] =
                'COALESCE(score.globalscore, 0) >= :scoremin';

            $params['scoremin'] = $criteria->scoremin;
        }

        if ($criteria->scoremax !== null) {
            $conditions[] =
                'COALESCE(score.globalscore, 0) <= :scoremax';

            $params['scoremax'] = $criteria->scoremax;
        }

        if ($criteria->riskmin !== null) {
            $conditions[] =
                'COALESCE(score.riskscore, 0) >= :riskmin';

            $params['riskmin'] = $criteria->riskmin;
        }

        if ($criteria->riskmax !== null) {
            $conditions[] =
                'COALESCE(score.riskscore, 0) <= :riskmax';

            $params['riskmax'] = $criteria->riskmax;
        }

        if (
            $criteria->hassubscription ===
            UserExplorerCriteria::PRESENCE_YES
        ) {
            $conditions[] = "
                (
                    EXISTS (
                        SELECT 1
                        FROM {user_subscription} filteredsubscription
                        WHERE filteredsubscription.userid = u.id
                    )
                    OR EXISTS (
                        SELECT 1
                        FROM {local_subscriptions_commerce_purchase} filteredcoursepurchase
                        WHERE filteredcoursepurchase.userid = u.id
                        AND LOWER(filteredcoursepurchase.type) IN (
                            'course', 'course_access', 'subscription'
                        )
                    )
                    OR EXISTS (
                        SELECT 1
                        FROM {local_subscriptions_commerce_purchase_item} filteredupgradeitem
                        JOIN {local_subscriptions_commerce_purchase} filteredupgradepurchase
                          ON filteredupgradepurchase.id = filteredupgradeitem.purchaseid
                        WHERE filteredupgradepurchase.userid = u.id
                        AND " . $DB->sql_like(
                            'filteredupgradeitem.metadatajson',
                            ':explorerupgradeoperation',
                            false,
                            false
                        ) . "
                    )
                )
            ";
            $params['explorerupgradeoperation'] = '%\"operation\":\"upgrade\"%';
        }

        if (
            $criteria->hassubscription ===
            UserExplorerCriteria::PRESENCE_NO
        ) {
            $conditions[] = "
                NOT (
                    EXISTS (
                        SELECT 1
                        FROM {user_subscription} filteredsubscription
                        WHERE filteredsubscription.userid = u.id
                    )
                    OR EXISTS (
                        SELECT 1
                        FROM {local_subscriptions_commerce_purchase} filteredcoursepurchase
                        WHERE filteredcoursepurchase.userid = u.id
                        AND LOWER(filteredcoursepurchase.type) IN (
                            'course', 'course_access', 'subscription'
                        )
                    )
                    OR EXISTS (
                        SELECT 1
                        FROM {local_subscriptions_commerce_purchase_item} filteredupgradeitem
                        JOIN {local_subscriptions_commerce_purchase} filteredupgradepurchase
                          ON filteredupgradepurchase.id = filteredupgradeitem.purchaseid
                        WHERE filteredupgradepurchase.userid = u.id
                        AND " . $DB->sql_like(
                            'filteredupgradeitem.metadatajson',
                            ':explorernoUpgradeOperation',
                            false,
                            false
                        ) . "
                    )
                )
            ";
            $params['explorernoUpgradeOperation'] = '%\"operation\":\"upgrade\"%';
        }

        if (
            $criteria->haspurchase !==
            UserExplorerCriteria::PRESENCE_ALL
        ) {
            $purchaseemailcompare =
                $DB->sql_compare_text('filteredpurchase.customeremail') .
                ' = ' .
                $DB->sql_compare_text('u.email');

            $purchaseexists = "
                EXISTS (
                    SELECT 1
                    FROM {local_subscriptions_commerce_purchase}
                        filteredpurchase
                    WHERE (
                        filteredpurchase.userid = u.id
                        OR (
                            filteredpurchase.userid IS NULL
                            AND {$purchaseemailcompare}
                        )
                    )
                    AND EXISTS (
                        SELECT 1
                        FROM {local_subscriptions_commerce_payment}
                            filteredpayment
                        WHERE filteredpayment.purchaseid =
                            filteredpurchase.id
                        AND LOWER(filteredpayment.status) IN (
                            'paid',
                            'completed',
                            'captured',
                            'succeeded',
                            'success'
                        )
                    )
                )
            ";

            $conditions[] =
                $criteria->haspurchase ===
                UserExplorerCriteria::PRESENCE_YES
                    ? $purchaseexists
                    : "NOT ({$purchaseexists})";
        }

        if (
            $criteria->hasinbox !==
            UserExplorerCriteria::PRESENCE_ALL
        ) {
            $inboxexists = "
                EXISTS (
                    SELECT 1
                    FROM {" . self::INBOX_CONTACT_TABLE . "}
                        filteredinboxcontact
                    JOIN {" . self::INBOX_THREAD_TABLE . "}
                        filteredinboxthread
                        ON filteredinboxthread.contactid =
                        filteredinboxcontact.id
                    WHERE filteredinboxcontact.matcheduserid = u.id
                    AND filteredinboxthread.locallydeleted = 0
                )
            ";

            $conditions[] =
                $criteria->hasinbox ===
                UserExplorerCriteria::PRESENCE_YES
                    ? $inboxexists
                    : "NOT ({$inboxexists})";
        }

        if (
            $criteria->hasinboxunread !==
            UserExplorerCriteria::PRESENCE_ALL
        ) {
            $inboxunreadexists = "
                EXISTS (
                    SELECT 1
                    FROM {" . self::INBOX_CONTACT_TABLE . "}
                        filteredunreadcontact
                    JOIN {" . self::INBOX_THREAD_TABLE . "}
                        filteredunreadthread
                        ON filteredunreadthread.contactid =
                        filteredunreadcontact.id
                    WHERE filteredunreadcontact.matcheduserid = u.id
                    AND filteredunreadthread.locallydeleted = 0
                    AND filteredunreadthread.unreadcount > 0
                )
            ";

            $conditions[] =
                $criteria->hasinboxunread ===
                UserExplorerCriteria::PRESENCE_YES
                    ? $inboxunreadexists
                    : "NOT ({$inboxunreadexists})";
        }


        if (
            $criteria->activity ===
            UserExplorerCriteria::ACTIVITY_NEVER
        ) {
            $conditions[] = 'COALESCE(u.lastaccess, 0) = 0';
        }

        $activitydays = match ($criteria->activity) {
            UserExplorerCriteria::ACTIVITY_7_DAYS => 7,
            UserExplorerCriteria::ACTIVITY_30_DAYS => 30,
            UserExplorerCriteria::ACTIVITY_90_DAYS => 90,
            default => 0,
        };

        if ($activitydays > 0) {
            $conditions[] = 'u.lastaccess >= :activitysince';
            $params['activitysince'] =
                time() - ($activitydays * DAYSECS);
        }

        if (
            $criteria->hascustomer_success_plan !==
            UserExplorerCriteria::PRESENCE_ALL
        ) {
            [$openinsql, $openparams] =
                $DB->get_in_or_equal(
                    CustomerSuccessPlanStatus::open(),
                    SQL_PARAMS_NAMED,
                    'explorercsopen'
                );

            $exists = "
                EXISTS (
                    SELECT 1
                    FROM {local_subscriptions_cs_plan}
                        filteredcsplan
                    WHERE filteredcsplan.userid = u.id
                    AND filteredcsplan.status {$openinsql}
                )
            ";

            $conditions[] =
                $criteria->hascustomer_success_plan ===
                UserExplorerCriteria::PRESENCE_YES
                    ? $exists
                    : "NOT ({$exists})";

            $params += $openparams;
        }

        if (
            $criteria->customer_success_plan_blocked !==
            UserExplorerCriteria::PRESENCE_ALL
        ) {
            $exists = "
                EXISTS (
                    SELECT 1
                    FROM {local_subscriptions_cs_plan}
                        filteredblockedplan
                    JOIN {local_subscriptions_cs_step}
                        filteredblockedstep
                        ON filteredblockedstep.planid =
                        filteredblockedplan.id
                    WHERE filteredblockedplan.userid = u.id
                    AND filteredblockedstep.status =
                        :explorercsblocked
                )
            ";

            $conditions[] =
                $criteria->customer_success_plan_blocked ===
                UserExplorerCriteria::PRESENCE_YES
                    ? $exists
                    : "NOT ({$exists})";

            $params['explorercsblocked'] =
                CustomerSuccessPlanStepStatus::BLOCKED;
        }

        if (
            $criteria->customer_success_plan_status !== ''
        ) {
            $conditions[] = "
                EXISTS (
                    SELECT 1
                    FROM {local_subscriptions_cs_plan}
                        filteredcsstatus
                    WHERE filteredcsstatus.userid = u.id
                    AND filteredcsstatus.status =
                        :explorercsstatus
                )
            ";

            $params['explorercsstatus'] =
                $criteria->customer_success_plan_status;
        }

        $this->apply_funnel_filter(
            $criteria,
            $conditions,
            $params
        );

        $this->apply_trend_filter(
            $criteria,
            $conditions,
            $params
        );

        return [
            $joins,
            implode(' AND ', $conditions),
            $params,
        ];
    }

    /**
     * Apply one Dashboard Funnel drill-down filter.
     *
     * @param UserExplorerCriteria $criteria
     * @param array $conditions
     * @param array $params
     * @return void
     */
    private function apply_funnel_filter(
        UserExplorerCriteria $criteria,
        array &$conditions,
        array &$params
    ): void {
        if (!$criteria->has_funnel_filter()) {
            return;
        }

        $params['funnelstart'] =
            $criteria->funnelstart;

        $params['funnelend'] =
            $criteria->funnelend;

        if (
            $criteria->funnelstage ===
            UserExplorerCriteria::FUNNEL_NEW_USERS
        ) {
            $conditions[] = "
                u.timecreated >= :funnelstart
                AND u.timecreated < :funnelend
            ";

            return;
        }

        if (
            $criteria->funnelstage ===
            UserExplorerCriteria::FUNNEL_TRIAL_USERS
        ) {
            $conditions[] = "
                EXISTS (
                    SELECT 1
                    FROM {user_subscription} funneltrial
                    JOIN {subscription_plan} funneltrialplan
                        ON funneltrialplan.id =
                            funneltrial.planid
                    WHERE funneltrial.userid = u.id
                    AND funneltrialplan.is_trial = 1
                GROUP BY funneltrial.userid
                    HAVING MIN(
                        funneltrial.creation_date
                    ) >= :funnelstart
                    AND MIN(
                        funneltrial.creation_date
                    ) < :funnelend
                )
            ";

            return;
        }

        if (
            $criteria->funnelstage ===
            UserExplorerCriteria::FUNNEL_NEW_CUSTOMERS
        ) {
            $paymentdate =
                CrmBusinessSql::subscription_payment_date_expression(
                    'funnelpayment'
                );

            $paymentcondition =
                CrmBusinessSql::successful_subscription_payment_condition(
                    'funnelpayment'
                );

            $conditions[] = "
                EXISTS (
                    SELECT 1
                    FROM {subscription_payment_request}
                        funnelpayment
                    WHERE funnelpayment.userid = u.id
                    AND {$paymentcondition}
                GROUP BY funnelpayment.userid
                    HAVING MIN({$paymentdate})
                        >= :funnelstart
                    AND MIN({$paymentdate})
                        < :funnelend
                )
            ";

            return;
        }

        if (
            $criteria->funnelstage ===
            UserExplorerCriteria::FUNNEL_DIGITAL_BUYERS
        ) {
            $paymentdate =
                CrmBusinessSql::digital_payment_date_expression(
                    'funneldigital'
                );

            $paymentcondition =
                CrmBusinessSql::successful_digital_payment_condition(
                    'funneldigital'
                );

            $conditions[] = "
                EXISTS (
                    SELECT 1
                    FROM {subscription_digital_payment_request}
                        funneldigital
                    WHERE funneldigital.userid = u.id
                    AND {$paymentcondition}
                    AND {$paymentdate} >= :funnelstart
                    AND {$paymentdate} < :funnelend
                )
            ";

            return;
        }

        if (
            $criteria->funnelstage ===
            UserExplorerCriteria::FUNNEL_CONVERTED_TRIALS
        ) {
            $windowseconds =
                $criteria->funnelwindow * DAYSECS;

            $paymentdate =
                CrmBusinessSql::subscription_payment_date_expression(
                    'funnelconversionpayment'
                );

            $paymentcondition =
                CrmBusinessSql::successful_subscription_payment_condition(
                    'funnelconversionpayment'
                );

            $params['funnelwindowseconds'] =
                $windowseconds;

            $conditions[] = "
                u.id IN (
                    SELECT convertedcohort.userid
                    FROM (
                            SELECT
                                trialcohort.userid,
                                trialcohort.firsttrialdate

                            FROM (
                                    SELECT
                                        funnelconversiontrial.userid,
                                        MIN(
                                            funnelconversiontrial.creation_date
                                        ) AS firsttrialdate

                                    FROM {user_subscription}
                                        funnelconversiontrial

                                    JOIN {subscription_plan}
                                        funnelconversionplan
                                        ON funnelconversionplan.id =
                                            funnelconversiontrial.planid

                                    WHERE funnelconversionplan.is_trial = 1
                                GROUP BY
                                        funnelconversiontrial.userid

                                    HAVING MIN(
                                        funnelconversiontrial.creation_date
                                    ) >= :funnelstart

                                    AND MIN(
                                        funnelconversiontrial.creation_date
                                    ) < :funnelend
                            ) trialcohort

                            WHERE EXISTS (
                                    SELECT 1
                                    FROM {subscription_payment_request}
                                        funnelconversionpayment
                                    WHERE funnelconversionpayment.userid =
                                        trialcohort.userid
                                    AND {$paymentcondition}
                                    AND {$paymentdate} >=
                                            trialcohort.firsttrialdate
                                    AND {$paymentdate} <=
                                            trialcohort.firsttrialdate
                                            + :funnelwindowseconds
                            )
                    ) convertedcohort
                )
            ";
        }
    }

    /**
     * Apply one Dashboard CRM trend drill-down filter.
     *
     * This reproduces the Dashboard trends calculation:
     * - latest persisted score during the selected period;
     * - latest persisted score before the selected period;
     * - minimum significant score difference.
     *
     * @param UserExplorerCriteria $criteria
     * @param array<int, string> $conditions
     * @param array<string, mixed> $params
     * @return void
     */
    private function apply_trend_filter(
        UserExplorerCriteria $criteria,
        array &$conditions,
        array &$params
    ): void {
        $filter = $criteria->trendfilter;

        if (!$filter->is_active()) {
            return;
        }

        $scorefield = $filter->score_field();

        /*
        * The SQL field cannot be passed as a bound parameter.
        * It is therefore accepted only from this strict internal list.
        */
        if (
            !in_array(
                $scorefield,
                [
                    'engagementscore',
                    'riskscore',
                    'globalscore',
                ],
                true
            )
        ) {
            return;
        }

        /*
        * Moodle named SQL parameters must remain unique.
        *
        * The period start is needed twice, so two different
        * parameter names are deliberately used.
        */
        $params['trendcurrentstart'] =
            $filter->start;

        $params['trendcurrentend'] =
            $filter->end;

        $params['trendbaselinestart'] =
            $filter->start;

        $params['trendcomparisondelta'] =
            $filter->expects_increase()
                ? $filter->delta
                : -$filter->delta;

        $operator = $filter->expects_increase()
            ? '>='
            : '<=';

        $conditions[] = "
            EXISTS (
                SELECT 1

                FROM {local_subscriptions_crm_score}
                        trendcurrent

                JOIN {local_subscriptions_crm_score}
                        trendbaseline
                    ON trendbaseline.userid =
                        trendcurrent.userid

                WHERE trendcurrent.userid = u.id

                AND trendcurrent.id = (
                        SELECT MAX(
                            trendcurrentlatest.id
                        )

                        FROM {local_subscriptions_crm_score}
                                trendcurrentlatest

                        WHERE trendcurrentlatest.userid =
                                trendcurrent.userid

                        AND trendcurrentlatest.timecreated
                                >= :trendcurrentstart

                        AND trendcurrentlatest.timecreated
                                < :trendcurrentend
                )

                AND trendbaseline.id = (
                        SELECT MAX(
                            trendbaselinelatest.id
                        )

                        FROM {local_subscriptions_crm_score}
                                trendbaselinelatest

                        WHERE trendbaselinelatest.userid =
                                trendcurrent.userid

                        AND trendbaselinelatest.timecreated
                                < :trendbaselinestart
                )

                AND (
                        trendcurrent.{$scorefield}
                        - trendbaseline.{$scorefield}
                ) {$operator} :trendcomparisondelta
            )
        ";
    }

    private function latest_score_join(): string {
        return "
            LEFT JOIN {" . self::SCORE_TABLE . "} score
              ON score.userid = u.id
             AND NOT EXISTS (
                    SELECT 1
                      FROM {" . self::SCORE_TABLE . "} newer
                     WHERE newer.userid = score.userid
                       AND (
                            newer.timecreated > score.timecreated
                            OR (
                                newer.timecreated = score.timecreated
                                AND newer.id > score.id
                            )
                       )
             )
        ";
    }

    private function inbox_summary_join(): string {
        return "
            LEFT JOIN (
                SELECT
                    contact.matcheduserid,

                    COUNT(thread.id)
                        AS conversationcount,

                    COALESCE(
                        SUM(
                            CASE
                                WHEN thread.status = 'open'
                                THEN 1
                                ELSE 0
                            END
                        ),
                        0
                    ) AS openconversationcount,

                    COALESCE(
                        SUM(thread.unreadcount),
                        0
                    ) AS unreadcount,

                    COALESCE(
                        SUM(
                            CASE
                                WHEN thread.priority = 'urgent'
                                THEN 1
                                ELSE 0
                            END
                        ),
                        0
                    ) AS urgentcount,

                    MAX(thread.lastmessageat)
                        AS lastmessageat

                FROM {" .
                    self::INBOX_CONTACT_TABLE .
                "} contact

                JOIN {" .
                    self::INBOX_THREAD_TABLE .
                "} thread
                  ON thread.contactid = contact.id
                 AND thread.locallydeleted = 0

               WHERE contact.matcheduserid IS NOT NULL

            GROUP BY contact.matcheduserid
            ) inbox
              ON inbox.matcheduserid = u.id
        ";
    }

    private function customer_success_plan_summary_join(): string {
        return "
            LEFT JOIN (
                SELECT
                    planmetrics.userid,
                    planmetrics.openplancount,
                    COALESCE(
                        blockedmetrics.blockedstepcount,
                        0
                    ) AS blockedstepcount,
                    planmetrics.highestpriority

                FROM (
                    SELECT
                        p.userid,

                        COUNT(
                            DISTINCT CASE
                                WHEN p.status IN (
                                    'draft',
                                    'active',
                                    'paused'
                                )
                                THEN p.id
                                ELSE NULL
                            END
                        ) AS openplancount,

                        CASE
                            MIN(
                                CASE
                                    WHEN p.status IN (
                                        'draft',
                                        'active',
                                        'paused'
                                    )
                                    THEN
                                        CASE p.priority
                                            WHEN 'critical' THEN 1
                                            WHEN 'urgent' THEN 2
                                            WHEN 'high' THEN 3
                                            WHEN 'normal' THEN 4
                                            WHEN 'low' THEN 5
                                            ELSE 6
                                        END
                                    ELSE 6
                                END
                            )
                            WHEN 1 THEN 'critical'
                            WHEN 2 THEN 'urgent'
                            WHEN 3 THEN 'high'
                            WHEN 4 THEN 'normal'
                            WHEN 5 THEN 'low'
                            ELSE NULL
                        END AS highestpriority

                    FROM {local_subscriptions_cs_plan} p

                    GROUP BY p.userid
                ) planmetrics

                LEFT JOIN (
                    SELECT
                        blockedplan.userid,
                        COUNT(
                            DISTINCT blockedstep.id
                        ) AS blockedstepcount

                    FROM {local_subscriptions_cs_plan}
                        blockedplan

                    JOIN {local_subscriptions_cs_step}
                        blockedstep
                    ON blockedstep.planid =
                        blockedplan.id

                    WHERE blockedplan.status IN (
                        'draft',
                        'active',
                        'paused'
                    )
                    AND blockedstep.status =
                        'blocked'

                    GROUP BY blockedplan.userid
                ) blockedmetrics
                ON blockedmetrics.userid =
                    planmetrics.userid
            ) successplans
            ON successplans.userid = u.id
        ";
    }

    private function digital_purchase_count_join(): string {
        global $DB;

        $emailcompare =
            $DB->sql_compare_text('matcheduser.email') .
            ' = ' .
            $DB->sql_compare_text('purchase.customeremail');

        return "
            LEFT JOIN (
                SELECT
                    matched.matcheduserid,
                    COUNT(DISTINCT matched.purchaseid) AS purchasecount,
                    COUNT(DISTINCT CASE
                        WHEN matched.successful = 1 THEN matched.purchaseid
                        ELSE NULL
                    END) AS successfulpurchasecount,
                    SUM(CASE
                        WHEN matched.successful = 1
                         AND matched.currency = 'EUR'
                        THEN matched.paidminor
                        ELSE 0
                    END) AS revenueeurminor,
                    SUM(CASE
                        WHEN matched.successful = 1
                         AND matched.currency = 'RUB'
                        THEN matched.paidminor
                        ELSE 0
                    END) AS revenuerubminor,
                    MAX(matched.timecreated) AS lastpurchaseat
                FROM (
                    SELECT
                        purchase.userid AS matcheduserid,
                        purchase.id AS purchaseid,
                        purchase.timecreated,
                        purchase.currency,
                        CASE WHEN EXISTS (
                            SELECT 1
                            FROM {local_subscriptions_commerce_payment} paidpayment
                            WHERE paidpayment.purchaseid = purchase.id
                            AND LOWER(paidpayment.status) IN (
                                'paid', 'completed', 'captured',
                                'succeeded', 'success'
                            )
                        ) THEN 1 ELSE 0 END AS successful,
                        purchase.totalminor AS paidminor
                    FROM {local_subscriptions_commerce_purchase} purchase
                    WHERE purchase.userid IS NOT NULL

                    UNION ALL

                    SELECT
                        matcheduser.id AS matcheduserid,
                        purchase.id AS purchaseid,
                        purchase.timecreated,
                        purchase.currency,
                        CASE WHEN EXISTS (
                            SELECT 1
                            FROM {local_subscriptions_commerce_payment} paidpayment
                            WHERE paidpayment.purchaseid = purchase.id
                            AND LOWER(paidpayment.status) IN (
                                'paid', 'completed', 'captured',
                                'succeeded', 'success'
                            )
                        ) THEN 1 ELSE 0 END AS successful,
                        purchase.totalminor AS paidminor
                    FROM {local_subscriptions_commerce_purchase} purchase
                    JOIN {user} matcheduser
                      ON {$emailcompare}
                     AND matcheduser.deleted = 0
                    WHERE purchase.userid IS NULL
                ) matched
                GROUP BY matched.matcheduserid
            ) purchases
              ON purchases.matcheduserid = u.id
        ";
    }
}