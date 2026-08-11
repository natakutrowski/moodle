<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\validation;

defined('MOODLE_INTERNAL') || die();

final class CommerceCatalogValidationResult {
    public function __construct(public readonly array $issues) {}
    public function is_valid(): bool { return !array_filter($this->issues, static fn($i): bool => $i->severity === 'error'); }
    public function has_issues(): bool { return $this->issues !== []; }
}
