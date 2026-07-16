<?php

namespace local_subscriptions\crm\success\repositories;

defined('MOODLE_INTERNAL') || die();

/**
 * Reads aggregated non-textual learning statistics from Poodll modules.
 *
 * No transcript, answer, feedback, session JSON or recording is loaded.
 */
final class PoodllLearningRepository {

    public function is_module_available(
        string $component,
        string $tablename
    ): bool {
        global $DB;

        if (
            \core_component::get_component_directory($component) === null
        ) {
            return false;
        }

        return $DB->get_manager()->table_exists(
            new \xmldb_table($tablename)
        );
    }

    /**
     * @return array<string,int|float|null>
     */
    public function get_minilesson_statistics(
        int $userid,
        int $measuredat
    ): array {
        global $DB;

        $this->validate($userid, $measuredat);

        $from7days = $measuredat - (7 * DAYSECS);
        $from30days = $measuredat - (30 * DAYSECS);

        $sql = "
            SELECT
                COUNT(id) AS attemptcount,
                COUNT(DISTINCT moduleid) AS activitycount,
                SUM(
                    CASE WHEN timecreated >= :from7days
                    THEN 1 ELSE 0 END
                ) AS attempts7d,
                SUM(
                    CASE WHEN timecreated >= :from30days
                    THEN 1 ELSE 0 END
                ) AS attempts30d,
                SUM(COALESCE(sessiontime, 0)) AS totalseconds,
                AVG(sessionscore) AS averagescore,
                MAX(sessionscore) AS bestscore,
                SUM(errorcount) AS totalerrors,
                SUM(
                    CASE WHEN COALESCE(sessionend, 0) > 0
                    THEN 1 ELSE 0 END
                ) AS completedattempts,
                MAX(
                    CASE
                        WHEN COALESCE(sessionend, 0) > 0
                        THEN sessionend
                        WHEN COALESCE(timemodified, 0) > 0
                        THEN timemodified
                        ELSE timecreated
                    END
                ) AS lastattemptat
              FROM {minilesson_attempt}
             WHERE userid = :userid
        ";

        $record = $DB->get_record_sql(
            $sql,
            [
                'userid' => $userid,
                'from7days' => $from7days,
                'from30days' => $from30days,
            ]
        );

        return $this->normalize_record($record, [
            'attempt_count' => 0,
            'activity_count' => 0,
            'attempts_7d' => 0,
            'attempts_30d' => 0,
            'total_seconds' => 0,
            'average_score' => null,
            'best_score' => null,
            'total_errors' => 0,
            'completed_attempts' => 0,
            'last_attempt_at' => 0,
        ], [
            'attemptcount' => 'attempt_count',
            'activitycount' => 'activity_count',
            'attempts7d' => 'attempts_7d',
            'attempts30d' => 'attempts_30d',
            'totalseconds' => 'total_seconds',
            'averagescore' => 'average_score',
            'bestscore' => 'best_score',
            'totalerrors' => 'total_errors',
            'completedattempts' => 'completed_attempts',
            'lastattemptat' => 'last_attempt_at',
        ]);
    }

    /**
     * @return array<string,int|float|null>
     */
    public function get_readaloud_statistics(
        int $userid,
        int $measuredat
    ): array {
        global $DB;

        $this->validate($userid, $measuredat);

        $from7days = $measuredat - (7 * DAYSECS);
        $from30days = $measuredat - (30 * DAYSECS);

        $sql = "
            SELECT
                COUNT(id) AS attemptcount,
                COUNT(DISTINCT readaloudid) AS activitycount,
                SUM(
                    CASE WHEN timecreated >= :from7days
                    THEN 1 ELSE 0 END
                ) AS attempts7d,
                SUM(
                    CASE WHEN timecreated >= :from30days
                    THEN 1 ELSE 0 END
                ) AS attempts30d,
                AVG(accuracy) AS averageaccuracy,
                MAX(accuracy) AS bestaccuracy,
                AVG(wpm) AS averagewpm,
                MAX(wpm) AS bestwpm,
                AVG(sessionscore) AS averagescore,
                SUM(COALESCE(sessiontime, 0)) AS totalseconds,
                SUM(errorcount) AS totalerrors,
                SUM(sccount) AS selfcorrectioncount,
                MAX(
                    CASE
                        WHEN COALESCE(timemodified, 0) > 0
                        THEN timemodified
                        ELSE timecreated
                    END
                ) AS lastattemptat
              FROM {readaloud_attempt}
             WHERE userid = :userid
               AND COALESCE(dontgrade, 0) = 0
        ";

        $record = $DB->get_record_sql(
            $sql,
            [
                'userid' => $userid,
                'from7days' => $from7days,
                'from30days' => $from30days,
            ]
        );

        return $this->normalize_record($record, [
            'attempt_count' => 0,
            'activity_count' => 0,
            'attempts_7d' => 0,
            'attempts_30d' => 0,
            'average_accuracy' => null,
            'best_accuracy' => null,
            'average_wpm' => null,
            'best_wpm' => null,
            'average_score' => null,
            'total_seconds' => 0,
            'total_errors' => 0,
            'self_correction_count' => 0,
            'last_attempt_at' => 0,
        ], [
            'attemptcount' => 'attempt_count',
            'activitycount' => 'activity_count',
            'attempts7d' => 'attempts_7d',
            'attempts30d' => 'attempts_30d',
            'averageaccuracy' => 'average_accuracy',
            'bestaccuracy' => 'best_accuracy',
            'averagewpm' => 'average_wpm',
            'bestwpm' => 'best_wpm',
            'averagescore' => 'average_score',
            'totalseconds' => 'total_seconds',
            'totalerrors' => 'total_errors',
            'selfcorrectioncount' => 'self_correction_count',
            'lastattemptat' => 'last_attempt_at',
        ]);
    }

    /**
     * @return array<string,int|float|null>
     */
    public function get_solo_statistics(
        int $userid,
        int $measuredat
    ): array {
        global $DB;

        $this->validate($userid, $measuredat);

        $from7days = $measuredat - (7 * DAYSECS);
        $from30days = $measuredat - (30 * DAYSECS);

        $sql = "
            SELECT
                COUNT(DISTINCT a.id) AS attemptcount,
                COUNT(DISTINCT a.solo) AS activitycount,
                SUM(
                    CASE WHEN a.timemodified >= :from7days
                    THEN 1 ELSE 0 END
                ) AS attempts7d,
                SUM(
                    CASE WHEN a.timemodified >= :from30days
                    THEN 1 ELSE 0 END
                ) AS attempts30d,
                AVG(a.grade) AS averagegrade,
                AVG(a.aigrade) AS averageaigrade,
                AVG(s.aiaccuracy) AS averageaccuracy,
                AVG(s.autogrammarscore) AS averagegrammarscore,
                AVG(s.autospellscore) AS averagespellscore,
                AVG(s.wpm) AS averagewpm,
                SUM(COALESCE(s.speakingtime, 0)) AS speakingseconds,
                SUM(COALESCE(s.words, 0)) AS totalwords,
                SUM(COALESCE(s.uniquewords, 0)) AS totaluniquewords,
                SUM(COALESCE(s.turns, 0)) AS totalturns,
                AVG(s.relevance) AS averagerelevance,
                MAX(a.timemodified) AS lastattemptat
              FROM {solo_attempts} a
         LEFT JOIN {solo_attemptstats} s
                ON s.attemptid = a.id
               AND s.userid = a.userid
             WHERE a.userid = :userid
               AND COALESCE(a.visible, 1) = 1
        ";

        $record = $DB->get_record_sql(
            $sql,
            [
                'userid' => $userid,
                'from7days' => $from7days,
                'from30days' => $from30days,
            ]
        );

        return $this->normalize_record($record, [
            'attempt_count' => 0,
            'activity_count' => 0,
            'attempts_7d' => 0,
            'attempts_30d' => 0,
            'average_grade' => null,
            'average_ai_grade' => null,
            'average_accuracy' => null,
            'average_grammar_score' => null,
            'average_spell_score' => null,
            'average_wpm' => null,
            'speaking_seconds' => 0,
            'total_words' => 0,
            'total_unique_words' => 0,
            'total_turns' => 0,
            'average_relevance' => null,
            'last_attempt_at' => 0,
        ], [
            'attemptcount' => 'attempt_count',
            'activitycount' => 'activity_count',
            'attempts7d' => 'attempts_7d',
            'attempts30d' => 'attempts_30d',
            'averagegrade' => 'average_grade',
            'averageaigrade' => 'average_ai_grade',
            'averageaccuracy' => 'average_accuracy',
            'averagegrammarscore' => 'average_grammar_score',
            'averagespellscore' => 'average_spell_score',
            'averagewpm' => 'average_wpm',
            'speakingseconds' => 'speaking_seconds',
            'totalwords' => 'total_words',
            'totaluniquewords' => 'total_unique_words',
            'totalturns' => 'total_turns',
            'averagerelevance' => 'average_relevance',
            'lastattemptat' => 'last_attempt_at',
        ]);
    }

    /**
     * @return array<string,int|float|null>
     */
    public function get_wordcards_statistics(
        int $userid,
        int $measuredat
    ): array {
        global $DB;

        $this->validate($userid, $measuredat);

        $from7days = $measuredat - (7 * DAYSECS);
        $from30days = $measuredat - (30 * DAYSECS);

        $seen = $DB->get_record_sql(
            "
                SELECT
                    COUNT(id) AS seenevents,
                    COUNT(DISTINCT termid) AS distinctterms,
                    SUM(
                        CASE WHEN timecreated >= :from7days
                        THEN 1 ELSE 0 END
                    ) AS seen7d,
                    SUM(
                        CASE WHEN timecreated >= :from30days
                        THEN 1 ELSE 0 END
                    ) AS seen30d,
                    MAX(timecreated) AS lastseenat
                  FROM {wordcards_seen}
                 WHERE userid = :userid
            ",
            [
                'userid' => $userid,
                'from7days' => $from7days,
                'from30days' => $from30days,
            ]
        );

        $associations = $DB->get_record_sql(
            "
                SELECT
                    COUNT(id) AS associationcount,
                    SUM(successcount) AS successcount,
                    SUM(failcount) AS failcount,
                    MAX(lastsuccess) AS lastsuccessat,
                    MAX(lastfail) AS lastfailat
                  FROM {wordcards_associations}
                 WHERE userid = :userid
            ",
            ['userid' => $userid]
        );

        $progress = $DB->get_record_sql(
            "
                SELECT
                    COUNT(DISTINCT modid) AS modulecount,
                    AVG(totalgrade) AS averagetotalgrade,
                    MAX(totalgrade) AS besttotalgrade,
                    MAX(timecreated) AS lastprogressat
                  FROM {wordcards_progress}
                 WHERE userid = :userid
            ",
            ['userid' => $userid]
        );

        $successcount = (int)($associations->successcount ?? 0);
        $failcount = (int)($associations->failcount ?? 0);
        $interactions = $successcount + $failcount;

        $successrate = $interactions > 0
            ? round(
                ($successcount / $interactions) * 100,
                2
            )
            : null;

        return [
            'seen_event_count' =>
                (int)($seen->seenevents ?? 0),
            'distinct_term_count' =>
                (int)($seen->distinctterms ?? 0),
            'seen_7d' =>
                (int)($seen->seen7d ?? 0),
            'seen_30d' =>
                (int)($seen->seen30d ?? 0),
            'last_seen_at' =>
                (int)($seen->lastseenat ?? 0),
            'association_count' =>
                (int)($associations->associationcount ?? 0),
            'success_count' => $successcount,
            'fail_count' => $failcount,
            'interaction_count' => $interactions,
            'success_rate_percentage' => $successrate,
            'last_success_at' =>
                (int)($associations->lastsuccessat ?? 0),
            'last_fail_at' =>
                (int)($associations->lastfailat ?? 0),
            'module_count' =>
                (int)($progress->modulecount ?? 0),
            'average_total_grade' =>
                $progress->averagetotalgrade !== null
                    ? round(
                        (float)$progress->averagetotalgrade,
                        2
                    )
                    : null,
            'best_total_grade' =>
                $progress->besttotalgrade !== null
                    ? (int)$progress->besttotalgrade
                    : null,
            'last_progress_at' =>
                (int)($progress->lastprogressat ?? 0),
        ];
    }

    private function validate(
        int $userid,
        int $measuredat
    ): void {
        if ($userid <= 0) {
            throw new \InvalidArgumentException(
                'Poodll learning userid must be greater than zero.'
            );
        }

        if ($measuredat <= 0) {
            throw new \InvalidArgumentException(
                'Poodll learning timestamp must be greater than zero.'
            );
        }
    }

    /**
     * @param array<string,int|float|null> $defaults
     * @param array<string,string> $mapping
     * @return array<string,int|float|null>
     */
    private function normalize_record(
        \stdClass|false $record,
        array $defaults,
        array $mapping
    ): array {
        if (!$record) {
            return $defaults;
        }

        $result = $defaults;

        foreach ($mapping as $source => $target) {
            $value = $record->{$source} ?? null;

            if ($value === null) {
                continue;
            }

            if (
                str_contains($target, 'average') ||
                str_contains($target, 'percentage')
            ) {
                $result[$target] = round(
                    (float)$value,
                    2
                );
            } else {
                $result[$target] = (int)$value;
            }
        }

        return $result;
    }
}