<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\mail\CommerceMailType;
use local_subscriptions\commerce\mail\library\CommerceMailLibrary;
use local_subscriptions\commerce\mail\library\CommerceMailLibraryExporter;
use local_subscriptions\commerce\mail\library\CommerceMailLibraryImporter;
use local_subscriptions\commerce\mail\library\CommerceMailLibraryRepository;
use local_subscriptions\commerce\mail\template\studio\CommerceMailTemplateRepository;
use local_subscriptions\commerce\mail\transactional\CommerceTransactionalMailStudioBridge;

final class commerce_mail_transactional_studio_n55_test extends advanced_testcase {
    public function test_transactional_template_migrates_to_linked_mail_studio_document(): void {
        global $DB;

        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user();

        $legacy = new CommerceMailTemplateRepository($DB);
        $legacy->save([
            'mailtype' => CommerceMailType::PAYMENT_FAILED,
            'language' => 'fr',
            'enabled' => 1,
            'subject' => 'Legacy subject {order_reference}',
            'preheader' => 'Legacy preheader',
            'heading' => 'Legacy heading',
            'introhtml' => '<p>Legacy intro {firstname}</p>',
            'outrohtml' => '<p>Legacy outro</p>',
            'signaturehtml' => '<p>Legacy signature</p>',
            'headerimage' => 0,
        ], (int)$user->id);

        $bridge = CommerceTransactionalMailStudioBridge::create($DB);
        $template = $bridge->migrate(
            CommerceMailType::PAYMENT_FAILED,
            (int)$user->id
        );

        self::assertSame(
            'transactional:' . CommerceMailType::PAYMENT_FAILED,
            (string)$template->runtimekey
        );
        self::assertSame(
            CommerceMailLibrary::CATEGORY_TRANSACTIONAL,
            (string)$template->category
        );
        self::assertSame(
            CommerceMailLibrary::STATUS_ACTIVE,
            (string)$template->status
        );

        $library = new CommerceMailLibraryRepository($DB);
        $contents = $library->contents((int)$template->id);
        $document = json_decode((string)$contents['fr']->contentjson, true);

        self::assertSame('transactional_editorial', $document['mode']);
        self::assertSame('Legacy heading', $document['heading']);
        self::assertSame('<p>Legacy intro {firstname}</p>', $document['introhtml']);
        self::assertSame('<p>Legacy outro</p>', $document['outrohtml']);
        self::assertSame('<p>Legacy signature</p>', $document['signaturehtml']);

        $resolved = $bridge->resolve(CommerceMailType::PAYMENT_FAILED, 'fr');
        self::assertNotNull($resolved);
        self::assertSame('Legacy subject {order_reference}', $resolved['subject']);
        self::assertSame('Legacy heading', $resolved['heading']);
    }

    public function test_mail_studio_transactional_edit_becomes_runtime_authority(): void {
        global $DB;

        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user();
        $bridge = CommerceTransactionalMailStudioBridge::create($DB);
        $template = $bridge->migrate(
            CommerceMailType::PAYMENT_CANCELLED,
            (int)$user->id
        );

        $library = new CommerceMailLibraryRepository($DB);
        $library->save([
            'name' => (string)$template->name,
            'category' => CommerceMailLibrary::CATEGORY_TRANSACTIONAL,
            'status' => CommerceMailLibrary::STATUS_ACTIVE,
            'runtimekey' => (string)$template->runtimekey,
            'metadata' => ['foundation' => 'N5.5'],
        ], [
            'fr' => [
                'subject' => 'Mail Studio {{order_reference}}',
                'preheader' => 'Studio preheader',
                'bodyhtml' => '<p>Studio intro {{firstname}}</p>',
                'document' => [
                    'mode' => 'transactional_editorial',
                    'bodyhtml' => '<p>Studio intro {{firstname}}</p>',
                    'heading' => 'Studio heading',
                    'introhtml' => '<p>Studio intro {{firstname}}</p>',
                    'outrohtml' => '<p>Studio outro</p>',
                    'signaturehtml' => '<p>Studio signature</p>',
                    'headerimage' => 0,
                    'blocks' => [],
                ],
            ],
        ], (int)$user->id, (int)$template->id);

        $resolved = $bridge->resolve(
            CommerceMailType::PAYMENT_CANCELLED,
            'fr'
        );
        self::assertNotNull($resolved);
        self::assertSame('Mail Studio {{order_reference}}', $resolved['subject']);
        self::assertSame('Studio heading', $resolved['heading']);
        self::assertStringContainsString('{{firstname}}', $resolved['introhtml']);

        $runtime = file_get_contents(
            dirname(__DIR__, 3)
                . '/classes/commerce/mail/template/AbstractCommerceMailTemplate.php'
        );
        self::assertStringContainsString(
            'CommerceTransactionalMailStudioBridge::create',
            $runtime
        );
        self::assertStringContainsString(
            '$mailstudioeditorial',
            $runtime
        );
    }

    public function test_personal_offer_is_not_taken_over_by_transactional_bridge(): void {
        self::assertNotContains(
            CommerceMailType::PERSONAL_OFFER,
            CommerceTransactionalMailStudioBridge::supported_types()
        );
    }

    public function test_transactional_export_import_preserves_structured_editorial_zones(): void {
        global $DB;

        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user();
        $bridge = CommerceTransactionalMailStudioBridge::create($DB);
        $template = $bridge->migrate(
            CommerceMailType::PAYMENT_PENDING,
            (int)$user->id
        );

        $library = new CommerceMailLibraryRepository($DB);
        $exporter = new CommerceMailLibraryExporter($DB, $library);
        $payload = $exporter->native((int)$template->id);

        self::assertSame(
            'transactional_editorial',
            $payload['translations']['fr']['content']['mode']
        );
        self::assertArrayHasKey(
            'signaturehtml',
            $payload['translations']['fr']['content']
        );

        $importer = new CommerceMailLibraryImporter($library);
        $imported = $importer->import_json(
            json_encode(
                $payload,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            ),
            (int)$user->id
        );
        self::assertEmpty($imported->runtimekey);

        $contents = $library->contents((int)$imported->id);
        $document = json_decode((string)$contents['fr']->contentjson, true);
        self::assertSame('transactional_editorial', $document['mode']);
        self::assertArrayHasKey('introhtml', $document);
        self::assertArrayHasKey('outrohtml', $document);
        self::assertArrayHasKey('signaturehtml', $document);
    }

    public function test_n55_ui_schema_and_n54_test_regressions_contract(): void {
        $root = dirname(__DIR__, 3);

        $install = file_get_contents($root . '/db/install.xml');
        $upgrade = file_get_contents($root . '/db/upgrade.php');
        $libraryeditor = file_get_contents(
            $root . '/admin/commerce/mail/templates/library_edit.php'
        );
        $libraryindex = file_get_contents(
            $root . '/admin/commerce/mail/templates/index.php'
        );
        $n54test = file_get_contents(
            $root . '/tests/commerce/mail/commerce_mail_marketing_campaign_n54_test.php'
        );

        self::assertStringContainsString(
            '<FIELD NAME="runtimekey" TYPE="char" LENGTH="191" NOTNULL="false" />',
            $install
        );
        self::assertStringContainsString(
            '<INDEX NAME="runtimekey_uix" UNIQUE="true" FIELDS="runtimekey" />',
            $install
        );
        self::assertStringContainsString('2026081504', $upgrade);
        self::assertStringContainsString('transactional_editorial', $libraryeditor);
        self::assertStringContainsString('transactional_migrate.php', $libraryindex);

        // The N5.4 test must neither interpolate test-source variables nor
        // leave scheduled-task mtrace as unexpected output.
        self::assertStringContainsString(
            '\'$dbman->add_key($table, $key);\'',
            $n54test
        );
        self::assertStringContainsString('expectOutputRegex', $n54test);
    }
}
