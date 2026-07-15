<?php

namespace local_subscriptions\crm\inbox\ai\prompts;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\ai\domain\InboxAiCapability;

final class InboxAiPromptVersionRegistry {

    private const VERSIONS = [
        InboxAiCapability::LANGUAGE_DETECTION =>
            'language-v1',

        InboxAiCapability::URGENCY_CLASSIFICATION =>
            'urgency-v1',

        InboxAiCapability::CATEGORIZATION =>
            'category-v1',

        InboxAiCapability::SUMMARY =>
            'summary-v1',

        InboxAiCapability::TRANSLATION =>
            'translation-v1',

        InboxAiCapability::REPLY_SUGGESTION =>
            'reply-v1',

        InboxAiCapability::REQUEST_EXTRACTION =>
            'request-extraction-v1',

        InboxAiCapability::CRM_RELEVANCE =>
            'crm-relevance-v1',
    ];

    public function get(
        string $capability
    ): string {
        if (
            !InboxAiCapability::is_valid(
                $capability
            )
        ) {
            throw new \invalid_parameter_exception(
                'Invalid Inbox AI capability.'
            );
        }

        return self::VERSIONS[$capability]
            ?? 'unknown-v1';
    }
}