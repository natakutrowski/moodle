<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\storefront\experience;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\storefront\localisation\CommerceStorefrontLocaleResolver;

use core_text;

/** Normalises F6B grouping, reassurance and quick-fact metadata. */
final class CommerceStorefrontExperienceResolver {
    public const GROUPS = ['auto', 'courses', 'resources', 'bundles'];
    public const TRUST_ITEMS = ['secure_payment', 'immediate_access', 'support', 'lifetime_access'];

    public function resolve(array $metadata, string $producttype): CommerceStorefrontExperience {
        $storefront = is_array($metadata['storefront'] ?? null) ? $metadata['storefront'] : [];
        $raw = is_array($storefront['experience'] ?? null) ? $storefront['experience'] : [];

        $group = strtolower(trim((string)($raw['group'] ?? 'auto')));
        if (!in_array($group, self::GROUPS, true)) {
            $group = 'auto';
        }
        if ($group === 'auto') {
            $group = $this->default_group($producttype);
        }

        $trust = [];
        $source = is_array($raw['trust'] ?? null) ? $raw['trust'] : $this->default_trust($producttype);
        foreach ($source as $item) {
            $item = strtolower(trim((string)$item));
            if (in_array($item, self::TRUST_ITEMS, true) && !in_array($item, $trust, true)) {
                $trust[] = $item;
            }
        }

        $facts = [];
        foreach (is_array($raw['quickfacts'] ?? null) ? $raw['quickfacts'] : [] as $fact) {
            if (!is_array($fact)) {
                continue;
            }
            $value = trim((string)($fact['value'] ?? ''));
            $label = trim((string)($fact['label'] ?? ''));
            if ($value === '' || $label === '') {
                continue;
            }
            $facts[] = [
                'value' => core_text::substr($value, 0, 40),
                'label' => core_text::substr($label, 0, 80),
            ];
            if (count($facts) >= 6) {
                break;
            }
        }

        return new CommerceStorefrontExperience($group, $trust, $facts);
    }

    private function default_group(string $type): string {
        return match ($type) {
            'subscription', 'course_access' => 'courses',
            'digital', 'digital_download' => 'resources',
            'bundle' => 'bundles',
            default => 'resources',
        };
    }

    /** @return string[] */
    private function default_trust(string $type): array {
        $items = ['secure_payment', 'immediate_access', 'support'];
        if (in_array($type, ['course_access', 'digital_download', 'bundle'], true)) {
            $items[] = 'lifetime_access';
        }
        return $items;
    }
}
