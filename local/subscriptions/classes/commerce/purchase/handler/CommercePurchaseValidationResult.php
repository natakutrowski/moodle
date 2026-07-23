<?php

namespace local_subscriptions\commerce\purchase\handler;

defined('MOODLE_INTERNAL') || die();

/**
 * Result of a PurchaseHandler business validation.
 */
final class CommercePurchaseValidationResult {

    /** @var CommercePurchaseValidationIssue[] */
    private array $issues = [];

    public static function valid(): self {
        return new self();
    }

    public function add_issue(
        CommercePurchaseValidationIssue $issue
    ): self {
        $this->issues[] = $issue;

        return $this;
    }

    public function add_error(
        string $code,
        string $message,
        array $context = []
    ): self {
        return $this->add_issue(
            new CommercePurchaseValidationIssue(
                $code,
                $message,
                CommercePurchaseValidationIssue::SEVERITY_ERROR,
                $context
            )
        );
    }

    public function add_warning(
        string $code,
        string $message,
        array $context = []
    ): self {
        return $this->add_issue(
            new CommercePurchaseValidationIssue(
                $code,
                $message,
                CommercePurchaseValidationIssue::SEVERITY_WARNING,
                $context
            )
        );
    }

    /**
     * @return CommercePurchaseValidationIssue[]
     */
    public function get_issues(): array {
        return $this->issues;
    }

    /**
     * @return CommercePurchaseValidationIssue[]
     */
    public function get_errors(): array {
        return array_values(
            array_filter(
                $this->issues,
                static fn(
                    CommercePurchaseValidationIssue $issue
                ): bool => $issue->is_error()
            )
        );
    }

    /**
     * @return CommercePurchaseValidationIssue[]
     */
    public function get_warnings(): array {
        return array_values(
            array_filter(
                $this->issues,
                static fn(
                    CommercePurchaseValidationIssue $issue
                ): bool => $issue->is_warning()
            )
        );
    }

    public function is_valid(): bool {
        return $this->get_errors() === [];
    }

    public function has_warnings(): bool {
        return $this->get_warnings() !== [];
    }

    public function merge(
        self $other
    ): self {
        foreach ($other->get_issues() as $issue) {
            $this->add_issue($issue);
        }

        return $this;
    }

    public function to_array(): array {
        return [
            'valid' => $this->is_valid(),
            'issues' => array_map(
                static fn(
                    CommercePurchaseValidationIssue $issue
                ): array => $issue->to_array(),
                $this->issues
            ),
        ];
    }
}