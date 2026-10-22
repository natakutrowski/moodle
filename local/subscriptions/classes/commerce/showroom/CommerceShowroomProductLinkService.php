<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\showroom;

use local_subscriptions\commerce\catalog\presentation\CommerceProductDiscoveryUrlResolver;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomPublishedDefinitionResolver;

defined('MOODLE_INTERNAL') || die();

/** Resolves the Showroom associated with product metadata. */
final class CommerceShowroomProductLinkService {
    /** @param array<string,mixed> $metadata @return array<string,mixed> */
    public function present(array $metadata, string $language): array {
        $configuration = is_array($metadata['showroom'] ?? null)
            ? $metadata['showroom']
            : [];
        global $DB;

        $key = strtolower(trim((string)($configuration['key'] ?? '')));
        if (
            $key === ''
            || !CommerceProductDiscoveryUrlResolver::storefront_presentation_enabled($metadata)
        ) {
            return ['hasshowroom' => false];
        }

        try {
            $definition = (new CommerceShowroomPublishedDefinitionResolver($DB))
                ->require($key);
        } catch (\moodle_exception $exception) {
            return ['hasshowroom' => false];
        }

        return [
            'hasshowroom' => true,
            'showroomurl' => CommerceShowroomUrl::make(
                $definition,
                ['source' => 'storefront'],
                $language
            )->out(false),
            'showroomlabel' => get_string(
                'commerce_storefront_full_presentation',
                'local_subscriptions'
            ),
        ];
    }
}
