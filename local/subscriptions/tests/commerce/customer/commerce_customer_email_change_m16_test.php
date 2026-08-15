<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\customer\identity\CommerceCustomerEmailChangeService;

final class commerce_customer_email_change_m16_test extends advanced_testcase {
    public function test_change_updates_current_moodle_email_and_preserves_explicit_history_policy(): void {
        global $DB, $CFG;
        $this->resetAfterTest(true);

        $user = $this->getDataGenerator()->create_user(['email' => 'old@example.test']);
        $actor = $this->getDataGenerator()->create_user();
        $service = new CommerceCustomerEmailChangeService($DB);

        // PHPUnit dataroot may only expose the English core language pack even when the
        // real CampusFR site has FR/RU installed. Always use a language that Moodle reports
        // as installed so this test validates the service rather than the test environment.
        $translations = get_string_manager()->get_list_of_translations();
        $installedlang = (string)array_key_first($translations);
        $this->assertNotSame('', $installedlang);

        $result = $service->change((int)$user->id, 'New@Example.Test', (int)$actor->id, $installedlang);

        $this->assertTrue($result['changed']);
        $this->assertSame('new@example.test', $DB->get_field('user', 'email', ['id' => $user->id]));
        $this->assertSame($installedlang, $DB->get_field('user', 'lang', ['id' => $user->id]));
        $this->assertTrue($result['emailchanged']);
        $this->assertSame((string)$user->lang !== $installedlang, $result['langchanged']);
        $this->assertSame(1, $DB->count_records('local_subscriptions_admin_tool_run', [
            'toolkey' => CommerceCustomerEmailChangeService::TOOL_KEY,
            'actorid' => $actor->id,
            'status' => 'success',
        ]));

        $source = file_get_contents($CFG->dirroot . '/local/subscriptions/classes/commerce/customer/identity/CommerceCustomerEmailChangeService.php');
        $this->assertStringContainsString("'local_subs_commerce_grant'", $source);
        $this->assertStringContainsString("'local_subs_commerce_dig_access'", $source);
        $this->assertStringContainsString("'local_subs_commerce_offer'", $source);
        $this->assertStringContainsString("'local_subscriptions_commerce_purchase'", $source);
        $this->assertStringContainsString('HISTORICAL_TABLES', $source);
    }

    public function test_change_rejects_email_already_owned_by_another_active_account(): void {
        global $DB;
        $this->resetAfterTest(true);

        $user = $this->getDataGenerator()->create_user(['email' => 'first@example.test']);
        $other = $this->getDataGenerator()->create_user(['email' => 'second@example.test']);
        $service = new CommerceCustomerEmailChangeService($DB);

        $this->expectException(\moodle_exception::class);
        $service->preview((int)$user->id, (string)$other->email);
    }

    public function test_change_rejects_language_that_is_not_installed(): void {
        global $DB;
        $this->resetAfterTest(true);

        $user = $this->getDataGenerator()->create_user(['email' => 'language@example.test']);
        $service = new CommerceCustomerEmailChangeService($DB);

        $this->expectException(\invalid_parameter_exception::class);
        $service->preview((int)$user->id, (string)$user->email, 'zz-not-installed');
    }

    public function test_user360_exposes_controlled_email_change_action(): void {
        global $CFG;
        $builder = file_get_contents($CFG->dirroot . '/local/subscriptions/classes/crm/user/UserProfileActionBuilder.php');
        $config = file_get_contents($CFG->dirroot . '/local/subscriptions/classes/subscription_config.php');
        $page = file_get_contents($CFG->dirroot . '/local/subscriptions/admin/users/change_email.php');

        $this->assertStringContainsString("'changeemail'", $builder);
        $this->assertStringContainsString('admin_user_change_email_page()', $builder);
        $this->assertStringContainsString('admin_user_change_email_page(): string', $config);
        $this->assertStringContainsString('CommerceCustomerEmailChangeService', $page);
        $this->assertStringContainsString('commerce_customer_email_change_preview', $page);
        $this->assertStringContainsString('confirmchange', $page);
        $this->assertStringContainsString('newlang', $page);
        $this->assertStringContainsString('preview_impacted_title', $page);
        $this->assertStringContainsString('preview_preserved_title', $page);
    }
}