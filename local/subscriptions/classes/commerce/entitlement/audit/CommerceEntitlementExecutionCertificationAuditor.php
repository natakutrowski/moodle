<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\entitlement\audit;

defined('MOODLE_INTERNAL') || die();

/**
 * Read-only certification of Native entitlement planning, ledger and type coverage.
 */
final class CommerceEntitlementExecutionCertificationAuditor {
    private const SUPPORTED_TYPES = [
        'course_access',
        'digital_download',
    ];

    public function __construct(
        private readonly \moodle_database $db,
        private readonly CommerceEntitlementPlanningAuditor $planningauditor,
        private readonly CommerceEntitlementLedgerShadowAuditor $ledgerauditor
    ) {
    }

    public function audit(string $language = 'fr'): array {
        $planning = $this->planningauditor->audit($language);
        $ledger = $this->ledgerauditor->audit($language);
        $types = array_values(array_unique(array_map(
            static fn(\stdClass $record): string => strtolower((string)$record->type),
            $this->db->get_records('local_subs_commerce_prod_ent', [], 'type ASC', 'id,type')
        )));
        $unsupported = array_values(array_diff($types, self::SUPPORTED_TYPES));

        return [
            'checked' => (int)$planning['checked'],
            'planned' => (int)$planning['planned'],
            'ledgergrants' => (int)$ledger['grants'],
            'supportedtypes' => $types,
            'unsupportedtypes' => $unsupported,
            'different' => (int)$planning['different'],
            'conflict' => (int)$ledger['conflict'],
            'errors' => array_values(array_merge($planning['errors'], $ledger['errors'])),
            'certified' => $planning['different'] === 0
                && $ledger['conflict'] === 0
                && $unsupported === []
                && $planning['errors'] === []
                && $ledger['errors'] === [],
        ];
    }
}
