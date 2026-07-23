<?php

namespace local_subscriptions\commerce\payment\provider;

defined('MOODLE_INTERNAL') || die();

/**
 * Result of provider-specific validation before initialization.
 */
final class CommercePaymentProviderValidationResult {

    /** @var CommercePaymentProviderValidationIssue[] */
    private array $issues = [];

    public static function valid(): self {
        return new self();
    }

    public function add_issue(
        CommercePaymentProviderValidationIssue $issue
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
            new CommercePaymentProviderValidationIssue(
                $code,
                $message,
                CommercePaymentProviderValidationIssue::SEVERITY_ERROR,
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
            new CommercePaymentProviderValidationIssue(
                $code,
                $message,
                CommercePaymentProviderValidationIssue::SEVERITY_WARNING,
                $context
            )
        );
    }

    /**
     * @return CommercePaymentProviderValidationIssue[]
     */
    public function get_issues(): array {
        return $this->issues;
    }

    /**
     * @return CommercePaymentProviderValidationIssue[]
     */
    public function get_errors(): array {
        return array_values(
            array_filter(
                $this->issues,
                static fn(
                    CommercePaymentProviderValidationIssue $issue
                ): bool => $issue->is_error()
            )
        );
    }

    /**
     * @return CommercePaymentProviderValidationIssue[]
     */
    public function get_warnings(): array {
        return array_values(
            array_filter(
                $this->issues,
                static fn(
                    CommercePaymentProviderValidationIssue $issue
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

    public function to_array(): array {
        return [
            'valid' => $this->is_valid(),

            'issues' => array_map(
                static fn(
                    CommercePaymentProviderValidationIssue $issue
                ): array => $issue->to_array(),
                $this->issues
            ),
        ];
    }
}