<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\student\StudentCommercePurchaseCollection;

final class student_commerce_purchase_collection_test extends \advanced_testcase {
    public function test_collection_exposes_student_purchase_groups(): void {
        $subscription = (object)['id' => 1];
        $digital = (object)['id' => 2];
        $collection = new StudentCommercePurchaseCollection([$subscription], [$digital], 'native');
        $this->assertSame([$subscription], $collection->get_subscriptions());
        $this->assertSame([$digital], $collection->get_digital_purchases());
        $this->assertSame('native', $collection->get_source());
        $this->assertTrue($collection->is_equivalent());
    }
}
