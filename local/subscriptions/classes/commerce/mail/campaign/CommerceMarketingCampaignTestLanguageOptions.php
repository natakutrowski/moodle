<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail\campaign;

defined('MOODLE_INTERNAL') || die();

/** Languages with complete, frozen content for a marketing campaign test email. */
final class CommerceMarketingCampaignTestLanguageOptions {
    /** @param array<string,\stdClass> $contents
     *  @return array<string,string>
     */
    public static function available(array $contents): array {
        $options = [];
        foreach (['fr' => 'FR', 'ru' => 'RU', 'en' => 'EN'] as $language => $label) {
            $content = CommerceMarketingCampaignContentPolicy::select($contents, $language);
            // select() may fall back to French for production delivery. The test
            // selector must show only translations that really exist in that language.
            if ($content && strtolower((string)$content->language) === $language) {
                $options[$language] = $label;
            }
        }
        return $options;
    }

    /** @param array<string,string> $options */
    public static function default_language(array $options): string {
        return (string)(array_key_first($options) ?? 'fr');
    }
}
