<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\fulfillment\native\course;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\entitlement\domain\CommerceEntitlementGrant;

/** Parsed and validated course_access grant data. */
final class CommerceCourseAccessGrant {
    private function __construct(
        private readonly int $courseid,
        private readonly string $accesslevel,
        private readonly string $roleshortname,
        private readonly array $cohortids,
        private readonly array $cohortnames,
        private readonly array $groupids,
        private readonly array $groupnames
    ) {
    }

    public static function from_grant(CommerceEntitlementGrant $grant): self {
        if ($grant->get_type() !== 'course_access') {
            throw new \coding_exception('CommerceCourseAccessGrant requires a course_access grant.');
        }

        if (!preg_match('/^course:(\d+):([a-z0-9_-]+)$/i', $grant->get_resource_key(), $matches)) {
            throw new \coding_exception('Invalid Native course access resource key.');
        }

        $configuration = $grant->get_configuration();
        $courseid = (int) $matches[1];
        $accesslevel = strtolower(trim((string) $matches[2]));
        $configuredcourseid = (int) ($configuration['courseid'] ?? 0);

        if ($configuredcourseid > 0 && $configuredcourseid !== $courseid) {
            throw new \coding_exception('The course access configuration does not match its resource key.');
        }

        $roleshortname = trim((string) ($configuration['roleshortname'] ?? self::role_for_access_level($accesslevel)));
        if ($roleshortname === '') {
            throw new \coding_exception('A Native course access grant requires a role shortname.');
        }

        return new self(
            $courseid,
            $accesslevel,
            $roleshortname,
            self::integer_list($configuration, ['cohortids', 'cohortid']),
            self::string_list($configuration, ['cohortnames', 'cohortname']),
            self::integer_list($configuration, ['groupids', 'groupid']),
            self::string_list($configuration, ['groupnames', 'groupname'])
        );
    }

    public function get_course_id(): int {
        return $this->courseid;
    }

    public function get_access_level(): string {
        return $this->accesslevel;
    }

    public function get_role_shortname(): string {
        return $this->roleshortname;
    }

    public function get_cohort_ids(): array {
        return $this->cohortids;
    }

    public function get_cohort_names(): array {
        return $this->cohortnames;
    }

    public function get_group_ids(): array {
        return $this->groupids;
    }

    public function get_group_names(): array {
        return $this->groupnames;
    }

    private static function role_for_access_level(string $accesslevel): string {
        return match ($accesslevel) {
            'trial' => 'trialstudent',
            'grammar' => 'grammarstudent',
            default => 'student',
        };
    }

    private static function integer_list(array $configuration, array $keys): array {
        $values = [];
        foreach ($keys as $key) {
            if (!array_key_exists($key, $configuration) || $configuration[$key] === null || $configuration[$key] === '') {
                continue;
            }
            $candidate = is_array($configuration[$key]) ? $configuration[$key] : [$configuration[$key]];
            foreach ($candidate as $value) {
                $value = (int) $value;
                if ($value > 0) {
                    $values[$value] = $value;
                }
            }
        }
        return array_values($values);
    }

    private static function string_list(array $configuration, array $keys): array {
        $values = [];
        foreach ($keys as $key) {
            if (!array_key_exists($key, $configuration) || $configuration[$key] === null || $configuration[$key] === '') {
                continue;
            }
            $candidate = is_array($configuration[$key]) ? $configuration[$key] : [$configuration[$key]];
            foreach ($candidate as $value) {
                $value = trim((string) $value);
                if ($value !== '') {
                    $values[\core_text::strtolower($value)] = $value;
                }
            }
        }
        return array_values($values);
    }
}
