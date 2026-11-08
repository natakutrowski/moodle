<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\showroom\bridge;

defined('MOODLE_INTERNAL') || die();

/** Language catalogues from a trusted, Git-deployed design package. */
final class CommerceShowroomBridgeTextCatalog {
    /** @param array<string,mixed> $overrides @return array<string,mixed> */
    public static function load(string $base, string $canonical, string $language, array $overrides): array {
        $ru = self::load_file($base . '/' . $canonical);
        $localised = in_array($language, ['fr', 'en'], true)
            ? self::load_file($base . '/content/' . $language . '.json', false)
            : [];
        return array_replace_recursive($ru, $localised, $overrides);
    }

    /** @return array<string,mixed> */
    private static function load_file(string $file, bool $required = true): array {
        if (!is_file($file) && !$required) {
            return [];
        }
        $data = json_decode((string)file_get_contents($file), true, 32, JSON_THROW_ON_ERROR);
        if (!is_array($data) || (array_is_list($data) && $data !== [])) {
            throw new \invalid_parameter_exception('Invalid bridge translation file.');
        }
        return $data;
    }
}
