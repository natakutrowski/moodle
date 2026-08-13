<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_showroom_anchors_and_guest_provider_probe_m93a_test extends advanced_testcase {
    public function test_showroom_exposes_stable_block_anchors(): void {
        global $CFG;
        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/showroom/third_group_verbs.mustache'
        );

        foreach ([
            'showroom-top',
            'showroom-problem',
            'showroom-problem-interactive',
            'showroom-learning-method',
            'showroom-video',
            'showroom-highlights',
            'showroom-ascent',
            'showroom-stage-method',
            'showroom-exercises',
            'showroom-offers',
            'showroom-comparison',
            'showroom-memory-method',
            'showroom-trust',
            'showroom-testimonials',
            'showroom-bonus',
            'showroom-faq',
            'showroom-support',
            'showroom-verbs-cards',
            'showroom-final',
        ] as $anchor) {
            self::assertStringContainsString('id="' . $anchor . '"', $template, $anchor);
        }
    }

    public function test_unfinished_checkout_crm_has_read_only_provider_probe_display(): void {
        global $CFG;
        $service = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/checkout/guest/CommerceUnfinishedGuestCheckoutCrmService.php'
        );
        $page = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/admin/commerce/unfinished-checkouts/index.php'
        );

        self::assertStringContainsString('->inspect_payment((int)$payment->id)', $service);
        self::assertStringNotContainsString('->reconcile_payment((int)$payment->id)', $service);
        self::assertStringContainsString('providerlivepaid', $page);
        self::assertStringContainsString('commerce_guest_crm_provider_paid_pending', $page);
    }
}
