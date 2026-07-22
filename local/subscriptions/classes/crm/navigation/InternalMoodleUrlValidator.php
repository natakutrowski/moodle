<?php

namespace local_subscriptions\crm\navigation;

defined('MOODLE_INTERNAL') || die();

use moodle_url;

/**
 * Validates and normalises URLs that must remain inside the current
 * Moodle installation.
 */
final class InternalMoodleUrlValidator {

    /**
     * Converts a safe internal URL into a moodle_url.
     *
     * Accepted values:
     * - absolute URLs belonging to the current Moodle wwwroot;
     * - root-relative paths beginning with one slash.
     *
     * Rejected values:
     * - external URLs;
     * - protocol-relative URLs;
     * - javascript/data URLs;
     * - malformed or empty values.
     */
    public static function normalise(
        string $url
    ): ?moodle_url {
        global $CFG;

        $url = trim($url);

        if ($url === '') {
            return null;
        }

        $wwwroot = rtrim(
            trim((string)$CFG->wwwroot),
            '/'
        );

        if ($wwwroot === '') {
            return null;
        }

        /*
         * Accept a root-relative Moodle path.
         *
         * "//example.com" is intentionally rejected because it is a
         * protocol-relative external URL.
         */
        if (
            str_starts_with($url, '/')
            && !str_starts_with($url, '//')
        ) {
            return self::create_url(
                $url
            );
        }

        /*
         * Absolute URL must belong to the exact current Moodle installation.
         *
         * The boundary checks prevent:
         * https://moodle.example.com.evil.example/
         */
        if (
            $url === $wwwroot
            || str_starts_with(
                $url,
                $wwwroot . '/'
            )
            || str_starts_with(
                $url,
                $wwwroot . '?'
            )
            || str_starts_with(
                $url,
                $wwwroot . '#'
            )
        ) {
            return self::create_url(
                $url
            );
        }

        return null;
    }

    /**
     * Returns whether a URL belongs to the current Moodle installation.
     */
    public static function is_internal(
        string $url
    ): bool {
        return self::normalise($url) !== null;
    }

    /**
     * Returns a normalised absolute URL string or null when invalid.
     */
    public static function normalise_to_string(
        string $url
    ): ?string {
        $normalised = self::normalise(
            $url
        );

        return $normalised?->out(false);
    }

    /**
     * Creates a moodle_url while treating malformed input as invalid.
     */
    private static function create_url(
        string $url
    ): ?moodle_url {
        try {
            return new moodle_url(
                $url
            );
        } catch (\Throwable $exception) {
            return null;
        }
    }
}