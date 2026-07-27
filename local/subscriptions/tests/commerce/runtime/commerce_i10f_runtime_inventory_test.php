<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\rollout\CommerceRuntimeWriteInventory;

final class commerce_i10f_runtime_inventory_test extends advanced_testcase {
    public function test_non_commerce_crm_write_is_ignored(): void {
        $directory = make_request_directory();

        mkdir($directory . '/classes/crm/inbox', 0777, true);

        file_put_contents(
            $directory . '/classes/crm/inbox/repository.php',
            "<?php \$DB->update_record('local_subscriptions_inbox_message', \$record);"
        );

        $inventory = new CommerceRuntimeWriteInventory();

        $this->assertCount(0, $inventory->scan($directory));
    }

    public function test_native_and_compatibility_writes_are_distinguished(): void {
        $directory = make_request_directory();

        mkdir($directory . '/classes/commerce/persistence/sql', 0777, true);
        mkdir($directory . '/classes/domain', 0777, true);

        file_put_contents(
            $directory . '/classes/commerce/persistence/sql/writer.php',
            "<?php \$DB->insert_record('local_subscriptions_commerce_purchase', \$record);"
        );

        file_put_contents(
            $directory . '/classes/domain/PaymentService.php',
            "<?php \$DB->update_record('subscription_payment_request', \$record);"
        );

        $inventory = new CommerceRuntimeWriteInventory();
        $findings = $inventory->scan($directory);
        $counts = $inventory->count_by_classification($findings);

        $this->assertSame(
            1,
            $counts[CommerceRuntimeWriteInventory::CLASS_NATIVE]
        );

        $this->assertSame(
            1,
            $counts[CommerceRuntimeWriteInventory::CLASS_COMPATIBILITY]
        );

        $this->assertSame(
            0,
            $counts[CommerceRuntimeWriteInventory::CLASS_MIGRATION_CANDIDATE]
        );
    }

    public function test_unknown_legacy_commerce_writer_is_a_migration_candidate(): void {
        $directory = make_request_directory();

        mkdir($directory . '/classes/custom', 0777, true);

        file_put_contents(
            $directory . '/classes/custom/writer.php',
            "<?php \$DB->update_record('user_subscription', \$record);"
        );

        $inventory = new CommerceRuntimeWriteInventory();
        $findings = $inventory->scan($directory);
        $candidates = $inventory->migration_candidates($findings);

        $this->assertCount(1, $candidates);
        $this->assertSame(
            'classes/custom/writer.php',
            $candidates[0]->get_file()
        );
    }
}
