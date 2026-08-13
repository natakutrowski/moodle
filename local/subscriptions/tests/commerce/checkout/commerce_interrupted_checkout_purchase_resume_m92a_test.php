<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\checkout\unified\CommerceCheckoutPurchasePersister;
use local_subscriptions\commerce\domain\CommerceItem;
use local_subscriptions\commerce\persistence\CommercePurchasePersistenceSnapshot;
use local_subscriptions\commerce\persistence\record\CommercePurchaseItemRecord;
use local_subscriptions\commerce\persistence\record\CommercePurchaseRecord;
use local_subscriptions\commerce\purchase\CommerceCustomer;
use local_subscriptions\commerce\purchase\CommercePurchaseRequest;
use local_subscriptions\commerce\purchase\CommercePurchaseRequestItem;

final class commerce_interrupted_checkout_purchase_resume_m92a_test extends advanced_testcase {
    public function test_resume_match_accepts_repository_persistence_snapshot(): void {
        $this->resetAfterTest(true);

        $userid = 2607;
        $email = 'loryk555@ukr.net';
        $purchaseuuid = md5('m92a-larysa');
        $reference = 'cmp_m92a_larysa';
        $now = time();

        $purchase = new CommercePurchaseRecord(
            $purchaseuuid,
            $reference,
            CommerceItem::TYPE_DIGITAL,
            null,
            null,
            $userid,
            $email,
            'payment_pending',
            'EUR',
            2900,
            0,
            2900,
            json_encode([
                'userid' => $userid,
                'email' => $email,
                'firstname' => 'Larysa',
                'lastname' => 'Tkachenko',
            ]),
            '{}',
            '{}',
            1,
            $now,
            $now
        );

        $item = new CommercePurchaseItemRecord(
            $purchaseuuid,
            0,
            CommerceItem::TYPE_DIGITAL,
            'COURSE.TEST.M92A',
            'Test course',
            1,
            'EUR',
            2900,
            2900,
            0,
            2900,
            '{}',
            '{}',
            '{}'
        );

        $snapshot = new CommercePurchasePersistenceSnapshot(
            $purchase,
            [$item],
            [],
            []
        );

        $request = new CommercePurchaseRequest(
            $reference,
            new CommerceCustomer(
                $userid,
                $email,
                'Larysa',
                'Tkachenko'
            ),
            [
                new CommercePurchaseRequestItem(
                    new CommerceItem(
                        CommerceItem::TYPE_DIGITAL,
                        'COURSE.TEST.M92A',
                        'Test course'
                    ),
                    1,
                    2900,
                    'EUR'
                ),
            ],
            preferredprovider: 'stripe'
        );

        $persister = (new \ReflectionClass(
            CommerceCheckoutPurchasePersister::class
        ))->newInstanceWithoutConstructor();

        $method = new \ReflectionMethod(
            CommerceCheckoutPurchasePersister::class,
            'assert_resume_matches'
        );
        $method->setAccessible(true);

        // Regression: before M9.2a this call raised a TypeError because
        // assert_resume_matches() expected NativePurchase while the repository
        // returns CommercePurchasePersistenceSnapshot / CommercePurchaseRecord.
        $method->invoke($persister, $snapshot, $request);

        self::assertTrue(true);
    }

    public function test_resume_match_still_rejects_changed_total(): void {
        $this->resetAfterTest(true);

        $purchaseuuid = md5('m92a-mismatch');
        $purchase = new CommercePurchaseRecord(
            $purchaseuuid,
            'cmp_m92a_mismatch',
            CommerceItem::TYPE_DIGITAL,
            null,
            null,
            42,
            'customer@example.test',
            'payment_pending',
            'EUR',
            2900,
            0,
            2900,
            json_encode([
                'userid' => 42,
                'email' => 'customer@example.test',
            ]),
            '{}',
            '{}',
            1,
            time(),
            time()
        );

        $snapshot = new CommercePurchasePersistenceSnapshot(
            $purchase,
            [
                new CommercePurchaseItemRecord(
                    $purchaseuuid,
                    0,
                    CommerceItem::TYPE_DIGITAL,
                    'COURSE.TEST.M92A',
                    'Test course',
                    1,
                    'EUR',
                    2900,
                    2900,
                    0,
                    2900,
                    '{}',
                    '{}',
                    '{}'
                ),
            ],
            [],
            []
        );

        $request = new CommercePurchaseRequest(
            'cmp_m92a_mismatch',
            new CommerceCustomer(42, 'customer@example.test'),
            [
                new CommercePurchaseRequestItem(
                    new CommerceItem(
                        CommerceItem::TYPE_DIGITAL,
                        'COURSE.TEST.M92A',
                        'Test course'
                    ),
                    1,
                    3000,
                    'EUR'
                ),
            ],
            preferredprovider: 'stripe'
        );

        $persister = (new \ReflectionClass(
            CommerceCheckoutPurchasePersister::class
        ))->newInstanceWithoutConstructor();

        $method = new \ReflectionMethod(
            CommerceCheckoutPurchasePersister::class,
            'assert_resume_matches'
        );
        $method->setAccessible(true);

        $this->expectException(
            \local_subscriptions\commerce\checkout\unified\CommerceInterruptedCheckoutResumeMismatchException::class
        );
        $method->invoke($persister, $snapshot, $request);
    }

    public function test_persister_passes_full_snapshot_to_resume_matcher(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/checkout/unified/CommerceCheckoutPurchasePersister.php'
        );

        self::assertStringContainsString(
            '$this->assert_resume_matches($existing, $request);',
            $source
        );
        self::assertStringContainsString(
            'CommercePurchasePersistenceSnapshot $snapshot',
            $source
        );
        self::assertStringNotContainsString(
            'assert_resume_matches($existing->get_purchase(), $request)',
            $source
        );
    }
}
