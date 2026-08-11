<?php

declare(strict_types=1);
namespace local_subscriptions\tests\commerce\storefront;
defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\admin\CommerceProductSkuGenerator;
use local_subscriptions\commerce\storefront\experience\CommerceStorefrontExperienceResolver;
use local_subscriptions\commerce\storefront\recommendation\CommerceStorefrontRecommendationResolver;

final class commerce_795f6c_polish_and_fixes_test extends \advanced_testcase {
    public function test_sku_is_generated_and_collision_safe(): void {
        global $DB; $this->resetAfterTest();
        $generator=new CommerceProductSkuGenerator($DB);
        $this->assertSame('COURSE_ACCESS.COMPLETE_A1_COURSE', $generator->generate('course_access','Complete A1 Course'));
    }
    public function test_experience_resolver_uses_global_core_text(): void {
        $resolved=(new CommerceStorefrontExperienceResolver())->resolve(['storefront'=>['experience'=>['quickfacts'=>[['value'=>str_repeat('x',60),'label'=>'videos']]]]],'course_access');
        $this->assertSame(40, \core_text::strlen($resolved->get_quick_facts()[0]['value']));
    }
    public function test_recommendations_are_normalised_and_limited(): void {
        $result=(new CommerceStorefrontRecommendationResolver())->resolve(['storefront'=>['recommendations'=>[' a.1 ','A.1','b.2','c.3','d.4','e.5']]]);
        $this->assertSame(['A.1','B.2','C.3','D.4'],$result);
    }
    public function test_ownership_query_does_not_embed_limit(): void {
        $source=file_get_contents(__DIR__.'/../../../classes/commerce/storefront/ownership/CommerceStorefrontOwnershipResolver.php');
        $this->assertStringNotContainsString('LIMIT 1', $source);
    }
}
