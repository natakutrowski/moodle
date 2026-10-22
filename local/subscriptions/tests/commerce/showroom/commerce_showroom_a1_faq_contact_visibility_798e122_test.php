<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\cms\CommerceShowroomRuntimeBlockSet;

/** E8.12.2: separate FAQ contact never conflicts with the teacher Support block. */
final class commerce_showroom_a1_faq_contact_visibility_798e122_test extends \advanced_testcase {
    public function test_support_and_faq_contact_are_independent_rendered_blocks(): void {
        $rows = [];
        foreach (['support', 'faq', 'faq_contact', 'final_cta'] as $index => $type) {
            $rows[] = (object)[
                'id' => $index + 1,
                'blocktype' => $type,
                'enabled' => 1,
                'sortorder' => ($index + 1) * 10,
                'configjson' => '{}',
            ];
        }
        $method = (new \ReflectionClass(CommerceShowroomRuntimeBlockSet::class))->getMethod('from_blocks');
        $set = $method->invoke(null, $rows);
        self::assertTrue($set->is_enabled('support'));
        self::assertTrue($set->is_enabled('faq_contact'));
        $ordered = $set->to_template_data()['showroomorderedblocks'];
        self::assertSame(['support', 'faq', 'faq_contact', 'final_cta'], array_column($ordered, 'type'));
        self::assertTrue($ordered[2]['isfaqcontact']);

        // Disabled blocks must not render, even when their type exists in PHP.
        $rows[2]->enabled = 0;
        $disabled = $method->invoke(null, $rows);
        self::assertFalse($disabled->is_enabled('faq_contact'));
        self::assertSame(
            ['support', 'faq', 'final_cta'],
            array_column($disabled->to_template_data()['showroomorderedblocks'], 'type')
        );
    }

    public function test_cta_spacing_is_only_applied_to_a1(): void {
        global $CFG;
        $css = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/showroom.css');
        self::assertStringContainsString('E8.14.2', $css);
        self::assertStringContainsString(
            '#showroom-final .commerce-showroom-final__content > .commerce-showroom-btn', $css
        );
    }
}
