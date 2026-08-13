<?php
declare(strict_types=1);
namespace local_subscriptions\commerce\payment\reconciliation\stripe;
defined('MOODLE_INTERNAL') || die();
use local_subscriptions\commerce\payment\attempt\CommercePaymentAttemptStatus;
use local_subscriptions\commerce\persistence\CommercePersistenceSchema;
use local_subscriptions\payment\Provider;
use moodle_database;
final class StripePaymentReconciliationBatchService {
    public function __construct(private readonly moodle_database $database,private readonly StripePaymentReconciliationEngineInterface $engine) {}
    public static function create(moodle_database $db): self { return new self($db,StripePaymentReconciliationService::create($db)); }
    public function run(int $limit=20,int $minage=300,int $maxage=172800): array {
        $limit=max(1,min(100,$limit)); $minage=max(60,$minage); $maxage=max($minage,$maxage); $now=time();
        $r=['candidates'=>0,'checked'=>0,'reconciled'=>0,'provider_pending'=>0,'blocked'=>0,'already_complete'=>0,'failed'=>0];
        foreach($this->candidate_payment_ids($limit,$now-$minage,$now-$maxage) as $id){
            $r['candidates']++;
            try{
                $i=$this->engine->inspect_payment($id); $r['checked']++;
                if($i->alreadycomplete){$r['already_complete']++;continue;}
                if(!$i->reconcilable){
                    if(!$i->providerpaid)$r['provider_pending']++;
                    else{$r['blocked']++;mtrace('[Stripe reconciliation] BLOCKED '.$i->purchasereference.' blockers='.implode(',',$i->blockers));}
                    continue;
                }
                $a=$this->engine->reconcile_payment($id);
                if($a->alreadycomplete){$r['reconciled']++;mtrace('[Stripe reconciliation] RECONCILED '.$a->purchasereference.' payment='.$a->paymentid.' session='.$a->providersessionid);}
                else $r['failed']++;
            }catch(\Throwable $e){$r['failed']++;mtrace('[Stripe reconciliation] ERROR payment='.$id.' '.$e->getMessage());}
        } return $r;
    }
    private function candidate_payment_ids(int $limit,int $before,int $after): array {
        $sql='SELECT pay.id FROM {'.CommercePersistenceSchema::TABLE_PAYMENT.'} pay
              JOIN {'.CommercePersistenceSchema::TABLE_PURCHASE.'} pur ON pur.id=pay.purchaseid
              WHERE pay.provider=:provider AND pay.status IN (:redirected,:pending)
              AND pur.status=:purchasestatus AND pay.timecreated<=:before AND pay.timecreated>=:after
              AND (pay.providerorderid IS NOT NULL OR pay.providerreference IS NOT NULL)
              ORDER BY pay.timecreated ASC,pay.id ASC';
        $recs=$this->database->get_records_sql($sql,[
            'provider'=>Provider::STRIPE,'redirected'=>CommercePaymentAttemptStatus::REDIRECTED,
            'pending'=>CommercePaymentAttemptStatus::PENDING,'purchasestatus'=>'payment_pending',
            'before'=>$before,'after'=>$after],0,$limit);
        return array_map('intval',array_keys($recs));
    }
}
