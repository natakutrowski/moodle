<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\statistics\product;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\statistics\CommerceStatisticsPeriod;
use local_subscriptions\commerce\statistics\CommerceStatisticsPeriodResolver;
use local_subscriptions\commerce\statistics\CommerceStatisticsSeries;

/** Precise product statistics, separating sales, payment attempts and non-purchase grants. */
final class CommerceProductStatisticsDashboardRepository {
    private const SUCCESS = ['paid','succeeded','completed','captured'];
    private const FAILED = ['failed','declined','expired'];
    private const CANCELLED = ['cancelled','canceled'];
    private const REFUNDED = ['refunded','partially_refunded'];

    public function __construct(private readonly \moodle_database $db) {}

    /** @param string[] $references @return array<string,mixed> */
    public function snapshot(CommerceStatisticsPeriod $period, array $references, string $rootsku, ?string $currency = null): array {
        $references=$this->references($references);
        $currency=$currency ? strtoupper($currency) : null;
        $currencies=$this->currencies($period,$references,$currency);
        $payments=$this->payment_statuses($period,$references,$currency);
        $manual=$this->manual_grants($period,$rootsku);
        $global=['paidorders'=>0,'soldquantity'=>0,'freeorders'=>0,'freequantity'=>0,'manualgrants'=>$manual['quantity'],'manualrecipients'=>$manual['recipients'],'delivered'=>0,'pending'=>0,'failed'=>0,'cancelled'=>0,'refunded'=>0];
        $bycurrency=[];
        foreach ($currencies as $code) {
            $commercial=$this->commercial_metrics($period,$references,$code);
            $health=$payments[$code] ?? self::empty_health();
            $bycurrency[$code]=$commercial + ['payments'=>$health];
            $global['paidorders'] += $commercial['paidorders'];
            $global['soldquantity'] += $commercial['soldquantity'];
            $global['freeorders'] += $commercial['freeorders'];
            $global['freequantity'] += $commercial['freequantity'];
            foreach (['pending','failed','cancelled','refunded'] as $k) { $global[$k]+=$health[$k]; }
        }
        $global['delivered']=$global['soldquantity']+$global['freequantity']+$global['manualgrants'];
        $providers=$this->provider_breakdown($period,$references,$currency);
        $acquisitions=$this->acquisition_breakdown($period,$references,$rootsku,$currency);
        $attempts=0;
        $successfulpayments=0;
        foreach($payments as $health){
            $attempts+=array_sum($health);
            $successfulpayments+=(int)($health['paid']??0);
        }
        $global['paymentattempts']=$attempts;
        $global['successfulpayments']=$successfulpayments;
        $global['paymentsuccessrate']=$attempts>0?($successfulpayments/$attempts)*100:0.0;
        $global['refundrate']=$attempts>0?((int)$global['refunded']/$attempts)*100:0.0;
        foreach($bycurrency as $code=>&$row){
            $row['averageorderminor']=$row['paidorders']>0?(int)round($row['revenueminor']/$row['paidorders']):0;
        }
        unset($row);
        return ['global'=>$global,'currencies'=>$bycurrency,'payments'=>$payments,'manual'=>$manual,'providers'=>$providers,'acquisitions'=>$acquisitions];
    }

    /** @param string[] $references @return array<string,CommerceStatisticsSeries> */
    public function revenue_series(CommerceStatisticsPeriod $period,array $references,?string $currency=null): array {
        return $this->purchase_series($period,$references,$currency,'revenue');
    }

    /** @param string[] $references @return array<string,array<string,CommerceStatisticsSeries>> */
    public function delivery_series(CommerceStatisticsPeriod $period,array $references,string $rootsku,?string $currency=null): array {
        $gran=CommerceStatisticsPeriodResolver::granularity($period);
        $result=['paid'=>[],'free'=>[],'manual'=>[]];
        $refs=$this->references($references);
        if ($refs) {
            $params=['start'=>$period->start(),'end'=>$period->end()];
            [$insql,$inparams]=$this->db->get_in_or_equal($refs,SQL_PARAMS_NAMED,'delref'); $params+=$inparams;
            $cw=''; if($currency){$params['cur']=strtoupper($currency);$cw=' AND pi.currency = :cur';}
            $success="EXISTS (SELECT 1 FROM {local_subscriptions_commerce_payment} pay WHERE pay.purchaseid=p.id AND pay.status IN ('paid','succeeded','completed','captured'))";
            $sql="SELECT pi.id,p.timecreated,pi.currency,pi.quantity,p.totalminor,CASE WHEN {$success} THEN 1 ELSE 0 END AS paidok
                    FROM {local_subscriptions_commerce_purchase_item} pi JOIN {local_subscriptions_commerce_purchase} p ON p.id=pi.purchaseid
                   WHERE p.timecreated>=:start AND p.timecreated<:end AND pi.itemreference {$insql}{$cw}";
            $b=['paid'=>[],'free'=>[]];
            foreach($this->db->get_records_sql($sql,$params) as $r){
                $kind=((int)$r->paidok===1)?'paid':(((int)$r->totalminor===0 && in_array((string)$this->purchase_status((int)$r->id),['fulfilled','completed','paid','captured'],true))?'free':null);
                if($kind===null) continue;
                $code=strtoupper((string)$r->currency); $bucket=$this->bucket_start((int)$r->timecreated,$gran);
                $b[$kind][$code][$bucket]=($b[$kind][$code][$bucket]??0)+(int)$r->quantity;
            }
            foreach(['paid','free'] as $kind){foreach($b[$kind] as $code=>$vals){$result[$kind][$code]=$this->series($kind,$code,$gran,$vals);}}
        }
        // Manual grants are currencyless and therefore rendered as their own neutral series.
        $vals=[]; foreach($this->manual_grant_rows($period,$rootsku) as $r){$bucket=$this->bucket_start((int)$r->timecreated,$gran);$vals[$bucket]=($vals[$bucket]??0)+(int)$r->quantity;}
        if($vals){$result['manual']['GRANT']=$this->series('manual','', $gran,$vals);}
        return $result;
    }

    /** @param string[] $references @return array<int,object> */
    public function order_rows(CommerceStatisticsPeriod $period,array $references,?string $currency=null): array {
        $refs=$this->references($references); if(!$refs)return[];
        $params=['start'=>$period->start(),'end'=>$period->end()]; [$insql,$inparams]=$this->db->get_in_or_equal($refs,SQL_PARAMS_NAMED,'expref');$params+=$inparams;
        $cw=''; if($currency){$params['cur']=strtoupper($currency);$cw=' AND pi.currency=:cur';}
        $sql="SELECT pi.id AS rowid,p.reference,p.userid,p.customeremail,p.status AS purchasestatus,p.timecreated,pi.label,pi.quantity,pi.currency,pi.netminor,
                     (SELECT pay.status FROM {local_subscriptions_commerce_payment} pay WHERE pay.purchaseid=p.id ORDER BY pay.id DESC LIMIT 1) AS paymentstatus,
                     (SELECT pay.provider FROM {local_subscriptions_commerce_payment} pay WHERE pay.purchaseid=p.id ORDER BY pay.id DESC LIMIT 1) AS provider
                FROM {local_subscriptions_commerce_purchase_item} pi JOIN {local_subscriptions_commerce_purchase} p ON p.id=pi.purchaseid
               WHERE p.timecreated>=:start AND p.timecreated<:end AND pi.itemreference {$insql}{$cw} ORDER BY p.timecreated DESC,pi.id DESC";
        return array_values($this->db->get_records_sql($sql,$params));
    }

    /** @return array<int,object> */
    public function manual_grant_rows(CommerceStatisticsPeriod $period,string $rootsku): array {
        $like='%'.$this->db->sql_like_escape('"rootsku":"'.strtoupper(trim($rootsku)).'"').'%';
        $sql="SELECT g.id,g.grantreference,g.beneficiaryuserid,g.beneficiaryemail,g.quantity,g.productsku,g.type,g.resourcekey,g.status,g.timecreated,g.metadatajson
                FROM {local_subs_commerce_grant} g
               WHERE g.timecreated>=:start AND g.timecreated<:end AND g.status='active'
                 AND (g.productsku=:sku OR " . $this->db->sql_like('g.metadatajson',':rootlike',false) . ")
                 AND " . $this->db->sql_like('g.metadatajson',':source',false) . " ORDER BY g.timecreated DESC,g.id DESC";
        return array_values($this->db->get_records_sql($sql,['start'=>$period->start(),'end'=>$period->end(),'sku'=>strtoupper(trim($rootsku)),'rootlike'=>$like,'source'=>'%crm_manual_grant%']));
    }


    /** @param string[] $references @return array<int,object> */
    public function payment_rows(CommerceStatisticsPeriod $period,array $references,?string $currency=null): array {
        $refs=$this->references($references);if(!$refs)return[];
        $params=['start'=>$period->start(),'end'=>$period->end()];
        [$insql,$inparams]=$this->db->get_in_or_equal($refs,SQL_PARAMS_NAMED,'payrowref');$params+=$inparams;
        $cw='';if($currency){$params['cur']=strtoupper($currency);$cw=' AND pay.currency=:cur';}
        $sql="SELECT pay.id,p.reference AS purchasereference,pay.sequence,pay.provider,pay.providerreference,pay.status,pay.currency,pay.amountminor,pay.paidat,pay.timecreated,pay.timemodified
                FROM {local_subscriptions_commerce_payment} pay
                JOIN {local_subscriptions_commerce_purchase} p ON p.id=pay.purchaseid
               WHERE p.timecreated>=:start AND p.timecreated<:end
                 AND EXISTS(SELECT 1 FROM {local_subscriptions_commerce_purchase_item} pi WHERE pi.purchaseid=p.id AND pi.itemreference {$insql}){$cw}
            ORDER BY pay.timecreated DESC,pay.id DESC";
        return array_values($this->db->get_records_sql($sql,$params));
    }

    /** @param string[] $references @return array<string,array<string,int>> */
    private function provider_breakdown(CommerceStatisticsPeriod $period,array $references,?string $currency): array {
        $refs=$this->references($references);if(!$refs)return[];
        $params=['start'=>$period->start(),'end'=>$period->end()];
        [$insql,$inparams]=$this->db->get_in_or_equal($refs,SQL_PARAMS_NAMED,'provref');$params+=$inparams;
        $cw='';if($currency){$params['cur']=strtoupper($currency);$cw=' AND pi.currency=:cur';}
        $success="'paid','succeeded','completed','captured'";
        $sql="SELECT MIN(pi.id) recordid,COALESCE(NULLIF(pay.provider,''),'unknown') provider,pi.currency,
                     COUNT(DISTINCT p.id) orders,COALESCE(SUM(pi.netminor),0) revenueminor
                FROM {local_subscriptions_commerce_purchase_item} pi
                JOIN {local_subscriptions_commerce_purchase} p ON p.id=pi.purchaseid
                JOIN {local_subscriptions_commerce_payment} pay ON pay.purchaseid=p.id
                 AND pay.id=(SELECT MAX(px.id) FROM {local_subscriptions_commerce_payment} px WHERE px.purchaseid=p.id)
               WHERE p.timecreated>=:start AND p.timecreated<:end AND pi.itemreference {$insql}{$cw}
                 AND pay.status IN ({$success})
            GROUP BY pay.provider,pi.currency ORDER BY pi.currency,pay.provider";
        $out=[];
        foreach($this->db->get_records_sql($sql,$params) as $row){
            $code=strtoupper((string)$row->currency);$provider=strtolower((string)$row->provider);
            $out[$code][$provider]=['orders'=>(int)$row->orders,'revenueminor'=>(int)$row->revenueminor];
        }
        return $out;
    }

    /** @param string[] $references @return array<string,int> */
    private function acquisition_breakdown(CommerceStatisticsPeriod $period,array $references,string $rootsku,?string $currency): array {
        $out=['standard'=>0,'promotion'=>0,'personaloffer'=>0,'free'=>0,'manual'=>0];
        $refs=$this->references($references);
        if($refs){
            $params=['start'=>$period->start(),'end'=>$period->end()];
            [$insql,$inparams]=$this->db->get_in_or_equal($refs,SQL_PARAMS_NAMED,'acqref');$params+=$inparams;
            $cw='';if($currency){$params['cur']=strtoupper($currency);$cw=' AND pi.currency=:cur';}
            $success="EXISTS(SELECT 1 FROM {local_subscriptions_commerce_payment} pay WHERE pay.purchaseid=p.id AND pay.status IN ('paid','succeeded','completed','captured'))";
            $sql="SELECT pi.id,pi.quantity,pi.metadatajson,p.metadatajson AS purchasemetadata,p.totalminor,p.status,
                         CASE WHEN {$success} THEN 1 ELSE 0 END paidok
                    FROM {local_subscriptions_commerce_purchase_item} pi
                    JOIN {local_subscriptions_commerce_purchase} p ON p.id=pi.purchaseid
                   WHERE p.timecreated>=:start AND p.timecreated<:end AND pi.itemreference {$insql}{$cw}";
            foreach($this->db->get_records_sql($sql,$params) as $row){
                $quantity=(int)$row->quantity;
                if((int)$row->paidok===1){
                    $item=$this->json((string)$row->metadatajson);
                    $purchase=$this->json((string)$row->purchasemetadata);
                    if(strtolower((string)($item['operation']??''))==='personaloffer'||!empty($item['personal_offer_uuid'])){
                        $out['personaloffer']+=$quantity;
                    }elseif(!empty($purchase['promotion_codes'])||(int)($purchase['cart_product_promotion_minor']??0)>0){
                        $out['promotion']+=$quantity;
                    }else{
                        $out['standard']+=$quantity;
                    }
                }elseif((int)$row->totalminor===0&&in_array((string)$row->status,['fulfilled','completed','paid','captured'],true)){
                    $out['free']+=$quantity;
                }
            }
        }
        $out['manual']=$this->manual_grants($period,$rootsku)['quantity'];
        return $out;
    }

    private function json(string $value): array {
        if(trim($value)==='')return[];
        $decoded=json_decode($value,true);
        return is_array($decoded)?$decoded:[];
    }

    private function commercial_metrics(CommerceStatisticsPeriod $period,array $refs,string $currency): array {
        $params=['start'=>$period->start(),'end'=>$period->end(),'cur'=>$currency];[$insql,$inparams]=$this->db->get_in_or_equal($refs,SQL_PARAMS_NAMED,'metref');$params+=$inparams;
        $success="EXISTS (SELECT 1 FROM {local_subscriptions_commerce_payment} pay WHERE pay.purchaseid=p.id AND pay.status IN ('paid','succeeded','completed','captured'))";
        $sql="SELECT MIN(pi.id) recordid,
                     COUNT(DISTINCT CASE WHEN {$success} THEN p.id END) paidorders,
                     COALESCE(SUM(CASE WHEN {$success} THEN pi.quantity ELSE 0 END),0) soldquantity,
                     COUNT(DISTINCT CASE WHEN p.totalminor=0 AND p.status IN ('captured','paid','fulfilled','completed') THEN p.id END) freeorders,
                     COALESCE(SUM(CASE WHEN p.totalminor=0 AND p.status IN ('captured','paid','fulfilled','completed') AND NOT {$success} THEN pi.quantity ELSE 0 END),0) freequantity,
                     COALESCE(SUM(CASE WHEN {$success} THEN pi.netminor ELSE 0 END),0) revenueminor
                FROM {local_subscriptions_commerce_purchase_item} pi JOIN {local_subscriptions_commerce_purchase} p ON p.id=pi.purchaseid
               WHERE p.timecreated>=:start AND p.timecreated<:end AND pi.itemreference {$insql} AND pi.currency=:cur";
        $r=$this->db->get_record_sql($sql,$params);
        return ['paidorders'=>(int)($r->paidorders??0),'soldquantity'=>(int)($r->soldquantity??0),'freeorders'=>(int)($r->freeorders??0),'freequantity'=>(int)($r->freequantity??0),'revenueminor'=>(int)($r->revenueminor??0)];
    }

    private function payment_statuses(CommerceStatisticsPeriod $period,array $refs,?string $currency): array {
        if(!$refs)return[];$params=['start'=>$period->start(),'end'=>$period->end()];[$insql,$inparams]=$this->db->get_in_or_equal($refs,SQL_PARAMS_NAMED,'payref');$params+=$inparams;
        $cw='';if($currency){$params['cur']=strtoupper($currency);$cw=' AND pay.currency=:cur';}
        $sql="SELECT MIN(pay.id) recordid,pay.currency,pay.status,COUNT(*) cnt FROM {local_subscriptions_commerce_payment} pay JOIN {local_subscriptions_commerce_purchase} p ON p.id=pay.purchaseid
               WHERE p.timecreated>=:start AND p.timecreated<:end AND pay.id=(SELECT MAX(px.id) FROM {local_subscriptions_commerce_payment} px WHERE px.purchaseid=p.id)
                 AND EXISTS(SELECT 1 FROM {local_subscriptions_commerce_purchase_item} pi WHERE pi.purchaseid=p.id AND pi.itemreference {$insql}){$cw}
               GROUP BY pay.currency,pay.status";
        $out=[];foreach($this->db->get_records_sql($sql,$params) as $r){$c=strtoupper((string)$r->currency);$out[$c]??=self::empty_health();$k=$this->payment_bucket((string)$r->status);$out[$c][$k]+=(int)$r->cnt;}return$out;
    }
    private function currencies(CommerceStatisticsPeriod $period,array $refs,?string $currency): array {if($currency)return[$currency];if(!$refs)return[];$params=['start'=>$period->start(),'end'=>$period->end()];[$insql,$inparams]=$this->db->get_in_or_equal($refs,SQL_PARAMS_NAMED,'curref');$params+=$inparams;$rows=$this->db->get_fieldset_sql("SELECT DISTINCT pi.currency FROM {local_subscriptions_commerce_purchase_item} pi JOIN {local_subscriptions_commerce_purchase} p ON p.id=pi.purchaseid WHERE p.timecreated>=:start AND p.timecreated<:end AND pi.itemreference {$insql} ORDER BY pi.currency",$params);return array_map('strtoupper',$rows);}
    private function manual_grants(CommerceStatisticsPeriod $period,string $rootsku): array {$rows=$this->manual_grant_rows($period,$rootsku);$q=0;$u=[];foreach($rows as $r){$q+=(int)$r->quantity;$key=!empty($r->beneficiaryuserid)?'u:'.$r->beneficiaryuserid:'e:'.strtolower((string)$r->beneficiaryemail);$u[$key]=1;}return['quantity'=>$q,'recipients'=>count($u)];}
    private function purchase_series(CommerceStatisticsPeriod $period,array $refs,?string $currency,string $metric): array {if(!$refs)return[];$params=['start'=>$period->start(),'end'=>$period->end()];[$insql,$inparams]=$this->db->get_in_or_equal($refs,SQL_PARAMS_NAMED,'serref');$params+=$inparams;$cw='';if($currency){$params['cur']=strtoupper($currency);$cw=' AND pi.currency=:cur';}$success="EXISTS (SELECT 1 FROM {local_subscriptions_commerce_payment} pay WHERE pay.purchaseid=p.id AND pay.status IN ('paid','succeeded','completed','captured'))";$sql="SELECT pi.id,p.timecreated,pi.currency,CASE WHEN {$success} THEN pi.netminor ELSE 0 END value FROM {local_subscriptions_commerce_purchase_item} pi JOIN {local_subscriptions_commerce_purchase} p ON p.id=pi.purchaseid WHERE p.timecreated>=:start AND p.timecreated<:end AND pi.itemreference {$insql}{$cw}";$gran=CommerceStatisticsPeriodResolver::granularity($period);$b=[];foreach($this->db->get_records_sql($sql,$params) as $r){$c=strtoupper((string)$r->currency);$k=$this->bucket_start((int)$r->timecreated,$gran);$b[$c][$k]=($b[$c][$k]??0)+(int)$r->value;}$out=[];foreach($b as $c=>$v){$out[$c]=$this->series($metric,$c,$gran,$v);}return$out;}
    private function series(string $key,string $currency,string $gran,array $vals): CommerceStatisticsSeries {ksort($vals);$p=[];foreach($vals as $t=>$v){$p[]=['timestamp'=>(int)$t,'label'=>$this->bucket_label((int)$t,$gran),'value'=>(int)$v];}return new CommerceStatisticsSeries($key,$currency,$gran,$p);}
    private function bucket_start(int $t,string $g): int {if($g==='hour'){return make_timestamp((int)userdate($t,'%Y'),(int)userdate($t,'%m'),(int)userdate($t,'%d'),(int)userdate($t,'%H'));}if($g==='month')return make_timestamp((int)userdate($t,'%Y'),(int)userdate($t,'%m'),1);if($g==='week'){return usergetmidnight($t-(((int)userdate($t,'%u')-1)*DAYSECS));}return usergetmidnight($t);}
    private function bucket_label(int $t,string $g): string {if($g==='hour')return userdate($t,'%H:00');if($g==='month')return userdate($t,'%b %Y');if($g==='week')return userdate($t,'%d %b');return userdate($t,'%a %d %b');}
    private function payment_bucket(string $s): string {$s=strtolower($s);if(in_array($s,self::SUCCESS,true))return'paid';if(in_array($s,self::FAILED,true))return'failed';if(in_array($s,self::CANCELLED,true))return'cancelled';if(in_array($s,self::REFUNDED,true))return'refunded';return'pending';}
    private function references(array $r): array{return array_values(array_unique(array_filter(array_map('trim',$r))));}
    private static function empty_health(): array{return['paid'=>0,'pending'=>0,'failed'=>0,'cancelled'=>0,'refunded'=>0];}
    private function purchase_status(int $purchaseitemid): string {return (string)$this->db->get_field_sql('SELECT p.status FROM {local_subscriptions_commerce_purchase} p JOIN {local_subscriptions_commerce_purchase_item} pi ON pi.purchaseid=p.id WHERE pi.id=:id',['id'=>$purchaseitemid]);}
}
