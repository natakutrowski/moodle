<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\migration;

defined('MOODLE_INTERNAL') || die();

/** Registry resolving a Legacy source by Commerce family. */
final class CommerceLegacyPurchaseSourceRegistry {
    /** @var array<string, CommerceLegacyPurchaseSource> */
    private array $sources = [];

    /** @param CommerceLegacyPurchaseSource[] $sources */
    public function __construct(array $sources) {
        foreach ($sources as $source) {
            if (!$source instanceof CommerceLegacyPurchaseSource) {
                throw new \coding_exception('Invalid Commerce Legacy purchase source.');
            }
            $family = $source->get_family();
            if (isset($this->sources[$family])) {
                throw new \coding_exception('Duplicate Commerce Legacy purchase source family: ' . $family);
            }
            $this->sources[$family] = $source;
        }
    }

    public function get(string $family): CommerceLegacyPurchaseSource {
        $family = strtolower(trim($family));
        if (!isset($this->sources[$family])) {
            throw new \coding_exception('Unsupported Commerce Legacy purchase family: ' . $family);
        }
        return $this->sources[$family];
    }

    /** @return string[] */
    public function get_families(): array { return array_keys($this->sources); }
}