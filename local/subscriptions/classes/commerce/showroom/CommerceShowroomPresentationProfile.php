<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

/**
 * Product/editorial presentation strategy layered on top of generic Showroom Commerce state.
 *
 * Profiles are compatibility/presentation adapters. They must not resolve catalogue prices,
 * ownership or pedagogical capacity themselves.
 */
interface CommerceShowroomPresentationProfile {
    /**
     * Adds merchandising-only fields to already resolved Commerce offers.
     *
     * @param array<int,array<string,mixed>> $offers
     * @return array<int,array<string,mixed>>
     */
    public function decorate_offers(array $offers): array;

    /**
     * Returns fallback template data specific to this presentation profile.
     * Published CMS blocks may override these values afterwards.
     *
     * @param array<int,array<string,mixed>> $offers
     * @return array<string,mixed>
     */
    public function fallback_data(array $offers): array;
}
