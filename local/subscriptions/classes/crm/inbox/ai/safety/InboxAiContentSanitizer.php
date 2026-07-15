<?php

namespace local_subscriptions\crm\inbox\ai\safety;

defined('MOODLE_INTERNAL') || die();

final class InboxAiContentSanitizer {

    private const MAX_CONTENT_LENGTH = 50000;

    public function sanitize(string $content): string {
        $content = html_to_text(
            clean_text(
                $content,
                FORMAT_HTML
            ),
            0,
            false
        );

        $content = preg_replace(
            '/\s+/u',
            ' ',
            $content
        ) ?? $content;

        $content = trim($content);

        if (
            \core_text::strlen($content) >
            self::MAX_CONTENT_LENGTH
        ) {
            $content = \core_text::substr(
                $content,
                0,
                self::MAX_CONTENT_LENGTH
            );
        }

        return $content;
    }
}