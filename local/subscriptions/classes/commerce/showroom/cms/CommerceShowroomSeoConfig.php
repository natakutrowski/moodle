<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\showroom\cms;

defined('MOODLE_INTERNAL') || die();

/**
 * Multilingual SEO payload stored inside Showroom settingsjson.
 */
final class CommerceShowroomSeoConfig {
    public const LANGUAGES = ['fr', 'en', 'ru'];

    /**
     * @return array<string,array<string,string>>
     */
    public static function from_settings_json(string $json): array {
        $settings = json_decode($json, true);
        if (!is_array($settings)) {
            return self::empty();
        }

        $seo = isset($settings['seo']) && is_array($settings['seo'])
            ? $settings['seo']
            : [];

        $result = self::empty();
        foreach (self::LANGUAGES as $language) {
            $source = isset($seo[$language]) && is_array($seo[$language])
                ? $seo[$language]
                : [];
            foreach (array_keys($result[$language]) as $field) {
                $result[$language][$field] = trim((string)($source[$field] ?? ''));
            }
        }
        return $result;
    }

    /**
     * Merge SEO values into arbitrary existing global settings.
     *
     * @param array<string,array<string,string>> $seo
     */
    public static function merge_into_settings_json(
        string $settingsjson,
        array $seo
    ): string {
        $settings = json_decode(trim($settingsjson) === '' ? '{}' : $settingsjson, true);
        if (!is_array($settings)) {
            throw new \invalid_parameter_exception('Invalid JSON configuration.');
        }

        $normalised = self::empty();
        foreach (self::LANGUAGES as $language) {
            foreach (array_keys($normalised[$language]) as $field) {
                $normalised[$language][$field] = trim(
                    (string)($seo[$language][$field] ?? '')
                );
            }
        }

        $settings['seo'] = $normalised;

        return (string)json_encode(
            $settings,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        );
    }

    /**
     * @return array<string,array<string,string>>
     */
    public static function empty(): array {
        $row = [
            'title' => '',
            'description' => '',
            'socialtitle' => '',
            'socialdescription' => '',
            'keywords' => '',
        ];

        return [
            'fr' => $row,
            'en' => $row,
            'ru' => $row,
        ];
    }
}
