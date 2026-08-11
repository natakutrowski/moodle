<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\course\recommendation\CommerceCourseRecommendationEligibility;
use local_subscriptions\commerce\course\recommendation\CommerceCourseRecommendationService;
use local_subscriptions\commerce\course\recommendation\CommerceCourseResourceKeyParser;

/** Integration and policy tests for learner course recommendations. */
final class commerce_course_recommendation_service_test extends \advanced_testcase {
    public function test_resource_keys_and_trial_upgrade_policy_are_supported(): void {
        $this->assertSame(17, CommerceCourseResourceKeyParser::course_id('17'));
        $this->assertSame(17, CommerceCourseResourceKeyParser::course_id('course:17:full'));
        $this->assertSame(23, CommerceCourseResourceKeyParser::course_id('invalid', ['courseid' => 23]));
        $this->assertNull(CommerceCourseResourceKeyParser::course_id('digital-product:17'));

        $decision = (new CommerceCourseRecommendationEligibility())->evaluate(
            true,
            [17],
            [17],
            [17]
        );
        $this->assertTrue($decision['relevant']);
        $this->assertTrue($decision['upgrade']);

        $owned = (new CommerceCourseRecommendationEligibility())->evaluate(
            true,
            [17],
            [17],
            []
        );
        $this->assertFalse($owned['relevant']);


        $explicitupgrade = (new CommerceCourseRecommendationEligibility())->evaluate(
            false,
            [17],
            [17],
            [],
            true
        );
        $this->assertTrue($explicitupgrade['relevant']);
        $this->assertTrue($explicitupgrade['upgrade']);
    }

    public function test_service_falls_back_to_available_currency_and_resolves_course_resource_key(): void {
        global $DB;

        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $course = $this->getDataGenerator()->create_course();

        $this->insert_course_product('COURSE.RECOMMENDED', (int)$course->id, 'RUB', 990000);

        $items = (new CommerceCourseRecommendationService($DB))->get_for_learner(
            (int)$user->id,
            [],
            [],
            'fr',
            'EUR'
        )->all();

        $this->assertCount(1, $items);
        $this->assertSame('COURSE.RECOMMENDED', $items[0]->sku);
        $this->assertStringContainsString('RUB', $items[0]->priceformatted);
    }

    public function test_explicit_upgrade_metadata_exposes_differential_price_and_path(): void {
        global $DB;

        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $course = $this->getDataGenerator()->create_course();

        $productid = $this->insert_product(
            'COURSE.A2.FULL',
            'course_access',
            'A2 Full',
            'EUR',
            12900,
            [
                'upgrade' => true,
                'upgrade_from_courseids' => [(int)$course->id],
                'upgrade_amount_minor' => 4900,
                'upgrade_currency' => 'EUR',
                'upgrade_from_label' => 'A2 Grammar',
                'upgrade_to_label' => 'A2 Full',
            ]
        );
        $this->attach_course_scope($productid, (int)$course->id);
        $now = time();
        $DB->insert_record('local_subs_commerce_prod_ent', (object)[
            'productid' => $productid,
            'type' => 'course_access',
            'resourcekey' => 'course:' . $course->id . ':full',
            'durationseconds' => null,
            'quantity' => 1,
            'configurationjson' => json_encode(['courseid' => (int)$course->id]),
            'sortorder' => 0,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);

        $items = (new CommerceCourseRecommendationService($DB))->get_for_learner(
            (int)$user->id,
            [(int)$course->id],
            [],
            'fr',
            'EUR',
            10
        )->all();

        $this->assertCount(1, $items);
        $this->assertTrue($items[0]->upgrade);
        $this->assertSame('49.00 EUR', $items[0]->upgradepriceformatted);
        $this->assertSame('A2 Grammar', $items[0]->upgradefromlabel);
        $this->assertSame('A2 Full', $items[0]->upgradetolabel);
    }


    public function test_owned_target_product_is_not_recommended_even_when_upgrade_metadata_exists(): void {
        global $DB;

        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $course = $this->getDataGenerator()->create_course();

        $sku = 'COURSE.A2.FULL.OWNED';
        $productid = $this->insert_product(
            $sku,
            'course_access',
            'A2 Full',
            'EUR',
            17000,
            [
                'upgrade' => true,
                'upgrade_from_courseids' => [(int)$course->id],
                'upgrade_amount_minor' => 7000,
                'upgrade_currency' => 'EUR',
            ]
        );
        $this->attach_course_scope($productid, (int)$course->id);
        $now = time();
        $DB->insert_record('local_subs_commerce_prod_ent', (object)[
            'productid' => $productid,
            'type' => 'course_access',
            'resourcekey' => 'course:' . $course->id . ':full',
            'durationseconds' => null,
            'quantity' => 1,
            'configurationjson' => json_encode(['courseid' => (int)$course->id]),
            'sortorder' => 0,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
        $DB->insert_record('local_subs_commerce_grant', (object)[
            'grantreference' => 'grant-owned-full',
            'idempotencykey' => 'grant-owned-full-key',
            'purchasereference' => 'cmp_owned_full',
            'itemreference' => $sku,
            'productsku' => $sku,
            'type' => 'course_access',
            'resourcekey' => 'course:' . $course->id . ':full',
            'quantity' => 1,
            'beneficiaryuserid' => $user->id,
            'beneficiaryemail' => $user->email,
            'validfrom' => $now - HOURSECS,
            'validuntil' => null,
            'status' => 'active',
            'configurationjson' => json_encode(['courseid' => (int)$course->id, 'accesslevel' => 'full']),
            'metadatajson' => '{}',
            'timecreated' => $now,
            'timemodified' => $now,
        ]);

        $items = (new CommerceCourseRecommendationService($DB))->get_for_learner(
            (int)$user->id,
            [(int)$course->id],
            [],
            'fr',
            'EUR',
            10
        )->all();

        $this->assertCount(0, $items);
    }

    public function test_bundle_components_use_catalogue_sku_contract(): void {
        global $DB;

        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $coursea = $this->getDataGenerator()->create_course();
        $courseb = $this->getDataGenerator()->create_course();

        $childa = $this->insert_course_product('COURSE.CHILD.A', (int)$coursea->id, 'EUR', 4900);
        $childb = $this->insert_course_product('COURSE.CHILD.B', (int)$courseb->id, 'EUR', 5900);
        $bundleid = $this->insert_product('BUNDLE.COURSES', 'bundle', 'Pack de cours', 'EUR', 8900, [
            'bundle_pricing_strategy' => 'fixed',
        ]);

        $now = time();
        foreach ([[$childa, 1], [$childb, 2]] as [$childid, $sortorder]) {
            $DB->insert_record('local_subs_commerce_prod_comp', (object)[
                'parentproductid' => $bundleid,
                'childproductid' => $childid,
                'quantity' => 1,
                'sortorder' => $sortorder,
                'metadatajson' => null,
                'timecreated' => $now,
                'timemodified' => $now,
            ]);
        }

        $items = (new CommerceCourseRecommendationService($DB))->get_for_learner(
            (int)$user->id,
            [],
            [],
            'fr',
            'EUR',
            10
        )->all();
        $skus = array_map(static fn($item): string => $item->sku, $items);

        $this->assertContains('BUNDLE.COURSES', $skus);
    }

    public function test_course_upgrade_takes_precedence_over_trial_offer(): void {
        global $DB;

        $this->resetAfterTest(true);

        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $now = time();

        $scopeid = (int)$DB->insert_record(
            'subscription_access_scope',
            (object)[
                'name' => 'Trial recommendation scope ' . $now,
                'course_ids' => (string)$course->id,
                'creation_date' => $now,
                'last_update' => $now,
            ]
        );

        $trialplanid = (int)$DB->insert_record(
            'subscription_plan',
            (object)[
                'accessscopeid' => $scopeid,
                'name' => 'Trial recommendation',
                'duration_key' => '1month',
                'is_active' => 1,
                'is_trial' => 1,
                'creation_date' => $now,
                'last_update' => $now,
            ]
        );

        set_config(
            'trial_plan_id',
            $trialplanid,
            'local_subscriptions'
        );
        set_config(
            'trial_discount_percent',
            20,
            'local_subscriptions'
        );
        set_config(
            'trial_discount_hours',
            72,
            'local_subscriptions'
        );

        $DB->insert_record(
            'user_subscription',
            (object)[
                'userid' => $user->id,
                'planid' => $trialplanid,
                'pricepaid' => 0,
                'currency' => 'EUR',
                'transactionid' => 'recommendation-trial-upgrade',
                'payment_provider' => 'trial',
                'start_date' => $now - HOURSECS,
                'end_date' => $now + DAYSECS,
                'status' => 'ACTIVE',
                'creation_date' => $now,
            ]
        );

        $productid = $this->insert_product(
            'COURSE.A2.FULL.TRIAL.UPGRADE',
            'course_access',
            'A2 Full',
            'EUR',
            17000,
            [
                'upgrade' => true,
                'upgrade_from_courseids' => [(int)$course->id],
                'upgrade_amount_minor' => 7000,
                'upgrade_currency' => 'EUR',
                'upgrade_from_label' => 'A2 Grammar',
                'upgrade_to_label' => 'A2 Full',
            ]
        );

        $this->attach_course_scope(
            $productid,
            (int)$course->id
        );

        $DB->insert_record(
            'local_subs_commerce_prod_ent',
            (object)[
                'productid' => $productid,
                'type' => 'course_access',
                'resourcekey' =>
                    'course:' . (int)$course->id . ':full',
                'durationseconds' => null,
                'quantity' => 1,
                'configurationjson' => json_encode([
                    'courseid' => (int)$course->id,
                    'accesslevel' => 'full',
                ]),
                'sortorder' => 0,
                'timecreated' => $now,
                'timemodified' => $now,
            ]
        );

        $items = (
            new CommerceCourseRecommendationService($DB)
        )->get_for_learner(
            (int)$user->id,
            [(int)$course->id],
            [(int)$course->id],
            'fr',
            'EUR',
            10
        )->all();

        $this->assertCount(1, $items);
        $this->assertTrue($items[0]->upgrade);
        $this->assertFalse($items[0]->trialoffer);
        $this->assertSame(
            'A2 Grammar',
            $items[0]->upgradefromlabel
        );
        $this->assertSame(
            'A2 Full',
            $items[0]->upgradetolabel
        );
    }

    public function test_bundle_containing_trial_course_is_never_upgrade(): void {
        global $DB;

        $this->resetAfterTest(true);

        $user = $this->getDataGenerator()->create_user();
        $coursea = $this->getDataGenerator()->create_course();
        $courseb = $this->getDataGenerator()->create_course();

        $childa = $this->insert_course_product(
            'COURSE.BUNDLE.TRIAL.A',
            (int)$coursea->id,
            'EUR',
            4900
        );
        $childb = $this->insert_course_product(
            'COURSE.BUNDLE.NEW.B',
            (int)$courseb->id,
            'EUR',
            5900
        );

        $bundleid = $this->insert_product(
            'BUNDLE.TRIAL.NOT.UPGRADE',
            'bundle',
            'Pack progression',
            'EUR',
            8900,
            [
                'upgrade' => true,
                'upgrade_from_courseids' => [(int)$coursea->id],
            ]
        );

        $now = time();
        foreach ([$childa, $childb] as $position => $childid) {
            $DB->insert_record(
                'local_subs_commerce_prod_comp',
                (object)[
                    'parentproductid' => $bundleid,
                    'childproductid' => $childid,
                    'quantity' => 1,
                    'sortorder' => $position + 1,
                    'metadatajson' => null,
                    'timecreated' => $now,
                    'timemodified' => $now,
                ]
            );
        }

        $items = (
            new CommerceCourseRecommendationService($DB)
        )->get_for_learner(
            (int)$user->id,
            [(int)$coursea->id],
            [(int)$coursea->id],
            'fr',
            'EUR',
            10
        )->all();

        $bundle = null;
        foreach ($items as $item) {
            if ($item->sku === 'BUNDLE.TRIAL.NOT.UPGRADE') {
                $bundle = $item;
                break;
            }
        }

        $this->assertNotNull($bundle);
        $this->assertSame('bundle', $bundle->type);
        $this->assertFalse($bundle->upgrade);
        $this->assertNull($bundle->upgradepriceformatted);
        $this->assertNull($bundle->upgradefromlabel);
        $this->assertNull($bundle->upgradetolabel);
    }

    private function insert_course_product(string $sku, int $courseid, string $currency, int $amountminor): int {
        global $DB;

        $productid = $this->insert_product($sku, 'course_access', $sku, $currency, $amountminor);
        $this->attach_course_scope($productid, $courseid);
        $now = time();
        $DB->insert_record('local_subs_commerce_prod_ent', (object)[
            'productid' => $productid,
            'type' => 'course_access',
            'resourcekey' => 'course:' . $courseid . ':full',
            'durationseconds' => null,
            'quantity' => 1,
            'configurationjson' => json_encode(['courseid' => $courseid]),
            'sortorder' => 0,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
        return $productid;
    }

    private function attach_course_scope(int $productid, int $courseid): void {
        global $DB;

        $now = time();
        $scopeid = (int)$DB->insert_record('subscription_access_scope', (object)[
            'name' => 'Recommendation scope ' . $productid,
            'course_ids' => json_encode([$courseid]),
            'creation_date' => $now,
            'last_update' => $now,
        ]);
        $product = $DB->get_record('local_subs_commerce_product', ['id' => $productid], '*', MUST_EXIST);
        $metadata = json_decode((string)($product->metadatajson ?? ''), true);
        $metadata = is_array($metadata) ? $metadata : [];
        $metadata['access'] = ['scopeid' => $scopeid];
        $product->metadatajson = json_encode($metadata);
        $product->timemodified = $now;
        $DB->update_record('local_subs_commerce_product', $product);
    }

    private function insert_product(
        string $sku,
        string $type,
        string $name,
        string $currency,
        int $amountminor,
        array $metadata = []
    ): int {
        global $DB;

        $now = time();
        $metadata = array_merge(['visibility' => 'visible'], $metadata);
        $productid = (int)$DB->insert_record('local_subs_commerce_product', (object)[
            'sku' => $sku,
            'type' => $type,
            'status' => 'active',
            'name' => $name,
            'description' => '',
            'metadatajson' => json_encode($metadata),
            'availablefrom' => null,
            'availableuntil' => null,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
        $DB->insert_record('local_subs_commerce_prod_price', (object)[
            'productid' => $productid,
            'currency' => $currency,
            'amountminor' => $amountminor,
            'provider' => 'test',
            'providerpriceid' => $sku . '.' . $currency,
            'active' => 1,
            'metadatajson' => null,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
        $DB->insert_record('local_subs_commerce_prod_tr', (object)[
            'productid' => $productid,
            'language' => 'fr',
            'name' => $name,
            'shortdescription' => 'Recommandation de test',
            'description' => '',
            'metadatajson' => null,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
        return $productid;
    }
}
