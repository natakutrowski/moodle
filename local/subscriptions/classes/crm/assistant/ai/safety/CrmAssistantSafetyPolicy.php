<?php

namespace local_subscriptions\crm\assistant\ai\safety;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\assistant\ai\dto\CrmAssistantQuestion;

/**
 * Deterministic safety policy applied before any AI request.
 */
final class CrmAssistantSafetyPolicy {

    private const FORBIDDEN_PATTERNS = [
        '/\bapi[\s_-]?key\b/iu',
        '/\bpassword\b/iu',
        '/\bmot de passe\b/iu',
        '/\bпарол[ья]\b/iu',
        '/\bsecret\b/iu',
        '/\btoken\b/iu',
        '/\bexecute\s+sql\b/iu',
        '/\bdrop\s+table\b/iu',
    ];

    public function validate(
        CrmAssistantQuestion $question
    ): void {
        foreach (
            self::FORBIDDEN_PATTERNS
            as $pattern
        ) {
            if (
                preg_match(
                    $pattern,
                    $question->question
                ) === 1
            ) {
                throw new \DomainException(
                    'crm_assistant_question_rejected'
                );
            }
        }
    }

    public function sanitize_question(
        string $question
    ): string {
        $question = strip_tags($question);

        $question = preg_replace(
            '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u',
            '',
            $question
        ) ?? '';

        return trim($question);
    }
}