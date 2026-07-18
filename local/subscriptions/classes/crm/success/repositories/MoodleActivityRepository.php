<?php

namespace local_subscriptions\crm\success\repositories;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\success\runtime\CustomerSuccessRepositoryProfiler;

/**
 * Reads native Moodle activity data for Customer Success collectors.
 */
final class MoodleActivityRepository {

    private const MAX_LOG_EVENTS = 20000;
    private const SESSION_GAP_SECONDS = 1800;
    private const SINGLE_EVENT_SECONDS = 60;
    private const MAX_SESSION_SECONDS = 14400;

    public function is_available(): bool {
        global $DB;

        $manager = $DB->get_manager();

        return $manager->table_exists(
            new \xmldb_table('logstore_standard_log')
        );
    }

    public function get_user_last_access(
        int $userid
    ): int {
        global $DB;

        if ($userid <= 0) {
            throw new \InvalidArgumentException(
                'Moodle activity userid must be greater than zero.'
            );
        }

        return (int)$DB->get_field(
            'user',
            'lastaccess',
            ['id' => $userid],
            MUST_EXIST
        );
    }

     /**
     * Returns normalized activity statistics for one user.
     *
     * @return array<string,int|float>
     */
    public function get_activity_statistics(
        int $userid,
        int $measuredat
    ): array {
        if ($userid <= 0) {
            throw new \InvalidArgumentException(
                'Moodle activity userid must be greater than zero.'
            );
        }

        if ($measuredat <= 0) {
            throw new \InvalidArgumentException(
                'Moodle activity timestamp must be greater than zero.'
            );
        }

        $available =
            CustomerSuccessRepositoryProfiler::measure(
                'moodle_activity',
                $userid,
                'availability',
                fn(): bool =>
                    $this->is_available()
            );

        if (!$available) {
            return $this->empty_statistics();
        }

        $from30days =
            $measuredat - (30 * DAYSECS);

        $events =
            CustomerSuccessRepositoryProfiler::measure(
                'moodle_activity',
                $userid,
                'recent_events_query',
                fn(): array =>
                    $this->get_recent_events(
                        $userid,
                        $from30days,
                        $measuredat
                    )
            );

        return CustomerSuccessRepositoryProfiler::measure(
            'moodle_activity',
            $userid,
            'statistics_build',
            fn(): array =>
                $this->build_statistics(
                    $events,
                    $measuredat
                )
        );
    }

    /**
     * @return \stdClass[]
     */
    private function get_recent_events(
        int $userid,
        int $from,
        int $until
    ): array {
        global $DB;

        $sql = "
            SELECT
                id,
                timecreated,
                eventname,
                component,
                action,
                target,
                objecttable,
                objectid,
                contextlevel,
                contextinstanceid,
                courseid
              FROM {logstore_standard_log}
             WHERE userid = :userid
               AND timecreated >= :timefrom
               AND timecreated <= :timeuntil
               AND anonymous = 0
          ORDER BY timecreated ASC, id ASC
        ";

        return array_values(
            $DB->get_records_sql(
                $sql,
                [
                    'userid' => $userid,
                    'timefrom' => $from,
                    'timeuntil' => $until,
                ],
                0,
                self::MAX_LOG_EVENTS
            )
        );
    }

    /**
     * @param \stdClass[] $events
     * @return array<string,int|float>
     */
    private function build_statistics(
        array $events,
        int $measuredat
    ): array {
        $from7days = $measuredat - (7 * DAYSECS);
        $from30days = $measuredat - (30 * DAYSECS);

        $timestamps7d = [];
        $timestamps30d = [];

        $activedays7d = [];
        $activedays30d = [];
        $navigationkeys30d = [];

        $events7d = 0;
        $events30d = 0;
        $logins7d = 0;
        $logins30d = 0;
        $lastlogevent = 0;

        foreach ($events as $event) {
            $timecreated = (int)($event->timecreated ?? 0);

            if (
                $timecreated <= 0 ||
                $timecreated < $from30days ||
                $timecreated > $measuredat
            ) {
                continue;
            }

            $events30d++;
            $timestamps30d[] = $timecreated;
            $activedays30d[$this->day_key($timecreated)] = true;
            $lastlogevent = max($lastlogevent, $timecreated);

            $navigationkey = $this->navigation_key($event);

            if ($navigationkey !== '') {
                $navigationkeys30d[$navigationkey] = true;
            }

            if ($this->is_login_event($event)) {
                $logins30d++;
            }

            if ($timecreated >= $from7days) {
                $events7d++;
                $timestamps7d[] = $timecreated;
                $activedays7d[$this->day_key($timecreated)] = true;

                if ($this->is_login_event($event)) {
                    $logins7d++;
                }
            }
        }

        $sessions7d = $this->calculate_sessions(
            $timestamps7d
        );

        $sessions30d = $this->calculate_sessions(
            $timestamps30d
        );

        $activeDays30dCount = count($activedays30d);

        return [
            'last_log_event_at' => $lastlogevent,
            'events_7d' => $events7d,
            'events_30d' => $events30d,
            'active_days_7d' => count($activedays7d),
            'active_days_30d' => $activeDays30dCount,
            'login_events_7d' => $logins7d,
            'login_events_30d' => $logins30d,
            'sessions_7d' => $sessions7d['count'],
            'sessions_30d' => $sessions30d['count'],
            'estimated_active_seconds_7d' =>
                $sessions7d['estimatedseconds'],
            'estimated_active_seconds_30d' =>
                $sessions30d['estimatedseconds'],
            'unique_navigation_targets_30d' =>
                count($navigationkeys30d),
            'regularity_percentage_30d' =>
                (float)round(
                    ($activeDays30dCount / 30) * 100,
                    2
                ),
            'event_limit_reached' =>
                count($events) >= self::MAX_LOG_EVENTS ? 1 : 0,
        ];
    }

    /**
     * @param int[] $timestamps
     * @return array{count:int,estimatedseconds:int}
     */
    private function calculate_sessions(
        array $timestamps
    ): array {
        if ($timestamps === []) {
            return [
                'count' => 0,
                'estimatedseconds' => 0,
            ];
        }

        sort($timestamps, SORT_NUMERIC);

        $sessioncount = 0;
        $estimatedseconds = 0;
        $sessionstart = null;
        $sessionend = null;

        foreach ($timestamps as $timestamp) {
            $timestamp = (int)$timestamp;

            if ($sessionstart === null) {
                $sessionstart = $timestamp;
                $sessionend = $timestamp;
                $sessioncount++;
                continue;
            }

            if (
                $timestamp - (int)$sessionend >
                self::SESSION_GAP_SECONDS
            ) {
                $estimatedseconds +=
                    $this->estimate_session_duration(
                        (int)$sessionstart,
                        (int)$sessionend
                    );

                $sessionstart = $timestamp;
                $sessionend = $timestamp;
                $sessioncount++;
                continue;
            }

            $sessionend = $timestamp;
        }

        $estimatedseconds += $this->estimate_session_duration(
            (int)$sessionstart,
            (int)$sessionend
        );

        return [
            'count' => $sessioncount,
            'estimatedseconds' => $estimatedseconds,
        ];
    }

    private function estimate_session_duration(
        int $start,
        int $end
    ): int {
        if ($end <= $start) {
            return self::SINGLE_EVENT_SECONDS;
        }

        return min(
            self::MAX_SESSION_SECONDS,
            ($end - $start) + self::SINGLE_EVENT_SECONDS
        );
    }

    private function is_login_event(
        \stdClass $event
    ): bool {
        return (string)($event->eventname ?? '') ===
            '\core\event\user_loggedin';
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

    private function navigation_key(
        \stdClass $event
    ): string {
        $action = trim(
            (string)($event->action ?? '')
        );

        $target = trim(
            (string)($event->target ?? '')
        );

        $courseid = max(
            0,
            (int)($event->courseid ?? 0)
        );

        $contextlevel = max(
            0,
            (int)($event->contextlevel ?? 0)
        );

        if ($action === '' && $target === '') {
            return '';
        }

        return implode(
            ':',
            [
                $action,
                $target,
                (string)$courseid,
                (string)$contextlevel,
            ]
        );
    }

    /**
     * @return array<string,int|float>
     */
    private function empty_statistics(): array {
        return [
            'last_log_event_at' => 0,
            'events_7d' => 0,
            'events_30d' => 0,
            'active_days_7d' => 0,
            'active_days_30d' => 0,
            'login_events_7d' => 0,
            'login_events_30d' => 0,
            'sessions_7d' => 0,
            'sessions_30d' => 0,
            'estimated_active_seconds_7d' => 0,
            'estimated_active_seconds_30d' => 0,
            'unique_navigation_targets_30d' => 0,
            'regularity_percentage_30d' => 0.0,
            'event_limit_reached' => 0,
        ];
    }
}