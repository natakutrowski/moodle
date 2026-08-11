<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\readmodel;

defined('MOODLE_INTERNAL') || die();

/** Detailed unified product read model, including translations and composition. */
final class CommerceCatalogProductDetails {
    public function __construct(
        private readonly CommerceCatalogProductSummary $summary,
        private readonly array $translations = [],
        private readonly array $components = [],
        private readonly array $legacyreferences = []
    ) {
    }
    public function get_summary(): CommerceCatalogProductSummary { return $this->summary; }
    public function get_translations(): array { return $this->translations; }
    public function get_components(): array { return $this->components; }
    public function get_legacy_references(): array { return $this->legacyreferences; }
}
