<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\showroom\cms;

defined('MOODLE_INTERNAL') || die();

/**
 * Generic Showroom product-role configuration stored in productsjson.
 *
 * Roles are merchandising identifiers owned by the Showroom (for example
 * course, pdf or bundle), not Commerce product types. Keeping this boundary
 * generic lets new Showrooms introduce roles without a schema migration or a
 * hard-coded CMS whitelist.
 */
final class CommerceShowroomProductConfig {
    /**
     * Resolve a products JSON payload over an optional compatibility fallback.
     *
     * Explicit empty values remove a fallback relationship. Invalid role keys
     * and non-scalar values are ignored rather than becoming runtime input.
     *
     * @param array<string,string> $fallback
     * @return array<string,string>
     */
    public static function from_json(string $json, array $fallback = []): array {
        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            return $fallback;
        }

        $resolved = $fallback;
        foreach ($decoded as $rawrole => $rawsku) {
            $role = self::normalise_role((string)$rawrole);
            if ($role === null || (!is_string($rawsku) && !is_numeric($rawsku))) {
                continue;
            }

            $sku = strtoupper(trim((string)$rawsku));
            if ($sku === '') {
                unset($resolved[$role]);
                continue;
            }

            $resolved[$role] = clean_param($sku, PARAM_RAW_TRIMMED);
        }

        return $resolved;
    }

    /**
     * Merge role/SKU updates without dropping roles not owned by the caller.
     *
     * Empty SKU values are intentionally persisted because an autonomous CMS
     * record must be able to suppress a relationship inherited from the legacy
     * PHP Registry.
     *
     * @param array<string,string> $updates
     */
    public static function merge_into_json(string $json, array $updates): string {
        $decoded = json_decode(trim($json) === '' ? '{}' : $json, true);
        if (!is_array($decoded)) {
            throw new \invalid_parameter_exception('Invalid Showroom products JSON configuration.');
        }

        foreach ($updates as $rawrole => $rawsku) {
            $role = self::normalise_role((string)$rawrole);
            if ($role === null) {
                throw new \invalid_parameter_exception('Invalid Showroom product role.');
            }

            $decoded[$role] = strtoupper(trim((string)$rawsku));
        }

        return (string)json_encode(
            $decoded,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        );
    }


    /**
     * Replace the complete role/SKU mapping with validated role keys.
     *
     * Empty SKU values are preserved because they explicitly suppress a
     * relationship inherited from the legacy PHP Registry.
     *
     * @param array<string,string> $products
     */
    public static function to_json(array $products): string {
        $normalised = [];
        foreach ($products as $rawrole => $rawsku) {
            $role = self::normalise_role((string)$rawrole);
            if ($role === null) {
                throw new \invalid_parameter_exception('Invalid Showroom product role.');
            }
            $normalised[$role] = strtoupper(trim((string)$rawsku));
        }

        return (string)json_encode(
            $normalised,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        );
    }

    /** @return string[] */
    public static function roles(string $json, array $fallback = []): array {
        $decoded = json_decode($json, true);
        $roles = [];
        if (is_array($decoded)) {
            foreach ($decoded as $rawrole => $rawsku) {
                $role = self::normalise_role((string)$rawrole);
                if ($role !== null && (is_string($rawsku) || is_numeric($rawsku))) {
                    $roles[$role] = $role;
                }
            }
        }

        if ($roles === []) {
            foreach (array_keys($fallback) as $rawrole) {
                $role = self::normalise_role((string)$rawrole);
                if ($role !== null) {
                    $roles[$role] = $role;
                }
            }
        }
        return array_values($roles);
    }

    public static function normalise_role(string $role): ?string {
        $role = strtolower(trim($role));
        if ($role === '' || !preg_match('/^[a-z][a-z0-9_-]{0,63}$/', $role)) {
            return null;
        }
        return $role;
    }
}
