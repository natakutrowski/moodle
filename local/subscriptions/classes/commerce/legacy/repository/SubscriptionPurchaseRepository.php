<?php

namespace local_subscriptions\commerce\legacy\repository;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\domain\purchase\SubscriptionPurchase;
use local_subscriptions\commerce\legacy\SubscriptionPurchaseFactory;

/**
 * Read-only repository exposing historical subscriptions as Commerce purchases.
 */
class SubscriptionPurchaseRepository {

    /**
     * Returns one historical subscription as a Commerce purchase.
     */
    public function get_by_subscription_id(
        int $subscriptionid
    ): ?SubscriptionPurchase {
        global $DB;

        if ($subscriptionid <= 0) {
            throw new \InvalidArgumentException(
                'Subscription identifier must be greater than zero.'
            );
        }

        $subscription = $DB->get_record(
            'user_subscription',
            [
                'id' => $subscriptionid,
            ]
        );

        if (!$subscription) {
            return null;
        }

        $paymentrequest =
            $this->get_latest_payment_request(
                $subscriptionid
            );

        $plan = $DB->get_record(
            'subscription_plan',
            [
                'id' => (int)$subscription->planid,
            ]
        ) ?: null;

        $user = $DB->get_record(
            'user',
            [
                'id' => (int)$subscription->userid,
            ],
            'id,email'
        ) ?: null;

        return SubscriptionPurchaseFactory::from_legacy_records(
            $subscription,
            $paymentrequest,
            $plan,
            $user
        );
    }

    /**
     * Returns all historical subscription purchases for one user.
     *
     * @return SubscriptionPurchase[]
     */
    public function get_by_user_id(
        int $userid
    ): array {
        global $DB;

        if ($userid <= 0) {
            throw new \InvalidArgumentException(
                'User identifier must be greater than zero.'
            );
        }

        $subscriptions = $DB->get_records(
            'user_subscription',
            [
                'userid' => $userid,
            ],
            'creation_date DESC, id DESC'
        );

        if ($subscriptions === []) {
            return [];
        }

        $planids = [];
        $subscriptionids = [];

        foreach ($subscriptions as $subscription) {
            $planids[] = (int)$subscription->planid;
            $subscriptionids[] = (int)$subscription->id;
        }

        $plans = $this->get_plans_by_ids(
            $planids
        );

        $paymentrequests =
            $this->get_latest_payment_requests_by_subscription_ids(
                $subscriptionids
            );

        $user = $DB->get_record(
            'user',
            [
                'id' => $userid,
            ],
            'id,email'
        ) ?: null;

        $result = [];

        foreach ($subscriptions as $subscription) {
            $subscriptionid =
                (int)$subscription->id;

            $planid =
                (int)$subscription->planid;

            $result[] =
                SubscriptionPurchaseFactory::from_legacy_records(
                    $subscription,
                    $paymentrequests[$subscriptionid] ?? null,
                    $plans[$planid] ?? null,
                    $user
                );
        }

        return $result;
    }

    /**
     * Returns the latest subscriptions regardless of user.
     *
     * @return SubscriptionPurchase[]
     */
    public function get_recent(
        int $limit = 50,
        int $offset = 0
    ): array {
        global $DB;

        $limit = max(
            1,
            min(500, $limit)
        );

        $offset = max(
            0,
            $offset
        );

        $subscriptions = $DB->get_records(
            'user_subscription',
            [],
            'creation_date DESC, id DESC',
            '*',
            $offset,
            $limit
        );

        return $this->hydrate_records(
            $subscriptions
        );
    }

    /**
     * @param \stdClass[] $subscriptions
     * @return SubscriptionPurchase[]
     */
    private function hydrate_records(
        array $subscriptions
    ): array {
        global $DB;

        if ($subscriptions === []) {
            return [];
        }

        $planids = [];
        $userids = [];
        $subscriptionids = [];

        foreach ($subscriptions as $subscription) {
            $planids[] = (int)$subscription->planid;
            $userids[] = (int)$subscription->userid;
            $subscriptionids[] = (int)$subscription->id;
        }

        $plans = $this->get_plans_by_ids(
            $planids
        );

        $users = $this->get_users_by_ids(
            $userids
        );

        $paymentrequests =
            $this->get_latest_payment_requests_by_subscription_ids(
                $subscriptionids
            );

        $result = [];

        foreach ($subscriptions as $subscription) {
            $subscriptionid =
                (int)$subscription->id;

            $planid =
                (int)$subscription->planid;

            $userid =
                (int)$subscription->userid;

            $result[] =
                SubscriptionPurchaseFactory::from_legacy_records(
                    $subscription,
                    $paymentrequests[$subscriptionid] ?? null,
                    $plans[$planid] ?? null,
                    $users[$userid] ?? null
                );
        }

        return $result;
    }

    private function get_latest_payment_request(
        int $subscriptionid
    ): ?\stdClass {
        global $DB;

        $records = $DB->get_records(
            'subscription_payment_request',
            [
                'subscriptionid' => $subscriptionid,
            ],
            'id DESC',
            '*',
            0,
            1
        );

        if ($records === []) {
            return null;
        }

        return reset($records) ?: null;
    }

    /**
     * @param int[] $subscriptionids
     * @return array<int,\stdClass>
     */
    private function get_latest_payment_requests_by_subscription_ids(
        array $subscriptionids
    ): array {
        global $DB;

        $subscriptionids = $this->normalise_ids(
            $subscriptionids
        );

        if ($subscriptionids === []) {
            return [];
        }

        [$insql, $params] = $DB->get_in_or_equal(
            $subscriptionids,
            SQL_PARAMS_NAMED,
            'commercesubscription'
        );

        $sql = "
            SELECT request.*
              FROM {subscription_payment_request} request
              JOIN (
                    SELECT
                        subscriptionid,
                        MAX(id) AS latestid
                      FROM {subscription_payment_request}
                     WHERE subscriptionid {$insql}
                  GROUP BY subscriptionid
                   ) latest
                ON latest.latestid = request.id
        ";

        $records = $DB->get_records_sql(
            $sql,
            $params
        );

        $result = [];

        foreach ($records as $record) {
            $result[(int)$record->subscriptionid] =
                $record;
        }

        return $result;
    }

    /**
     * @param int[] $planids
     * @return array<int,\stdClass>
     */
    private function get_plans_by_ids(
        array $planids
    ): array {
        global $DB;

        $planids = $this->normalise_ids(
            $planids
        );

        if ($planids === []) {
            return [];
        }

        return $DB->get_records_list(
            'subscription_plan',
            'id',
            $planids
        );
    }

    /**
     * @param int[] $userids
     * @return array<int,\stdClass>
     */
    private function get_users_by_ids(
        array $userids
    ): array {
        global $DB;

        $userids = $this->normalise_ids(
            $userids
        );

        if ($userids === []) {
            return [];
        }

        return $DB->get_records_list(
            'user',
            'id',
            $userids,
            '',
            'id,email'
        );
    }

    /**
     * @param int[] $ids
     * @return int[]
     */
    private function normalise_ids(
        array $ids
    ): array {
        return array_values(
            array_unique(
                array_filter(
                    array_map(
                        'intval',
                        $ids
                    ),
                    static fn(int $id): bool =>
                        $id > 0
                )
            )
        );
    }
}