<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

/** Canonicalises campaign attribution attached to Showroom cart items. */
final class CommerceShowroomTrackingContext {
    /** @return array<string,string> */
    public static function metadata(string $showroomkey, string $role = ''): array {
        $showroomkey = self::clean($showroomkey);
        $role = self::clean($role);

        $metadata = [
            'source' => 'showroom',
            'showroom' => $showroomkey,
        ];
        if ($role !== '') {
            $metadata['showroom_offer'] = $role;
        }
        return $metadata;
    }

    private static function clean(string $value): string {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';

        return trim($value, '-');
    }
}
