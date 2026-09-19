<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\legal\document;

defined('MOODLE_INTERNAL') || die();

/** Immutable versioned legal-document set presented for one Commerce market. */
final class CommerceLegalDocumentSet {
    public function __construct(
        private readonly string $legalentitykey,
        private readonly string $marketcountry,
        private readonly string $language,
        private readonly string $version,
        private readonly string $privacyurl,
        private readonly string $termsurl,
        private readonly string $offerurl
    ) {
        if (!preg_match('/^[a-z][a-z0-9_]*$/', trim($legalentitykey))) {
            throw new \coding_exception('A legal document set requires a stable legal entity key.');
        }
        if (!preg_match('/^[A-Z]{2}$/', strtoupper(trim($marketcountry)))) {
            throw new \coding_exception('A legal document set requires an ISO2 market country.');
        }
        if (trim($language) === '' || trim($version) === '') {
            throw new \coding_exception('A legal document set requires language and version.');
        }
        foreach ([$privacyurl, $termsurl, $offerurl] as $url) {
            if (trim($url) === '') {
                throw new \coding_exception('A legal document set cannot contain an empty document URL.');
            }
        }
    }

    public function get_legal_entity_key(): string { return trim($this->legalentitykey); }
    public function get_market_country(): string { return strtoupper(trim($this->marketcountry)); }
    public function get_language(): string { return trim($this->language); }
    public function get_version(): string { return trim($this->version); }
    public function get_privacy_url(): string { return trim($this->privacyurl); }
    public function get_terms_url(): string { return trim($this->termsurl); }
    public function get_offer_url(): string { return trim($this->offerurl); }

    public function get_fingerprint(): string {
        return hash('sha256', json_encode($this->fingerprint_payload(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    /** @return array<string, mixed> */
    public function to_array(): array {
        return [
            'schema' => 'legal_documents_v1',
            'legal_entity_key' => $this->get_legal_entity_key(),
            'market_country' => $this->get_market_country(),
            'language' => $this->get_language(),
            'version' => $this->get_version(),
            'fingerprint' => $this->get_fingerprint(),
            'documents' => [
                'privacy' => $this->document('privacy', $this->get_privacy_url()),
                'terms' => $this->document('terms', $this->get_terms_url()),
                'offer' => $this->document('offer', $this->get_offer_url()),
            ],
        ];
    }

    /** @return array<string, string> */
    private function document(string $key, string $url): array {
        return [
            'key' => $key,
            'url' => $url,
            'version' => $this->get_version(),
            'fingerprint' => hash('sha256', $key . "\n" . $this->get_version() . "\n" . $url),
        ];
    }

    /** @return array<string, string> */
    private function fingerprint_payload(): array {
        return [
            'legal_entity_key' => $this->get_legal_entity_key(),
            'market_country' => $this->get_market_country(),
            'language' => $this->get_language(),
            'version' => $this->get_version(),
            'privacy_url' => $this->get_privacy_url(),
            'terms_url' => $this->get_terms_url(),
            'offer_url' => $this->get_offer_url(),
        ];
    }
}
