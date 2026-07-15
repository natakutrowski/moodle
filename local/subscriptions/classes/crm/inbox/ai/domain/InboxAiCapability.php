<?php

namespace local_subscriptions\crm\inbox\ai\domain;

defined('MOODLE_INTERNAL') || die();

final class InboxAiCapability {

    public const LANGUAGE_DETECTION =
        'language_detection';

    public const URGENCY_CLASSIFICATION =
        'urgency_classification';

    public const CATEGORIZATION =
        'categorization';

    public const SUMMARY =
        'summary';

    public const TRANSLATION =
        'translation';

    public const REPLY_SUGGESTION =
        'reply_suggestion';

    public const REQUEST_EXTRACTION =
        'request_extraction';

    public const CRM_RELEVANCE =
        'crm_relevance';

    public static function values(): array {
        return [
            self::LANGUAGE_DETECTION,
            self::URGENCY_CLASSIFICATION,
            self::CATEGORIZATION,
            self::SUMMARY,
            self::TRANSLATION,
            self::REPLY_SUGGESTION,
            self::REQUEST_EXTRACTION,
            self::CRM_RELEVANCE,
        ];
    }

    public static function is_valid(
        string $capability
    ): bool {
        return in_array(
            $capability,
            self::values(),
            true
        );
    }
}