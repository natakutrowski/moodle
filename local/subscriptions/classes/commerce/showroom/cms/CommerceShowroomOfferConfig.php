<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\showroom\cms;

defined('MOODLE_INTERNAL') || die();

/** Per-offer presentation options stored in Showroom settingsjson. */
final class CommerceShowroomOfferConfig {
    /** Legacy roles kept as defaults for existing Showrooms and admin controls. */
    public const ROLES = ['course', 'pdf', 'bundle'];

    public const DONATION_MODE_NONE = 'none';
    public const DONATION_MODE_OPTIONAL = 'optional';
    public const DONATION_MODE_REQUIRED = 'required';

    /** @return array<string,array{detailsenabled:bool,donationmode:string}> */
    public static function from_settings_json(string $json): array {
        $settings = json_decode(trim($json) === '' ? '{}' : $json, true);
        $settings = is_array($settings) ? $settings : [];
        $offers = isset($settings['offers']) && is_array($settings['offers']) ? $settings['offers'] : [];

        $roles = self::ROLES;
        foreach (array_keys($offers) as $role) {
            $role = self::normalise_role((string)$role);
            if ($role !== null && !in_array($role, $roles, true)) {
                $roles[] = $role;
            }
        }

        $result = [];
        foreach ($roles as $role) {
            $row = isset($offers[$role]) && is_array($offers[$role]) ? $offers[$role] : [];
            $result[$role] = [
                // Backward compatible: existing Showrooms keep the current CTA until explicitly disabled.
                'detailsenabled' => !array_key_exists('detailsenabled', $row) || !empty($row['detailsenabled']),
                // B.4 shipped the companion as optional for every normal offer. Preserve that behaviour
                // until an administrator explicitly selects "none" or "required" for a role.
                'donationmode' => self::normalise_donation_mode((string)($row['donationmode'] ?? self::DONATION_MODE_OPTIONAL)),
            ];
        }
        return $result;
    }

    /** @param array<string,array{detailsenabled:bool,donationmode?:string}> $offers */
    public static function merge_into_settings_json(string $settingsjson, array $offers): string {
        $settings = json_decode(trim($settingsjson) === '' ? '{}' : $settingsjson, true);
        if (!is_array($settings)) {
            throw new \invalid_parameter_exception('Invalid JSON configuration.');
        }

        $normalised = isset($settings['offers']) && is_array($settings['offers'])
            ? $settings['offers']
            : [];
        foreach ($offers as $rawrole => $config) {
            $role = self::normalise_role((string)$rawrole);
            if ($role === null) {
                throw new \invalid_parameter_exception('Invalid Showroom offer role.');
            }
            $existing = isset($normalised[$role]) && is_array($normalised[$role])
                ? $normalised[$role]
                : [];
            $normalised[$role] = array_replace($existing, [
                'detailsenabled' => !empty($config['detailsenabled']),
                'donationmode' => self::normalise_donation_mode((string)($config['donationmode'] ?? self::DONATION_MODE_OPTIONAL)),
            ]);
        }
        $settings['offers'] = $normalised;

        return (string)json_encode(
            $settings,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        );
    }

    public static function normalise_donation_mode(string $mode): string {
        $mode = strtolower(trim($mode));
        return in_array($mode, [
            self::DONATION_MODE_NONE,
            self::DONATION_MODE_OPTIONAL,
            self::DONATION_MODE_REQUIRED,
        ], true) ? $mode : self::DONATION_MODE_OPTIONAL;
    }

    private static function normalise_role(string $role): ?string {
        $role = strtolower(trim($role));
        if ($role === '' || !preg_match('/^[a-z][a-z0-9_-]{0,63}$/', $role)) {
            return null;
        }
        return $role;
    }
}
