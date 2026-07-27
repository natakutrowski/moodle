<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\service;

defined('MOODLE_INTERNAL') || die();

/** Resolves editable catalogue languages from installed Moodle language packs. */
final class CommerceCatalogLocaleService {
    /** @return array<string, string> language code => display name */
    public function get_languages(): array {
        $translations = get_string_manager()->get_list_of_translations(true);
        $languages = [];

        foreach ($translations as $code => $name) {
            $code = strtolower(trim((string) $code));
            if ($code !== '' && preg_match('/^[a-z]{2,3}(?:_[a-z0-9]{2,8})?$/', $code)) {
                $languages[$code] = (string) $name;
            }
        }

        if ($languages === []) {
            $languages[current_language()] = current_language();
        }

        ksort($languages, SORT_NATURAL | SORT_FLAG_CASE);
        return $languages;
    }
}
