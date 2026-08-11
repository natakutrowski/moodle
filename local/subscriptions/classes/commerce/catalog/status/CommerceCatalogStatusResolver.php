<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\status;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\domain\CommerceProductStatus;

/** Maps current Native/Legacy flags to the four unified status dimensions. */
final class CommerceCatalogStatusResolver {
    public function resolve(
        string $sourceStatus,
        ?int $availablefrom,
        ?int $availableuntil,
        array $metadata = [],
        int $now = 0,
        bool $configurationvalid = true
    ): CommerceCatalogStatusSnapshot {
        $now = $now > 0 ? $now : time();
        $sourceStatus = strtolower(trim($sourceStatus));

        $editorial = match ($sourceStatus) {
            CommerceProductStatus::ARCHIVED, 'archived' => CommerceCatalogEditorialStatus::ARCHIVED,
            CommerceProductStatus::ACTIVE, 'enabled', 'published', '1' => CommerceCatalogEditorialStatus::PUBLISHED,
            default => CommerceCatalogEditorialStatus::DRAFT,
        };

        $visibility = CommerceCatalogVisibility::require_valid(
            (string)($metadata['visibility'] ?? CommerceCatalogVisibility::VISIBLE)
        );

        if ($editorial !== CommerceCatalogEditorialStatus::PUBLISHED || $sourceStatus === CommerceProductStatus::INACTIVE) {
            $availability = CommerceCatalogAvailability::UNAVAILABLE;
        } else if ($availablefrom !== null && $now < $availablefrom) {
            $availability = CommerceCatalogAvailability::UPCOMING;
        } else if ($availableuntil !== null && $now > $availableuntil) {
            $availability = CommerceCatalogAvailability::ENDED;
        } else {
            $availability = CommerceCatalogAvailability::ON_SALE;
        }

        $technical = $configurationvalid
            ? CommerceCatalogTechnicalState::VALID
            : CommerceCatalogTechnicalState::INCOMPLETE;

        return new CommerceCatalogStatusSnapshot($editorial, $visibility, $availability, $technical);
    }
}
