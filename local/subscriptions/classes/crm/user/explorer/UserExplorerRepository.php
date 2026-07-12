<?php

namespace local_subscriptions\crm\user\explorer;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\user\UserExplorerFilter;

final class UserExplorerRepository {

    private const SCORE_TABLE =
        'local_subscriptions_crm_score';

    private const TAG_TABLE =
        'local_subscriptions_user_tag';

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
        UserExplorerCriteria $criteria
    ): array {
        return $this->fetch_records(
            $criteria,
            $criteria->offset(),
            $criteria->perpage
        );
    }

    public function get_records_for_export(
        UserExplorerCriteria $criteria,
        int $limit = 5000
    ): array {
        return $this->fetch_records(
            $criteria,
            0,
            max(1, min(5000, $limit))
        );
    }

    private function fetch_records(
        UserExplorerCriteria $criteria,
        int $offset,
        int $limit
    ): array {
        global $DB;

        [$joins, $where, $params] =
            $this->build_filter_parts($criteria);

        $order = UserExplorerSort::sql(
            $criteria->sort
        );

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

                COALESCE(score.commercialscore, 0)
                    AS commercialscore,

                COALESCE(score.engagementscore, 0)
                    AS engagementscore,

                COALESCE(score.riskscore, 0)
                    AS riskscore,

                COALESCE(score.globalscore, 0)
                    AS globalscore,

                score.level AS scorelevel,
                score.segmentsjson,
                score.opportunitiesjson,
                score.recommendationsjson,
                score.timecreated AS scoretimecreated,

                COALESCE(subscriptions.subscriptioncount, 0)
                    AS subscriptioncount,

                COALESCE(purchases.purchasecount, 0)
                    AS purchasecount

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
                EXISTS (
                    SELECT 1
                    FROM {user_subscription} filteredsubscription
                    WHERE filteredsubscription.userid = u.id
                )
            ";
        }

        if (
            $criteria->hassubscription ===
            UserExplorerCriteria::PRESENCE_NO
        ) {
            $conditions[] = "
                NOT EXISTS (
                    SELECT 1
                    FROM {user_subscription} filteredsubscription
                    WHERE filteredsubscription.userid = u.id
                )
            ";
        }

        if (
            $criteria->haspurchase !==
            UserExplorerCriteria::PRESENCE_ALL
        ) {
            $purchaseemailcompare =
                $DB->sql_compare_text('filteredpurchase.email') .
                ' = ' .
                $DB->sql_compare_text('u.email');

            $purchaseexists = "
                EXISTS (
                    SELECT 1
                    FROM {subscription_digital_payment_request}
                        filteredpurchase
                    WHERE filteredpurchase.status IN (
                            'paid',
                            'completed',
                            'PAID',
                            'COMPLETED'
                        )
                    AND (
                            filteredpurchase.userid = u.id
                            OR (
                                filteredpurchase.userid IS NULL
                                AND {$purchaseemailcompare}
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

        return [
            $joins,
            implode(' AND ', $conditions),
            $params,
        ];
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

    private function digital_purchase_count_join(): string {
        global $DB;

        $emailcompare =
            $DB->sql_compare_text('matcheduser.email') .
            ' = ' .
            $DB->sql_compare_text('purchase.email');

        return "
            LEFT JOIN (
                SELECT
                    matched.matcheduserid,
                    COUNT(DISTINCT matched.purchaseid)
                        AS purchasecount

                  FROM (
                        SELECT
                            purchase.userid AS matcheduserid,
                            purchase.id AS purchaseid
                        FROM {subscription_digital_payment_request} purchase
                        WHERE purchase.userid IS NOT NULL
                        AND purchase.status IN (
                                'paid',
                                'completed',
                                'PAID',
                                'COMPLETED'
                        )

                        UNION ALL

                        SELECT
                            matcheduser.id AS matcheduserid,
                            purchase.id AS purchaseid
                        FROM {subscription_digital_payment_request} purchase
                        JOIN {user} matcheduser
                            ON {$emailcompare}
                        AND matcheduser.deleted = 0
                        WHERE purchase.userid IS NULL
                        AND purchase.status IN (
                                'paid',
                                'completed',
                                'PAID',
                                'COMPLETED'
                        )
                  ) matched

              GROUP BY matched.matcheduserid
            ) purchases
              ON purchases.matcheduserid = u.id
        ";
    }
}