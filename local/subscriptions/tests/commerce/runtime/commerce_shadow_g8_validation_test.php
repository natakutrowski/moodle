<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\shadow\CommerceShadowComparison;
use local_subscriptions\commerce\shadow\CommerceShadowDivergenceClassifier;
use local_subscriptions\commerce\shadow\reporting\CommerceShadowSearchCriteria;

final class commerce_shadow_g8_validation_test extends \advanced_testcase {
    public function test_all_comparison_outcomes_have_stable_business_classification(): void {
        $classifier = new CommerceShadowDivergenceClassifier();
        $expected = [
            CommerceShadowComparison::EQUAL => CommerceShadowDivergenceClassifier::MATCH,
            CommerceShadowComparison::EQUIVALENT => CommerceShadowDivergenceClassifier::REPRESENTATION_ONLY,
            CommerceShadowComparison::DIFFERENT => CommerceShadowDivergenceClassifier::BUSINESS_DIFFERENCE,
            CommerceShadowComparison::NOT_COMPARABLE => CommerceShadowDivergenceClassifier::NOT_COMPARABLE,
            CommerceShadowComparison::SHADOW_ERROR => CommerceShadowDivergenceClassifier::SHADOW_FAILURE,
        ];
        foreach ($expected as $status => $classification) {
            $this->assertSame($classification, $classifier->classify(new CommerceShadowComparison('purchase', $status)));
        }
    }

    public function test_search_guard_rejects_unbounded_or_invalid_queries(): void {
        $this->expectException(\coding_exception::class);
        new CommerceShadowSearchCriteria(limit: 1001);
    }

    public function test_runtime_hook_is_non_blocking_and_feature_flagged(): void {
        $path = __DIR__ . '/../../../classes/commerce/shadow/runtime/CommerceShadowRuntimeHook.php';
        $contents = (string) file_get_contents($path);
        $this->assertStringContainsString('commerce_fulfillment_shadow_enabled', $contents);
        $this->assertStringContainsString('catch (\\Throwable', $contents);
    }

    public function test_shadow_executor_cannot_use_persistent_executor(): void {
        $path = __DIR__ . '/../../../classes/commerce/shadow/CommerceKernelShadowNativeExecutor.php';
        $contents = (string) file_get_contents($path);
        $this->assertStringContainsString('CommerceNativeFulfillmentExecutor', $contents);
        $this->assertStringNotContainsString('CommercePersistentNativeFulfillmentExecutor', $contents);
        $this->assertStringContainsString('is_dry_run()', $contents);
    }
}
