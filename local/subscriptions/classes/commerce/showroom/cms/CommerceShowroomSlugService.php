<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\showroom\cms;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\url\CommerceProductSlugService;
use local_subscriptions\url\CommerceRouteRegistry;

/**
 * Resolves and validates public Showroom slugs.
 *
 * Top-level published Showroom slugs intentionally keep precedence over
 * product slugs, matching the historical explicit .htaccess ordering.
 * Publication still prevents collisions with another published Showroom or
 * a reserved Commerce route.
 */
final class CommerceShowroomSlugService {
    /** @var string[] */
    private const SLUG_FIELDS = ['slugfr', 'slugen', 'slugru'];

    /**
     * Extra single-segment routes defined directly in .htaccess rather than
     * through CommerceRouteRegistry::route_from_slug().
     *
     * @var string[]
     */
    private const RESERVED_SEGMENTS = [
        'crm',
        'boutique',
        'shop',
        'magazin',
        'digital',
        'terms',
        'privacy',
    ];

    public function __construct(
        private readonly \moodle_database $db
    ) {
    }

    public function find_published_showroom_key(string $slug): ?string {
        $slug = CommerceProductSlugService::clean($slug);
        if ($slug === '') {
            return null;
        }

        $records = $this->db->get_records(
            'local_subs_showroom',
            ['status' => CommerceShowroomStatus::PUBLISHED],
            'id ASC',
            'id,showroomkey,slugfr,slugen,slugru'
        );

        $match = null;
        foreach ($records as $record) {
            foreach (self::SLUG_FIELDS as $field) {
                $candidate = CommerceProductSlugService::clean(
                    (string)($record->{$field} ?? '')
                );
                if ($candidate !== $slug) {
                    continue;
                }

                if (
                    $match !== null
                    && $match !== (string)$record->showroomkey
                ) {
                    // A published duplicate should have been blocked by the
                    // publication workflow. Fail closed if legacy data contains
                    // one anyway.
                    return null;
                }

                $match = (string)$record->showroomkey;
            }
        }

        return $match;
    }

    public function assert_publishable_slugs(
        \stdClass $showroom
    ): void {
        $showroomid = (int)$showroom->id;
        $seen = [];

        foreach (self::SLUG_FIELDS as $field) {
            $rawslug = trim((string)($showroom->{$field} ?? ''));
            if ($rawslug === '') {
                continue;
            }

            $slug = CommerceProductSlugService::clean($rawslug);
            if ($slug === '') {
                throw new \moodle_exception(
                    'commerce_showroom_publish_slug_conflict',
                    'local_subscriptions',
                    '',
                    $rawslug
                );
            }

            // Repeating the same slug across the FR/EN/RU fields of the same
            // Showroom is harmless and should only be validated once.
            if (isset($seen[$slug])) {
                continue;
            }
            $seen[$slug] = true;

            if ($this->is_reserved($slug)) {
                throw new \moodle_exception(
                    'commerce_showroom_publish_slug_conflict',
                    'local_subscriptions',
                    '',
                    $rawslug
                );
            }

            if ($this->used_by_other_published_showroom(
                $slug,
                $showroomid
            )) {
                throw new \moodle_exception(
                    'commerce_showroom_publish_slug_conflict',
                    'local_subscriptions',
                    '',
                    $rawslug
                );
            }
        }
    }

    private function is_reserved(string $slug): bool {
        if (CommerceRouteRegistry::route_from_slug($slug) !== null) {
            return true;
        }

        return in_array($slug, self::RESERVED_SEGMENTS, true);
    }

    private function used_by_other_published_showroom(
        string $slug,
        int $showroomid
    ): bool {
        $records = $this->db->get_records(
            'local_subs_showroom',
            ['status' => CommerceShowroomStatus::PUBLISHED],
            '',
            'id,slugfr,slugen,slugru'
        );

        foreach ($records as $record) {
            if ((int)$record->id === $showroomid) {
                continue;
            }

            foreach (self::SLUG_FIELDS as $field) {
                if (
                    CommerceProductSlugService::clean(
                        (string)($record->{$field} ?? '')
                    ) === $slug
                ) {
                    return true;
                }
            }
        }

        return false;
    }
}
