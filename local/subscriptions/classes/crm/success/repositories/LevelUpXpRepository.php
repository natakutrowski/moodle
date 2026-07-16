<?php

namespace local_subscriptions\crm\success\repositories;

defined('MOODLE_INTERNAL') || die();

/**
 * Reads Level Up XP state and activity without depending on deprecated fields.
 */
final class LevelUpXpRepository {

    private const MAX_RECENT_LOGS = 20000;

    public function is_available(): bool {
        global $DB;

        if (
            \core_component::get_component_directory('block_xp') === null
        ) {
            return false;
        }

        $manager = $DB->get_manager();

        return
            $manager->table_exists(
                new \xmldb_table('block_xp')
            ) &&
            $manager->table_exists(
                new \xmldb_table('block_xp_config')
            );
    }

    /**
     * Returns Level Up statistics for one Moodle user.
     *
     * @return array<string,int|float>
     */
    public function get_statistics(
        int $userid,
        int $measuredat
    ): array {
        if ($userid <= 0) {
            throw new \InvalidArgumentException(
                'Level Up XP userid must be greater than zero.'
            );
        }

        if ($measuredat <= 0) {
            throw new \InvalidArgumentException(
                'Level Up XP timestamp must be greater than zero.'
            );
        }

        if (!$this->is_available()) {
            return $this->empty_statistics();
        }

        $enabledcourseids = $this->get_enabled_enrolled_course_ids(
            $userid
        );

        $current = $this->get_current_states(
            $userid,
            $enabledcourseids
        );

        $recent = $this->get_recent_activity(
            $userid,
            $measuredat
        );

        return array_merge(
            [
                'enabled_course_count' =>
                    count($enabledcourseids),
            ],
            $current,
            $recent
        );
    }

    /**
     * @return int[]
     */
    private function get_enabled_enrolled_course_ids(
        int $userid
    ): array {
        global $DB;

        $courses = enrol_get_users_courses(
            $userid,
            true,
            'id'
        );

        $enrolledcourseids = array_values(
            array_filter(
                array_map(
                    static fn(\stdClass $course): int =>
                        (int)$course->id,
                    $courses
                ),
                static fn(int $courseid): bool =>
                    $courseid > SITEID
            )
        );

        if ($enrolledcourseids === []) {
            return [];
        }

        [$insql, $params] = $DB->get_in_or_equal(
            $enrolledcourseids,
            SQL_PARAMS_NAMED,
            'xpcourse'
        );

        $records = $DB->get_records_select(
            'block_xp_config',
            "enabled = :enabled AND courseid {$insql}",
            ['enabled' => 1] + $params,
            '',
            'id,courseid'
        );

        return array_values(
            array_unique(
                array_map(
                    static fn(\stdClass $record): int =>
                        (int)$record->courseid,
                    $records
                )
            )
        );
    }

    /**
     * Uses the public Level Up XP world API.
     *
     * The deprecated block_xp.lvl field is deliberately never read.
     *
     * @param int[] $courseids
     * @return array<string,int|float>
     */
    private function get_current_states(
        int $userid,
        array $courseids
    ): array {
        $totalxp = 0;
        $highestlevel = 0;
        $courseswithxp = 0;
        $totalxpinlevel = 0;
        $totallevelcapacity = 0;

        foreach ($courseids as $courseid) {
            try {
                $world = \block_xp\di::get(
                    'course_world_factory'
                )->get_world($courseid);

                $state = $world
                    ->get_store()
                    ->get_state($userid);

                $xp = max(
                    0,
                    (int)$state->get_xp()
                );

                $level = max(
                    0,
                    (int)$state->get_level()
                );

                $xpinlevel = max(
                    0,
                    (int)$state->get_xp_in_level()
                );

                $levelcapacity = max(
                    0,
                    (int)$state->get_total_xp_in_level()
                );

                $totalxp += $xp;
                $highestlevel = max(
                    $highestlevel,
                    $level
                );

                $totalxpinlevel += $xpinlevel;
                $totallevelcapacity += $levelcapacity;

                if ($xp > 0) {
                    $courseswithxp++;
                }
            } catch (\Throwable $exception) {
                // One unavailable or malformed world must not break all XP data.
                continue;
            }
        }

        $levelprogress = $totallevelcapacity > 0
            ? round(
                ($totalxpinlevel / $totallevelcapacity) * 100,
                2
            )
            : null;

        return [
            'course_count_with_xp' => $courseswithxp,
            'total_xp' => $totalxp,
            'highest_level' => $highestlevel,
            'xp_in_current_levels' => $totalxpinlevel,
            'current_levels_capacity' => $totallevelcapacity,
            'current_levels_progress_percentage' =>
                $levelprogress,
        ];
    }

    /**
     * @return array<string,int>
     */
    private function get_recent_activity(
        int $userid,
        int $measuredat
    ): array {
        global $DB;

        $manager = $DB->get_manager();

        if (
            !$manager->table_exists(
                new \xmldb_table('block_xp_logs')
            )
        ) {
            return [
                'xp_7d' => 0,
                'xp_30d' => 0,
                'reward_events_7d' => 0,
                'reward_events_30d' => 0,
                'active_days_7d' => 0,
                'active_days_30d' => 0,
                'last_reward_at' => 0,
                'log_limit_reached' => 0,
            ];
        }

        $from30days = $measuredat - (30 * DAYSECS);
        $from7days = $measuredat - (7 * DAYSECS);

        $records = array_values(
            $DB->get_records_select(
                'block_xp_logs',
                '
                    userid = :userid
                    AND timerecorded >= :timefrom
                    AND timerecorded <= :timeuntil
                ',
                [
                    'userid' => $userid,
                    'timefrom' => $from30days,
                    'timeuntil' => $measuredat,
                ],
                'timerecorded ASC, id ASC',
                'id,points,timerecorded',
                0,
                self::MAX_RECENT_LOGS
            )
        );

        $xp7d = 0;
        $xp30d = 0;
        $events7d = 0;
        $events30d = 0;
        $days7d = [];
        $days30d = [];
        $lastrewardat = 0;

        foreach ($records as $record) {
            $points = (int)$record->points;
            $timerecorded = (int)$record->timerecorded;

            if ($points <= 0 || $timerecorded <= 0) {
                continue;
            }

            $xp30d += $points;
            $events30d++;
            $days30d[$this->day_key($timerecorded)] = true;
            $lastrewardat = max(
                $lastrewardat,
                $timerecorded
            );

            if ($timerecorded >= $from7days) {
                $xp7d += $points;
                $events7d++;
                $days7d[$this->day_key($timerecorded)] = true;
            }
        }

        return [
            'xp_7d' => $xp7d,
            'xp_30d' => $xp30d,
            'reward_events_7d' => $events7d,
            'reward_events_30d' => $events30d,
            'active_days_7d' => count($days7d),
            'active_days_30d' => count($days30d),
            'last_reward_at' => $lastrewardat,
            'log_limit_reached' =>
                count($records) >= self::MAX_RECENT_LOGS
                    ? 1
                    : 0,
        ];
    }

    private function day_key(
        int $timestamp
    ): string {
        return userdate(
            $timestamp,
            '%Y-%m-%d',
            99,
            false
        );
    }

    /**
     * @return array<string,int|float|null>
     */
    private function empty_statistics(): array {
        return [
            'enabled_course_count' => 0,
            'course_count_with_xp' => 0,
            'total_xp' => 0,
            'highest_level' => 0,
            'xp_in_current_levels' => 0,
            'current_levels_capacity' => 0,
            'current_levels_progress_percentage' => null,
            'xp_7d' => 0,
            'xp_30d' => 0,
            'reward_events_7d' => 0,
            'reward_events_30d' => 0,
            'active_days_7d' => 0,
            'active_days_30d' => 0,
            'last_reward_at' => 0,
            'log_limit_reached' => 0,
        ];
    }
}