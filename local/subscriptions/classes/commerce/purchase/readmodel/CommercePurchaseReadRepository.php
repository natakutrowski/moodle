<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\purchase\readmodel;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\persistence\CommercePersistenceSchema;
use local_subscriptions\commerce\purchase\status\CommerceCommercialStatusResolver;
use local_subscriptions\commerce\order\reference\CommercePublicOrderReference;
use core_text;
use moodle_database;

/** Native-only read repository for the unified CRM purchase experience. */
final class CommercePurchaseReadRepository {
    private const GRANT_TABLE = 'local_subs_commerce_grant';
    private const FULFILLMENT_STATE_TABLE = 'local_subs_commerce_ful_state';
    private const FULFILLMENT_ATTEMPT_TABLE = 'local_subs_commerce_ful_attempt';
    private const ADMIN_STATE_TABLE = 'local_subs_commerce_purchase_admin';

    public function __construct(
        private readonly moodle_database $database,
        private readonly CommerceCommercialStatusResolver $statusresolver = new CommerceCommercialStatusResolver()
    ) {
    }

    public function find_by_reference(string $reference): ?CommercePurchaseDetails {
        $record = $this->database->get_record(
            CommercePersistenceSchema::TABLE_PURCHASE,
            ['reference' => trim($reference)],
            '*',
            IGNORE_MISSING
        );
        return $record === false ? null : $this->map_details($record);
    }

    public function find_by_id(int $id): ?CommercePurchaseDetails {
        $record = $this->database->get_record(
            CommercePersistenceSchema::TABLE_PURCHASE,
            ['id' => $id],
            '*',
            IGNORE_MISSING
        );
        return $record === false ? null : $this->map_details($record);
    }

    public function find_by_legacy(string $family, int $legacyid): ?CommercePurchaseDetails {
        $family = strtolower(trim($family));
        if ($legacyid <= 0 || !in_array($family, ['subscription', 'digital'], true)) {
            return null;
        }
        $record = $this->database->get_record(
            CommercePersistenceSchema::TABLE_PURCHASE,
            ['legacyfamily' => $family, 'legacyid' => $legacyid]
        );
        return $record === false ? null : $this->map_details($record);
    }

    /** @return CommercePurchaseDetails[] */
    public function find_details_for_customer(int $userid, string $email = ''): array {
        $conditions = [];
        $params = [];

        if ($userid > 0) {
            $conditions[] = 'userid = :customeruserid';
            $conditions[] = 'EXISTS (SELECT 1 FROM {' . self::GRANT_TABLE . '} customergrantuserid'
                . ' WHERE customergrantuserid.purchasereference = {' . CommercePersistenceSchema::TABLE_PURCHASE . '}.reference'
                . ' AND customergrantuserid.beneficiaryuserid = :grantcustomeruserid)';
            $params['customeruserid'] = $userid;
            $params['grantcustomeruserid'] = $userid;
        }

        $email = core_text::strtolower(trim($email));
        if ($email !== '') {
            $conditions[] = $this->database->sql_equal('customeremail', ':customeremail', false, false);
            $conditions[] = 'EXISTS (SELECT 1 FROM {' . self::GRANT_TABLE . '} customergrantemail'
                . ' WHERE customergrantemail.purchasereference = {' . CommercePersistenceSchema::TABLE_PURCHASE . '}.reference'
                . ' AND ' . $this->database->sql_equal(
                    'customergrantemail.beneficiaryemail',
                    ':grantcustomeremail',
                    false,
                    false
                ) . ')';
            $params['customeremail'] = $email;
            $params['grantcustomeremail'] = $email;
        }

        if ($conditions === []) {
            return [];
        }

        $records = $this->database->get_records_select(
            CommercePersistenceSchema::TABLE_PURCHASE,
            '(' . implode(' OR ', $conditions) . ')',
            $params,
            'timecreated DESC, id DESC'
        );

        return array_map(fn(\stdClass $record): CommercePurchaseDetails => $this->map_details($record), array_values($records));
    }

    /** @return CommercePurchaseSummary[] */
    public function recent(int $limit = 50, int $offset = 0): array {
        $limit = max(1, min(200, $limit));
        $offset = max(0, $offset);
        $records = $this->database->get_records(
            CommercePersistenceSchema::TABLE_PURCHASE,
            null,
            'timecreated DESC, id DESC',
            '*',
            $offset,
            $limit
        );
        return $this->map_summaries_bulk(array_values($records));
    }

    public function search(CommercePurchaseListFilter $filter, int $page = 0, int $perpage = 25): CommercePurchaseListResult {
        $page = max(0, $page);
        $perpage = max(10, min(100, $perpage));

        $query = $filter->normalized_query();
        if (str_starts_with(core_text::strtoupper($query), 'CFR-')) {
            $result = $this->search_by_public_reference($filter, $page, $perpage);
            return $this->sort_result_if_needed($result, $filter);
        }

        [$where, $params] = $this->build_search_conditions($filter);
        $purchasetable = '{' . CommercePersistenceSchema::TABLE_PURCHASE . '}';
        $sort = $filter->normalized_sort();

        // Commercial status and the three aggregate columns are read-model values,
        // not simple purchase-table fields. For those cases we map the bounded CRM
        // result set first, then sort/filter before pagination so the result is exact.
        $requiresmappedordering = $filter->commercialstatus !== ''
            || in_array($sort, ['product', 'payment', 'fulfillment', 'commercial'], true);

        if ($requiresmappedordering) {
            $sql = "SELECT p.* FROM {$purchasetable} p WHERE {$where} ORDER BY p.timecreated DESC, p.id DESC";
            $records = array_values($this->database->get_records_sql($sql, $params));
            $summaries = $this->map_summaries_bulk($records);

            if ($filter->commercialstatus !== '') {
                $summaries = array_values(array_filter(
                    $summaries,
                    static fn(CommercePurchaseSummary $summary): bool =>
                        $summary->commercialstatus === $filter->commercialstatus
                ));
            }

            $this->sort_summaries($summaries, $filter);
            $total = count($summaries);
            $summaries = array_slice($summaries, $page * $perpage, $perpage);

            return new CommercePurchaseListResult($summaries, $total, $page, $perpage);
        }

        $orderby = $this->sql_order_by($filter);
        $sql = "SELECT p.* FROM {$purchasetable} p WHERE {$where} ORDER BY {$orderby}";
        $countsql = "SELECT COUNT(1) FROM {$purchasetable} p WHERE {$where}";
        $total = (int)$this->database->count_records_sql($countsql, $params);
        $records = array_values($this->database->get_records_sql($sql, $params, $page * $perpage, $perpage));

        return new CommercePurchaseListResult(
            $this->map_summaries_bulk($records),
            $total,
            $page,
            $perpage
        );
    }

    /**
     * @param CommercePurchaseSummary[] $summaries
     */
    private function sort_summaries(array &$summaries, CommercePurchaseListFilter $filter): void {
        $sort = $filter->normalized_sort();
        $direction = $filter->normalized_direction() === 'asc' ? 1 : -1;

        usort($summaries, function(CommercePurchaseSummary $left, CommercePurchaseSummary $right) use ($sort, $direction): int {
            $comparison = match ($sort) {
                'reference' => $this->compare_text(
                    $left->publicreference !== '' ? $left->publicreference : $left->reference,
                    $right->publicreference !== '' ? $right->publicreference : $right->reference
                ),
                'customer' => $this->compare_text(
                    $left->customer->display_name() ?: $left->customer->email,
                    $right->customer->display_name() ?: $right->customer->email
                ),
                'type' => $this->compare_text($left->type, $right->type),
                'product' => $this->compare_text(
                    (string)($left->productlabels[0] ?? ''),
                    (string)($right->productlabels[0] ?? '')
                ),
                'amount' => $left->totalminor <=> $right->totalminor,
                'payment' => $this->compare_text($left->paymentstatus, $right->paymentstatus),
                'fulfillment' => $this->compare_text($left->fulfillmentstatus, $right->fulfillmentstatus),
                'commercial' => $this->compare_text($left->commercialstatus, $right->commercialstatus),
                default => $left->timecreated <=> $right->timecreated,
            };

            if ($comparison === 0) {
                $comparison = $left->id <=> $right->id;
            }
            return $comparison * $direction;
        });
    }

    private function compare_text(string $left, string $right): int {
        return core_text::strtolower(trim($left)) <=> core_text::strtolower(trim($right));
    }

    private function sql_order_by(CommercePurchaseListFilter $filter): string {
        $field = match ($filter->normalized_sort()) {
            'reference' => 'p.reference',
            'customer' => 'p.customeremail',
            'type' => 'p.type',
            'amount' => 'p.totalminor',
            default => 'p.timecreated',
        };
        $direction = strtoupper($filter->normalized_direction());
        return "{$field} {$direction}, p.id {$direction}";
    }

    private function sort_result_if_needed(
        CommercePurchaseListResult $result,
        CommercePurchaseListFilter $filter
    ): CommercePurchaseListResult {
        if ($result->purchases === []) {
            return $result;
        }
        $purchases = $result->purchases;
        $this->sort_summaries($purchases, $filter);
        return new CommercePurchaseListResult(
            $purchases,
            $result->total,
            $result->page,
            $result->perpage
        );
    }

    /**
     * Returns every summary matching the current operational filters.
     *
     * This is intended for bounded CRM analytics/KPI calculations on the sales page.
     * The same mapping path as the paginated table is used so commercial/payment/
     * fulfillment semantics stay aligned with the visible rows.
     *
     * @return CommercePurchaseSummary[]
     */
    public function summaries_for_metrics(CommercePurchaseListFilter $filter): array {
        $query = $filter->normalized_query();
        if (str_starts_with(core_text::strtoupper($query), 'CFR-')) {
            return $this->search_by_public_reference($filter, 0, 100)->purchases;
        }

        [$where, $params] = $this->build_search_conditions($filter);
        $purchasetable = '{' . CommercePersistenceSchema::TABLE_PURCHASE . '}';
        $sql = "SELECT p.* FROM {$purchasetable} p WHERE {$where} ORDER BY p.timecreated DESC, p.id DESC";
        $records = array_values($this->database->get_records_sql($sql, $params));
        $summaries = $this->map_summaries_bulk($records);

        if ($filter->commercialstatus !== '') {
            $summaries = array_values(array_filter(
                $summaries,
                static fn(CommercePurchaseSummary $summary): bool =>
                    $summary->commercialstatus === $filter->commercialstatus
            ));
        }

        return $summaries;
    }

    /**
     * Complete sorted result for CSV/administrative export.
     *
     * @return CommercePurchaseSummary[]
     */
    public function summaries_for_export(CommercePurchaseListFilter $filter): array {
        $summaries = $this->summaries_for_metrics($filter);
        $this->sort_summaries($summaries, $filter);
        return $summaries;
    }

    /** @return array{0:string,1:array} */
    private function build_search_conditions(CommercePurchaseListFilter $filter): array {
        $conditions = ['1 = 1'];
        $params = [];
        if ($filter->type !== '') {
            $conditions[] = 'p.type = :purchasetype';
            $params['purchasetype'] = $filter->type;
        }
        if ($filter->currency !== '') {
            $conditions[] = 'p.currency = :currency';
            $params['currency'] = strtoupper($filter->currency);
        }
        if ($filter->datefrom > 0) {
            $conditions[] = 'p.timecreated >= :datefrom';
            $params['datefrom'] = $filter->datefrom;
        }
        if ($filter->dateto > 0) {
            $conditions[] = 'p.timecreated <= :dateto';
            $params['dateto'] = $filter->dateto;
        }
        if ($filter->paymentstatus !== '') {
            $conditions[] = 'EXISTS (SELECT 1 FROM {' . CommercePersistenceSchema::TABLE_PAYMENT . '} pay'
                . ' WHERE pay.purchaseid = p.id AND pay.status = :paymentstatus)';
            $params['paymentstatus'] = $filter->paymentstatus;
        }
        if ($filter->fulfillmentstatus !== '') {
            $granttable = '{' . self::GRANT_TABLE . '}';
            $statetable = '{' . self::FULFILLMENT_STATE_TABLE . '}';
            if ($filter->fulfillmentstatus === 'planned') {
                $conditions[] = "EXISTS (SELECT 1 FROM {$granttable} fg"
                    . " LEFT JOIN {$statetable} fs ON fs.grantreference = fg.grantreference"
                    . " WHERE fg.purchasereference = p.reference"
                    . " AND (fs.status = :fulfillmentstatus OR fs.id IS NULL))";
            } else {
                $conditions[] = "EXISTS (SELECT 1 FROM {$granttable} fg"
                    . " JOIN {$statetable} fs ON fs.grantreference = fg.grantreference"
                    . " WHERE fg.purchasereference = p.reference AND fs.status = :fulfillmentstatus)";
            }
            $params['fulfillmentstatus'] = $filter->fulfillmentstatus;
        }
        if ($filter->provider !== '') {
            $conditions[] = 'EXISTS (SELECT 1 FROM {' . CommercePersistenceSchema::TABLE_PAYMENT . '} prv'
                . ' WHERE prv.purchaseid = p.id AND prv.provider = :provider)';
            $params['provider'] = $filter->provider;
        }

        $adminstate = $filter->normalized_admin_state();
        if ($adminstate === 'open') {
            $conditions[] = 'NOT EXISTS (SELECT 1 FROM {' . self::ADMIN_STATE_TABLE . '} pas'
                . ' WHERE pas.purchaseid = p.id AND pas.state = :adminclosedstate)';
            $params['adminclosedstate'] = 'closed';
        } elseif ($adminstate === 'closed') {
            $conditions[] = 'EXISTS (SELECT 1 FROM {' . self::ADMIN_STATE_TABLE . '} pas'
                . ' WHERE pas.purchaseid = p.id AND pas.state = :adminclosedstate)';
            $params['adminclosedstate'] = 'closed';
        }

        $offerorigin = $filter->normalized_offer_origin();
        if ($offerorigin !== '') {
            $personalofferlike = '%"operation":"personaloffer"%';
            $offerexists = 'EXISTS (SELECT 1 FROM {' . CommercePersistenceSchema::TABLE_ITEM . '} poi'
                . ' WHERE poi.purchaseid = p.id AND '
                . $this->database->sql_like('poi.metadatajson', ':personalofferoperation', false, false)
                . ')';
            $conditions[] = $offerorigin === 'personaloffer'
                ? $offerexists
                : 'NOT ' . $offerexists;
            $params['personalofferoperation'] = $personalofferlike;
        }

        $query = $filter->normalized_query();
        if ($query !== '') {
            $like = '%' . $this->database->sql_like_escape($query) . '%';
            $conditions[] = '(' . $this->database->sql_like('p.reference', ':qreference', false, false)
                . ' OR ' . $this->database->sql_like('p.purchaseuuid', ':quuid', false, false)
                . ' OR ' . $this->database->sql_like('p.customeremail', ':qemail', false, false)
                . ' OR EXISTS (SELECT 1 FROM {' . CommercePersistenceSchema::TABLE_ITEM . '} qi'
                . ' WHERE qi.purchaseid = p.id AND ('
                . $this->database->sql_like('qi.label', ':qlabel', false, false)
                . ' OR ' . $this->database->sql_like('qi.itemreference', ':qitemreference', false, false) . ')))';
            $params += ['qreference' => $like, 'quuid' => $like, 'qemail' => $like, 'qlabel' => $like, 'qitemreference' => $like];
        }
        return [implode(' AND ', $conditions), $params];
    }

    private function map_details(\stdClass $purchase): CommercePurchaseDetails {
        $purchaseid = (int)$purchase->id;
        $items = array_values($this->database->get_records(CommercePersistenceSchema::TABLE_ITEM, ['purchaseid' => $purchaseid], 'position ASC, id ASC'));
        $payments = array_values($this->database->get_records(CommercePersistenceSchema::TABLE_PAYMENT, ['purchaseid' => $purchaseid], 'sequence ASC, id ASC'));
        $nativefulfillment = $this->load_native_fulfillment((string)$purchase->reference);
        $fulfillments = $nativefulfillment['fulfillments'];
        $users = $this->load_users_for_purchases([$purchase]);
        $adminstate = $this->database->get_record(
            self::ADMIN_STATE_TABLE,
            ['purchaseid' => $purchaseid, 'state' => 'closed'],
            '*',
            IGNORE_MISSING
        );

        return new CommercePurchaseDetails(
            $this->map_summary(
                $purchase,
                $items,
                $payments,
                $fulfillments,
                $users,
                $adminstate ?: null
            ),
            $items,
            array_map(fn(\stdClass $payment): CommercePurchasePaymentSummary => new CommercePurchasePaymentSummary(
                (string)$payment->status,
                $payment->provider === null ? null : (string)$payment->provider,
                $payment->providerreference === null ? null : (string)$payment->providerreference,
                $payment->transactionid === null ? null : (string)$payment->transactionid,
                (string)$payment->currency,
                (int)$payment->amountminor,
                $payment->paidat === null ? null : (int)$payment->paidat,
                $this->load_legacy_payment_request(
                    $payment->legacyrequestid === null ? null : (int)$payment->legacyrequestid,
                    $purchase->legacyfamily === null ? null : (string)$purchase->legacyfamily
                )
            ), $payments),
            $fulfillments,
            $purchase->legacyfamily === null ? null : (string)$purchase->legacyfamily,
            $purchase->legacyid === null ? null : (int)$purchase->legacyid,
            $this->decode_json((string)$purchase->metadatajson),
            $nativefulfillment['grants'],
            $nativefulfillment['attempts']
        );
    }

    /** @return array{grants:array,fulfillments:array,attempts:array} */
    private function load_native_fulfillment(string $purchasereference): array {
        $grantrecords = array_values($this->database->get_records(
            self::GRANT_TABLE,
            ['purchasereference' => trim($purchasereference)],
            'id ASC'
        ));

        if ($grantrecords === []) {
            return ['grants' => [], 'fulfillments' => [], 'attempts' => []];
        }

        $grantreferences = array_map(static fn(\stdClass $record): string => (string)$record->grantreference, $grantrecords);
        [$insql, $params] = $this->database->get_in_or_equal($grantreferences, SQL_PARAMS_NAMED, 'grantref');
        $states = $this->database->get_records_select(self::FULFILLMENT_STATE_TABLE, "grantreference {$insql}", $params);
        $attemptrecords = array_values($this->database->get_records_select(
            self::FULFILLMENT_ATTEMPT_TABLE,
            "grantreference {$insql}",
            $params,
            'timestarted DESC, id DESC'
        ));

        $statesbygrant = [];
        foreach ($states as $state) {
            $statesbygrant[(string)$state->grantreference] = $state;
        }

        $grants = [];
        $fulfillments = [];
        foreach ($grantrecords as $grant) {
            $reference = (string)$grant->grantreference;
            $state = $statesbygrant[$reference] ?? null;
            $grants[] = new CommercePurchaseGrantSummary(
                $reference,
                (string)$grant->itemreference,
                (string)$grant->productsku,
                (string)$grant->type,
                (string)$grant->resourcekey,
                (int)$grant->quantity,
                (string)$grant->status,
                $grant->beneficiaryuserid === null ? null : (int)$grant->beneficiaryuserid,
                (string)$grant->beneficiaryemail,
                (int)$grant->validfrom,
                $grant->validuntil === null ? null : (int)$grant->validuntil,
                $this->decode_json((string)$grant->configurationjson),
                $this->decode_json((string)$grant->metadatajson)
            );
            $fulfillments[] = new CommercePurchaseFulfillmentSummary(
                $reference,
                (string)$grant->type,
                $state === null ? 'planned' : (string)$state->status,
                (string)$grant->idempotencykey,
                $state === null ? null : (string)$state->handlerclass,
                $state === null ? 0 : (int)$state->attempts,
                $state === null ? null : (string)$state->lastexecutionreference,
                $state === null ? null : (string)$state->lastsource,
                $state === null || $state->lastactoruserid === null ? null : (int)$state->lastactoruserid,
                $state === null ? [] : $this->decode_json((string)$state->lastpayloadjson),
                $state === null || $state->lastmessage === null ? null : (string)$state->lastmessage,
                $state === null || $state->lasterrorclass === null ? null : (string)$state->lasterrorclass,
                $state === null ? null : (int)$state->timestarted,
                $state === null || $state->timecompleted === null ? null : (int)$state->timecompleted
            );
        }

        $attempts = array_map(fn(\stdClass $attempt): CommercePurchaseFulfillmentAttemptSummary =>
            new CommercePurchaseFulfillmentAttemptSummary(
                (int)$attempt->id,
                (string)$attempt->grantreference,
                (string)$attempt->executionreference,
                (string)$attempt->granttype,
                (string)$attempt->handlerclass,
                (string)$attempt->status,
                (bool)$attempt->dryrun,
                (string)$attempt->source,
                $attempt->actoruserid === null ? null : (int)$attempt->actoruserid,
                $this->decode_json((string)$attempt->payloadjson),
                $attempt->message === null ? null : (string)$attempt->message,
                $attempt->errorclass === null ? null : (string)$attempt->errorclass,
                (int)$attempt->timestarted,
                $attempt->timecompleted === null ? null : (int)$attempt->timecompleted
            ),
            $attemptrecords
        );

        return ['grants' => $grants, 'fulfillments' => $fulfillments, 'attempts' => $attempts];
    }

    /** @param \stdClass[] $purchases @return CommercePurchaseSummary[] */
    private function map_summaries_bulk(array $purchases): array {
        if ($purchases === []) {
            return [];
        }
        $ids = array_map(static fn(\stdClass $purchase): int => (int)$purchase->id, $purchases);
        [$insql, $params] = $this->database->get_in_or_equal($ids, SQL_PARAMS_NAMED, 'purchase');
        $items = $this->group_by_purchase($this->database->get_records_select(CommercePersistenceSchema::TABLE_ITEM, "purchaseid {$insql}", $params, 'purchaseid, position, id'));
        $payments = $this->group_by_purchase($this->database->get_records_select(CommercePersistenceSchema::TABLE_PAYMENT, "purchaseid {$insql}", $params, 'purchaseid, sequence, id'));
        $fulfillments = $this->load_native_fulfillment_statuses_for_purchases($purchases);
        $users = $this->load_users_for_purchases($purchases);
        $adminstates = [];
        foreach ($this->database->get_records_select(
            self::ADMIN_STATE_TABLE,
            "purchaseid {$insql} AND state = :adminstate",
            $params + ['adminstate' => 'closed']
        ) as $record) {
            $adminstates[(int)$record->purchaseid] = $record;
        }

        return array_map(fn(\stdClass $purchase): CommercePurchaseSummary => $this->map_summary(
            $purchase,
            $items[(int)$purchase->id] ?? [],
            $payments[(int)$purchase->id] ?? [],
            $fulfillments[(string)$purchase->reference] ?? [],
            $users,
            $adminstates[(int)$purchase->id] ?? null
        ), $purchases);
    }

    /**
     * Loads current Native fulfillment states for a purchase page in bounded queries.
     * Grants without a state are represented as planned fulfillment operations.
     *
     * @param \stdClass[] $purchases
     * @return array<string, array<int, \stdClass>>
     */
    private function load_native_fulfillment_statuses_for_purchases(array $purchases): array {
        $references = array_values(array_unique(array_filter(array_map(
            static fn(\stdClass $purchase): string => trim((string)$purchase->reference),
            $purchases
        ))));
        if ($references === []) {
            return [];
        }

        [$insql, $params] = $this->database->get_in_or_equal($references, SQL_PARAMS_NAMED, 'purchaseref');
        $granttable = '{' . self::GRANT_TABLE . '}';
        $statetable = '{' . self::FULFILLMENT_STATE_TABLE . '}';
        $sql = "SELECT g.id, g.purchasereference, COALESCE(s.status, 'planned') AS status"
            . " FROM {$granttable} g"
            . " LEFT JOIN {$statetable} s ON s.grantreference = g.grantreference"
            . " WHERE g.purchasereference {$insql}"
            . " ORDER BY g.purchasereference, g.id";

        $grouped = [];
        foreach ($this->database->get_records_sql($sql, $params) as $record) {
            $grouped[(string)$record->purchasereference][] = $record;
        }
        return $grouped;
    }

    /** @param array<int,\stdClass> $records */
    private function group_by_purchase(array $records): array {
        $grouped = [];
        foreach ($records as $record) {
            $grouped[(int)$record->purchaseid][] = $record;
        }
        return $grouped;
    }

    private function map_summary(
        \stdClass $purchase,
        array $items,
        array $payments,
        array $fulfillments,
        array $users = [],
        ?\stdClass $adminstate = null
    ): CommercePurchaseSummary {
        $customerdata = $this->decode_json((string)$purchase->customerjson);
        $userid = $purchase->userid === null ? null : (int)$purchase->userid;
        $user = $userid === null ? null : ($users[$userid] ?? null);
        $firstname = trim((string)($customerdata['firstname'] ?? ''));
        $lastname = trim((string)($customerdata['lastname'] ?? ''));
        $email = trim((string)($purchase->customeremail ?? ''));

        if ($user !== null) {
            $firstname = $firstname !== '' ? $firstname : (string)$user->firstname;
            $lastname = $lastname !== '' ? $lastname : (string)$user->lastname;
            $email = $email !== '' ? $email : (string)$user->email;
        }
        $paymentstatuses = array_map(fn(\stdClass $record): string => (string)$record->status, $payments);
        $fulfillmentstatuses = array_map(
            static fn(object $record): string => (string)$record->status,
            $fulfillments
        );
        $lastpayment = $payments === [] ? null : end($payments);

        $haspersonaloffer = false;
        $personalofferuuid = '';
        $personaloffercampaign = '';
        foreach ($items as $item) {
            $itemmetadata = $this->decode_json((string)($item->metadatajson ?? ''));
            if (strtolower(trim((string)($itemmetadata['operation'] ?? ''))) !== 'personaloffer') {
                continue;
            }
            $haspersonaloffer = true;
            $personalofferuuid = strtolower(trim((string)($itemmetadata['personal_offer_uuid'] ?? '')));
            $personaloffercampaign = trim((string)($itemmetadata['personal_offer_campaign'] ?? ''));
            break;
        }

        return new CommercePurchaseSummary(
            (int)$purchase->id,
            (string)$purchase->purchaseuuid,
            (string)$purchase->reference,
            $this->resolve_purchase_type((string)$purchase->type, $items),
            new CommercePurchaseCustomer(
                $userid,
                $email,
                $firstname,
                $lastname
            ),
            array_map(fn(\stdClass $item): string => (string)$item->label, $items),
            (string)$purchase->currency,
            (int)$purchase->totalminor,
            $this->statusresolver->resolve((string)$purchase->status, $paymentstatuses, $fulfillmentstatuses),
            $paymentstatuses === [] ? 'none' : (string)end($paymentstatuses),
            $fulfillmentstatuses === [] ? 'none' : (string)end($fulfillmentstatuses),
            $lastpayment === null || $lastpayment->provider === null ? null : (string)$lastpayment->provider,
            $purchase->legacyfamily === null ? 'native' : 'legacy',
            (int)$purchase->timecreated,
            array_map(function(\stdClass $item): array {
                $metadata = $this->decode_json((string)($item->metadatajson ?? ''));
                return [
                    'label' => (string)$item->label,
                    'reference' => (string)$item->itemreference,
                    'sku' => (string)($metadata['catalogue_sku'] ?? $item->itemreference),
                ];
            }, $items),
            (new CommercePublicOrderReference())->from_internal(
                (string)$purchase->reference,
                (int)$purchase->timecreated
            ),
            $haspersonaloffer,
            $personalofferuuid,
            $personaloffercampaign,
            $adminstate !== null,
            $adminstate !== null ? (int)$adminstate->closedat : 0,
            $adminstate !== null ? (int)$adminstate->closedby : 0,
            $adminstate !== null ? (string)($adminstate->reason ?? '') : ''
        );
    }

    /**
     * Searches the computed public CFR reference without exposing the internal reference.
     *
     * The public alias is intentionally derived rather than persisted, so matching is
     * performed on a bounded candidate set and then paginated in memory.
     */
    private function search_by_public_reference(
        CommercePurchaseListFilter $filter,
        int $page,
        int $perpage
    ): CommercePurchaseListResult {
        $query = core_text::strtoupper($filter->normalized_query());
        $year = null;
        if (preg_match('/^CFR-(\d{4})(?:-|$)/', $query, $matches) === 1) {
            $year = (int)$matches[1];
        }

        $filterwithoutquery = new CommercePurchaseListFilter(
            '',
            $filter->type,
            $filter->commercialstatus,
            $filter->paymentstatus,
            $filter->fulfillmentstatus,
            $filter->provider,
            $filter->currency,
            $filter->datefrom,
            $filter->dateto,
            $filter->sort,
            $filter->direction,
            $filter->offerorigin,
            $filter->adminstate
        );
        [$where, $params] = $this->build_search_conditions($filterwithoutquery);

        if ($year !== null && $year >= 2000 && $year <= 2100) {
            $yearstart = make_timestamp($year, 1, 1, 0, 0, 0);
            $yearend = make_timestamp($year + 1, 1, 1, 0, 0, 0);
            $where .= ' AND p.timecreated >= :publicyearstart AND p.timecreated < :publicyearend';
            $params['publicyearstart'] = $yearstart;
            $params['publicyearend'] = $yearend;
        }

        $purchasetable = '{' . CommercePersistenceSchema::TABLE_PURCHASE . '}';
        $sql = "SELECT p.* FROM {$purchasetable} p WHERE {$where} ORDER BY p.timecreated DESC, p.id DESC";
        $records = array_values($this->database->get_records_sql($sql, $params));
        $summaries = $this->map_summaries_bulk($records);
        $summaries = array_values(array_filter(
            $summaries,
            static fn(CommercePurchaseSummary $summary): bool =>
                str_contains(core_text::strtoupper($summary->publicreference), $query)
        ));

        if ($filter->commercialstatus !== '') {
            $summaries = array_values(array_filter(
                $summaries,
                static fn(CommercePurchaseSummary $summary): bool =>
                    $summary->commercialstatus === $filter->commercialstatus
            ));
        }

        $total = count($summaries);
        $summaries = array_slice($summaries, $page * $perpage, $perpage);
        return new CommercePurchaseListResult($summaries, $total, $page, $perpage);
    }

    /**
     * Loads Moodle users in one query so old subscription purchases can display
     * the customer's current first and last name even when customerjson is sparse.
     *
     * @param \stdClass[] $purchases
     * @return array<int,\stdClass>
     */
    private function load_users_for_purchases(array $purchases): array {
        $userids = [];
        foreach ($purchases as $purchase) {
            if ($purchase->userid !== null && (int)$purchase->userid > 0) {
                $userids[] = (int)$purchase->userid;
            }
        }

        $userids = array_values(array_unique($userids));
        if ($userids === []) {
            return [];
        }

        return $this->database->get_records_list(
            'user',
            'id',
            $userids,
            '',
            'id, firstname, lastname, email'
        );
    }

    private function load_legacy_payment_request(
        ?int $requestid,
        ?string $legacyfamily
    ): ?CommercePurchasePaymentRequestSummary {
        if ($requestid === null || $requestid <= 0) {
            return null;
        }

        $family = strtolower(trim((string)$legacyfamily));
        $table = match ($family) {
            'subscription' => 'subscription_payment_request',
            'digital' => 'subscription_digital_payment_request',
            default => null,
        };

        if ($table === null) {
            return null;
        }

        $record = $this->database->get_record($table, ['id' => $requestid]);
        if ($record === false) {
            return null;
        }

        $amountminor = (int)($record->amount_minor ?? 0);
        if ($amountminor === 0 && isset($record->price)) {
            $amountminor = (int)round((float)$record->price * 100);
        }

        return new CommercePurchasePaymentRequestSummary(
            (int)$record->id,
            $family,
            (string)($record->status ?? ''),
            (string)($record->payment_provider ?? ''),
            (string)($record->currency ?? ''),
            $amountminor,
            self::nullable_string($record->sessionid ?? null),
            self::nullable_string($record->transactionid ?? null),
            (int)($record->creation_date ?? 0),
            (int)($record->last_update ?? 0),
            isset($record->expiration_date) ? (int)$record->expiration_date : null,
            (int)($record->attempts ?? 0),
            isset($record->last_attempt) ? (int)$record->last_attempt : null,
            self::nullable_string($record->last_error ?? null),
            $this->payment_request_details($record, $family)
        );
    }


    /**
     * Keeps the legacy operational detail useful without exposing arbitrary table columns.
     *
     * @return array<string, mixed>
     */
    private function payment_request_details(\stdClass $record, string $family): array {
        $common = [
            'userid', 'email', 'firstname', 'lastname', 'currency', 'price', 'amount_minor',
            'payment_provider', 'sessionid', 'status', 'transactionid', 'payment_link',
            'creation_date', 'last_update', 'payment_date', 'expiration_date', 'attempts',
            'last_attempt', 'last_error', 'locked_list_price', 'locked_discount_percent',
            'locked_discount_amount', 'locked_discount_reason', 'locked_final_price', 'locked_at',
            'created_ip', 'created_useragent', 'accept_language', 'http_referer', 'response_json',
            'emailsent',
        ];
        $specific = $family === 'subscription'
            ? ['planid', 'phone', 'phone_country', 'subscriptionid', 'retry_expires',
                'reminder_stage', 'reminder1_at', 'reminder2_at', 'login_token_expires',
                'operation', 'reference_subscription_id']
            : ['productid', 'download_token_expires', 'receipt_sent', 'buyer_lang'];

        $details = [];
        foreach (array_merge($common, $specific) as $field) {
            if (property_exists($record, $field)) {
                $details[$field] = $record->{$field};
            }
        }
        return $details;
    }

    private static function nullable_string(mixed $value): ?string {
        $value = trim((string)$value);
        return $value === '' ? null : $value;
    }

    /**
     * Checkout is a purchase origin, not a business product type.
     * Existing checkout purchases are normalised from their persisted item types.
     *
     * @param array<int, \stdClass> $items
     */
    private function resolve_purchase_type(string $persistedtype, array $items): string {
        $persistedtype = strtolower(trim($persistedtype));
        if ($persistedtype !== 'checkout') {
            return $persistedtype;
        }

        $itemtypes = array_values(array_unique(array_filter(array_map(
            static fn(\stdClass $item): string => strtolower(trim((string)($item->itemtype ?? ''))),
            $items
        ))));

        if (count($itemtypes) === 1) {
            return $itemtypes[0];
        }

        return count($itemtypes) > 1 ? 'bundle' : 'unknown';
    }

    private function decode_json(string $json): array {
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : [];
    }
}
