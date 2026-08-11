<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\status;

defined('MOODLE_INTERNAL') || die();

/** Normalised independent status dimensions for one catalogue product. */
final class CommerceCatalogStatusSnapshot {
    public function __construct(
        private readonly string $editorial,
        private readonly string $visibility,
        private readonly string $availability,
        private readonly string $technical
    ) {
        CommerceCatalogEditorialStatus::require_valid($editorial);
        CommerceCatalogVisibility::require_valid($visibility);
        if (!in_array($availability, CommerceCatalogAvailability::all(), true)) {
            throw new \coding_exception('Unsupported catalogue availability: ' . $availability);
        }
        if (!in_array($technical, CommerceCatalogTechnicalState::all(), true)) {
            throw new \coding_exception('Unsupported catalogue technical state: ' . $technical);
        }
    }

    public function get_editorial(): string { return $this->editorial; }
    public function get_visibility(): string { return $this->visibility; }
    public function get_availability(): string { return $this->availability; }
    public function get_technical(): string { return $this->technical; }
    public function to_array(): array {
        return ['editorial' => $this->editorial, 'visibility' => $this->visibility,
            'availability' => $this->availability, 'technical' => $this->technical];
    }
}
