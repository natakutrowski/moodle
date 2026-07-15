<?php

namespace local_subscriptions\crm\inbox\ai\cache;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\ai\domain\InboxAiCapability;
use local_subscriptions\crm\inbox\ai\domain\InboxAiStatus;

final class InboxAiCachePolicy {

    private const SUCCESS_TTLS = [
        InboxAiCapability::LANGUAGE_DETECTION =>
            2592000,

        InboxAiCapability::URGENCY_CLASSIFICATION =>
            86400,

        InboxAiCapability::CATEGORIZATION =>
            604800,

        InboxAiCapability::SUMMARY =>
            604800,

        InboxAiCapability::TRANSLATION =>
            604800,

        InboxAiCapability::REPLY_SUGGESTION =>
            3600,

        InboxAiCapability::REQUEST_EXTRACTION =>
            604800,

        InboxAiCapability::CRM_RELEVANCE =>
            86400,
    ];

    public function expires_at(
        string $capability,
        string $status,
        ?int $generatedat = null
    ): int {
        $generatedat ??= time();

        $ttl = match ($status) {
            InboxAiStatus::SUCCESS,
            InboxAiStatus::PARTIAL =>
                self::SUCCESS_TTLS[$capability]
                    ?? 86400,

            InboxAiStatus::UNAVAILABLE =>
                600,

            InboxAiStatus::FAILED =>
                300,

            InboxAiStatus::BLOCKED =>
                3600,

            default =>
                300,
        };

        return $generatedat + $ttl;
    }
}