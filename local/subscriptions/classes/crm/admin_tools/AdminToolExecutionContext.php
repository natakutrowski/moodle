<?php

namespace local_subscriptions\crm\admin_tools;

defined('MOODLE_INTERNAL') || die();

/**
 * Immutable context passed to an administrative tool.
 */
final class AdminToolExecutionContext {

    public function __construct(
        public readonly int $actorid,
        public readonly string $requestid,
        public readonly array $parameters = []
    ) {
        if ($this->actorid <= 0) {
            throw new \InvalidArgumentException(
                'Administrative tool actor ID must be greater than zero.'
            );
        }

        if (trim($this->requestid) === '') {
            throw new \InvalidArgumentException(
                'Administrative tool request ID cannot be empty.'
            );
        }
    }
}