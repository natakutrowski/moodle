<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\showroom\cms;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\url\CommerceProductSlugService;
use local_subscriptions\url\CommerceRouteRegistry;

/**
 * Resolves and validates public Showroom slugs and short aliases.
 *
 * Canonical top-level Showroom slugs intentionally keep precedence over
 * product slugs, matching the historical explicit .htaccess ordering. A short
 * alias (for example /a1) never renders content directly: it temporarily
 * redirects to the language-specific canonical Showroom slug.
 */
final class CommerceShowroomSlugService {
    /** @var string[] */
    private const SLUG_FIELDS = ['slugfr', 'slugen', 'slugru'];

    /** @var array<string,string> */
    private const LANGUAGE_FIELDS = [
        'fr' => 'slugfr',
        'en' => 'slugen',
        'ru' => 'slugru',
    ];

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

    /**
     * Resolves a published short alias to one canonical language-specific slug.
     *
     * @return array{showroomkey:string,language:string,slug:string}|null
     */
    public function resolve_published_short_alias(
        string $alias,
        ?string $language = null
    ): ?array {
        $alias = CommerceShowroomAliasConfig::clean($alias);
        if ($alias === '') {
            return null;
        }

        $records = $this->db->get_records(
            'local_subs_showroom',
            ['status' => CommerceShowroomStatus::PUBLISHED],
            'id ASC',
            'id,showroomkey,slugfr,slugen,slugru,settingsjson'
        );

        $match = null;
        foreach ($records as $record) {
            if (CommerceShowroomAliasConfig::from_settings_json(
                (string)($record->settingsjson ?? '')
            ) !== $alias) {
                continue;
            }

            if ($match !== null) {
                // Publication prevents duplicate aliases. Fail closed if old
                // data nevertheless contains more than one published match.
                return null;
            }
            $match = $record;
        }

        if ($match === null) {
            return null;
        }

        $language = self::normalise_language($language);
        $targetlanguage = $language;
        $targetslug = $this->slug_for_language($match, $targetlanguage);

        if ($targetslug === '') {
            $targetlanguage = 'fr';
            $targetslug = $this->slug_for_language($match, $targetlanguage);
        }

        if ($targetslug === '') {
            foreach (array_keys(self::LANGUAGE_FIELDS) as $fallbacklanguage) {
                $candidate = $this->slug_for_language($match, $fallbacklanguage);
                if ($candidate === '') {
                    continue;
                }
                $targetlanguage = $fallbacklanguage;
                $targetslug = $candidate;
                break;
            }
        }

        if ($targetslug === '' || $targetslug === $alias) {
            return null;
        }

        return [
            'showroomkey' => (string)$match->showroomkey,
            'language' => $targetlanguage,
            'slug' => $targetslug,
        ];
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
                $this->throw_conflict($rawslug);
            }

            // Repeating the same slug across the FR/EN/RU fields of the same
            // Showroom is harmless and should only be validated once.
            if (isset($seen[$slug])) {
                continue;
            }
            $seen[$slug] = true;

            $this->assert_available($slug, $rawslug, $showroomid);
        }

        $alias = CommerceShowroomAliasConfig::from_settings_json(
            (string)($showroom->settingsjson ?? '')
        );
        if ($alias === '') {
            return;
        }

        // A short alias must remain a redirect-only route. Reusing one of the
        // Showroom's own canonical slugs would create an ambiguous loop.
        if (isset($seen[$alias])) {
            $this->throw_conflict($alias);
        }

        $this->assert_available($alias, $alias, $showroomid);
    }

    private function assert_available(
        string $slug,
        string $rawslug,
        int $showroomid
    ): void {
        if ($this->is_reserved($slug)) {
            $this->throw_conflict($rawslug);
        }

        if ($this->used_by_other_published_showroom($slug, $showroomid)) {
            $this->throw_conflict($rawslug);
        }
    }

    private function throw_conflict(string $slug): never {
        throw new \moodle_exception(
            'commerce_showroom_publish_slug_conflict',
            'local_subscriptions',
            '',
            $slug
        );
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
            'id,slugfr,slugen,slugru,settingsjson'
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

            if (CommerceShowroomAliasConfig::from_settings_json(
                (string)($record->settingsjson ?? '')
            ) === $slug) {
                return true;
            }
        }

        return false;
    }

    private function slug_for_language(\stdClass $record, string $language): string {
        $field = self::LANGUAGE_FIELDS[$language] ?? 'slugfr';
        return CommerceProductSlugService::clean((string)($record->{$field} ?? ''));
    }

    private static function normalise_language(?string $language): string {
        $language = strtolower(trim((string)($language ?: current_language())));
        $language = explode('_', str_replace('-', '_', $language))[0];
        return in_array($language, ['fr', 'en', 'ru'], true) ? $language : 'fr';
    }
}
