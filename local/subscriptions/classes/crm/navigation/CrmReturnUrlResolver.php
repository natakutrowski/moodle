<?php

namespace local_subscriptions\crm\navigation;

defined('MOODLE_INTERNAL') || die();

use moodle_url;

/**
 * Resolves return URLs that must remain inside Moodle.
 */
final class CrmReturnUrlResolver {

    /**
     * Returns a safe internal return URL or the provided fallback.
     *
     * @param string $candidate Raw return URL candidate.
     * @param moodle_url $fallback Fallback URL.
     * @return moodle_url
     */
    public static function resolve(
        string $candidate,
        moodle_url $fallback
    ): moodle_url {
        $candidate = trim(
            $candidate
        );

        if ($candidate === '') {
            return clone $fallback;
        }

        $normalised =
            InternalMoodleUrlValidator::normalise(
                $candidate
            );

        return $normalised
            ?? clone $fallback;
    }

    /**
     * Converts an internal Moodle URL into a returnurl parameter value.
     *
     * @param moodle_url $url
     * @return string
     */
    public static function to_parameter(
        moodle_url $url
    ): string {
        return $url->out_as_local_url(
            false
        );
    }
}