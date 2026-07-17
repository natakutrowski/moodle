<?php

namespace local_subscriptions\crm\assistant\ai\dto;

defined('MOODLE_INTERNAL') || die();

/**
 * Immutable administrator question submitted to the CRM Assistant.
 */
final class CrmAssistantQuestion {

    public const SCOPE_GLOBAL = 'global';
    public const SCOPE_USER = 'user';
    public const SCOPE_RECOMMENDATION = 'recommendation';

    public function __construct(
        public readonly string $question,
        public readonly string $language,
        public readonly string $scope = self::SCOPE_GLOBAL,
        public readonly ?int $userid = null,
        public readonly ?int $recommendationid = null
    ) {
        $question = trim($this->question);

        if ($question === '') {
            throw new \InvalidArgumentException(
                'CRM Assistant question cannot be empty.'
            );
        }

        if (\core_text::strlen($question) > 1000) {
            throw new \InvalidArgumentException(
                'CRM Assistant question is too long.'
            );
        }

        if (
            !in_array(
                $this->scope,
                self::scopes(),
                true
            )
        ) {
            throw new \InvalidArgumentException(
                'Invalid CRM Assistant question scope.'
            );
        }

        if (
            $this->userid !== null &&
            $this->userid <= 0
        ) {
            throw new \InvalidArgumentException(
                'CRM Assistant user ID must be greater than zero.'
            );
        }

        if (
            $this->recommendationid !== null &&
            $this->recommendationid <= 0
        ) {
            throw new \InvalidArgumentException(
                'CRM Assistant recommendation ID must be greater than zero.'
            );
        }

        if (
            $this->scope === self::SCOPE_USER &&
            $this->userid === null
        ) {
            throw new \InvalidArgumentException(
                'User scope requires a user ID.'
            );
        }

        if (
            $this->scope === self::SCOPE_RECOMMENDATION &&
            $this->recommendationid === null
        ) {
            throw new \InvalidArgumentException(
                'Recommendation scope requires a recommendation ID.'
            );
        }
    }

    /**
     * @return string[]
     */
    public static function scopes(): array {
        return [
            self::SCOPE_GLOBAL,
            self::SCOPE_USER,
            self::SCOPE_RECOMMENDATION,
        ];
    }
}