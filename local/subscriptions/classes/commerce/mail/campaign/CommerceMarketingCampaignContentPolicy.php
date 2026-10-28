<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail\campaign;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\mail\builder\CommerceMailBuilder;

/** Shared language/content rules for scheduling, queuing, rendering and tests. */
final class CommerceMarketingCampaignContentPolicy {
    /**
     * Select the requested complete translation, otherwise French if available.
     * Never silently send Russian (or English) to a recipient expecting French.
     *
     * @param array<string,\stdClass> $contents Frozen campaign translations keyed by language.
     */
    public static function select(array $contents, string $language): ?\stdClass {
        $language = strtolower(trim($language));
        foreach (array_unique([$language, 'fr']) as $candidate) {
            $content = $contents[$candidate] ?? null;
            if ($content && trim((string)$content->subject) !== ''
                && !CommerceMailBuilder::editorial_empty((string)$content->bodyhtml)) {
                return $content;
            }
        }
        return null;
    }

    /**
     * @param array<string,\stdClass> $contents
     * @param \stdClass[] $recipients
     * @return array<string,int> Unsupported language => recipient count.
     */
    public static function missing_languages(array $contents, array $recipients): array {
        $missing = [];
        foreach ($recipients as $recipient) {
            if ((string)($recipient->status ?? '') === 'cancelled') {
                continue;
            }
            $language = strtolower(trim((string)$recipient->language));
            if (self::select($contents, $language) === null) {
                $missing[$language] = ($missing[$language] ?? 0) + 1;
            }
        }
        ksort($missing);
        return $missing;
    }

    /** @param array<string,int> $missing */
    public static function format_missing(array $missing): string {
        $parts = [];
        foreach ($missing as $language => $count) {
            $parts[] = $language . ' (' . $count . ')';
        }
        return implode(', ', $parts);
    }
}
