<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use context_system;
use local_subscriptions\commerce\mail\CommerceMailType;
use local_subscriptions\commerce\mail\library\CommerceMailLibraryHeaderImageService;
use local_subscriptions\commerce\mail\library\CommerceMailLibraryRepository;
use local_subscriptions\commerce\mail\template\studio\CommerceMailHeaderImageService;
use local_subscriptions\commerce\mail\template\studio\CommerceMailTemplateRepository;
use local_subscriptions\commerce\mail\transactional\CommerceTransactionalMailStudioBridge;

final class commerce_mail_transactional_consolidation_n56_test extends advanced_testcase {
    public function test_migration_copies_legacy_header_image_to_mail_studio_storage(): void {
        global $DB;

        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user();
        $legacy = new CommerceMailTemplateRepository($DB);
        $legacyrecord = $legacy->save([
            'mailtype' => CommerceMailType::PAYMENT_FAILED,
            'language' => 'fr',
            'enabled' => 1,
            'subject' => 'Image test',
            'preheader' => '',
            'heading' => 'Image',
            'introhtml' => '<p>Test</p>',
            'outrohtml' => '',
            'signaturehtml' => '',
            'headerimage' => 1,
        ], (int)$user->id);

        $fs = get_file_storage();
        $fs->create_file_from_string([
            'contextid' => context_system::instance()->id,
            'component' => 'local_subscriptions',
            'filearea' => CommerceMailHeaderImageService::FILEAREA,
            'itemid' => (int)$legacyrecord->id,
            'filepath' => '/',
            'filename' => 'header.png',
        ], 'fake-image-content');

        $bridge = CommerceTransactionalMailStudioBridge::create($DB);
        $template = $bridge->migrate(
            CommerceMailType::PAYMENT_FAILED,
            (int)$user->id
        );

        $library = new CommerceMailLibraryRepository($DB);
        $contents = $library->contents((int)$template->id);
        self::assertArrayHasKey('fr', $contents);
        $contentid = (int)$contents['fr']->id;

        self::assertNotSame(
            '',
            CommerceMailLibraryHeaderImageService::url($contentid)
        );

        $resolved = $bridge->resolve(CommerceMailType::PAYMENT_FAILED, 'fr');
        self::assertNotNull($resolved);
        self::assertTrue($resolved['headerimage']);
        self::assertStringContainsString(
            '/commerce_mail_library_header/' . $contentid . '/',
            $resolved['headerimageurl']
        );
        self::assertSame(0, $resolved['templateid']);
    }

    public function test_n56_legacy_editor_redirect_and_bulk_migration_contract(): void {
        $root = dirname(__DIR__, 3);

        $legacyeditor = file_get_contents(
            $root . '/admin/commerce/mail/templates/edit.php'
        );
        $bulk = file_get_contents(
            $root . '/admin/commerce/mail/templates/transactional_migrate_all.php'
        );
        $index = file_get_contents(
            $root . '/admin/commerce/mail/templates/index.php'
        );
        $pluginfile = file_get_contents($root . '/lib.php');

        self::assertStringContainsString(
            'CommerceTransactionalMailStudioBridge::create',
            $legacyeditor
        );
        self::assertStringContainsString(
            '/local/subscriptions/admin/commerce/mail/templates/library_edit.php',
            $legacyeditor
        );
        self::assertStringContainsString(
            'CommerceTransactionalMailStudioBridge::supported_types()',
            $bulk
        );
        self::assertStringContainsString(
            'transactional_migrate_all.php',
            $index
        );
        self::assertStringContainsString(
            'CommerceMailLibraryHeaderImageService::FILEAREA',
            $pluginfile
        );
    }

    public function test_n55_fresh_install_schema_contains_runtime_key_and_unique_index(): void {
        $root = dirname(__DIR__, 3);
        $install = file_get_contents($root . '/db/install.xml');

        self::assertStringContainsString(
            '<FIELD NAME="runtimekey" TYPE="char" LENGTH="191" NOTNULL="false" />',
            $install
        );
        self::assertStringContainsString(
            '<INDEX NAME="runtimekey_uix" UNIQUE="true" FIELDS="runtimekey" />',
            $install
        );
    }

    public function test_n51_transactional_export_test_tracks_normalised_format(): void {
        $root = dirname(__DIR__, 3);
        $n51 = file_get_contents(
            $root . '/tests/commerce/mail/commerce_mail_library_n51_test.php'
        );

        self::assertStringContainsString(
            "self::assertSame('transactional_editorial'",
            $n51
        );
        self::assertStringNotContainsString(
            "self::assertSame('legacy_transactional_editorial'",
            $n51
        );
    }
}
