<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\legal\document;

use local_subscriptions\commerce\currency\market\CommerceMarketCountryResolver;
use local_subscriptions\commerce\legal\entity\CommerceLegalEntityRegistry;

defined('MOODLE_INTERNAL') || die();

/** Resolves the versioned legal documents from the same market policy used by merchant routing. */
final class CommerceLegalDocumentResolver {
    public const DEFAULT_ROW_VERSION = '2026-09-v1';
    public const DEFAULT_RU_VERSION = '2026-09-v1';

    public function __construct(
        private readonly ?CommerceMarketCountryResolver $countryresolver = null
    ) {
    }

    public function resolve(?string $marketcountry = null, ?string $language = null): CommerceLegalDocumentSet {
        global $CFG;

        $country = $this->normalize_country(
            $marketcountry ?? ($this->countryresolver ?? new CommerceMarketCountryResolver())->resolve()
        );
        $language = $this->normalize_language($language ?? current_language());
        $isruby = in_array($country, ['RU', 'BY'], true);
        $profile = $isruby ? 'ru' : 'row';
        $entitykey = $isruby
            ? CommerceLegalEntityRegistry::RU_MAIN
            : CommerceLegalEntityRegistry::FR_MAIN;
        $version = trim((string)get_config('local_subscriptions', 'legal_documents_' . $profile . '_version'));
        if ($version === '') {
            $version = $isruby ? self::DEFAULT_RU_VERSION : self::DEFAULT_ROW_VERSION;
        }

        $privacy = trim((string)get_config('local_subscriptions', 'policy_url_' . $profile));
        $terms = trim((string)get_config('local_subscriptions', 'terms_url_' . $profile));
        $offer = trim((string)get_config('local_subscriptions', 'offer_url_' . $profile));

        if ($privacy === '') {
            $privacy = $CFG->wwwroot . $this->default_privacy_path($isruby, $language);
        }
        if ($terms === '') {
            $terms = $CFG->wwwroot . $this->default_terms_path($isruby, $language);
        }
        if ($offer === '') {
            // V1 has one combined CGU/CGV document; keep a distinct offer key/fingerprint for future split.
            $offer = $terms;
        }

        return new CommerceLegalDocumentSet(
            $entitykey,
            $country,
            $language,
            $version,
            $privacy,
            $terms,
            $offer
        );
    }

    private function normalize_country(string $country): string {
        $country = strtoupper(trim($country));
        return preg_match('/^[A-Z]{2}$/', $country) === 1 ? $country : 'ZZ';
    }

    private function normalize_language(string $language): string {
        $language = strtolower(trim($language));
        if (str_starts_with($language, 'fr')) {
            return 'fr';
        }
        if (str_starts_with($language, 'ru')) {
            return 'ru';
        }
        return 'en';
    }

    private function default_privacy_path(bool $isruby, string $language): string {
        if ($isruby) {
            return '/local/subscriptions/pages/policy_ru.php';
        }
        return $language === 'fr'
            ? '/local/subscriptions/pages/policy_fr.php'
            : '/local/subscriptions/pages/policy_en.php';
    }

    private function default_terms_path(bool $isruby, string $language): string {
        if ($isruby) {
            return '/local/subscriptions/pages/terms_ru.php';
        }
        return $language === 'fr'
            ? '/local/subscriptions/pages/terms_fr.php'
            : '/local/subscriptions/pages/terms_en.php';
    }
}
