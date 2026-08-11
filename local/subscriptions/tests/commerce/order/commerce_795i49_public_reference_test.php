<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\order\reference\CommercePublicOrderReference;

/** Tests the stable public order reference introduced by Commerce 7.95 I4.9. */
final class commerce_795i49_public_reference_test extends \advanced_testcase {
    public function test_public_reference_is_stable_and_hides_internal_reference(): void {
        $resolver = new CommercePublicOrderReference();
        $timestamp = make_timestamp(2026, 7, 31, 10, 0, 0);

        $first = $resolver->from_internal('cmp_daa568ffe256ba5c0f028746', $timestamp);
        $second = $resolver->from_internal('cmp_daa568ffe256ba5c0f028746', $timestamp);

        $this->assertSame($first, $second);
        $this->assertMatchesRegularExpression('/^CFR-2026-[A-F0-9]{6}$/', $first);
        $this->assertStringNotContainsString('cmp_', $first);
    }
}
