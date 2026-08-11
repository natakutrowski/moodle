<?php

declare(strict_types=1);

namespace local_subscriptions\crm\user\explorer;

defined('MOODLE_INTERNAL') || die();

final class UserExplorerLegacyGuestM1DETest extends \advanced_testcase {

    public function test_paid_legacy_buyer_without_moodle_account_is_exposed_once(): void {
        $this->resetAfterTest(true);
        $email = 'legacy.explorer@example.test';
        $this->create_purchase($email, 'Legacy', 'Explorer', 'paid', 990, 1700000000);
        $this->create_purchase(strtoupper($email), 'Legacy', 'Explorer', 'completed', 1500, 1700001000);

        $repo = new UserExplorerLegacyGuestRepository();
        $criteria = new UserExplorerCriteria(query: 'legacy.explorer');
        $records = $repo->get_records($criteria, 25);

        self::assertSame(1, $repo->count($criteria));
        self::assertCount(1, $records);
        self::assertSame($email, $records[0]->email);
        self::assertSame(2, (int)$records[0]->purchasecount);
        self::assertSame(2490, (int)$records[0]->revenueeurminor);
        self::assertSame(1, (int)$records[0]->iscommerceguest);
        self::assertSame(0, (int)$records[0]->id);
    }

    public function test_moodle_account_with_same_email_reconciles_and_removes_guest_row(): void {
        $this->resetAfterTest(true);
        $email = 'reconciled.explorer@example.test';
        $this->create_purchase($email, 'Before', 'Account', 'paid', 990, 1700000000);

        $repo = new UserExplorerLegacyGuestRepository();
        $criteria = new UserExplorerCriteria(query: 'reconciled.explorer');
        self::assertSame(1, $repo->count($criteria));

        $this->getDataGenerator()->create_user([
            'email' => strtoupper($email),
            'firstname' => 'Canonical',
            'lastname' => 'Moodle',
        ]);

        self::assertSame(0, $repo->count($criteria));
        self::assertSame([], $repo->get_records($criteria, 25));
    }

    public function test_pending_or_failed_legacy_requests_are_not_customer_identities(): void {
        $this->resetAfterTest(true);
        $this->create_purchase('pending@example.test', 'Pending', 'Buyer', 'pending', 990, 1700000000);
        $this->create_purchase('failed@example.test', 'Failed', 'Buyer', 'failed', 990, 1700000100);

        $repo = new UserExplorerLegacyGuestRepository();
        self::assertSame(0, $repo->count(new UserExplorerCriteria(query: '@example.test')));
    }

    public function test_account_specific_filters_do_not_misclassify_commerce_only_identity(): void {
        $this->resetAfterTest(true);
        $this->create_purchase('filters@example.test', 'Filter', 'Guest', 'paid', 990, 1700000000);
        $repo = new UserExplorerLegacyGuestRepository();

        self::assertSame(1, $repo->count(new UserExplorerCriteria(haspurchase: UserExplorerCriteria::PRESENCE_YES)));
        self::assertSame(0, $repo->count(new UserExplorerCriteria(haspurchase: UserExplorerCriteria::PRESENCE_NO)));
        self::assertSame(0, $repo->count(new UserExplorerCriteria(hassubscription: UserExplorerCriteria::PRESENCE_YES)));
        self::assertSame(0, $repo->count(new UserExplorerCriteria(accountstatus: UserExplorerCriteria::ACCOUNT_ACTIVE)));
    }

    public function test_renderer_links_commerce_only_identity_to_email_user360(): void {
        global $CFG;
        $source = file_get_contents($CFG->dirroot . '/local/subscriptions/classes/crm/user/explorer/UserExplorerRenderer.php');
        self::assertIsString($source);
        self::assertStringContainsString("['email' => (string)\$user->email]", $source);
        self::assertStringContainsString('iscommerceguest', $source);
    }

    private function create_purchase(
        string $email,
        string $firstname,
        string $lastname,
        string $status,
        int $amountminor,
        int $created
    ): int {
        global $DB;
        static $productid = null;

        if ($productid === null || !$DB->record_exists('subscription_digital_product', ['id' => $productid])) {
            $productid = $DB->insert_record('subscription_digital_product', (object)[
                'slug' => 'm1de-product-' . $created,
                'name' => 'M1DE Product',
                'filename' => 'm1de.pdf',
            ]);
        }

        return (int)$DB->insert_record('subscription_digital_payment_request', (object)[
            'productid' => $productid,
            'userid' => null,
            'email' => $email,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'currency' => 'EUR',
            'price' => $amountminor / 100,
            'amount_minor' => $amountminor,
            'payment_provider' => 'legacy',
            'status' => $status,
            'creation_date' => $created,
            'last_update' => $created,
            'payment_date' => $status === 'paid' || $status === 'completed' ? $created : null,
        ]);
    }
}
