<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\trial\CommerceTrialCartPricingService;
use local_subscriptions\commerce\trial\CommerceTrialConversionBridge;

/** @covers \local_subscriptions\commerce\trial\CommerceTrialCartPricingService */
final class commerce_trial_native_cart_discount_test extends \advanced_testcase {
    public function test_trial_discount_is_recalculated_server_side(): void {
        global $DB;

        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $now = time();

        $scopeid = (int)$DB->insert_record(
            'subscription_access_scope',
            (object)[
                'name' => 'Trial scope ' . $now,
                'course_ids' => (string)$course->id,
                'creation_date' => $now,
                'last_update' => $now,
            ]
        );

        $trialplanid = $DB->insert_record('subscription_plan', (object)[
            'accessscopeid' => $scopeid,
            'name' => 'Trial',
            'duration_key' => '1month',
            'is_active' => 1,
            'is_trial' => 1,
            'creation_date' => $now,
            'last_update' => $now,
        ]);

        set_config('trial_plan_id', $trialplanid, 'local_subscriptions');
        set_config('trial_discount_percent', 20, 'local_subscriptions');
        set_config('trial_discount_hours', 72, 'local_subscriptions');
        set_config('trial_conversion_product_sku', 'COURSE.A1.FULL', 'local_subscriptions');

        $DB->insert_record('user_subscription', (object)[
            'userid' => $user->id,
            'planid' => $trialplanid,
            'pricepaid' => 0,
            'currency' => 'EUR',
            'transactionid' => 'trial-native-discount',
            'payment_provider' => 'trial',
            'start_date' => $now - HOURSECS,
            'end_date' => $now + DAYSECS,
            'status' => 'ACTIVE',
            'creation_date' => $now,
        ]);

        $productid = (int)$DB->insert_record(
            'local_subs_commerce_product',
            (object)[
            'sku' => 'COURSE.A1.FULL',
            'type' => 'course_access',
            'status' => 'active',
            'name' => 'A1 Full',
            'description' => '',
            'metadatajson' => null,
            'availablefrom' => null,
            'availableuntil' => null,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);

        $DB->insert_record('local_subs_commerce_prod_ent', (object)[
            'productid' => $productid,
            'type' => 'course_access',
            'resourcekey' => 'course:' . (int)$course->id . ':full',
            'durationseconds' => null,
            'quantity' => 1,
            'configurationjson' => json_encode([
                'courseid' => (int)$course->id,
                'accesslevel' => 'full',
                'roleshortname' => 'student',
            ]),
            'sortorder' => 100,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);

        $service = new CommerceTrialCartPricingService(
            new CommerceTrialConversionBridge($DB)
        );

        $metadata = $service->canonical_metadata(
            (int)$user->id,
            'COURSE.A1.FULL',
            'EUR',
            $now
        );
        $price = $service->resolve(
            (int)$user->id,
            'COURSE.A1.FULL',
            'EUR',
            12000,
            $now
        );

        $this->assertSame('trialconversion', $metadata['operation']);
        $this->assertSame(20, $metadata['trialdiscountpercent']);
        $this->assertNotNull($price);
        $this->assertSame(2400, $price->get_discount_minor());
        $this->assertSame(9600, $price->get_total_minor());
    }

    public function test_storefront_posts_only_trial_operation_not_discount_amount(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/storefront/product_commerce_panel.mustache'
        );
        $action = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/cart_action.php'
        );

        $this->assertIsString($template);
        $this->assertStringNotContainsString(
            'name="operation" value="trialconversion"',
            $template
        );
        $this->assertStringNotContainsString(
            'trialdiscountpercent"',
            $template
        );
        $this->assertIsString($action);
        $this->assertStringNotContainsString(
            "\$metadata = ['operation' => 'trialconversion'];",
            $action
        );
    }

    public function test_checkout_builder_preserves_trial_operation(): void {
        global $CFG;
        $builder = file_get_contents($CFG->dirroot . '/local/subscriptions/classes/commerce/checkout/unified/CommerceCheckoutPurchaseBuilder.php');
        $this->assertStringContainsString("'commerceoperation' => strtolower", $builder);
        $this->assertStringContainsString("\$cartitem->get_metadata()['operation']", $builder);
        $this->assertStringContainsString("'locked_trial_discount_minor'", $builder);
    }
}
