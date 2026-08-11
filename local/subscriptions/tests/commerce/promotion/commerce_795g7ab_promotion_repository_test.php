<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\promotion;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\promotion\domain\CommercePromotion;
use local_subscriptions\commerce\promotion\repository\MoodleCommercePromotionRepository;

final class commerce_795g7ab_promotion_repository_test extends \advanced_testcase {
    public function test_repository_round_trip_and_code_normalisation(): void {
        $this->resetAfterTest();
        $repository = new MoodleCommercePromotionRepository();
        $saved = $repository->save(new CommercePromotion(null, 'Welcome', ' welcome20 ', 'percentage', 2000,
            'eur', 5000, null, null, true, false, false, 10, 100, 1, ['COURSE-A1'], ['course'], ['source' => 'test']));
        $loaded = $repository->get_by_code('WELCOME20');
        $this->assertNotNull($loaded);
        $this->assertSame($saved->get_id(), $loaded->get_id());
        $this->assertSame('WELCOME20', $loaded->get_code());
        $this->assertSame('EUR', $loaded->get_currency());
        $this->assertSame(['COURSE-A1'], $loaded->get_product_skus());
    }
}
