<?php

declare(strict_types=1);
namespace local_subscriptions;
defined('MOODLE_INTERNAL') || die();
use advanced_testcase;
use local_subscriptions\commerce\mail\CommerceMailContext;
use local_subscriptions\commerce\mail\CommerceMailIdempotencyKey;
use local_subscriptions\commerce\mail\CommerceMailQueueRepository;
use local_subscriptions\commerce\mail\CommerceMailRecipient;
use local_subscriptions\commerce\mail\CommerceMailRequest;
use local_subscriptions\commerce\mail\CommerceMailStatus;
use local_subscriptions\commerce\mail\CommerceMailType;
final class commerce_transactional_mail_admin_test extends advanced_testcase {
 public function test_search_and_cancel_pending_message(): void {
  $this->resetAfterTest(); $repo=new CommerceMailQueueRepository();
  $r=$repo->enqueue(new CommerceMailRequest(CommerceMailType::PURCHASE_RECEIPT,new CommerceMailRecipient('test@example.com','Test'),new CommerceMailContext(['customer'=>[],'purchase'=>[],'items'=>[],'payment'=>[],'links'=>[]]),'fr',CommerceMailIdempotencyKey::for_purchase(999,CommerceMailType::PURCHASE_RECEIPT),999));
  $result=$repo->search(['status'=>CommerceMailStatus::QUEUED],0,25); $this->assertSame(1,$result['total']);
  $this->assertTrue($repo->cancel_pending((int)$r->id)); $this->assertSame(CommerceMailStatus::CANCELLED,$repo->find_by_id((int)$r->id)->status);
 }
}
