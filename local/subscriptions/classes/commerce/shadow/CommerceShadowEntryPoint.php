<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\shadow;

defined('MOODLE_INTERNAL') || die();

/** Immutable description of one current Legacy fulfillment entry point. */
final class CommerceShadowEntryPoint {
    public function __construct(
        private readonly string $key,
        private readonly string $label,
        private readonly string $source,
        private readonly string $relativepath,
        private readonly string $family,
        private readonly bool $critical = true
    ) {
        if (!preg_match('/^[a-z][a-z0-9_]{2,63}$/', $this->key)) {
            throw new \coding_exception('Invalid Commerce Shadow entry-point key.');
        }
        if (trim($this->label) === '' || trim($this->relativepath) === '') {
            throw new \coding_exception('A Commerce Shadow entry point requires a label and path.');
        }
        if (!CommerceShadowSource::is_valid($this->source)) {
            throw new \coding_exception('Invalid Commerce Shadow entry-point source.');
        }
        if (!in_array($this->family, ['subscription', 'digital', 'both'], true)) {
            throw new \coding_exception('Invalid Commerce Shadow entry-point family.');
        }
    }

    public function get_key(): string { return $this->key; }
    public function get_label(): string { return $this->label; }
    public function get_source(): string { return $this->source; }
    public function get_relative_path(): string { return $this->relativepath; }
    public function get_family(): string { return $this->family; }
    public function is_critical(): bool { return $this->critical; }

    public function to_array(): array {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'source' => $this->source,
            'path' => $this->relativepath,
            'family' => $this->family,
            'critical' => $this->critical,
        ];
    }
}
