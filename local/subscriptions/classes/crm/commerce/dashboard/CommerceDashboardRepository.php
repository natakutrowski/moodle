<?php

declare(strict_types=1);

namespace local_subscriptions\crm\commerce\dashboard;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\statistics\CommerceStatisticsProductCanonicalizer;

/** Read model for the Commerce CRM dashboard. */
final class CommerceDashboardRepository {
    private const SUCCESS_PAYMENT_STATUSES = ['paid', 'succeeded', 'completed', 'captured'];
    private const AUDIT_EMAIL = 'log@campusfr.fr';

    public function __construct(private readonly \moodle_database $db) {}

    /** @return array<string,mixed> */
    public function snapshot(
        ?int $now = null,
        string|int $period = 30,
        ?int $customstart = null,
        ?int $customend = null,
        string $requestedcurrency = ''
    ): array {
        $now ??= time();
        [$start, $end, $mode, $days] = $this->resolve_period($now, $period, $customstart, $customend);
        $duration = max(1, $end - $start);
        $previousstart = $start - $duration;

        $revenue = $this->revenue($start, $end);
        $previous = $this->revenue($previousstart, $start);
        $series = $this->revenue_series($start, $end);
        $topproducts = $this->top_products($start, $end, 5);
        $currencies = array_values(array_unique(array_merge(
            array_keys($revenue), array_keys($previous), array_keys($series), array_keys($topproducts)
        )));
        sort($currencies);
        $requestedcurrency = strtoupper(trim($requestedcurrency));
        $currency = in_array($requestedcurrency, $currencies, true)
            ? $requestedcurrency
            : ($currencies[0] ?? ($requestedcurrency !== '' ? $requestedcurrency : 'EUR'));

        return [
            'period' => ['start' => $start, 'end' => $end, 'mode' => $mode, 'days' => $days],
            'currency' => $currency,
            'currencies' => $currencies,
            'revenue' => $revenue,
            'revenueprevious' => $previous,
            'orders' => $this->paid_order_count($start, $end),
            'ordersprevious' => $this->paid_order_count($previousstart, $start),
            'orderstatus' => $this->order_status_summary($start, $end),
            'conversion' => $this->conversion_rate($start, $end),
            'conversionprevious' => $this->conversion_rate($previousstart, $start),
            'customers' => $this->customer_count($start, $end),
            'customersprevious' => $this->customer_count($previousstart, $start),
            'mailssent' => $this->sent_mail_count($start, $end),
            'mailssentprevious' => $this->sent_mail_count($previousstart, $start),
            'mailfailed' => $this->mail_count_by_status(['failed']),
            'mailpending' => $this->mail_count_by_status(['queued', 'processing']),
            'unfinishedcheckouts' => $this->unfinished_checkout_count(),
            'activeoffers' => $this->active_offer_count($now),
            'expiringoffers' => $this->expiring_offer_count($now, $now + (7 * DAYSECS)),
            'grantproblems' => $this->grant_problem_count($now),
            'latestsales' => $this->latest_sales(4),
            'topproducts' => $topproducts,
            'products' => $this->product_summary(),
            'showrooms' => $this->showroom_summary(),
            'series' => $series,
        ];
    }

    /** @return array{0:int,1:int,2:string,3:int} */
    private function resolve_period(int $now, string|int $period, ?int $customstart, ?int $customend): array {
        $mode = strtolower((string)$period);
        if ($mode === 'today') {
            $start = usergetmidnight($now);
            return [$start, $now, 'today', 1];
        }
        if ($mode === 'custom' && $customstart !== null && $customend !== null && $customstart < $customend) {
            return [$customstart, min($customend, $now), 'custom', max(1, (int)ceil(($customend - $customstart) / DAYSECS))];
        }
        $days = (int)$period;
        $days = in_array($days, [7, 30, 90, 365], true) ? $days : 30;
        return [$now - ($days * DAYSECS), $now, (string)$days, $days];
    }

    /** @return array<string,int> */
    private function revenue(int $start, int $end): array {
        [$statussql, $params] = $this->db->get_in_or_equal(self::SUCCESS_PAYMENT_STATUSES, SQL_PARAMS_NAMED, 'payst');
        $params += ['start' => $start, 'end' => $end];
        $sql = "SELECT MIN(pay.id) AS rowid, UPPER(pay.currency) AS currency, COALESCE(SUM(pay.amountminor), 0) AS amountminor
                  FROM {local_subscriptions_commerce_payment} pay
                  JOIN {local_subscriptions_commerce_purchase} p ON p.id = pay.purchaseid
                 WHERE pay.status {$statussql} AND p.timecreated >= :start AND p.timecreated < :end
              GROUP BY UPPER(pay.currency)";
        $result = [];
        foreach ($this->db->get_records_sql($sql, $params) as $row) {
            $result[(string)$row->currency] = (int)$row->amountminor;
        }
        ksort($result);
        return $result;
    }

    private function paid_order_count(int $start, int $end): int {
        [$statussql, $params] = $this->db->get_in_or_equal(self::SUCCESS_PAYMENT_STATUSES, SQL_PARAMS_NAMED, 'ordst');
        $params += ['start' => $start, 'end' => $end];
        return (int)$this->db->count_records_sql(
            "SELECT COUNT(DISTINCT p.id) FROM {local_subscriptions_commerce_purchase} p
               JOIN {local_subscriptions_commerce_payment} pay ON pay.purchaseid = p.id
              WHERE pay.status {$statussql} AND p.timecreated >= :start AND p.timecreated < :end", $params
        );
    }

    /** @return array{total:int,pending:int,failed:int} */
    private function order_status_summary(int $start, int $end): array {
        $params = ['start' => $start, 'end' => $end, 'draft' => 'draft'];
        $total = (int)$this->db->count_records_select('local_subscriptions_commerce_purchase',
            'timecreated >= :start AND timecreated < :end AND status <> :draft', $params);
        [$pendinginsql, $pendingparams] = $this->db->get_in_or_equal(
            ['created', 'prepared', 'payment_pending', 'authorized'], SQL_PARAMS_NAMED, 'dashpending');
        $pendingparams += ['start' => $start, 'end' => $end];
        $pending = (int)$this->db->count_records_select('local_subscriptions_commerce_purchase',
            'timecreated >= :start AND timecreated < :end AND status ' . $pendinginsql, $pendingparams);
        [$failedinsql, $failedparams] = $this->db->get_in_or_equal(['failed', 'cancelled'], SQL_PARAMS_NAMED, 'dashfailed');
        $failedparams += ['start' => $start, 'end' => $end];
        $failed = (int)$this->db->count_records_select('local_subscriptions_commerce_purchase',
            'timecreated >= :start AND timecreated < :end AND status ' . $failedinsql, $failedparams);
        return ['total' => $total, 'pending' => $pending, 'failed' => $failed];
    }

    private function conversion_rate(int $start, int $end): float {
        $summary = $this->order_status_summary($start, $end);
        return $summary['total'] > 0 ? ($this->paid_order_count($start, $end) / $summary['total']) * 100 : 0.0;
    }

    private function customer_count(int $start, int $end): int {
        [$statussql, $params] = $this->db->get_in_or_equal(self::SUCCESS_PAYMENT_STATUSES, SQL_PARAMS_NAMED, 'custst');
        $params += ['start' => $start, 'end' => $end];
        $records = $this->db->get_records_sql(
            "SELECT p.id, p.userid, p.customeremail FROM {local_subscriptions_commerce_purchase} p
              WHERE p.timecreated >= :start AND p.timecreated < :end
                AND EXISTS (SELECT 1 FROM {local_subscriptions_commerce_payment} pay
                             WHERE pay.purchaseid = p.id AND pay.status {$statussql})", $params);
        $customers = [];
        foreach ($records as $record) {
            if (!empty($record->userid)) { $customers['u:' . (int)$record->userid] = true; continue; }
            $email = \core_text::strtolower(trim((string)$record->customeremail));
            if ($email !== '') { $customers['e:' . $email] = true; }
        }
        return count($customers);
    }

    private function sent_mail_count(int $start, int $end): int {
        return (int)$this->db->count_records_select('local_subs_commerce_mail',
            'status = :status AND timesent IS NOT NULL AND timesent >= :start AND timesent < :end
             AND LOWER(recipientemail) <> :auditmail AND idempotencykey NOT LIKE :auditpattern',
            ['status' => 'sent', 'start' => $start, 'end' => $end,
             'auditmail' => self::AUDIT_EMAIL, 'auditpattern' => '%:audit']);
    }

    /** @param string[] $statuses */
    private function mail_count_by_status(array $statuses): int {
        [$insql, $params] = $this->db->get_in_or_equal($statuses, SQL_PARAMS_NAMED, 'mailst');
        $params['auditmail'] = self::AUDIT_EMAIL;
        return (int)$this->db->count_records_select('local_subs_commerce_mail',
            'status ' . $insql . ' AND LOWER(recipientemail) <> :auditmail', $params);
    }

    private function unfinished_checkout_count(): int {
        return (int)$this->db->count_records('local_subscriptions_commerce_purchase', ['status' => 'payment_pending']);
    }

    private function active_offer_count(int $now): int {
        return (int)$this->db->count_records_select('local_subs_commerce_offer',
            'status = :status AND (validfrom IS NULL OR validfrom <= :now1) AND (expiresat IS NULL OR expiresat >= :now2)',
            ['status' => 'issued', 'now1' => $now, 'now2' => $now]);
    }

    private function expiring_offer_count(int $now, int $until): int {
        return (int)$this->db->count_records_select('local_subs_commerce_offer',
            'status = :status AND expiresat IS NOT NULL AND expiresat >= :now AND expiresat < :until',
            ['status' => 'issued', 'now' => $now, 'until' => $until]);
    }

    private function grant_problem_count(int $now): int {
        return (int)$this->db->count_records_select('local_subs_commerce_grant',
            '(status = :failed) OR (status = :planned AND timecreated < :stale)',
            ['failed' => 'failed', 'planned' => 'planned', 'stale' => $now - (15 * MINSECS)]);
    }

    /** @return array<int,array<string,mixed>> */
    private function latest_sales(int $limit): array {
        [$statussql, $params] = $this->db->get_in_or_equal(self::SUCCESS_PAYMENT_STATUSES, SQL_PARAMS_NAMED, 'latestst');
        $sql = "SELECT p.id, p.reference, p.userid, p.customeremail, p.currency, p.totalminor, p.timecreated,
                       (SELECT pi.label FROM {local_subscriptions_commerce_purchase_item} pi
                         WHERE pi.purchaseid = p.id ORDER BY pi.position ASC, pi.id ASC LIMIT 1) AS productlabel,
                       (SELECT pi.itemreference FROM {local_subscriptions_commerce_purchase_item} pi
                         WHERE pi.purchaseid = p.id ORDER BY pi.position ASC, pi.id ASC LIMIT 1) AS productsku
                  FROM {local_subscriptions_commerce_purchase} p
                 WHERE EXISTS (SELECT 1 FROM {local_subscriptions_commerce_payment} pay
                                WHERE pay.purchaseid = p.id AND pay.status {$statussql})
              ORDER BY p.timecreated DESC, p.id DESC";
        $rows = array_values($this->db->get_records_sql($sql, $params, 0, $limit));
        $result = [];
        foreach ($rows as $row) {
            $name = trim((string)$row->customeremail);
            $user = null;
            if (!empty($row->userid)) {
                $user = $this->db->get_record('user', ['id' => (int)$row->userid, 'deleted' => 0], '*');
                if ($user) {
                    $name = fullname($user) ?: (string)$user->email;
                }
            }
            $sku = trim((string)$row->productsku);
            $productexists = $sku !== '' && $this->db->record_exists('local_subs_commerce_product', ['sku' => $sku]);
            $result[] = [
                'id' => (int)$row->id,
                'userid' => (int)($row->userid ?? 0),
                'user' => $user ?: null,
                'customer' => $name !== '' ? $name : get_string('unknownuser'),
                'product' => trim((string)$row->productlabel) !== '' ? (string)$row->productlabel : '—',
                'productsku' => $productexists ? $sku : '',
                'currency' => strtoupper((string)$row->currency),
                'totalminor' => (int)$row->totalminor,
                'timecreated' => (int)$row->timecreated,
            ];
        }
        return $result;
    }

    /** @return array<string,array<int,array<string,mixed>>> */
    private function top_products(int $start, int $end, int $limit): array {
        [$statussql, $params] = $this->db->get_in_or_equal(
            self::SUCCESS_PAYMENT_STATUSES,
            SQL_PARAMS_NAMED,
            'topst'
        );
        $params += ['start' => $start, 'end' => $end];

        // Keep immutable snapshots in SQL, then canonicalise Legacy + Native
        // references in PHP so one logical catalogue product appears once.
        $sql = "SELECT MIN(pi.id) AS rowid, UPPER(pi.currency) AS currency,
                       pi.itemreference, pi.label, pi.itemtype, pi.metadatajson,
                       SUM(pi.quantity) AS quantity, SUM(pi.netminor) AS revenueminor
                  FROM {local_subscriptions_commerce_purchase_item} pi
                  JOIN {local_subscriptions_commerce_purchase} p ON p.id = pi.purchaseid
                 WHERE p.timecreated >= :start AND p.timecreated < :end
                   AND EXISTS (
                       SELECT 1
                         FROM {local_subscriptions_commerce_payment} pay
                        WHERE pay.purchaseid = p.id
                          AND pay.status {$statussql}
                   )
              GROUP BY UPPER(pi.currency), pi.itemreference, pi.label, pi.itemtype, pi.metadatajson";

        $canonicalizer = new CommerceStatisticsProductCanonicalizer($this->db);
        $aggregated = [];
        foreach ($this->db->get_records_sql($sql, $params) as $row) {
            $currency = strtoupper((string)$row->currency);
            $canonical = $canonicalizer->canonicalise(
                (string)$row->itemreference,
                (string)$row->label,
                (string)$row->metadatajson
            );
            $displaykey = \core_text::strtolower(trim((string)$canonical['label']));
            $key = $currency . '|label:' . $displaykey;

            if (!isset($aggregated[$key])) {
                $aggregated[$key] = [
                    'currency' => $currency,
                    'reference' => $canonical['reference'],
                    'label' => $canonical['label'],
                    'quantity' => 0,
                    'revenueminor' => 0,
                    '_priority' => (int)$canonical['priority'],
                ];
            } else if ((int)$canonical['priority'] > (int)$aggregated[$key]['_priority']) {
                // Prefer the sellable Native product for the link/label when
                // Legacy and Native snapshots have the same display identity.
                $aggregated[$key]['reference'] = $canonical['reference'];
                $aggregated[$key]['label'] = $canonical['label'];
                $aggregated[$key]['_priority'] = (int)$canonical['priority'];
            }
            $aggregated[$key]['quantity'] += (int)$row->quantity;
            $aggregated[$key]['revenueminor'] += (int)$row->revenueminor;
        }

        $bycurrency = [];
        foreach ($aggregated as $row) {
            unset($row['_priority']);
            $bycurrency[$row['currency']][] = $row;
        }

        $result = [];
        foreach ($bycurrency as $currency => $rows) {
            usort($rows, static fn(array $a, array $b): int =>
                $b['revenueminor'] <=> $a['revenueminor']
            );
            $result[$currency] = array_slice($rows, 0, $limit);
        }

        return $result;
    }

    /** @return array<string,int> */
    private function product_summary(): array {
        $rows = $this->db->get_records_sql(
            "SELECT MIN(id) AS rowid, type, COUNT(1) AS productcount FROM {local_subs_commerce_product}
              WHERE status = :status GROUP BY type", ['status' => 'active']);
        $result = ['total' => 0, 'bundle' => 0, 'course' => 0, 'digital' => 0, 'other' => 0];
        foreach ($rows as $row) {
            $count = (int)$row->productcount;
            $type = strtolower((string)$row->type);
            $result['total'] += $count;
            if ($type === 'bundle') { $result['bundle'] += $count; }
            else if (in_array($type, ['course', 'course_access', 'subscription'], true)) { $result['course'] += $count; }
            else if (in_array($type, ['digital', 'digital_download', 'digital_pdf', 'pdf'], true)) { $result['digital'] += $count; }
            else { $result['other'] += $count; }
        }
        return $result;
    }

    /** @return array{total:int,published:int,workinprogress:int} */
    private function showroom_summary(): array {
        $rows = $this->db->get_records_sql(
            "SELECT MIN(id) AS rowid, status, COUNT(1) AS showroomcount
               FROM {local_subs_showroom}
              WHERE status <> :archived
           GROUP BY status",
            ['archived' => 'archived']
        );
        $result = ['total' => 0, 'published' => 0, 'workinprogress' => 0];
        foreach ($rows as $row) {
            $count = (int)$row->showroomcount;
            $status = strtolower((string)$row->status);
            $result['total'] += $count;
            if ($status === 'published') {
                $result['published'] += $count;
            } else {
                $result['workinprogress'] += $count;
            }
        }
        return $result;
    }

    /** @return array<string,array<int,array{timestamp:int,value:int}>> */
    private function revenue_series(int $start, int $end): array {
        [$statussql, $params] = $this->db->get_in_or_equal(self::SUCCESS_PAYMENT_STATUSES, SQL_PARAMS_NAMED, 'serst');
        $params += ['start' => $start, 'end' => $end];
        $records = $this->db->get_records_sql(
            "SELECT pay.id, pay.currency, pay.amountminor, p.timecreated
               FROM {local_subscriptions_commerce_payment} pay
               JOIN {local_subscriptions_commerce_purchase} p ON p.id = pay.purchaseid
              WHERE pay.status {$statussql} AND p.timecreated >= :start AND p.timecreated < :end
           ORDER BY p.timecreated ASC", $params);
        $totaldays = max(1, (int)ceil(($end - $start) / DAYSECS));
        $bucketdays = $totaldays > 120 ? 7 : 1;
        $bucketsecs = $bucketdays * DAYSECS;
        $bucketcount = max(1, (int)ceil(($end - $start) / $bucketsecs));
        $values = [];
        foreach ($records as $record) {
            $currency = strtoupper((string)$record->currency);
            $index = min($bucketcount - 1, max(0, (int)floor(((int)$record->timecreated - $start) / $bucketsecs)));
            $values[$currency][$index] = ($values[$currency][$index] ?? 0) + (int)$record->amountminor;
        }
        $result = [];
        foreach ($values as $currency => $currencyvalues) {
            for ($i = 0; $i < $bucketcount; $i++) {
                $result[$currency][] = ['timestamp' => $start + ($i * $bucketsecs), 'value' => (int)($currencyvalues[$i] ?? 0)];
            }
        }
        return $result;
    }
}
