<?php

namespace local_subscriptions\crm\success\repositories;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\success\runtime\CustomerSuccessRepositoryProfiler;

/**
 * Provides and caches accessible Moodle courses during one CRM computation.
 */
final class EnrolledCourseProvider {

    /**
     * Cached courses indexed by user ID.
     *
     * @var array<int,array<int,\stdClass>>
     */
    private array $cache = [];

    /**
     * Return active accessible courses for one Moodle user.
     *
     * @return \stdClass[]
     */
    public function get_courses(
        int $userid
    ): array {
        if ($userid <= 0) {
            throw new \InvalidArgumentException(
                'Enrolled course userid must be greater than zero.'
            );
        }

        if (array_key_exists($userid, $this->cache)) {
            return CustomerSuccessRepositoryProfiler::measure(
                'enrolled_course_provider',
                $userid,
                'cache_hit',
                fn(): array =>
                    $this->cache[$userid]
            );
        }

        $courses =
            CustomerSuccessRepositoryProfiler::measure(
                'enrolled_course_provider',
                $userid,
                'enrolment_query',
                fn(): array =>
                    enrol_get_users_courses(
                        $userid,
                        true,
                        'id,fullname,shortname,visible,startdate,enddate'
                    )
            );

        $courses =
            CustomerSuccessRepositoryProfiler::measure(
                'enrolled_course_provider',
                $userid,
                'course_filtering',
                fn(): array =>
                    array_values(
                        array_filter(
                            $courses,
                            static fn(\stdClass $course): bool =>
                                (int)$course->id > SITEID
                        )
                    )
            );

        $this->cache[$userid] = $courses;

        return $courses;
    }
    /**
     * Return only accessible course IDs.
     *
     * @return int[]
     */
    public function get_course_ids(
        int $userid
    ): array {
        return array_values(
            array_unique(
                array_map(
                    static fn(\stdClass $course): int =>
                        (int)$course->id,
                    $this->get_courses($userid)
                )
            )
        );
    }
}