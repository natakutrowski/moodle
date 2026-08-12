<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\customer\reconciliation\CommerceCustomerBulkReconciliationService;
use local_subscriptions\commerce\customer\reconciliation\CommerceCustomerIdentityReconciliationResult;
use local_subscriptions\commerce\customer\reconciliation\CommerceCustomerIdentityReconciliationService;
use local_subscriptions\commerce\customer\reconciliation\CommerceCustomerIdentitySearchService;
use local_subscriptions\commerce\persistence\CommercePersistenceSchema;

final class commerce_customer_identity_bulk_reconciliation_m42a_test extends advanced_testcase {
    protected function setUp(): void { parent::setUp(); $this->resetAfterTest(true); }

    public function test_advanced_search_filters_by_partial_email_and_status(): void {
        global $DB;
        $user=$this->getDataGenerator()->create_user(['email'=>'bulk.match@example.com']);
        $match=$this->purchase('bulk.match@example.com','Alice Match');
        $this->purchase('other@example.com','Bob Other');
        $service=new CommerceCustomerIdentityReconciliationService($DB);
        $result=(new CommerceCustomerIdentitySearchService($DB,$service))->search(['email'=>'bulk.match','status'=>'matched'],0,100);
        self::assertSame(1,$result['total']);
        self::assertSame($match,(int)$result['items'][0]['purchase']->id);
        self::assertSame((int)$user->id,$result['items'][0]['preview']->result->userid);
    }

    public function test_bulk_preview_is_read_only_and_execute_rechecks_each_purchase(): void {
        global $DB;
        $user=$this->getDataGenerator()->create_user(['email'=>'bulk@example.com']);
        $first=$this->purchase('bulk@example.com','Bulk Buyer');
        $second=$this->purchase('bulk@example.com','Bulk Buyer');
        $bulk=new CommerceCustomerBulkReconciliationService(new CommerceCustomerIdentityReconciliationService($DB));
        $preview=$bulk->preview([$first,$second]);
        self::assertCount(2,$preview);
        self::assertSame(CommerceCustomerIdentityReconciliationResult::STATUS_MATCHED,$preview[0]->result->status);
        self::assertNull($DB->get_field(CommercePersistenceSchema::TABLE_PURCHASE,'userid',['id'=>$first]));
        self::assertNull($DB->get_field(CommercePersistenceSchema::TABLE_PURCHASE,'userid',['id'=>$second]));
        $results=$bulk->execute([$first,$second]);
        self::assertSame(CommerceCustomerIdentityReconciliationResult::STATUS_RECONCILED,$results[0]->status);
        self::assertSame(CommerceCustomerIdentityReconciliationResult::STATUS_RECONCILED,$results[1]->status);
        self::assertEquals($user->id,$DB->get_field(CommercePersistenceSchema::TABLE_PURCHASE,'userid',['id'=>$first]));
        self::assertEquals($user->id,$DB->get_field(CommercePersistenceSchema::TABLE_PURCHASE,'userid',['id'=>$second]));
    }

    private function purchase(string $email,string $name): int {
        global $DB;
        $now=time();$uuid=bin2hex(random_bytes(16));$ref='PUR-'.substr(strtoupper($uuid),0,20);
        return (int)$DB->insert_record(CommercePersistenceSchema::TABLE_PURCHASE,(object)[
            'purchaseuuid'=>$uuid,'reference'=>$ref,'type'=>'digital','legacyfamily'=>null,'legacyid'=>null,'userid'=>null,
            'customeremail'=>$email,'status'=>'paid','currency'=>'EUR','subtotalminor'=>990,'discountminor'=>0,'totalminor'=>990,
            'customerjson'=>json_encode(['email'=>$email,'name'=>$name]),'snapshotjson'=>'{}','metadatajson'=>'{}','snapshotversion'=>1,
            'timecreated'=>$now,'timemodified'=>$now,
        ]);
    }
}
