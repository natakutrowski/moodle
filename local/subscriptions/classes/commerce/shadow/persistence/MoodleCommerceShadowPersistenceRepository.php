<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\shadow\persistence;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\shadow\CommerceLegacyFulfillmentObservation;
use local_subscriptions\commerce\shadow\CommerceShadowComparison;
use local_subscriptions\commerce\shadow\CommerceShadowExecutionReport;

/** Moodle database implementation for immutable Shadow comparisons. */
final class MoodleCommerceShadowPersistenceRepository implements CommerceShadowPersistenceRepository {
    private const TABLE = 'local_subs_commerce_shadow';

    public function save(
        string $entrypoint,
        CommerceLegacyFulfillmentObservation $legacy,
        CommerceShadowExecutionReport $native,
        CommerceShadowComparison $comparison,
        string $classification,
        ?string $errorclass = null,
        ?string $errormessage = null
    ): int {
        global $DB;

        $record = (object) [
            'executionreference' => $native->get_execution_reference(),
            'purchasereference' => $native->get_purchase_reference(),
            'source' => $native->get_source(),
            'entrypoint' => trim($entrypoint),
            'comparisonstatus' => $comparison->get_status(),
            'classification' => trim($classification),
            'legacyjson' => $this->encode($this->legacy_array($legacy)),
            'nativejson' => $this->encode($native->to_array()),
            'differencesjson' => $this->encode($comparison->get_differences()),
            'errorclass' => $errorclass,
            'errormessage' => $errormessage,
            'timestarted' => $native->get_started_at(),
            'timefinished' => $native->get_finished_at(),
            'timecreated' => time(),
        ];

        return (int) $DB->insert_record(self::TABLE, $record);
    }

    private function legacy_array(CommerceLegacyFulfillmentObservation $legacy): array {
        return [
            'purchasereference' => $legacy->get_purchase_reference(),
            'source' => $legacy->get_source(),
            'comparable' => $legacy->is_comparable(),
            'issues' => $legacy->get_issues(),
            'effects' => array_map(
                static fn($effect): array => $effect->canonical_array(),
                $legacy->get_effects()
            ),
        ];
    }

    private function encode(array $value): string {
        $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($encoded === false) {
            throw new \RuntimeException('Unable to encode Commerce Shadow persistence payload.');
        }
        return $encoded;
    }
}
