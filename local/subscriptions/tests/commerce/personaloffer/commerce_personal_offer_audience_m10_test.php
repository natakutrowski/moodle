<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\personaloffer;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\personaloffer\audience\CommercePersonalOfferAudienceRuleEvaluator;

final class commerce_personal_offer_audience_m10_test extends advanced_testcase {
    public function test_normalise_groups_keeps_supported_boolean_rules(): void {
        global $DB;
        $this->resetAfterTest();

        $service = new CommercePersonalOfferAudienceRuleEvaluator($DB);
        $groups = $service->normalise_groups([
            ['rules' => [
                ['operator' => 'owns', 'sourcetype' => 'native_product', 'sourceid' => 12],
                ['operator' => 'not_owns', 'sourcetype' => 'legacy_digital', 'sourceid' => 7],
            ]],
            ['rules' => [
                ['operator' => 'not_owns', 'sourcetype' => 'legacy_plan', 'sourceid' => 3],
                ['operator' => 'invalid', 'sourcetype' => 'native_product', 'sourceid' => 99],
            ]],
            ['rules' => []],
        ]);

        $this->assertCount(2, $groups);
        $this->assertCount(2, $groups[0]['rules']);
        $this->assertSame('owns', $groups[0]['rules'][0]['operator']);
        $this->assertSame('not_owns', $groups[0]['rules'][1]['operator']);
        $this->assertCount(1, $groups[1]['rules']);
        $this->assertSame('legacy_plan', $groups[1]['rules'][0]['sourcetype']);
    }

    public function test_empty_filter_groups_match_every_candidate(): void {
        global $DB;
        $this->resetAfterTest();

        $result = (new CommercePersonalOfferAudienceRuleEvaluator($DB))->evaluate(
            ['userid' => null, 'email' => 'guest@example.test'],
            []
        );

        $this->assertTrue($result['matched']);
        $this->assertSame([], $result['evidence']);
    }
}
