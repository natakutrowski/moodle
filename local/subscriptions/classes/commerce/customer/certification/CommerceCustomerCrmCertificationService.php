<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\customer\certification;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\customer\analytics\CommerceCustomerAnalyticsRepository;
use local_subscriptions\commerce\persistence\CommercePersistenceSchema;

/** Read-only certification of the Native CRM customer data path. */
final class CommerceCustomerCrmCertificationService {
    public function __construct(private readonly \moodle_database $db) {}

    public function certify(): CommerceCustomerCrmCertificationReport {
        $findings = [];
        foreach ([
            CommercePersistenceSchema::TABLE_PURCHASE,
            CommercePersistenceSchema::TABLE_ITEM,
            CommercePersistenceSchema::TABLE_PAYMENT,
            'local_subs_commerce_grant',
        ] as $table) {
            $findings[] = $this->finding(
                $this->db->get_manager()->table_exists($table) ? 'OK' : 'ERROR',
                'Database table ' . $table,
                $this->db->get_manager()->table_exists($table) ? 'Available.' : 'Missing.'
            );
        }

        $required = [
            'classes/commerce/customer/readmodel/CommerceCustomerReadService.php',
            'classes/commerce/customer/crm/CommerceCustomerCrmAdapter.php',
            'classes/commerce/customer/crm/CommerceCustomerTimelineCollector.php',
            'classes/commerce/customer/analytics/CommerceCustomerAnalyticsRepository.php',
            'classes/dashboard/repositories/DashboardStatsRepository.php',
            'classes/dashboard/funnel/DashboardFunnelRepository.php',
            'classes/crm/user/explorer/UserExplorerRepository.php',
        ];
        $missing = array_values(array_filter($required, static fn(string $path): bool => !is_file(__DIR__ . '/../../../../' . $path)));
        $findings[] = $this->finding(
            $missing === [] ? 'OK' : 'ERROR',
            'Unified CRM components',
            $missing === [] ? count($required) . ' required components available.' : 'Missing: ' . implode(', ', $missing)
        );

        $now = time();
        $analytics = (new CommerceCustomerAnalyticsRepository($this->db))->snapshot($now - 30 * DAYSECS, $now + 1);
        $findings[] = $this->finding(
            'OK',
            'Native Commerce analytics',
            sprintf(
                'purchases=%d successful=%d failed=%d fulfilled=%d guests=%d attached=%d',
                $analytics->purchasecount,
                $analytics->successfulpurchasecount,
                $analytics->failedpurchasecount,
                $analytics->fulfilledpurchasecount,
                $analytics->guestpurchasecount,
                $analytics->attachedguestcount
            )
        );
        $findings[] = $this->finding(
            'OK',
            'Revenue currencies',
            $analytics->revenuebycurrency === [] ? 'No Native revenue in the last 30 days.' : implode(', ', array_keys($analytics->revenuebycurrency))
        );

        return new CommerceCustomerCrmCertificationReport($findings);
    }

    /** @return array{status:string,label:string,detail:string} */
    private function finding(string $status, string $label, string $detail): array {
        return compact('status', 'label', 'detail');
    }
}
