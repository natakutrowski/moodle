<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\legal\entity;

defined('MOODLE_INTERNAL') || die();

/** Immutable configured legal entity used by Commerce. */
final class CommerceLegalEntity {
    public function __construct(
        private readonly string $key,
        private readonly string $registeredcountry,
        private readonly string $name = '',
        private readonly string $address = '',
        private readonly string $legal = '',
        private readonly string $registration = '',
        private readonly string $taxidentifier = '',
        private readonly string $email = '',
        private readonly string $phone = '',
        private readonly string $website = '',
        private readonly string $taxnotice = '',
        private readonly string $footer = ''
    ) {
        if (!preg_match('/^[a-z][a-z0-9_]{1,63}$/', $this->key)) {
            throw new \coding_exception('Invalid Commerce legal entity key: ' . $this->key);
        }
        if (!preg_match('/^[A-Z]{2}$/', $this->registeredcountry)) {
            throw new \coding_exception('Invalid Commerce legal entity registered country: ' . $this->registeredcountry);
        }
    }

    public function get_key(): string { return $this->key; }
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

    /** Stable configuration representation; no payment provider or currency belongs here. */
    public function to_array(): array {
        return [
            'key' => $this->key,
            'registeredcountry' => $this->registeredcountry,
            'name' => $this->name,
            'address' => $this->address,
            'legal' => $this->legal,
            'registration' => $this->registration,
            'taxidentifier' => $this->taxidentifier,
            'email' => $this->email,
            'phone' => $this->phone,
            'website' => $this->website,
            'taxnotice' => $this->taxnotice,
            'footer' => $this->footer,
        ];
    }
}
