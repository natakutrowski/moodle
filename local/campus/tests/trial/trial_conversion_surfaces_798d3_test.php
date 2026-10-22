<?php

declare(strict_types=1);

namespace local_campus;

defined('MOODLE_INTERNAL') || die();

/** Structural guard for 7.98D3 conversion surfaces. */
final class trial_conversion_surfaces_798d3_test extends \advanced_testcase {
    public function test_banner_is_driven_by_native_conversion_offer_not_remaining_trial_access(): void {
        global $CFG;

        $source = file_get_contents($CFG->dirroot . '/local/campus/lib.php');
        self::assertStringContainsString(
            'return $bridge->resolve_for_user($userid);',
            $source
        );
        self::assertStringContainsString(
            '$deadline = $trialoffer->get_expires_at();',
            $source
        );
        self::assertStringNotContainsString(
            'trial_manager::user_has_trial_access_remaining(',
            $this->banner_source($source)
        );
        self::assertStringNotContainsString(
            'trial_manager::user_has_active_trial(',
            $this->banner_source($source)
        );
    }

    public function test_expired_conversion_user_gets_purchase_cta_instead_of_real_course_access_cta(): void {
        global $CFG;

        $source = file_get_contents($CFG->dirroot . '/local/campus/course.php');
        self::assertStringContainsString('$islegacyconversion = false;', $source);
        self::assertStringContainsString(
            'local_campus_trial_conversion_offer((int)$USER->id, $displayid)',
            $source
        );
        self::assertStringContainsString(
            '$isrestricted = $isrestricted || $islegacyconversion;',
            $source
        );
        self::assertStringContainsString(
            'local_campus_trial_conversion_url((int)$USER->id, $displayid)',
            $source
        );
    }

    private function banner_source(string $source): string {
        $start = strpos($source, 'function local_campus_render_trial_discount_banner');
        $end = strpos($source, 'function local_campus_get_trial_discount_banner_html', $start ?: 0);
        if ($start === false || $end === false) {
            return '';
        }
        return substr($source, $start, $end - $start);
    }
}
