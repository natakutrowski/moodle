<?php

namespace local_subscriptions\commerce\purchase\domain;

defined('MOODLE_INTERNAL') || die();

/**
 * Single issue detected while validating a Commerce purchase.
 */
final class CommercePurchaseValidationIssue {

    /**
     * @param string $code Stable machine-readable issue code.
     * @param string $message Human-readable issue message.
     * @param array $context Additional diagnostic context.
     */
    public function __construct(
        private readonly string $code,
        private readonly string $message,
        private readonly array $context = []
    ) {
        if (trim($code) === '') {
            throw new \coding_exception(
                'A Commerce purchase validation issue code cannot be empty.'
            );
        }

        if (trim($message) === '') {
            throw new \coding_exception(
                'A Commerce purchase validation issue message cannot be empty.'
            );
        }
    }

    /**
     * Return the issue code.
     *
     * @return string
     */
    public function get_code(): string {
        return trim(
            $this->code
        );
    }

    /**
     * Return the issue message.
     *
     * @return string
     */
    public function get_message(): string {
        return trim(
            $this->message
        );
    }

    /**
     * Return the issue context.
     *
     * @return array
     */
    public function get_context(): array {
        return $this->context;
    }

    /**
     * Export the issue.
     *
     * @return array
     */
    public function to_array(): array {
        return [
            'code' =>
                $this->get_code(),

            'message' =>
                $this->get_message(),

            'context' =>
                $this->get_context(),
        ];
    }
}