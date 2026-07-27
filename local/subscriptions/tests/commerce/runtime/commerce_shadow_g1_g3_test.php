<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\entitlement\domain\CommerceEntitlementGrant;
use local_subscriptions\commerce\fulfillment\native\CommerceNativeFulfillmentContext;
use local_subscriptions\commerce\fulfillment\native\CommerceNativeFulfillmentResult;
use local_subscriptions\commerce\shadow\CommerceLegacyFulfillmentObservation;
use local_subscriptions\commerce\shadow\CommerceShadowComparator;
use local_subscriptions\commerce\shadow\CommerceShadowComparison;
use local_subscriptions\commerce\shadow\CommerceShadowEffect;
use local_subscriptions\commerce\shadow\CommerceShadowEntryPointRegistry;
use local_subscriptions\commerce\shadow\CommerceShadowExecutionReport;
use local_subscriptions\commerce\shadow\CommerceShadowExecutionService;
use local_subscriptions\commerce\shadow\CommerceShadowGrantSource;
use local_subscriptions\commerce\shadow\CommerceShadowNativeExecutor;
use local_subscriptions\commerce\shadow\CommerceShadowSource;

final class commerce_shadow_g1_g3_test extends \advanced_testcase {
    public function test_inventory_has_unique_existing_sources(): void {
        $keys = [];
        foreach ((new CommerceShadowEntryPointRegistry())->all() as $entrypoint) {
            $this->assertTrue(CommerceShadowSource::is_valid($entrypoint->get_source()));
            $this->assertNotContains($entrypoint->get_key(), $keys);
            $keys[] = $entrypoint->get_key();
        }
        $this->assertCount(8, $keys);
    }

    public function test_shadow_execution_is_dry_run_and_returns_effects(): void {
        $grant = $this->grant();
        $source = new class($grant) implements CommerceShadowGrantSource {
            public function __construct(private readonly CommerceEntitlementGrant $grant) {}
            public function find_for_purchase(string $purchasereference): array { return [$this->grant]; }
        };
        $executor = new class implements CommerceShadowNativeExecutor {
            public bool $dryrun = false;
            public function execute(CommerceEntitlementGrant $grant, CommerceNativeFulfillmentContext $context): CommerceNativeFulfillmentResult {
                $this->dryrun = $context->is_dry_run();
                return CommerceNativeFulfillmentResult::skipped($grant, 'dry-run', ['courseid' => 17, 'dryrun' => true]);
            }
        };

        $report = (new CommerceShadowExecutionService($source, $executor))->execute('purchase-1', CommerceShadowSource::STRIPE_WEBHOOK, null, 1700000000);
        $this->assertTrue($executor->dryrun);
        $this->assertTrue($report->is_successful());
        $this->assertCount(1, $report->get_effects());
        $this->assertSame(17, $report->get_effects()[0]->get_attributes()['courseid']);
    }

    public function test_comparator_detects_equal_equivalent_and_different(): void {
        $nativeeffect = new CommerceShadowEffect('grant-1', 'course_access', 'course:17:full', 7, 'u@example.com', ['courseid' => 17, 'status' => 'would_create']);
        $native = new CommerceShadowExecutionReport('purchase-1', CommerceShadowSource::STRIPE_WEBHOOK, 'exec', 1, 1, [], [$nativeeffect]);
        $comparator = new CommerceShadowComparator();

        $equal = $comparator->compare(new CommerceLegacyFulfillmentObservation('purchase-1', CommerceShadowSource::STRIPE_WEBHOOK, [$nativeeffect]), $native);
        $this->assertSame(CommerceShadowComparison::EQUAL, $equal->get_status());

        $legacyrepresentation = new CommerceShadowEffect('legacy-1', 'course_access', 'course:17:full', 7, 'u@example.com', ['courseid' => 17, 'status' => 'active']);
        $equivalent = $comparator->compare(new CommerceLegacyFulfillmentObservation('purchase-1', CommerceShadowSource::STRIPE_WEBHOOK, [$legacyrepresentation]), $native);
        $this->assertSame(CommerceShadowComparison::EQUIVALENT, $equivalent->get_status());

        $different = new CommerceShadowEffect('legacy-2', 'course_access', 'course:18:full', 7, 'u@example.com', ['courseid' => 18]);
        $comparison = $comparator->compare(new CommerceLegacyFulfillmentObservation('purchase-1', CommerceShadowSource::STRIPE_WEBHOOK, [$different]), $native);
        $this->assertSame(CommerceShadowComparison::DIFFERENT, $comparison->get_status());
    }

    public function test_course_access_implementation_details_are_representation_only(): void {
        $legacy = new CommerceShadowEffect(
            'legacy-course',
            'course_access',
            'course:3:full',
            113,
            'nicolas.kutrowski21@gmail.com',
            [
                'accesslevel' => 'full',
                'active' => true,
                'courseid' => 3,
                'userid' => 113,
            ]
        );
        $native = new CommerceShadowEffect(
            'native-course',
            'course_access',
            'course:3:full',
            113,
            'nicolas.kutrowski21@gmail.com',
            [
                'accesslevel' => 'full',
                'cohorts' => [],
                'courseid' => 3,
                'enrolment' => [
                    'courseid' => 3,
                    'roleid' => 5,
                    'status' => 'would_update',
                    'timeend' => 0,
                    'timestart' => 1785147782,
                    'userid' => 113,
                    'wouldcreateinstance' => false,
                ],
                'groups' => [],
                'role' => [
                    'removed' => [],
                    'roleid' => 5,
                    'roleshortname' => 'student',
                    'status' => 'unchanged',
                ],
                'roleshortname' => 'student',
                'userid' => 113,
                'validfrom' => 1785147782,
                'validuntil' => null,
            ]
        );

        $report = new CommerceShadowExecutionReport(
            'purchase-course',
            CommerceShadowSource::CHECKOUT_SUBSCRIPTION,
            'exec-course',
            1,
            1,
            [],
            [$native]
        );
        $comparison = (new CommerceShadowComparator())->compare(
            new CommerceLegacyFulfillmentObservation(
                'purchase-course',
                CommerceShadowSource::CHECKOUT_SUBSCRIPTION,
                [$legacy]
            ),
            $report
        );

        $this->assertSame(CommerceShadowComparison::EQUIVALENT, $comparison->get_status());
        $this->assertSame([], $comparison->get_differences());
    }

    public function test_course_access_level_remains_a_business_difference(): void {
        $legacy = new CommerceShadowEffect(
            'legacy-course',
            'course_access',
            'course:3:full',
            114,
            'user114@example.com',
            ['accesslevel' => 'full', 'active' => true]
        );
        $native = new CommerceShadowEffect(
            'native-course',
            'course_access',
            'course:3:full',
            114,
            'user114@example.com',
            ['accesslevel' => 'grammar', 'active' => true]
        );

        $report = new CommerceShadowExecutionReport(
            'purchase-course-level',
            CommerceShadowSource::CHECKOUT_SUBSCRIPTION,
            'exec-course-level',
            1,
            1,
            [],
            [$native]
        );
        $comparison = (new CommerceShadowComparator())->compare(
            new CommerceLegacyFulfillmentObservation(
                'purchase-course-level',
                CommerceShadowSource::CHECKOUT_SUBSCRIPTION,
                [$legacy]
            ),
            $report
        );

        $this->assertSame(CommerceShadowComparison::DIFFERENT, $comparison->get_status());
    }

    private function grant(): CommerceEntitlementGrant {
        return new CommerceEntitlementGrant('grant-1', 'purchase-1', 'item-1', 'A1-FULL', 'course_access', 'course:17:full', 1, 7, 'u@example.com', 1700000000);
    }
}
