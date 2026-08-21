<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\purchase\action\CommercePurchaseAdminClosureService;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseCustomer;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseListFilter;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseSummary;

final class commerce_sales_workspace_n63_test extends advanced_testcase {
    public function test_manual_closure_policy_only_allows_old_pending_or_failed_sales(): void {
        global $DB;

        $this->resetAfterTest(true);
        $service = new CommercePurchaseAdminClosureService($DB);
        $now = time();

        self::assertTrue($service->can_close(
            $this->summary('failed', $now)
        ));
        self::assertTrue($service->can_close(
            $this->summary('cancelled', $now)
        ));
        self::assertFalse($service->can_close(
            $this->summary('pending', $now - HOURSECS)
        ));
        self::assertTrue($service->can_close(
            $this->summary('redirected', $now - (2 * DAYSECS))
        ));
        self::assertFalse($service->can_close(
            $this->summary('paid', $now - (5 * DAYSECS))
        ));
    }

    public function test_manual_closure_is_persisted_as_separate_crm_overlay(): void {
        global $DB;

        $this->resetAfterTest(true);
        $service = new CommercePurchaseAdminClosureService($DB);

        $DB->insert_record('local_subscriptions_commerce_purchase', (object)[
            'purchaseuuid' => 'n63purchase000000000000000000001',
            'reference' => 'cmp_n63_purchase_1',
            'type' => 'course_access',
            'legacyfamily' => null,
            'legacyid' => null,
            'userid' => null,
            'customeremail' => 'client@example.test',
            'status' => 'payment_pending',
            'currency' => 'EUR',
            'subtotalminor' => 4500,
            'discountminor' => 0,
            'totalminor' => 4500,
            'customerjson' => '{}',
            'snapshotjson' => '{}',
            'metadatajson' => '{}',
            'snapshotversion' => 1,
            'timecreated' => time() - (2 * DAYSECS),
            'timemodified' => time(),
        ]);
        $purchaseid = (int)$DB->get_field(
            'local_subscriptions_commerce_purchase',
            'id',
            ['reference' => 'cmp_n63_purchase_1'],
            MUST_EXIST
        );

        $service->close($purchaseid, 2, 'Customer abandoned checkout');

        self::assertTrue($service->is_closed($purchaseid));
        $state = $DB->get_record(
            CommercePurchaseAdminClosureService::TABLE,
            ['purchaseid' => $purchaseid],
            '*',
            MUST_EXIST
        );
        self::assertSame('closed', $state->state);
        self::assertSame('Customer abandoned checkout', $state->reason);
        self::assertSame(2, (int)$state->closedby);

        $purchase = $DB->get_record(
            'local_subscriptions_commerce_purchase',
            ['id' => $purchaseid],
            '*',
            MUST_EXIST
        );
        self::assertSame('payment_pending', $purchase->status);

        $service->reopen($purchaseid);
        self::assertFalse($service->is_closed($purchaseid));
    }

    public function test_purchase_list_hides_closed_sales_by_default(): void {
        $filter = new CommercePurchaseListFilter();
        self::assertSame('open', $filter->normalized_admin_state());

        $closed = new CommercePurchaseListFilter(adminstate: 'closed');
        self::assertSame('closed', $closed->normalized_admin_state());

        $all = new CommercePurchaseListFilter(adminstate: 'all');
        self::assertSame('all', $all->normalized_admin_state());
    }

    public function test_sales_menu_and_unfinished_checkout_workspace_contract(): void {
        $root = dirname(__DIR__, 3);

        $sales = file_get_contents(
            $root . '/admin/commerce/purchases/index.php'
        );
        $unfinished = file_get_contents(
            $root . '/admin/commerce/unfinished-checkouts/index.php'
        );
        $repository = file_get_contents(
            $root . '/classes/commerce/purchase/readmodel/CommercePurchaseReadRepository.php'
        );
        $followup = file_get_contents(
            $root . '/classes/commerce/mail/sales/CommerceSalesFollowupService.php'
        );

        self::assertStringContainsString(
            "normalized_admin_state()",
            $repository
        );
        self::assertStringContainsString(
            "NOT EXISTS (SELECT 1 FROM {' . self::ADMIN_STATE_TABLE",
            $repository
        );

        foreach ([
            "'customer' => []",
            "'communication' => []",
            "'commercial' => []",
            "'order' => []",
            "'administration' => []",
            'commerce_sales_followup_action',
            'commerce_sales_action_close',
            'commerce_sales_action_reopen',
            'commerce_sales_action_view_customer_order',
            'commerce_purchase_download_invoice',
            'commerce_purchase_open_mail_journal',
        ] as $needle) {
            self::assertStringContainsString($needle, $sales);
        }

        self::assertStringContainsString(
            "if (\$items === [])",
            $sales
        );

        self::assertStringContainsString(
            "'redirected'",
            $followup
        );

        self::assertStringNotContainsString(
            'CommerceSalesNavigationRenderer::UNFINISHED',
            $unfinished
        );
        self::assertStringContainsString(
            'crm-unfinished-table',
            $unfinished
        );
        self::assertStringContainsString(
            'commerce_sales_followup_action',
            $unfinished
        );
        self::assertStringContainsString(
            "'selectpurchase'",
            $unfinished
        );
    }

    public function test_n63_schema_is_explicitly_versioned_because_it_changes_database(): void {
        $root = dirname(__DIR__, 3);
        $install = file_get_contents($root . '/db/install.xml');
        $upgrade = file_get_contents($root . '/db/upgrade.php');
        $version = file_get_contents($root . '/version.php');

        self::assertStringContainsString(
            'TABLE NAME="local_subs_commerce_purchase_admin"',
            $install
        );
        self::assertStringContainsString(
            'TYPE="foreign-unique" FIELDS="purchaseid"',
            $install
        );
        self::assertStringNotContainsString(
            'INDEX NAME="purchase_uix"',
            $install
        );
        self::assertStringContainsString(
            'XMLDB_KEY_FOREIGN_UNIQUE',
            $upgrade
        );
        self::assertStringNotContainsString(
            "add_index('purchase_uix'",
            $upgrade
        );
        self::assertStringContainsString(
            '2026081510',
            $upgrade
        );
        self::assertStringContainsString(
            '$plugin->version = 2026081602;',
            $version
        );
    }

    private function summary(
        string $paymentstatus,
        int $timecreated
    ): CommercePurchaseSummary {
        return new CommercePurchaseSummary(
            1,
            'uuid',
            'cmp-test',
            'course_access',
            new CommercePurchaseCustomer(
                3,
                'client@example.test',
                'Client',
                'Test'
            ),
            ['A1 Full'],
            'EUR',
            4500,
            'pending',
            $paymentstatus,
            'none',
            'stripe',
            'native',
            $timecreated
        );
    }
}
