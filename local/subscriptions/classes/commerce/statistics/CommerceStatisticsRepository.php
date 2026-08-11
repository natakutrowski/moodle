<?php

namespace local_subscriptions\commerce\statistics;

defined('MOODLE_INTERNAL') || die();

/** Native persistence reader for Commerce statistics. */
final class CommerceStatisticsRepository {
    public function __construct(private readonly \moodle_database $db) {
    }

    /**
     * Return aggregated values indexed by currency.
     *
     * Failed/refunded payment counts are attached to the purchase creation period because the
     * current Native payment table has no attempt timestamp. C0/C8 may later add event timestamps.
     *
     * @return array<string,array<string,int|float>>
     */
    public function aggregate(CommerceStatisticsPeriod $period, CommerceStatisticsFilter $filter): array {
        $params = ['start' => $period->start(), 'end' => $period->end()];
        $where = ['p.timecreated >= :start', 'p.timecreated < :end'];
        if ($filter->currency() !== null) {
            $where[] = 'p.currency = :currency';
            $params['currency'] = $filter->currency();
        }
        if ($filter->product_reference() !== null) {
            $where[] = 'EXISTS (SELECT 1 FROM {local_subscriptions_commerce_purchase_item} pi
                               WHERE pi.purchaseid = p.id AND pi.itemreference = :itemreference)';
            $params['itemreference'] = $filter->product_reference();
        }
        $sql = "SELECT p.currency,
                       COUNT(DISTINCT p.id) AS orders,
                       COUNT(DISTINCT COALESCE(p.userid, 0) || ':' || COALESCE(p.customeremail, '')) AS customers,
                       COALESCE(SUM(p.totalminor), 0) AS orderedminor
                  FROM {local_subscriptions_commerce_purchase} p
                 WHERE " . implode(' AND ', $where) . "
              GROUP BY p.currency";

        // MySQL does not support || as concatenation. Use a portable customer query separately.
        $sql = str_replace("COUNT(DISTINCT COALESCE(p.userid, 0) || ':' || COALESCE(p.customeremail, '')) AS customers,\n", '', $sql);
        $rows = $this->db->get_records_sql($sql, $params);
        $result = [];
        foreach ($rows as $row) {
            $currency = strtoupper((string)$row->currency);
            $result[$currency] = [
                'orders' => (int)$row->orders,
                'customers' => $this->count_customers($period, $filter, $currency),
                'ordered_minor' => (int)$row->orderedminor,
                'paid_minor' => 0,
                'successful_payments' => 0,
                'failed_payments' => 0,
                'refunded_payments' => 0,
                'refunded_minor' => 0,
                'pending_fulfillments' => 0,
            ];
        }
        $this->append_payment_metrics($result, $period, $filter);
        $this->append_fulfillment_metrics($result, $period, $filter);
        return $result;
    }

    private function count_customers(CommerceStatisticsPeriod $period, CommerceStatisticsFilter $filter, string $currency): int {
        $params = ['start' => $period->start(), 'end' => $period->end(), 'currency' => $currency];
        $where = ['p.timecreated >= :start', 'p.timecreated < :end', 'p.currency = :currency'];
        if ($filter->product_reference() !== null) {
            $where[] = 'EXISTS (SELECT 1 FROM {local_subscriptions_commerce_purchase_item} pi
                               WHERE pi.purchaseid = p.id AND pi.itemreference = :itemreference)';
            $params['itemreference'] = $filter->product_reference();
        }
        $records = $this->db->get_records_sql(
            'SELECT p.id, p.userid, p.customeremail FROM {local_subscriptions_commerce_purchase} p WHERE ' . implode(' AND ', $where),
            $params
        );
        $customers = [];
        foreach ($records as $record) {
            $key = !empty($record->userid) ? 'u:' . $record->userid : 'e:' . \core_text::strtolower(trim((string)$record->customeremail));
            if ($key !== 'e:') {
                $customers[$key] = true;
            }
        }
        return count($customers);
    }

    private function append_payment_metrics(array &$result, CommerceStatisticsPeriod $period, CommerceStatisticsFilter $filter): void {
        $params = ['start' => $period->start(), 'end' => $period->end()];
        $where = ['p.timecreated >= :start', 'p.timecreated < :end'];
        if ($filter->currency() !== null) {
            $where[] = 'pay.currency = :currency';
            $params['currency'] = $filter->currency();
        }
        if ($filter->provider() !== null) {
            $where[] = 'pay.provider = :provider';
            $params['provider'] = $filter->provider();
        }
        if ($filter->product_reference() !== null) {
            $where[] = 'EXISTS (SELECT 1 FROM {local_subscriptions_commerce_purchase_item} pi
                               WHERE pi.purchaseid = p.id AND pi.itemreference = :itemreference)';
            $params['itemreference'] = $filter->product_reference();
        }
        // Moodle indexes get_records_sql() results by the first selected column.
        // Currency is not unique here because one row is returned per payment status.
        // MIN(pay.id) provides a stable unique key for each grouped row.
        $sql = "SELECT MIN(pay.id) AS recordid, pay.currency, pay.status, COUNT(pay.id) AS paymentcount,
                       COALESCE(SUM(pay.amountminor), 0) AS amountminor
                  FROM {local_subscriptions_commerce_payment} pay
                  JOIN {local_subscriptions_commerce_purchase} p ON p.id = pay.purchaseid
                 WHERE " . implode(' AND ', $where) . "
              GROUP BY pay.currency, pay.status";
        foreach ($this->db->get_records_sql($sql, $params) as $row) {
            $currency = strtoupper((string)$row->currency);
            $result[$currency] ??= $this->empty_currency_metrics();
            $status = strtolower((string)$row->status);
            if (in_array($status, ['paid', 'succeeded', 'completed', 'captured'], true)) {
                $result[$currency]['successful_payments'] += (int)$row->paymentcount;
                $result[$currency]['paid_minor'] += (int)$row->amountminor;
            } else if (in_array($status, ['failed', 'declined', 'cancelled', 'canceled', 'expired'], true)) {
                $result[$currency]['failed_payments'] += (int)$row->paymentcount;
            } else if (in_array($status, ['refunded', 'partially_refunded'], true)) {
                $result[$currency]['refunded_payments'] += (int)$row->paymentcount;
                $result[$currency]['refunded_minor'] += (int)$row->amountminor;
            }
        }
    }

    private function append_fulfillment_metrics(array &$result, CommerceStatisticsPeriod $period, CommerceStatisticsFilter $filter): void {
        $params = ['start' => $period->start(), 'end' => $period->end()];
        $where = ['p.timecreated >= :start', 'p.timecreated < :end', "f.status IN ('pending', 'queued', 'processing', 'retry')"];
        if ($filter->currency() !== null) {
            $where[] = 'p.currency = :currency';
            $params['currency'] = $filter->currency();
        }
        $sql = "SELECT p.currency, COUNT(f.id) AS pendingcount
                  FROM {local_subscriptions_commerce_fulfillment} f
                  JOIN {local_subscriptions_commerce_purchase} p ON p.id = f.purchaseid
                 WHERE " . implode(' AND ', $where) . "
              GROUP BY p.currency";
        foreach ($this->db->get_records_sql($sql, $params) as $row) {
            $currency = strtoupper((string)$row->currency);
            $result[$currency] ??= $this->empty_currency_metrics();
            $result[$currency]['pending_fulfillments'] = (int)$row->pendingcount;
        }
    }

    private function empty_currency_metrics(): array {
        return [
            'orders' => 0, 'customers' => 0, 'ordered_minor' => 0, 'paid_minor' => 0,
            'successful_payments' => 0, 'failed_payments' => 0, 'refunded_payments' => 0,
            'refunded_minor' => 0, 'pending_fulfillments' => 0,
        ];
    }

    /** @return array<string,CommerceStatisticsSeries> */
    public function time_series(CommerceStatisticsPeriod $period, CommerceStatisticsFilter $filter, string $metric): array {
        if (!in_array($metric, ['revenue', 'orders'], true)) {
            throw new \coding_exception('Unsupported Commerce statistics series metric.');
        }
        $params = ['start' => $period->start(), 'end' => $period->end()];
        $where = ['p.timecreated >= :start', 'p.timecreated < :end'];
        if ($filter->currency() !== null) {
            $where[] = 'p.currency = :currency';
            $params['currency'] = $filter->currency();
        }
        if ($filter->product_reference() !== null) {
            $where[] = 'EXISTS (SELECT 1 FROM {local_subscriptions_commerce_purchase_item} pi WHERE pi.purchaseid = p.id AND pi.itemreference = :itemreference)';
            $params['itemreference'] = $filter->product_reference();
        }
        $records = $this->db->get_records_sql(
            'SELECT p.id, p.timecreated, p.currency, p.totalminor FROM {local_subscriptions_commerce_purchase} p WHERE ' . implode(' AND ', $where) . ' ORDER BY p.timecreated ASC',
            $params
        );
        $granularity = self::granularity($period);
        $buckets = [];
        foreach ($records as $record) {
            $currency = strtoupper((string)$record->currency);
            $bucket = self::bucket_start((int)$record->timecreated, $granularity);
            $buckets[$currency][$bucket] ??= 0;
            $buckets[$currency][$bucket] += $metric === 'orders' ? 1 : (int)$record->totalminor;
        }
        $result = [];
        foreach ($buckets as $currency => $values) {
            ksort($values);
            $points = [];
            foreach ($values as $timestamp => $value) {
                $points[] = ['timestamp' => (int)$timestamp, 'label' => self::bucket_label((int)$timestamp, $granularity), 'value' => $value];
            }
            $result[$currency] = new CommerceStatisticsSeries($metric, $currency, $granularity, $points);
        }
        return $result;
    }

    /** @return array<string,array<string,int>> */
    public function payment_health(CommerceStatisticsPeriod $period, CommerceStatisticsFilter $filter): array {
        $params = ['start' => $period->start(), 'end' => $period->end()];
        $where = ['p.timecreated >= :start', 'p.timecreated < :end'];
        if ($filter->currency() !== null) { $where[] = 'pay.currency = :currency'; $params['currency'] = $filter->currency(); }
        if ($filter->provider() !== null) { $where[] = 'pay.provider = :provider'; $params['provider'] = $filter->provider(); }
        $sql = "SELECT MIN(pay.id) AS recordid, pay.currency, pay.status, COUNT(pay.id) AS paymentcount
                  FROM {local_subscriptions_commerce_payment} pay
                  JOIN {local_subscriptions_commerce_purchase} p ON p.id = pay.purchaseid
                 WHERE " . implode(' AND ', $where) . " GROUP BY pay.currency, pay.status";
        $result = [];
        foreach ($this->db->get_records_sql($sql, $params) as $row) {
            $currency = strtoupper((string)$row->currency);
            $status = strtolower((string)$row->status);
            $group = in_array($status, ['paid','succeeded','completed','captured'], true) ? 'successful'
                : (in_array($status, ['refunded','partially_refunded'], true) ? 'refunded' : 'failed');
            $result[$currency][$group] = ($result[$currency][$group] ?? 0) + (int)$row->paymentcount;
        }
        return $result;
    }

    /** @return array<string,array<int,array{reference:string,label:string,quantity:int,revenue_minor:int,itemtype:string}>> */
    public function top_products(CommerceStatisticsPeriod $period, CommerceStatisticsFilter $filter, int $limit): array {
        $params = ['start' => $period->start(), 'end' => $period->end()];
        $where = ['p.timecreated >= :start', 'p.timecreated < :end'];
        if ($filter->currency() !== null) { $where[] = 'pi.currency = :currency'; $params['currency'] = $filter->currency(); }
        $sql = "SELECT MIN(pi.id) AS recordid, pi.currency, pi.itemreference, pi.label, pi.itemtype,
                       SUM(pi.quantity) AS quantity, SUM(pi.netminor) AS revenueminor
                  FROM {local_subscriptions_commerce_purchase_item} pi
                  JOIN {local_subscriptions_commerce_purchase} p ON p.id = pi.purchaseid
                 WHERE " . implode(' AND ', $where) . "
              GROUP BY pi.currency, pi.itemreference, pi.label, pi.itemtype
              ORDER BY revenueminor DESC";
        $result = [];
        foreach ($this->db->get_records_sql($sql, $params, 0, $limit * 4) as $row) {
            $currency = strtoupper((string)$row->currency);
            if (count($result[$currency] ?? []) >= $limit) { continue; }
            $result[$currency][] = [
                'reference' => (string)$row->itemreference,
                'label' => (string)$row->label,
                'quantity' => (int)$row->quantity,
                'revenue_minor' => (int)$row->revenueminor,
                'itemtype' => (string)$row->itemtype,
            ];
        }
        return $result;
    }

    /**
     * Product-level commercial performance, without currency conversion.
     *
     * Revenue only includes purchases with a successful payment. Free orders are
     * counted separately and never mixed into paid revenue.
     *
     * @return CommerceProductStatisticsRow[]
     */
    public function product_statistics(
        CommerceStatisticsPeriod $period,
        CommerceStatisticsFilter $filter,
        int $limit = 100
    ): array {
        $limit = max(1, min(500, $limit));
        $params = ['start' => $period->start(), 'end' => $period->end()];
        $where = ['p.timecreated >= :start', 'p.timecreated < :end'];

        if ($filter->currency() !== null) {
            $where[] = 'pi.currency = :currency';
            $params['currency'] = $filter->currency();
        }
        if ($filter->product_reference() !== null) {
            $where[] = 'pi.itemreference = :itemreference';
            $params['itemreference'] = $filter->product_reference();
        }

        $successfulpayment = "EXISTS (
            SELECT 1
              FROM {local_subscriptions_commerce_payment} pay
             WHERE pay.purchaseid = p.id
               AND pay.status IN ('paid', 'succeeded', 'completed', 'captured')
        )";

        $sql = "SELECT MIN(pi.id) AS recordid,
                       pi.itemreference,
                       pi.label,
                       pi.itemtype,
                       pi.currency,
                       COUNT(DISTINCT p.id) AS orderscount,
                       SUM(pi.quantity) AS quantitycount,
                       COUNT(DISTINCT CASE WHEN {$successfulpayment} THEN p.id END) AS paidorders,
                       COUNT(DISTINCT CASE WHEN p.totalminor = 0 THEN p.id END) AS freeorders,
                       COALESCE(SUM(CASE WHEN {$successfulpayment} THEN pi.netminor ELSE 0 END), 0) AS revenueminor
                  FROM {local_subscriptions_commerce_purchase_item} pi
                  JOIN {local_subscriptions_commerce_purchase} p ON p.id = pi.purchaseid
                 WHERE " . implode(' AND ', $where) . "
              GROUP BY pi.itemreference, pi.label, pi.itemtype, pi.currency
              ORDER BY revenueminor DESC, paidorders DESC, orderscount DESC";

        $rows = [];
        foreach ($this->db->get_records_sql($sql, $params, 0, $limit) as $row) {
            $rows[] = new CommerceProductStatisticsRow(
                (string)$row->itemreference,
                (string)$row->label,
                (string)$row->itemtype,
                strtoupper((string)$row->currency),
                (int)$row->orderscount,
                (int)$row->quantitycount,
                (int)$row->paidorders,
                (int)$row->freeorders,
                (int)$row->revenueminor
            );
        }

        return $rows;
    }


    /**
     * Product performance for all known references of one catalogue product.
     *
     * Native purchases use the catalogue SKU, while imported historical purchases
     * can retain a legacy reference such as subscription-plan:12 or digital-product:7.
     *
     * @param string[] $references
     * @return CommerceProductStatisticsRow[]
     */
    public function product_statistics_for_references(
        CommerceStatisticsPeriod $period,
        array $references,
        int $limit = 100,
        ?string $currency = null
    ): array {
        $references = array_values(array_unique(array_filter(array_map('trim', $references))));
        if ($references === []) {
            return [];
        }

        $limit = max(1, min(500, $limit));
        $params = ['start' => $period->start(), 'end' => $period->end()];
        [$insql, $inparams] = $this->db->get_in_or_equal($references, SQL_PARAMS_NAMED, 'productref');
        $params += $inparams;
        $currencywhere = '';
        if ($currency !== null) {
            $currency = strtoupper($currency);
            $params['productcurrency'] = $currency;
            $currencywhere = ' AND pi.currency = :productcurrency';
        }

        $successfulpayment = "EXISTS (
            SELECT 1
              FROM {local_subscriptions_commerce_payment} pay
             WHERE pay.purchaseid = p.id
               AND pay.status IN ('paid', 'succeeded', 'completed', 'captured')
        )";

        $sql = "SELECT MIN(pi.id) AS recordid,
                       MIN(pi.itemreference) AS itemreference,
                       MIN(pi.label) AS label,
                       MIN(pi.itemtype) AS itemtype,
                       pi.currency,
                       COUNT(DISTINCT p.id) AS orderscount,
                       SUM(pi.quantity) AS quantitycount,
                       COUNT(DISTINCT CASE WHEN {$successfulpayment} THEN p.id END) AS paidorders,
                       COUNT(DISTINCT CASE WHEN p.totalminor = 0 THEN p.id END) AS freeorders,
                       COALESCE(SUM(CASE WHEN {$successfulpayment} THEN pi.netminor ELSE 0 END), 0) AS revenueminor
                  FROM {local_subscriptions_commerce_purchase_item} pi
                  JOIN {local_subscriptions_commerce_purchase} p ON p.id = pi.purchaseid
                 WHERE p.timecreated >= :start
                   AND p.timecreated < :end
                   AND pi.itemreference {$insql}{$currencywhere}
              GROUP BY pi.currency
              ORDER BY revenueminor DESC, paidorders DESC, orderscount DESC";

        $rows = [];
        foreach ($this->db->get_records_sql($sql, $params, 0, $limit) as $row) {
            $rows[] = new CommerceProductStatisticsRow(
                (string)$row->itemreference,
                (string)$row->label,
                (string)$row->itemtype,
                strtoupper((string)$row->currency),
                (int)$row->orderscount,
                (int)$row->quantitycount,
                (int)$row->paidorders,
                (int)$row->freeorders,
                (int)$row->revenueminor
            );
        }
        return $rows;
    }

    /** @param string[] $references @return array<string,CommerceStatisticsSeries> */
    public function product_revenue_series_for_references(
        CommerceStatisticsPeriod $period,
        array $references,
        ?string $currency = null
    ): array {
        $references = array_values(array_unique(array_filter(array_map('trim', $references))));
        if ($references === []) {
            return [];
        }

        $params = ['start' => $period->start(), 'end' => $period->end()];
        [$insql, $inparams] = $this->db->get_in_or_equal($references, SQL_PARAMS_NAMED, 'seriesref');
        $params += $inparams;
        $currencywhere = '';
        if ($currency !== null) {
            $currency = strtoupper($currency);
            $params['seriescurrency'] = $currency;
            $currencywhere = ' AND pi.currency = :seriescurrency';
        }
        $successfulpayment = "EXISTS (
            SELECT 1 FROM {local_subscriptions_commerce_payment} pay
             WHERE pay.purchaseid = p.id
               AND pay.status IN ('paid', 'succeeded', 'completed', 'captured')
        )";
        $sql = "SELECT pi.id, p.timecreated, pi.currency,
                       CASE WHEN {$successfulpayment} THEN pi.netminor ELSE 0 END AS revenueminor
                  FROM {local_subscriptions_commerce_purchase_item} pi
                  JOIN {local_subscriptions_commerce_purchase} p ON p.id = pi.purchaseid
                 WHERE p.timecreated >= :start
                   AND p.timecreated < :end
                   AND pi.itemreference {$insql}{$currencywhere}
              ORDER BY p.timecreated ASC";
        $records = $this->db->get_records_sql($sql, $params);
        $granularity = self::granularity($period);
        $buckets = [];
        foreach ($records as $record) {
            $currency = strtoupper((string)$record->currency);
            $bucket = self::bucket_start((int)$record->timecreated, $granularity);
            $buckets[$currency][$bucket] ??= 0;
            $buckets[$currency][$bucket] += (int)$record->revenueminor;
        }
        $result = [];
        foreach ($buckets as $currency => $values) {
            ksort($values);
            $points = [];
            foreach ($values as $timestamp => $value) {
                $points[] = [
                    'timestamp' => (int)$timestamp,
                    'label' => self::bucket_label((int)$timestamp, $granularity),
                    'value' => $value,
                ];
            }
            $result[$currency] = new CommerceStatisticsSeries('revenue', $currency, $granularity, $points);
        }
        return $result;
    }


    /** @param string[] $references @return array<string,CommerceStatisticsSeries> */
    public function product_order_series_for_references(
        CommerceStatisticsPeriod $period,
        array $references,
        ?string $currency = null
    ): array {
        $references = array_values(array_unique(array_filter(array_map('trim', $references))));
        if ($references === []) {
            return [];
        }
        $params = ['start' => $period->start(), 'end' => $period->end()];
        [$insql, $inparams] = $this->db->get_in_or_equal($references, SQL_PARAMS_NAMED, 'orderref');
        $params += $inparams;
        $currencywhere = '';
        if ($currency !== null) {
            $params['ordercurrency'] = strtoupper($currency);
            $currencywhere = ' AND pi.currency = :ordercurrency';
        }
        $sql = "SELECT MIN(pi.id) AS recordid, p.id AS purchaseid, p.timecreated, pi.currency
                  FROM {local_subscriptions_commerce_purchase_item} pi
                  JOIN {local_subscriptions_commerce_purchase} p ON p.id = pi.purchaseid
                 WHERE p.timecreated >= :start
                   AND p.timecreated < :end
                   AND pi.itemreference {$insql}{$currencywhere}
              GROUP BY p.id, p.timecreated, pi.currency
              ORDER BY p.timecreated ASC";
        $granularity = self::granularity($period);
        $buckets = [];
        foreach ($this->db->get_records_sql($sql, $params) as $record) {
            $code = strtoupper((string)$record->currency);
            $bucket = self::bucket_start((int)$record->timecreated, $granularity);
            $buckets[$code][$bucket] = ($buckets[$code][$bucket] ?? 0) + 1;
        }
        $result = [];
        foreach ($buckets as $code => $values) {
            ksort($values);
            $points = [];
            foreach ($values as $timestamp => $value) {
                $points[] = [
                    'timestamp' => (int)$timestamp,
                    'label' => self::bucket_label((int)$timestamp, $granularity),
                    'value' => (int)$value,
                ];
            }
            $result[$code] = new CommerceStatisticsSeries('orders', $code, $granularity, $points);
        }
        return $result;
    }

    /** @param string[] $references @return array<string,int> */
    public function product_failed_payments_for_references(
        CommerceStatisticsPeriod $period,
        array $references,
        ?string $currency = null
    ): array {
        $references = array_values(array_unique(array_filter(array_map('trim', $references))));
        if ($references === []) {
            return [];
        }
        $params = ['start' => $period->start(), 'end' => $period->end()];
        [$insql, $inparams] = $this->db->get_in_or_equal($references, SQL_PARAMS_NAMED, 'failedref');
        $params += $inparams;
        $currencywhere = '';
        if ($currency !== null) {
            $params['failedcurrency'] = strtoupper($currency);
            $currencywhere = ' AND pay.currency = :failedcurrency';
        }
        $sql = "SELECT MIN(pay.id) AS recordid, pay.currency, COUNT(pay.id) AS failedcount
                  FROM {local_subscriptions_commerce_payment} pay
                  JOIN {local_subscriptions_commerce_purchase} p ON p.id = pay.purchaseid
                 WHERE p.timecreated >= :start
                   AND p.timecreated < :end
                   AND pay.status IN ('failed', 'declined', 'cancelled', 'canceled', 'expired')
                   AND EXISTS (
                       SELECT 1
                         FROM {local_subscriptions_commerce_purchase_item} pi
                        WHERE pi.purchaseid = p.id
                          AND pi.itemreference {$insql}
                   ){$currencywhere}
              GROUP BY pay.currency";
        $result = [];
        foreach ($this->db->get_records_sql($sql, $params) as $row) {
            $result[strtoupper((string)$row->currency)] = (int)$row->failedcount;
        }
        return $result;
    }

    public static function granularity(CommerceStatisticsPeriod $period): string {
        $days = (int)ceil($period->duration() / DAYSECS);
        return $days <= 45 ? 'day' : ($days <= 180 ? 'week' : 'month');
    }

    private static function bucket_start(int $timestamp, string $granularity): int {
        if ($granularity === 'month') { return make_timestamp((int)userdate($timestamp, '%Y'), (int)userdate($timestamp, '%m'), 1); }
        if ($granularity === 'week') {
            $day = (int)userdate($timestamp, '%u');
            return usergetmidnight($timestamp - (($day - 1) * DAYSECS));
        }
        return usergetmidnight($timestamp);
    }

    private static function bucket_label(int $timestamp, string $granularity): string {
        return $granularity === 'month' ? userdate($timestamp, '%b %Y') : userdate($timestamp, '%d %b');
    }

}
