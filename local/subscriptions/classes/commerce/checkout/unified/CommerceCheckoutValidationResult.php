<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\checkout\unified;

defined('MOODLE_INTERNAL') || die();

/** Immutable checkout validation result. */
final class CommerceCheckoutValidationResult {
    /** @param CommerceCheckoutValidationIssue[] $issues */
    public function __construct(private readonly array $issues = []) {
        foreach ($issues as $issue) {
            if (!$issue instanceof CommerceCheckoutValidationIssue) {
                throw new \coding_exception('Invalid checkout validation issue collection.');
            }
        }
    }

    public static function valid(): self { return new self(); }
    public function is_valid(): bool { return $this->issues === []; }
    /** @return CommerceCheckoutValidationIssue[] */
    public function get_issues(): array { return $this->issues; }
}
