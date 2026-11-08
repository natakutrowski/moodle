<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\showroom\bridge;

defined('MOODLE_INTERNAL') || die();

/**
 * X2: Declarative editor for text fields in *trusted* Lovable design packages.
 *
 * A design owns its immutable RU/FR/EN defaults. The CMS stores only changed
 * values in settingsjson.designbridge.content. No JavaScript may be uploaded
 * through this editor, and no database schema change is required.
 */
final class CommerceShowroomBridgeContentEditor {
    public const LANGUAGES = ['ru', 'fr', 'en'];
    private const MAX_FIELDS = 800;
    private const MAX_FIELD_BYTES = 5000;
    private const MAX_OVERRIDE_BYTES = 65536;

    /**
     * @param array<string,array<string,mixed>> $overrides
     * @return array{fields:array<string,array<string,mixed>>,groups:array<string,array<string,mixed>>}
     */
    public static function model(string $designid, array $overrides = []): array {
        $catalogs = self::catalogs($designid);
        $fields = [];
        $groups = [];
        foreach ($catalogs['ru'] as $section => $sectionfields) {
            if (!self::valid_key($section) || !is_array($sectionfields) || array_is_list($sectionfields)) {
                throw new \invalid_parameter_exception('Invalid Bridge section in RU catalogue.');
            }
            foreach ($sectionfields as $key => $source) {
                if (!self::valid_key((string)$key)) {
                    throw new \invalid_parameter_exception('Invalid Bridge field key.');
                }
                if (is_string($source)) {
                    $paths = [[$section, $key]];
                } else if (is_array($source) && array_is_list($source)
                        && count($source) > 0 && count($source) <= 20
                        && count(array_filter($source, 'is_string')) === count($source)) {
                    $paths = [];
                    foreach ($source as $position => $value) {
                        $paths[] = [$section, $key, $position];
                    }
                } else {
                    throw new \invalid_parameter_exception('Bridge editor supports only text and lists of text.');
                }
                foreach ($paths as $path) {
                    $fieldid = implode('__', array_map('strval', $path));
                    if (isset($fields[$fieldid])) {
                        throw new \invalid_parameter_exception('Duplicate Bridge editor field id.');
                    }
                    if (count($fields) >= self::MAX_FIELDS) {
                        throw new \invalid_parameter_exception('Too many Bridge editor fields.');
                    }
                    $groupid = self::group_id($section, $key);
                    $groups[$groupid] ??= ['label' => self::group_label($groupid), 'fields' => []];
                    $groups[$groupid]['fields'][] = $fieldid;
                    $sourcevalue = self::path_value($catalogs['ru'], $path);
                    $field = [
                        'id' => $fieldid,
                        'path' => $path,
                        'group' => $groupid,
                        'label' => implode(' · ', array_map('strval', array_slice($path, 1))),
                        'reference' => $sourcevalue,
                        'default' => [],
                        'value' => [],
                        'modified' => [],
                    ];
                    foreach (self::LANGUAGES as $language) {
                        $fallback = self::path_value($catalogs[$language], $path);
                        $default = $fallback ?? $sourcevalue;
                        $override = self::path_value((array)($overrides[$language] ?? []), $path);
                        $field['default'][$language] = $default;
                        $field['value'][$language] = $override ?? $default;
                        $field['modified'][$language] = $override !== null;
                    }
                    $fields[$fieldid] = $field;
                }
            }
        }
        return ['fields' => $fields, 'groups' => $groups];
    }

    /**
     * Update precisely ONE language, keeping all other languages and unknown
     * legacy CMS override keys intact. Missing or unknown submitted fields are
     * rejected instead of silently destroying data (e.g. max_input_vars).
     *
     * @param array<string,mixed> $submitted Flat optional_param_array() data.
     * @param array<string,array<string,mixed>> $existing
     * @return array<string,array<string,mixed>>
     */
    public static function merge_language(
        string $designid,
        string $language,
        array $submitted,
        array $existing
    ): array {
        if (!in_array($language, self::LANGUAGES, true)) {
            throw new \invalid_parameter_exception('Unsupported Bridge editor language.');
        }
        $model = self::model($designid, $existing);
        $fields = $model['fields'];
        if (count($submitted) !== count($fields)) {
            throw new \invalid_parameter_exception('Incomplete Bridge editor submission: no content saved.');
        }
        foreach ($submitted as $key => $value) {
            if (!array_key_exists((string)$key, $fields) || !is_string($value)) {
                throw new \invalid_parameter_exception('Unexpected Bridge editor field.');
            }
            if (strlen($value) > self::MAX_FIELD_BYTES || str_contains($value, "\0")) {
                throw new \invalid_parameter_exception('Bridge text is too long or invalid.');
            }
        }
        $result = [];
        foreach (self::LANGUAGES as $lang) {
            $value = $existing[$lang] ?? [];
            if (!is_array($value) || (array_is_list($value) && $value !== [])) {
                throw new \invalid_parameter_exception('Invalid existing CMS Bridge overrides.');
            }
            $result[$lang] = $value;
        }

        // The facts array must remain a complete, ordered list for the legacy
        // BridgeSettings validator. Rebuild it atomically, not as a sparse array.
        $factpaths = [];
        foreach ($fields as $field) {
            $path = $field['path'];
            if (count($path) === 3 && $path[0] === 'hero' && $path[1] === 'facts') {
                $factpaths[] = $field;
            }
        }
        if ($factpaths !== []) {
            self::unset_path($result[$language], ['hero', 'facts']);
            $newfacts = [];
            $changed = false;
            foreach ($factpaths as $field) {
                $value = $submitted[$field['id']];
                $newfacts[] = $value;
                $changed = $changed || $value !== $field['default'][$language];
            }
            if ($changed) {
                self::set_path($result[$language], ['hero', 'facts'], $newfacts);
            }
        }
        foreach ($fields as $field) {
            $path = $field['path'];
            if (count($path) === 3 && $path[0] === 'hero' && $path[1] === 'facts') {
                continue;
            }
            $value = $submitted[$field['id']];
            if ($value === $field['default'][$language]) {
                self::unset_path($result[$language], $path);
            } else {
                self::set_path($result[$language], $path, $value);
            }
        }
        foreach ($result as $lang => $content) {
            $encoded = json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            if (strlen($encoded) > self::MAX_OVERRIDE_BYTES) {
                throw new \invalid_parameter_exception('Bridge overrides exceed CMS size limit: ' . $lang);
            }
        }
        return $result;
    }

    /** @return array<string,array<string,mixed>> */
    private static function catalogs(string $designid): array {
        $manifest = CommerceShowroomBridgePackageRegistry::require_manifest($designid);
        $base = CommerceShowroomBridgePackageRegistry::base_directory($designid);
        $catalogs = [];
        foreach (self::LANGUAGES as $lang) {
            $path = $lang === 'ru' ? (string)$manifest['content'] : 'content/' . $lang . '.json';
            $file = $base . '/' . $path;
            if (!is_readable($file) || filesize($file) > 512 * 1024) {
                throw new \invalid_parameter_exception('Missing/oversized Bridge language catalogue: ' . $lang);
            }
            $value = json_decode((string)file_get_contents($file), true, 32, JSON_THROW_ON_ERROR);
            if (!is_array($value) || (array_is_list($value) && $value !== [])) {
                throw new \invalid_parameter_exception('Invalid Bridge catalogue: ' . $lang);
            }
            $catalogs[$lang] = $value;
        }
        return $catalogs;
    }

    private static function valid_key(string $key): bool {
        return (bool)preg_match('/\A[a-z][a-z0-9_]*\z/i', $key) && !str_contains($key, '__');
    }

    /** @param array<string,mixed> $root @param array<int,string|int> $path */
    private static function path_value(array $root, array $path): ?string {
        $cursor = $root;
        foreach ($path as $key) {
            if (!is_array($cursor) || !array_key_exists($key, $cursor)) {
                return null;
            }
            $cursor = $cursor[$key];
        }
        return is_string($cursor) ? $cursor : null;
    }

    /** @param array<string,mixed> $root @param array<int,string|int> $path */
    private static function set_path(array &$root, array $path, mixed $value): void {
        $cursor =& $root;
        foreach ($path as $key) {
            if (!isset($cursor[$key]) || !is_array($cursor[$key])) {
                $cursor[$key] = [];
            }
            $cursor =& $cursor[$key];
        }
        // We set only the leaf, not a nested array outside the allowlist.
        // The loop above creates the leaf as an array; replace it here.
        $cursor = $value;
        unset($cursor);
    }

    /** @param array<string,mixed> $root @param array<int,string|int> $path */
    private static function unset_path(array &$root, array $path): void {
        $key = array_shift($path);
        if ($key === null || !array_key_exists($key, $root)) {
            return;
        }
        if ($path === []) {
            unset($root[$key]);
            return;
        }
        if (!is_array($root[$key])) {
            return;
        }
        self::unset_path($root[$key], $path);
        if ($root[$key] === []) {
            unset($root[$key]);
        }
    }

    private static function group_id(string $section, string $field): string {
        if ($section !== 'page') {
            return $section;
        }
        if (preg_match('/\A([a-z][a-z0-9]*)_/', $field, $matches)) {
            return 'page__' . $matches[1];
        }
        return 'page__divers';
    }

    private static function group_label(string $group): string {
        $labels = [
            'header' => 'Navigation', 'hero' => 'En-tête / Hero',
            'page__familiar' => 'Le problème', 'page__firstclue' => 'Première énigme',
            'page__bigpicture' => 'Le cœur de Paris', 'page__howitworks' => 'Fonctionnement du cours',
            'page__casesolved' => 'Avant / Après', 'page__forwhom' => 'Pour qui ?',
            'page__offers' => 'Offres', 'page__faq' => 'FAQ',
            'page__finale' => 'Final', 'page__commerce' => 'Commerce',
        ];
        return $labels[$group] ?? str_replace(['page__', '_'], ['', ' '], $group);
    }
}
