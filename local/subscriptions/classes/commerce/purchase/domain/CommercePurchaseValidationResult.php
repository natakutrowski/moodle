<?php

namespace local_subscriptions\commerce\purchase\domain;

defined('MOODLE_INTERNAL') || die();

/**
 * Result of a Commerce purchase validation.
 */
final class CommercePurchaseValidationResult {

    /**
     * Validation issues.
     *
     * @var CommercePurchaseValidationIssue[]
     */
    private array $issues = [];

    /**
     * Create a valid empty result.
     *
     * @return self
     */
    public static function valid(): self {
        return new self();
    }

    /**
     * Add a validation issue.
     *
     * @param string $code Stable issue code.
     * @param string $message Human-readable message.
     * @param array $context Diagnostic context.
     * @return self
     */
    public function add(
        string $code,
        string $message,
        array $context = []
    ): self {
        return $this->add_issue(
            new CommercePurchaseValidationIssue(
                $code,
                $message,
                $context
            )
        );
    }

    /**
     * Add an existing validation issue.
     *
     * @param CommercePurchaseValidationIssue $issue Issue.
     * @return self
     */
    public function add_issue(
        CommercePurchaseValidationIssue $issue
    ): self {
        $this->issues[] =
            $issue;

        return $this;
    }

    /**
     * Merge another validation result.
     *
     * @param self $result Validation result.
     * @return self
     */
    public function merge(
        self $result
    ): self {
        foreach (
            $result->get_issues()
            as $issue
        ) {
            $this->add_issue(
                $issue
            );
        }

        return $this;
    }

    /**
     * Whether validation succeeded.
     *
     * @return bool
     */
    public function is_valid(): bool {
        return $this->issues === [];
    }

    /**
     * Whether validation found issues.
     *
     * @return bool
     */
    public function has_issues(): bool {
        return !$this->is_valid();
    }

    /**
     * Return all validation issues.
     *
     * @return CommercePurchaseValidationIssue[]
     */
    public function get_issues(): array {
        return $this->issues;
    }

    /**
     * Return the number of issues.
     *
     * @return int
     */
    public function count(): int {
        return count(
            $this->issues
        );
    }

    /**
     * Whether an issue with the given code exists.
     *
     * @param string $code Issue code.
     * @return bool
     */
    public function has_code(
        string $code
    ): bool {
        foreach ($this->issues as $issue) {
            if (
                $issue->get_code()
                === $code
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Export all validation issues.
     *
     * @return array
     */
    public function to_array(): array {
        return array_map(
            static fn(
                CommercePurchaseValidationIssue $issue
            ): array =>
                $issue->to_array(),
            $this->issues
        );
    }
}