<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\mail\CommerceMailType;
use local_subscriptions\commerce\mail\library\CommerceMailLibrary;
use local_subscriptions\commerce\mail\library\CommerceMailLibraryExporter;
use local_subscriptions\commerce\mail\library\CommerceMailLibraryImporter;
use local_subscriptions\commerce\mail\library\CommerceMailLibraryRepository;

final class commerce_mail_library_n51_test extends \advanced_testcase {
    public function test_native_template_roundtrip_export_import_is_safe_draft_copy(): void {
        global $DB;
        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user();
        $repository = new CommerceMailLibraryRepository($DB);
        $record = $repository->save([
            'name' => 'Summer launch',
            'category' => CommerceMailLibrary::CATEGORY_MARKETING,
            'status' => CommerceMailLibrary::STATUS_ACTIVE,
            'metadata' => ['channel' => 'email'],
        ], [
            'fr' => ['subject' => 'Bonjour', 'preheader' => 'Préheader', 'bodyhtml' => '<p>FR</p>'],
            'en' => ['subject' => 'Hello', 'preheader' => 'Preview', 'bodyhtml' => '<p>EN</p>'],
        ], (int)$user->id);

        $exporter = new CommerceMailLibraryExporter($DB, $repository);
        $payload = $exporter->native((int)$record->id);
        self::assertSame(CommerceMailLibrary::SCHEMA, $payload['schema']);
        self::assertSame('Summer launch', $payload['template']['name']);
        self::assertSame('<p>FR</p>', $payload['translations']['fr']['content']['bodyhtml']);

        $copy = (new CommerceMailLibraryImporter($repository))->import_json(
            json_encode($payload, JSON_THROW_ON_ERROR),
            (int)$user->id
        );
        self::assertNotSame((int)$record->id, (int)$copy->id);
        self::assertNotSame((string)$record->templateuuid, (string)$copy->templateuuid);
        self::assertSame(CommerceMailLibrary::STATUS_DRAFT, (string)$copy->status);
        self::assertStringContainsString('import', (string)$copy->name);
        $copycontents = $repository->contents((int)$copy->id);
        self::assertSame('Bonjour', (string)$copycontents['fr']->subject);
    }

    public function test_transactional_runtime_template_exports_without_changing_runtime_storage(): void {
        global $DB;
        $this->resetAfterTest(true);
        $repository = new CommerceMailLibraryRepository($DB);
        $payload = (new CommerceMailLibraryExporter($DB, $repository))
            ->transactional(CommerceMailType::PURCHASE_RECEIPT);
        self::assertSame(CommerceMailLibrary::CATEGORY_TRANSACTIONAL, $payload['template']['category']);
        self::assertSame(CommerceMailLibrary::SOURCE_TRANSACTIONAL, $payload['template']['source']);
        self::assertSame('transactional_editorial', $payload['translations']['fr']['content']['mode']);
        self::assertFalse($DB->record_exists('local_subs_mail_library', []));
    }

    public function test_n51_runtime_rendering_is_not_rewired_to_library_yet(): void {
        $root = dirname(__DIR__, 3);
        $abstract = file_get_contents($root . '/classes/commerce/mail/template/AbstractCommerceMailTemplate.php');
        $librarypage = file_get_contents($root . '/admin/commerce/mail/templates/index.php');
        self::assertIsString($abstract);
        self::assertIsString($librarypage);
        self::assertStringNotContainsString('CommerceMailLibraryRepository', $abstract);
        self::assertStringContainsString('CommerceMailTemplateRepository', $abstract);
        self::assertStringContainsString('CommerceMailLibraryRepository', $librarypage);
        self::assertStringContainsString('commerce_mail_library_runtime_title', $librarypage);
    }
}
