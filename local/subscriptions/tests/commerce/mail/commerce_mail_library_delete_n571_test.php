<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\mail\library\CommerceMailLibrary;
use local_subscriptions\commerce\mail\library\CommerceMailLibraryRepository;

final class commerce_mail_library_delete_n571_test extends advanced_testcase {
    public function test_archived_template_can_be_permanently_deleted(): void {
        global $DB;

        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user();
        $repository = new CommerceMailLibraryRepository($DB);

        $template = $repository->save([
            'name' => 'Delete me',
            'category' => CommerceMailLibrary::CATEGORY_MARKETING,
            'status' => CommerceMailLibrary::STATUS_ACTIVE,
            'metadata' => ['test' => 'n571'],
        ], [
            'fr' => [
                'subject' => 'Sujet',
                'preheader' => '',
                'bodyhtml' => '<p>Contenu</p>',
            ],
        ], (int)$user->id);

        $repository->archive((int)$template->id, (int)$user->id);
        $repository->delete_archived((int)$template->id);

        self::assertFalse($DB->record_exists(
            'local_subs_mail_library',
            ['id' => (int)$template->id]
        ));
        self::assertFalse($DB->record_exists(
            'local_subs_mail_lib_content',
            ['templateid' => (int)$template->id]
        ));
    }

    public function test_active_template_cannot_be_deleted_directly(): void {
        global $DB;

        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user();
        $repository = new CommerceMailLibraryRepository($DB);

        $template = $repository->save([
            'name' => 'Still active',
            'category' => CommerceMailLibrary::CATEGORY_MARKETING,
            'status' => CommerceMailLibrary::STATUS_ACTIVE,
        ], [
            'fr' => [
                'subject' => 'Sujet',
                'preheader' => '',
                'bodyhtml' => '<p>Contenu</p>',
            ],
        ], (int)$user->id);

        $this->expectException(\coding_exception::class);
        $repository->delete_archived((int)$template->id);
    }

    public function test_n571_ui_and_legacy_config_hotfix_contract(): void {
        $root = dirname(__DIR__, 3);
        $configuration = file_get_contents(
            $root . '/admin/commerce/mail/configuration.php'
        );
        $index = file_get_contents(
            $root . '/admin/commerce/mail/templates/index.php'
        );
        $action = file_get_contents(
            $root . '/admin/commerce/mail/templates/library_action.php'
        );

        foreach ([
            "'legacy_auto_enabled' => \$configbool(",
            "'legacy_payment_reminders' => \$configbool(",
            "'legacy_expiry_reminders' => \$configbool(",
            "'legacy_lifecycle_emails' => \$configbool(",
        ] as $needle) {
            self::assertStringContainsString($needle, $configuration);
        }

        self::assertStringContainsString(
            "CommerceMailLibrary::STATUS_ARCHIVED",
            $index
        );
        self::assertStringContainsString(
            "'action' => 'delete'",
            $index
        );
        self::assertStringContainsString(
            "if (\$action === 'delete')",
            $action
        );
    }
}
