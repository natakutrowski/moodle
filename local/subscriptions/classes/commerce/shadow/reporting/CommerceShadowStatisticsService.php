<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\shadow\reporting;

defined('MOODLE_INTERNAL') || die();

/** Read-only statistics over persisted Commerce Shadow comparisons. */
final class CommerceShadowStatisticsService {
    private const TABLE = 'local_subs_commerce_shadow';

    public function summarize(?int $timefrom = null, ?int $timeto = null): CommerceShadowSummary {
        global $DB;

        [$where, $params] = $this->time_condition($timefrom, $timeto);
        $records = $DB->get_records_select(
            self::TABLE,
            $where,
            $params,
            'id ASC',
            'id,source,comparisonstatus,classification,timestarted,timefinished'
        );

        $byclassification = [];
        $bystatus = [];
        $bysource = [];
        $durationtotal = 0;

        foreach ($records as $record) {
            $this->increment($byclassification, (string) $record->classification);
            $this->increment($bystatus, (string) $record->comparisonstatus);
            $this->increment($bysource, (string) $record->source);
            $durationtotal += max(0, ((int) $record->timefinished - (int) $record->timestarted) * 1000);
        }

        ksort($byclassification);
        ksort($bystatus);
        ksort($bysource);
        $total = count($records);

        return new CommerceShadowSummary(
            $total,
            $byclassification,
            $bystatus,
            $bysource,
            $total > 0 ? (int) round($durationtotal / $total) : 0
        );
    }

    private function time_condition(?int $timefrom, ?int $timeto): array {
        $conditions = [];
        $params = [];
        if ($timefrom !== null && $timefrom > 0) {
            $conditions[] = 'timecreated >= :timefrom';
            $params['timefrom'] = $timefrom;
        }
        if ($timeto !== null && $timeto > 0) {
            $conditions[] = 'timecreated <= :timeto';
            $params['timeto'] = $timeto;
        }
        return [$conditions === [] ? '1 = 1' : implode(' AND ', $conditions), $params];
    }

    private function increment(array &$values, string $key): void {
        $values[$key] = ($values[$key] ?? 0) + 1;
    }
}
