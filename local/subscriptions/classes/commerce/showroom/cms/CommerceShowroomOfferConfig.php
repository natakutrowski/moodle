<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\showroom\cms;

defined('MOODLE_INTERNAL') || die();

/** Per-offer presentation options stored in Showroom settingsjson. */
final class CommerceShowroomOfferConfig {
    public const ROLES = ['course', 'pdf', 'bundle'];

    /** @return array<string,array{detailsenabled:bool}> */
    public static function from_settings_json(string $json): array {
        $settings = json_decode(trim($json) === '' ? '{}' : $json, true);
        $settings = is_array($settings) ? $settings : [];
        $offers = isset($settings['offers']) && is_array($settings['offers']) ? $settings['offers'] : [];

        $result = [];
        foreach (self::ROLES as $role) {
            $row = isset($offers[$role]) && is_array($offers[$role]) ? $offers[$role] : [];
            $result[$role] = [
                // Backward compatible: existing Showrooms keep the current CTA until explicitly disabled.
                'detailsenabled' => !array_key_exists('detailsenabled', $row) || !empty($row['detailsenabled']),
            ];
        }
        return $result;
    }

    /** @param array<string,array{detailsenabled:bool}> $offers */
    public static function merge_into_settings_json(string $settingsjson, array $offers): string {
        $settings = json_decode(trim($settingsjson) === '' ? '{}' : $settingsjson, true);
        if (!is_array($settings)) {
            throw new \invalid_parameter_exception('Invalid JSON configuration.');
        }

        $normalised = [];
        foreach (self::ROLES as $role) {
            $normalised[$role] = [
                'detailsenabled' => !empty($offers[$role]['detailsenabled']),
            ];
        }
        $settings['offers'] = $normalised;

        return (string)json_encode(
            $settings,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        );
    }
}
