<?php

namespace local_subscriptions\crm\success\repositories;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\success\runtime\CustomerSuccessRepositoryProfiler;

/**
 * Reads Level Up XP state and activity without depending on deprecated fields.
 */
final class LevelUpXpRepository {

    private const MAX_RECENT_LOGS = 20000;

    /**
     * Cached Level Up availability.
     */
    private ?bool $availabilitycache = null;

    /**
     * Cached XP log table availability.
     */
    private ?bool $logtableavailable = null;

    public function __construct(
        private readonly EnrolledCourseProvider $courseprovider =
            new EnrolledCourseProvider()
    ) {
    }

    public function is_available(): bool {
        global $DB;

        if ($this->availabilitycache !== null) {
            return $this->availabilitycache;
        }

        if (
            \core_component::get_component_directory(
                'block_xp'
            ) === null
        ) {
            $this->availabilitycache = false;

            return false;
        }

        $manager = $DB->get_manager();

        $this->availabilitycache =
            $manager->table_exists(
                new \xmldb_table('block_xp')
            ) &&
            $manager->table_exists(
                new \xmldb_table('block_xp_config')
            );

        return $this->availabilitycache;
    }

    /**
     * Returns Level Up XP scope and current state for supplied courses.
     *
     * @param int[] $courseids
     * @return array<int,\stdClass>
     */
    public function get_course_scope_records(
        int $userid,
        array $courseids
    ): array {
        global $DB;

        if ($userid <= 0) {
            throw new \InvalidArgumentException(
                'Level Up XP userid must be greater than zero.'
            );
        }

        $courseids = array_values(array_unique(array_filter(
            array_map('intval', $courseids),
            static fn(int $courseid): bool => $courseid > SITEID
        )));

        if ($courseids === []) {
            return [];
        }

        $result = [];
        foreach ($courseids as $courseid) {
            $result[$courseid] = (object)[
                'courseid' => $courseid,
                'available' => false,
                'enabled' => false,
                'scope' => 'none',
                'worldcourseid' => 0,
                'xp' => 0,
                'level' => 0,
                'xpinlevel' => 0,
                'levelcapacity' => 0,
                'levelprogresspercentage' => null,
            ];
        }

        if (!$this->is_available()) {
            return $result;
        }

        $siteenabled = $DB->record_exists(
            'block_xp_config',
            ['courseid' => SITEID, 'enabled' => 1]
        );

        [$insql, $params] = $DB->get_in_or_equal(
            $courseids,
            SQL_PARAMS_NAMED,
            'xpuserscope'
        );

        $configs = $DB->get_records_select(
            'block_xp_config',
            "enabled = :enabled AND courseid {$insql}",
            ['enabled' => 1] + $params,
            '',
            'courseid,enabled'
        );

        $worldfactory = null;
        foreach ($courseids as $courseid) {
            $record = $result[$courseid];
            $record->available = true;

            $courseenabled = isset($configs[$courseid]);
            if (!$siteenabled && !$courseenabled) {
                continue;
            }

            try {
                if ($worldfactory === null) {
                    $worldfactory = \block_xp\di::get(
                        'course_world_factory'
                    );
                }

                $world = $worldfactory->get_world($courseid);
                $worldid = method_exists($world, 'get_courseid')
                    ? (int)$world->get_courseid()
                    : $courseid;
                $state = $world->get_store()->get_state($userid);
                $capacity = max(0, (int)$state->get_total_xp_in_level());
                $xpinlevel = max(0, (int)$state->get_xp_in_level());

                $record->enabled = true;
                $record->scope = $worldid === SITEID
                    ? 'site'
                    : 'course';
                $record->worldcourseid = $worldid;
                $record->xp = max(0, (int)$state->get_xp());
                $record->level = $this->normalize_level_number(
                    $state->get_level()
                ) ?? 0;
                $record->xpinlevel = $xpinlevel;
                $record->levelcapacity = $capacity;
                $record->levelprogresspercentage = $capacity > 0
                    ? round(($xpinlevel / $capacity) * 100, 2)
                    : null;
            } catch (\Throwable $exception) {
                $record->enabled = false;
                $record->scope = $siteenabled ? 'site' : 'course';
            }
        }

        return $result;
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

        $available =
            CustomerSuccessRepositoryProfiler::measure(
                'levelup_xp',
                $userid,
                'availability',
                fn(): bool =>
                    $this->is_available()
            );

        if (!$available) {
            return $this->empty_statistics();
        }

        $enabledcourseids =
            CustomerSuccessRepositoryProfiler::measure(
                'levelup_xp',
                $userid,
                'enabled_courses',
                fn(): array =>
                    $this
                        ->get_enabled_enrolled_course_ids(
                            $userid
                        )
            );

        $current =
            CustomerSuccessRepositoryProfiler::measure(
                'levelup_xp',
                $userid,
                'current_states',
                fn(): array =>
                    $this->get_current_states(
                        $userid,
                        $enabledcourseids
                    )
            );

        $recent =
            CustomerSuccessRepositoryProfiler::measure(
                'levelup_xp',
                $userid,
                'recent_activity',
                fn(): array =>
                    $this->get_recent_activity(
                        $userid,
                        $measuredat
                    )
            );

        return CustomerSuccessRepositoryProfiler::measure(
            'levelup_xp',
            $userid,
            'result_merge',
            fn(): array =>
                array_merge(
                    [
                        'enabled_course_count' =>
                            count(
                                $enabledcourseids
                            ),
                    ],
                    $current,
                    $recent
                )
        );
    }

    /**
     * @return int[]
     */
    private function get_enabled_enrolled_course_ids(
        int $userid
    ): array {
        global $DB;

        $enrolledcourseids =
            $this->courseprovider
                ->get_course_ids($userid);

        // Level Up XP may run in site-wide mode. In that configuration the
        // active world is attached to SITEID rather than to an enrolled
        // course, so limiting the lookup to enrolments returns no current
        // state even though the user has XP in the official block.
        $candidatecourseids = array_values(array_unique(array_merge(
            [SITEID],
            $enrolledcourseids
        )));

        [$insql, $params] = $DB->get_in_or_equal(
            $candidatecourseids,
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
        $bestleaderboardrank = 0;
        $leaderboardcount = 0;

        $worldfactory = null;
        $processedworlds = [];

        foreach ($courseids as $courseid) {
            try {
                if ($worldfactory === null) {
                    $worldfactory =
                        \block_xp\di::get(
                            'course_world_factory'
                        );
                }

                $world =
                    $worldfactory->get_world(
                        $courseid
                    );

                // In site-wide mode every course resolves to the SITEID
                // world. Count that state once, not once per enrolment.
                $worldid = method_exists($world, 'get_courseid')
                    ? (int)$world->get_courseid()
                    : (int)$courseid;
                if (isset($processedworlds[$worldid])) {
                    continue;
                }
                $processedworlds[$worldid] = true;

                $state = $world
                    ->get_store()
                    ->get_state($userid);

                $xp = max(
                    0,
                    (int)$state->get_xp()
                );

                $level =
                    $this->normalize_level_number(
                        $state->get_level()
                    ) ?? 0;

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

                [$rank, $participants] = $this->get_absolute_rank(
                    $userid,
                    $worldid,
                    $xp
                );
                if ($rank > 0 && ($bestleaderboardrank === 0 || $rank < $bestleaderboardrank)) {
                    $bestleaderboardrank = $rank;
                    $leaderboardcount = $participants;
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
            'leaderboard_rank' => $bestleaderboardrank,
            'leaderboard_count' => $leaderboardcount,
        ];
    }

    /**
     * Returns the absolute Level Up position for one world.
     *
     * This mirrors the ordering used by Level Up XP's course leaderboard:
     * XP descending, then user ID ascending for ties.
     *
     * @return array{0:int,1:int} Rank (1-based) and participant count.
     */
    private function get_absolute_rank(
        int $userid,
        int $worldid,
        int $xp
    ): array {
        global $DB;

        if ($worldid <= 0 || $xp < 0) {
            return [0, 0];
        }

        try {
            $params = [
                'courseid' => $worldid,
                'userid' => $userid,
                'xp' => $xp,
                'xpeq' => $xp,
            ];
            $conditions = [
                'x.courseid = :courseid',
                'u.deleted = 0',
                'u.suspended = 0',
            ];

            $guestid = (int)guest_user()->id;
            if ($guestid > 0) {
                $conditions[] = 'u.id <> :guestid';
                $params['guestid'] = $guestid;
            }

            $adminids = array_values(array_unique(array_map(
                static fn(\stdClass $admin): int => (int)$admin->id,
                get_admins()
            )));
            if ($adminids !== []) {
                [$notinsql, $adminparams] = $DB->get_in_or_equal(
                    $adminids,
                    SQL_PARAMS_NAMED,
                    'xpadmin',
                    false
                );
                $conditions[] = "u.id {$notinsql}";
                $params += $adminparams;
            }

            $where = implode(' AND ', $conditions);
            $from = '{block_xp} x JOIN {user} u ON u.id = x.userid';

            $eligible = $DB->record_exists_sql(
                "SELECT 1
                   FROM {$from}
                  WHERE {$where}
                    AND x.userid = :userid",
                $params
            );
            if (!$eligible) {
                return [0, 0];
            }

            $participants = (int)$DB->count_records_sql(
                "SELECT COUNT(1)
                   FROM {$from}
                  WHERE {$where}",
                $params
            );
            $ahead = (int)$DB->count_records_sql(
                "SELECT COUNT(1)
                   FROM {$from}
                  WHERE {$where}
                    AND (
                        x.xp > :xp
                        OR (x.xp = :xpeq AND x.userid < :userid)
                    )",
                $params
            );

            return [$ahead + 1, $participants];
        } catch (\Throwable) {
            return [0, 0];
        }
    }

    /**
     * @return array<string,int>
     */
    private function get_recent_activity(
        int $userid,
        int $measuredat
    ): array {
        global $DB;

        if ($this->logtableavailable === null) {
            $this->logtableavailable =
                $DB->get_manager()->table_exists(
                    new \xmldb_table(
                        'block_xp_logs'
                    )
                );
        }

        if (!$this->logtableavailable) {
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
            'leaderboard_rank' => 0,
            'leaderboard_count' => 0,
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

    /**
     * Extracts the numeric level from the different Level Up XP API versions.
     */
    private function normalize_level_number(
        mixed $level
    ): ?int {
        if ($level === null) {
            return null;
        }

        if (is_int($level)) {
            return max(0, $level);
        }

        if (is_numeric($level)) {
            return max(
                0,
                (int)$level
            );
        }

        if (!is_object($level)) {
            return null;
        }

        /*
        * Known public accessors used by Level Up XP level objects.
        */
        foreach (
            [
                'get_number',
                'get_level_number',
                'get_rank',
                'get_id',
            ]
            as $method
        ) {
            if (!method_exists($level, $method)) {
                continue;
            }

            try {
                $value = $level->{$method}();

                if (
                    is_int($value) ||
                    is_numeric($value)
                ) {
                    return max(
                        0,
                        (int)$value
                    );
                }
            } catch (\Throwable) {
                continue;
            }
        }

        /*
        * Some Level Up XP value objects expose their level through get_level().
        * Only accept the result when it is scalar to avoid object recursion.
        */
        if (method_exists($level, 'get_level')) {
            try {
                $value = $level->get_level();

                if (
                    is_int($value) ||
                    is_numeric($value)
                ) {
                    return max(
                        0,
                        (int)$value
                    );
                }
            } catch (\Throwable) {
                // Continue with property-based compatibility fallbacks.
            }
        }

        foreach (
            [
                'level',
                'number',
                'rank',
                'id',
            ]
            as $property
        ) {
            if (!isset($level->{$property})) {
                continue;
            }

            $value = $level->{$property};

            if (
                is_int($value) ||
                is_numeric($value)
            ) {
                return max(
                    0,
                    (int)$value
                );
            }
        }

        return null;
    }

}