<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\personaloffer\audience;

defined('MOODLE_INTERNAL') || die();

interface CommercePersonalOfferAudienceProvider {
    public function get_type(): string;

    /** @return array<string,mixed> */
    public function source(int $sourceid, string $language): array;

    /**
     * @param array<string,mixed> $criteria
     * @return array<int,array<string,mixed>>
     */
    public function candidates(int $sourceid, array $criteria, string $language): array;
}
