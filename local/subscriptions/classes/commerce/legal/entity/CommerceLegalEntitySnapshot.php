<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\legal\entity;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\legal\merchant\CommerceMerchantResolutionResult;

/** Immutable legal-entity identity captured at purchase creation time. */
final class CommerceLegalEntitySnapshot {
    public const VERSION = 1;

    public function __construct(
        private readonly string $entitykey,
        private readonly string $registeredcountry,
        private readonly string $name,
        private readonly string $address,
        private readonly string $legal,
        private readonly string $registration,
        private readonly string $taxidentifier,
        private readonly string $email,
        private readonly string $phone,
        private readonly string $website,
        private readonly string $taxnotice,
        private readonly string $footer,
        private readonly string $marketcountry,
        private readonly string $resolutionrule,
        private readonly int $resolvedat,
        private readonly string $currency = '',
        private readonly string $provider = ''
    ) {
        if (!preg_match('/^[a-z][a-z0-9_]{1,63}$/', $this->entitykey)) {
            throw new \coding_exception('Invalid Commerce legal entity snapshot key.');
        }
        if (!preg_match('/^[A-Z]{2}$/', $this->registeredcountry)) {
            throw new \coding_exception('Invalid Commerce legal entity snapshot registered country.');
        }
        if (!preg_match('/^[A-Z]{2}$/', $this->marketcountry)) {
            throw new \coding_exception('Invalid Commerce legal entity snapshot market country.');
        }
        if (trim($this->resolutionrule) === '') {
            throw new \coding_exception('Commerce legal entity snapshot resolution rule is required.');
        }
        if ($this->resolvedat <= 0) {
            throw new \coding_exception('Commerce legal entity snapshot resolution timestamp is required.');
        }
        if ($this->currency !== '' && !preg_match('/^[A-Z]{3}$/', $this->currency)) {
            throw new \coding_exception('Invalid Commerce legal entity snapshot currency.');
        }
    }

    public static function from_resolution_result(
        CommerceMerchantResolutionResult $result,
        int $resolvedat,
        string $currency = '',
        string $provider = ''
    ): self {
        $entity = $result->get_entity();

        return new self(
            $entity->get_key(),
            $entity->get_registered_country(),
            $entity->get_name(),
            $entity->get_address(),
            $entity->get_legal(),
            $entity->get_registration(),
            $entity->get_tax_identifier(),
            $entity->get_email(),
            $entity->get_phone(),
            $entity->get_website(),
            $entity->get_tax_notice(),
            $entity->get_footer(),
            $result->get_market_country(),
            $result->get_rule(),
            $resolvedat,
            strtoupper(trim($currency)),
            strtolower(trim($provider))
        );
    }

    public static function from_array(array $data): self {
        return new self(
            (string)($data['legal_entity_key'] ?? ''),
            strtoupper(trim((string)($data['registered_country'] ?? ''))),
            (string)($data['name'] ?? ''),
            (string)($data['address'] ?? ''),
            (string)($data['legal'] ?? ''),
            (string)($data['registration'] ?? ''),
            (string)($data['tax_identifier'] ?? ''),
            (string)($data['email'] ?? ''),
            (string)($data['phone'] ?? ''),
            (string)($data['website'] ?? ''),
            (string)($data['tax_notice'] ?? ''),
            (string)($data['footer'] ?? ''),
            strtoupper(trim((string)($data['market_country'] ?? ''))),
            (string)($data['resolution_rule'] ?? ''),
            (int)($data['resolved_at'] ?? 0),
            strtoupper(trim((string)($data['currency'] ?? ''))),
            strtolower(trim((string)($data['provider'] ?? '')))
        );
    }

    public function get_entity_key(): string { return $this->entitykey; }
    public function get_registered_country(): string { return $this->registeredcountry; }
    public function get_name(): string { return $this->name; }
    public function get_address(): string { return $this->address; }
    public function get_legal(): string { return $this->legal; }
    public function get_registration(): string { return $this->registration; }
    public function get_tax_identifier(): string { return $this->taxidentifier; }
    public function get_email(): string { return $this->email; }
    public function get_phone(): string { return $this->phone; }
    public function get_website(): string { return $this->website; }
    public function get_tax_notice(): string { return $this->taxnotice; }
    public function get_footer(): string { return $this->footer; }
    public function get_market_country(): string { return $this->marketcountry; }
    public function get_resolution_rule(): string { return $this->resolutionrule; }
    public function get_resolved_at(): int { return $this->resolvedat; }
    public function get_currency(): string { return $this->currency; }
    public function get_provider(): string { return $this->provider; }

    /** Complete seller identity as it existed when the purchase was created. */
    public function to_array(): array {
        return [
            'version' => self::VERSION,
            'legal_entity_key' => $this->entitykey,
            'registered_country' => $this->registeredcountry,
            'name' => $this->name,
            'address' => $this->address,
            'legal' => $this->legal,
            'registration' => $this->registration,
            'tax_identifier' => $this->taxidentifier,
            'email' => $this->email,
            'phone' => $this->phone,
            'website' => $this->website,
            'tax_notice' => $this->taxnotice,
            'footer' => $this->footer,
            'market_country' => $this->marketcountry,
            'resolution_rule' => $this->resolutionrule,
            'resolved_at' => $this->resolvedat,
            'currency' => $this->currency,
            'provider' => $this->provider,
        ];
    }
}
