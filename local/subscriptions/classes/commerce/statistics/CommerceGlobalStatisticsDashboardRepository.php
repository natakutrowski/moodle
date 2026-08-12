<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\statistics;

defined('MOODLE_INTERNAL') || die();

/**
 * Commercially authoritative global Commerce dashboard datasets.
 *
 * Paid revenue / orders are based on successful payment records. Payment-health
 * status uses the latest payment attempt for each purchase, preventing historical
 * retries from being displayed as current order status.
 */
final class CommerceGlobalStatisticsDashboardRepository {
    public function __construct(private readonly \moodle_database $db) {}

    public function snapshot(
        CommerceStatisticsPeriod $period,
        ?string $currency = null,
        ?string $provider = null
    ): array {
        $currency = $this->normalise_currency($currency);
        $provider = $this->normalise_provider($provider);

        $paid = $this->paid_metrics($period, $currency, $provider);
        $health = $this->payment_health($period, $currency, $provider);
        $manual = $this->manual_grants($period);
        $providers = $this->provider_breakdown($period, $currency, $provider);
        $acquisitions = $this->acquisition_breakdown($period, $currency, $provider, $manual['quantity']);

        $attempts = 0;
        $success = 0;
        foreach ($health as $row) {
            $attempts += array_sum($row);
            $success += (int)($row['paid'] ?? 0);
        }

        $global = [
            'paidorders' => 0,
            'paidcustomers' => 0,
            'soldquantity' => 0,
            'manualgrants' => $manual['quantity'],
            'manualrecipients' => $manual['recipients'],
            'paymentattempts' => $attempts,
            'successfulpayments' => $success,
            'pending' => 0,
            'failed' => 0,
            'cancelled' => 0,
            'refunded' => 0,
            'pendingfulfillments' => $this->pending_fulfillments($period, $currency),
        ];

        foreach ($paid as &$row) {
            $row['averageorderminor'] = $row['paidorders'] > 0
                ? (int)round($row['revenueminor'] / $row['paidorders'])
                : 0;
            $global['paidorders'] += $row['paidorders'];
            $global['paidcustomers'] += $row['paidcustomers'];
            $global['soldquantity'] += $row['soldquantity'];
        }
        unset($row);

        foreach ($health as $row) {
            foreach (['pending', 'failed', 'cancelled', 'refunded'] as $key) {
                $global[$key] += (int)($row[$key] ?? 0);
            }
        }

        $global['paymentsuccessrate'] = $attempts > 0 ? ($success / $attempts) * 100 : 0.0;
        $global['delivered'] = $global['soldquantity'] + (int)($acquisitions['free'] ?? 0) + $global['manualgrants'];

        return [
            'global' => $global,
            'currencies' => $paid,
            'payments' => $health,
            'providers' => $providers,
            'acquisitions' => $acquisitions,
            'manual' => $manual,
        ];
    }

    /** @return array<string,CommerceStatisticsSeries> */
    public function revenue_series(
        CommerceStatisticsPeriod $period,
        ?string $currency = null,
        ?string $provider = null
    ): array {
        $currency = $this->normalise_currency($currency);
        $provider = $this->normalise_provider($provider);
        $params = ['start' => $period->start(), 'end' => $period->end()];
        $where = ['p.timecreated >= :start', 'p.timecreated < :end'];

        if ($currency !== null) {
            $where[] = 'pi.currency = :currency';
            $params['currency'] = $currency;
        }
        $providerwhere = '';
        if ($provider !== null) {
            $params['provider'] = $provider;
            $providerwhere = ' AND pay.provider = :provider';
        }

        $sql = "SELECT pi.id,p.timecreated,pi.currency,pi.netminor
                  FROM {local_subscriptions_commerce_purchase_item} pi
                  JOIN {local_subscriptions_commerce_purchase} p ON p.id=pi.purchaseid
                 WHERE " . implode(' AND ', $where) . "
                   AND EXISTS (
                       SELECT 1 FROM {local_subscriptions_commerce_payment} pay
                        WHERE pay.purchaseid=p.id
                          AND pay.status IN ('paid','succeeded','completed','captured')
                          {$providerwhere}
                   )
              ORDER BY p.timecreated,pi.id";

        return $this->series_from_rows(
            $this->db->get_records_sql($sql, $params),
            $period,
            static fn(object $row): int => (int)$row->netminor,
            'revenue'
        );
    }

    /** @return array<string,CommerceStatisticsSeries> */
    public function paid_order_series(
        CommerceStatisticsPeriod $period,
        ?string $currency = null,
        ?string $provider = null
    ): array {
        $currency = $this->normalise_currency($currency);
        $provider = $this->normalise_provider($provider);
        $params = ['start' => $period->start(), 'end' => $period->end()];
        $where = ['p.timecreated >= :start', 'p.timecreated < :end'];

        if ($currency !== null) {
            $where[] = 'p.currency = :currency';
            $params['currency'] = $currency;
        }
        $providerwhere = '';
        if ($provider !== null) {
            $params['provider'] = $provider;
            $providerwhere = ' AND pay.provider = :provider';
        }

        $sql = "SELECT p.id,p.timecreated,p.currency
                  FROM {local_subscriptions_commerce_purchase} p
                 WHERE " . implode(' AND ', $where) . "
                   AND EXISTS (
                       SELECT 1 FROM {local_subscriptions_commerce_payment} pay
                        WHERE pay.purchaseid=p.id
                          AND pay.status IN ('paid','succeeded','completed','captured')
                          {$providerwhere}
                   )
              ORDER BY p.timecreated,p.id";

        return $this->series_from_rows(
            $this->db->get_records_sql($sql, $params),
            $period,
            static fn(object $row): int => 1,
            'orders'
        );
    }

    /** @return array<string,array<int,array<string,mixed>>> */
    public function top_products(
        CommerceStatisticsPeriod $period,
        ?string $currency = null,
        ?string $provider = null,
        int $limit = 8
    ): array {
        $currency = $this->normalise_currency($currency);
        $provider = $this->normalise_provider($provider);
        $params = ['start' => $period->start(), 'end' => $period->end()];
        $where = ['p.timecreated >= :start', 'p.timecreated < :end'];

        if ($currency !== null) {
            $where[] = 'pi.currency = :currency';
            $params['currency'] = $currency;
        }
        $providerwhere = '';
        if ($provider !== null) {
            $params['provider'] = $provider;
            $providerwhere = ' AND pay.provider = :provider';
        }

        $sql = "SELECT MIN(pi.id) recordid,pi.currency,pi.itemreference,pi.label,pi.itemtype,
                       SUM(pi.quantity) quantity,SUM(pi.netminor) revenueminor
                  FROM {local_subscriptions_commerce_purchase_item} pi
                  JOIN {local_subscriptions_commerce_purchase} p ON p.id=pi.purchaseid
                 WHERE " . implode(' AND ', $where) . "
                   AND EXISTS (
                       SELECT 1 FROM {local_subscriptions_commerce_payment} pay
                        WHERE pay.purchaseid=p.id
                          AND pay.status IN ('paid','succeeded','completed','captured')
                          {$providerwhere}
                   )
              GROUP BY pi.currency,pi.itemreference,pi.label,pi.itemtype
              ORDER BY revenueminor DESC";

        $out = [];
        foreach ($this->db->get_records_sql($sql, $params, 0, max(8, $limit) * 4) as $row) {
            $code = strtoupper((string)$row->currency);
            if (count($out[$code] ?? []) >= $limit) {
                continue;
            }
            $out[$code][] = [
                'reference' => (string)$row->itemreference,
                'label' => (string)$row->label,
                'itemtype' => (string)$row->itemtype,
                'quantity' => (int)$row->quantity,
                'revenue_minor' => (int)$row->revenueminor,
            ];
        }
        return $out;
    }

    /**
     * Current/latest payment state by product.
     *
     * Each purchase contributes once per product using its latest payment attempt,
     * so branch totals remain mutually exclusive and suitable for a payment tree.
     *
     * @return array<string,array<int,array<string,mixed>>>
     */
    public function product_payment_breakdown(
        CommerceStatisticsPeriod $period,
        ?string $currency = null,
        ?string $provider = null,
        int $limit = 12
    ): array {
        $currency = $this->normalise_currency($currency);
        $provider = $this->normalise_provider($provider);
        $params = ['start'=>$period->start(),'end'=>$period->end()];
        $where = ['p.timecreated>=:start','p.timecreated<:end'];

        if ($currency !== null) {
            $where[]='pi.currency=:currency';
            $params['currency']=$currency;
        }
        if ($provider !== null) {
            $where[]='pay.provider=:provider';
            $params['provider']=$provider;
        }

        $sql="SELECT MIN(pi.id) recordid,pi.currency,pi.itemreference,pi.label,pay.status,
                    COUNT(DISTINCT p.id) paymentcount
               FROM {local_subscriptions_commerce_purchase_item} pi
               JOIN {local_subscriptions_commerce_purchase} p ON p.id=pi.purchaseid
               JOIN {local_subscriptions_commerce_payment} pay ON pay.purchaseid=p.id
                AND pay.id=(SELECT MAX(px.id) FROM {local_subscriptions_commerce_payment} px WHERE px.purchaseid=p.id)
              WHERE ".implode(' AND ',$where)."
           GROUP BY pi.currency,pi.itemreference,pi.label,pay.status
           ORDER BY paymentcount DESC";

        $grouped=[];
        foreach($this->db->get_records_sql($sql,$params) as $row){
            $code=strtoupper((string)$row->currency);
            $ref=(string)$row->itemreference;
            $grouped[$code][$ref] ??= [
                'reference'=>$ref,
                'label'=>(string)$row->label,
                'attempts'=>0,'paid'=>0,'pending'=>0,'failed'=>0,'cancelled'=>0,'refunded'=>0,
            ];
            $count=(int)$row->paymentcount;
            $grouped[$code][$ref]['attempts'] += $count;
            $status=strtolower((string)$row->status);
            if(in_array($status,['paid','succeeded','completed','captured'],true)){
                $grouped[$code][$ref]['paid'] += $count;
            }else if(in_array($status,['pending','created','processing','requires_action'],true)){
                $grouped[$code][$ref]['pending'] += $count;
            }else if(in_array($status,['cancelled','canceled','expired'],true)){
                $grouped[$code][$ref]['cancelled'] += $count;
            }else if(in_array($status,['refunded','partially_refunded'],true)){
                $grouped[$code][$ref]['refunded'] += $count;
            }else{
                $grouped[$code][$ref]['failed'] += $count;
            }
        }

        $out=[];
        foreach($grouped as $code=>$items){
            uasort($items,static fn(array $a,array $b): int => $b['attempts'] <=> $a['attempts']);
            foreach(array_slice(array_values($items),0,max(1,$limit)) as $item){
                $item['conversion']=$item['attempts']>0?($item['paid']/$item['attempts'])*100:0.0;
                $out[$code][]=$item;
            }
        }
        return $out;
    }

    /** @return array<int,object> */
    public function order_rows(
        CommerceStatisticsPeriod $period,
        ?string $currency = null,
        ?string $provider = null
    ): array {
        $currency = $this->normalise_currency($currency);
        $provider = $this->normalise_provider($provider);
        $params = ['start' => $period->start(), 'end' => $period->end()];
        $where = ['p.timecreated >= :start', 'p.timecreated < :end'];

        if ($currency !== null) {
            $where[] = 'p.currency = :currency';
            $params['currency'] = $currency;
        }
        if ($provider !== null) {
            $where[] = 'EXISTS (SELECT 1 FROM {local_subscriptions_commerce_payment} pp WHERE pp.purchaseid=p.id AND pp.provider=:provider)';
            $params['provider'] = $provider;
        }

        $sql = "SELECT p.id,p.reference,p.userid,p.customeremail,p.status,p.currency,p.totalminor,
                       p.timecreated,p.timemodified
                  FROM {local_subscriptions_commerce_purchase} p
                 WHERE " . implode(' AND ', $where) . "
              ORDER BY p.timecreated DESC,p.id DESC";
        return array_values($this->db->get_records_sql($sql, $params));
    }

    /** @return array<int,object> */
    public function payment_rows(
        CommerceStatisticsPeriod $period,
        ?string $currency = null,
        ?string $provider = null
    ): array {
        $currency = $this->normalise_currency($currency);
        $provider = $this->normalise_provider($provider);
        $params = ['start' => $period->start(), 'end' => $period->end()];
        $where = ['p.timecreated >= :start', 'p.timecreated < :end'];

        if ($currency !== null) {
            $where[] = 'pay.currency = :currency';
            $params['currency'] = $currency;
        }
        if ($provider !== null) {
            $where[] = 'pay.provider = :provider';
            $params['provider'] = $provider;
        }

        $sql = "SELECT pay.id,p.reference purchasereference,pay.sequence,pay.provider,
                       pay.providerreference,pay.status,pay.currency,pay.amountminor,
                       pay.paidat,pay.timecreated,pay.timemodified
                  FROM {local_subscriptions_commerce_payment} pay
                  JOIN {local_subscriptions_commerce_purchase} p ON p.id=pay.purchaseid
                 WHERE " . implode(' AND ', $where) . "
              ORDER BY pay.timecreated DESC,pay.id DESC";
        return array_values($this->db->get_records_sql($sql, $params));
    }

    /** @return array<int,object> */
    public function manual_grant_rows(CommerceStatisticsPeriod $period): array {
        $sql = "SELECT g.id,g.grantreference,g.beneficiaryuserid,g.beneficiaryemail,g.quantity,
                       g.productsku,g.type,g.resourcekey,g.status,g.timecreated,g.metadatajson
                  FROM {local_subs_commerce_grant} g
                 WHERE g.timecreated>=:start AND g.timecreated<:end
                   AND g.status='active'
                   AND " . $this->db->sql_like('g.metadatajson', ':source', false) . "
              ORDER BY g.timecreated DESC,g.id DESC";
        return array_values($this->db->get_records_sql($sql, [
            'start' => $period->start(),
            'end' => $period->end(),
            'source' => '%crm_manual_grant%',
        ]));
    }

    private function paid_metrics(
        CommerceStatisticsPeriod $period,
        ?string $currency,
        ?string $provider
    ): array {
        $params = ['start' => $period->start(), 'end' => $period->end()];
        $where = ['p.timecreated >= :start', 'p.timecreated < :end'];
        if ($currency !== null) {
            $where[] = 'p.currency = :currency';
            $params['currency'] = $currency;
        }
        $providerwhere = '';
        if ($provider !== null) {
            $params['provider'] = $provider;
            $providerwhere = ' AND pay.provider=:provider';
        }

        $sql = "SELECT MIN(p.id) recordid,p.currency,
                       COUNT(DISTINCT p.id) paidorders,
                       COUNT(DISTINCT CASE WHEN p.userid>0 THEN CONCAT('u:',p.userid) ELSE CONCAT('e:',LOWER(p.customeremail)) END) paidcustomers,
                       COALESCE(SUM((SELECT SUM(pi.quantity) FROM {local_subscriptions_commerce_purchase_item} pi WHERE pi.purchaseid=p.id)),0) soldquantity,
                       COALESCE(SUM((SELECT SUM(pi.netminor) FROM {local_subscriptions_commerce_purchase_item} pi WHERE pi.purchaseid=p.id)),0) revenueminor
                  FROM {local_subscriptions_commerce_purchase} p
                 WHERE " . implode(' AND ', $where) . "
                   AND EXISTS (
                       SELECT 1 FROM {local_subscriptions_commerce_payment} pay
                        WHERE pay.purchaseid=p.id
                          AND pay.status IN ('paid','succeeded','completed','captured')
                          {$providerwhere}
                   )
              GROUP BY p.currency";

        $out = [];
        foreach ($this->db->get_records_sql($sql, $params) as $row) {
            $out[strtoupper((string)$row->currency)] = [
                'paidorders' => (int)$row->paidorders,
                'paidcustomers' => (int)$row->paidcustomers,
                'soldquantity' => (int)$row->soldquantity,
                'revenueminor' => (int)$row->revenueminor,
            ];
        }
        return $out;
    }

    private function payment_health(
        CommerceStatisticsPeriod $period,
        ?string $currency,
        ?string $provider
    ): array {
        $params = ['start' => $period->start(), 'end' => $period->end()];
        $where = ['p.timecreated >= :start', 'p.timecreated < :end'];
        if ($currency !== null) {
            $where[] = 'pay.currency=:currency';
            $params['currency'] = $currency;
        }
        if ($provider !== null) {
            $where[] = 'pay.provider=:provider';
            $params['provider'] = $provider;
        }

        $sql = "SELECT MIN(pay.id) recordid,pay.currency,pay.status,COUNT(pay.id) paymentcount
                  FROM {local_subscriptions_commerce_payment} pay
                  JOIN {local_subscriptions_commerce_purchase} p ON p.id=pay.purchaseid
                 WHERE " . implode(' AND ', $where) . "
                   AND pay.id=(SELECT MAX(px.id) FROM {local_subscriptions_commerce_payment} px WHERE px.purchaseid=p.id)
              GROUP BY pay.currency,pay.status";

        $out = [];
        foreach ($this->db->get_records_sql($sql, $params) as $row) {
            $code = strtoupper((string)$row->currency);
            $out[$code] ??= ['paid'=>0,'pending'=>0,'failed'=>0,'cancelled'=>0,'refunded'=>0];
            $status = strtolower((string)$row->status);
            $count = (int)$row->paymentcount;
            if (in_array($status, ['paid','succeeded','completed','captured'], true)) {
                $out[$code]['paid'] += $count;
            } else if (in_array($status, ['pending','created','processing','requires_action'], true)) {
                $out[$code]['pending'] += $count;
            } else if (in_array($status, ['cancelled','canceled','expired'], true)) {
                $out[$code]['cancelled'] += $count;
            } else if (in_array($status, ['refunded','partially_refunded'], true)) {
                $out[$code]['refunded'] += $count;
            } else {
                $out[$code]['failed'] += $count;
            }
        }
        return $out;
    }

    private function pending_fulfillments(CommerceStatisticsPeriod $period, ?string $currency): int {
        $params = ['start'=>$period->start(),'end'=>$period->end()];
        $currencywhere = '';
        if ($currency !== null) {
            $params['currency'] = $currency;
            $currencywhere = ' AND p.currency=:currency';
        }
        return (int)$this->db->count_records_sql(
            "SELECT COUNT(1)
               FROM {local_subscriptions_commerce_purchase} p
              WHERE p.timecreated>=:start AND p.timecreated<:end {$currencywhere}
                AND p.status IN ('paid','payment_completed','fulfillment_pending','paid_pending_fulfillment')",
            $params
        );
    }

    private function manual_grants(CommerceStatisticsPeriod $period): array {
        $quantity = 0;
        $recipients = [];
        foreach ($this->manual_grant_rows($period) as $row) {
            $quantity += (int)$row->quantity;
            $key = !empty($row->beneficiaryuserid)
                ? 'u:' . $row->beneficiaryuserid
                : 'e:' . strtolower((string)$row->beneficiaryemail);
            $recipients[$key] = true;
        }
        return ['quantity'=>$quantity,'recipients'=>count($recipients)];
    }

    private function provider_breakdown(
        CommerceStatisticsPeriod $period,
        ?string $currency,
        ?string $provider
    ): array {
        $params = ['start'=>$period->start(),'end'=>$period->end()];
        $where = ['p.timecreated>=:start','p.timecreated<:end',"pay.status IN ('paid','succeeded','completed','captured')"];
        if ($currency !== null) {
            $where[]='pay.currency=:currency';$params['currency']=$currency;
        }
        if ($provider !== null) {
            $where[]='pay.provider=:provider';$params['provider']=$provider;
        }
        $sql="SELECT MIN(pay.id) recordid,pay.currency,pay.provider,COUNT(DISTINCT p.id) orders,
                     COALESCE(SUM(pay.amountminor),0) amountminor
                FROM {local_subscriptions_commerce_payment} pay
                JOIN {local_subscriptions_commerce_purchase} p ON p.id=pay.purchaseid
               WHERE ".implode(' AND ',$where)."
            GROUP BY pay.currency,pay.provider";
        $out=[];
        foreach($this->db->get_records_sql($sql,$params) as $row){
            $out[strtoupper((string)$row->currency)][strtolower((string)$row->provider)]=[
                'orders'=>(int)$row->orders,'revenueminor'=>(int)$row->amountminor
            ];
        }
        return $out;
    }

    private function acquisition_breakdown(
        CommerceStatisticsPeriod $period,
        ?string $currency,
        ?string $provider,
        int $manualquantity
    ): array {
        $out=['standard'=>0,'promotion'=>0,'personaloffer'=>0,'free'=>0,'manual'=>$manualquantity];
        $params=['start'=>$period->start(),'end'=>$period->end()];
        $where=['p.timecreated>=:start','p.timecreated<:end'];
        if($currency!==null){$where[]='p.currency=:currency';$params['currency']=$currency;}
        $providerwhere='';
        if($provider!==null){$params['provider']=$provider;$providerwhere=' AND pay.provider=:provider';}
        $sql="SELECT pi.id,pi.quantity,pi.metadatajson,p.metadatajson purchasemetadata,p.totalminor,p.status,
                    CASE WHEN EXISTS(
                        SELECT 1 FROM {local_subscriptions_commerce_payment} pay
                         WHERE pay.purchaseid=p.id AND pay.status IN ('paid','succeeded','completed','captured') {$providerwhere}
                    ) THEN 1 ELSE 0 END paidok
               FROM {local_subscriptions_commerce_purchase_item} pi
               JOIN {local_subscriptions_commerce_purchase} p ON p.id=pi.purchaseid
              WHERE ".implode(' AND ',$where);
        foreach($this->db->get_records_sql($sql,$params) as $row){
            $quantity=(int)$row->quantity;
            if((int)$row->paidok===1){
                $item=$this->json((string)$row->metadatajson);
                $purchase=$this->json((string)$row->purchasemetadata);
                if(strtolower((string)($item['operation']??''))==='personaloffer'||!empty($item['personal_offer_uuid'])){
                    $out['personaloffer']+=$quantity;
                }else if(!empty($purchase['promotion_codes'])||(int)($purchase['cart_product_promotion_minor']??0)>0){
                    $out['promotion']+=$quantity;
                }else{
                    $out['standard']+=$quantity;
                }
            }else if((int)$row->totalminor===0&&in_array((string)$row->status,['fulfilled','completed','paid','captured'],true)){
                $out['free']+=$quantity;
            }
        }
        return $out;
    }

    private function series_from_rows(
        array $rows,
        CommerceStatisticsPeriod $period,
        callable $value,
        string $key
    ): array {
        $granularity = CommerceStatisticsPeriodResolver::granularity($period);
        $buckets = [];
        foreach ($rows as $row) {
            $code = strtoupper((string)$row->currency);
            $bucket = $this->bucket_start((int)$row->timecreated, $granularity);
            $buckets[$code][$bucket] = ($buckets[$code][$bucket] ?? 0) + $value($row);
        }
        $out = [];
        foreach ($buckets as $code=>$values) {
            ksort($values);
            $points=[];
            foreach($values as $timestamp=>$amount){
                $points[]=['timestamp'=>(int)$timestamp,'label'=>$this->bucket_label((int)$timestamp,$granularity),'value'=>$amount];
            }
            $out[$code]=new CommerceStatisticsSeries($key,$code,$granularity,$points);
        }
        return $out;
    }

    private function bucket_start(int $timestamp,string $granularity): int {
        if($granularity==='hour'){
            return make_timestamp((int)userdate($timestamp,'%Y'),(int)userdate($timestamp,'%m'),(int)userdate($timestamp,'%d'),(int)userdate($timestamp,'%H'));
        }
        if($granularity==='month'){
            return make_timestamp((int)userdate($timestamp,'%Y'),(int)userdate($timestamp,'%m'),1);
        }
        if($granularity==='week'){
            $day=(int)userdate($timestamp,'%u');
            return usergetmidnight($timestamp-(($day-1)*DAYSECS));
        }
        return usergetmidnight($timestamp);
    }

    private function bucket_label(int $timestamp,string $granularity): string {
        return match($granularity){
            'hour'=>userdate($timestamp,'%H:00'),
            'week'=>userdate($timestamp,'%d %b'),
            'month'=>userdate($timestamp,'%b %Y'),
            default=>userdate($timestamp,'%a %d %b'),
        };
    }

    private function normalise_currency(?string $currency): ?string {
        $currency = strtoupper(trim((string)$currency));
        return in_array($currency,['EUR','RUB'],true)?$currency:null;
    }

    private function normalise_provider(?string $provider): ?string {
        $provider = strtolower(trim((string)$provider));
        return in_array($provider,['stripe','alfa'],true)?$provider:null;
    }

    private function json(string $value): array {
        if(trim($value)==='')return[];
        $decoded=json_decode($value,true);
        return is_array($decoded)?$decoded:[];
    }
}
