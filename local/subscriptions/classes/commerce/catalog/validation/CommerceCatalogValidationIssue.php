<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\validation;

defined('MOODLE_INTERNAL') || die();

final class CommerceCatalogValidationIssue {
    public function __construct(public readonly string $severity, public readonly string $code, public readonly string $message) {}
}
