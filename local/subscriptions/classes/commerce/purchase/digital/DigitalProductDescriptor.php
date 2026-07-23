<?php

namespace local_subscriptions\commerce\purchase\digital;

defined('MOODLE_INTERNAL') || die();

/**
 * Provider-independent description of a digital product.
 */
final class DigitalProductDescriptor {

    public function __construct(
        private readonly int $id,
        private readonly string $name,
        private readonly ?string $slug,
        private readonly bool $active,
        private readonly ?string $filename,
        private readonly array $metadata = []
    ) {
        if ($id <= 0) {
            throw new \coding_exception(
                'A digital product identifier must be positive.'
            );
        }

        if (trim($name) === '') {
            throw new \coding_exception(
                'A digital product name cannot be empty.'
            );
        }
    }

    public function get_id(): int {
        return $this->id;
    }

    public function get_name(): string {
        return trim($this->name);
    }

    public function get_slug(): ?string {
        return $this->normalise_nullable_string(
            $this->slug
        );
    }

    public function is_active(): bool {
        return $this->active;
    }

    public function get_filename(): ?string {
        return $this->normalise_nullable_string(
            $this->filename
        );
    }

    public function get_metadata(): array {
        return $this->metadata;
    }

    public function get_metadata_value(
        string $key,
        mixed $default = null
    ): mixed {
        return $this->metadata[$key]
            ?? $default;
    }

    private function normalise_nullable_string(
        ?string $value
    ): ?string {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value !== ''
            ? $value
            : null;
    }
}