<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\catalog\assets\CommerceCatalogDigitalFileManager;
use local_subscriptions\commerce\catalog\domain\CommerceProduct;
use local_subscriptions\commerce\catalog\validation\CommerceCatalogActivationValidator;
use local_subscriptions\commerce\fulfillment\native\digital\CommerceNativeDigitalDownloadResolver;

final class commerce_795h485_native_digital_files_test extends advanced_testcase {
    public function test_resolver_uses_private_native_catalog_file_without_legacy_mapping(): void {
        global $DB;
        $this->resetAfterTest(true);

        $now = time();
        $productid = (int)$DB->insert_record('local_subs_commerce_product', (object) [
            'sku' => 'DIGITAL.H485.NATIVE',
            'type' => 'digital_download',
            'status' => 'active',
            'name' => 'H4.8.5 Native file',
            'description' => '',
            'metadatajson' => '{}',
            'availablefrom' => null,
            'availableuntil' => null,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);

        $file = get_file_storage()->create_file_from_string([
            'contextid' => \context_system::instance()->id,
            'component' => CommerceCatalogDigitalFileManager::COMPONENT,
            'filearea' => CommerceCatalogDigitalFileManager::FILEAREA,
            'itemid' => $productid,
            'filepath' => '/' . CommerceCatalogDigitalFileManager::ROLE_DESKTOP . '/',
            'filename' => 'native-h485.pdf',
        ], '%PDF-1.4 Native H4.8.5');

        $token = str_repeat('b', 64);
        $DB->insert_record('local_subs_commerce_dig_access', (object) [
            'grantreference' => 'ent-h485-native',
            'idempotencykey' => 'h485:native',
            'purchasereference' => 'cmp-h485-native',
            'productsku' => 'DIGITAL.H485.NATIVE',
            'resourcekey' => 'digital:sku:DIGITAL.H485.NATIVE',
            'beneficiaryuserid' => null,
            'beneficiaryemail' => 'h485@campusfr.test',
            'downloadtoken' => $token,
            'maxdownloads' => null,
            'downloadcount' => 0,
            'validfrom' => $now - 10,
            'validuntil' => null,
            'status' => 'active',
            'lastdownloadat' => null,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);

        $resolved = (new CommerceNativeDigitalDownloadResolver($DB))->resolve($token, $now);

        $this->assertSame('native-h485.pdf', $resolved['filename']);
        $this->assertNull($resolved['filepath']);
        $this->assertInstanceOf(\stored_file::class, $resolved['storedfile']);
        $this->assertSame($file->get_contenthash(), $resolved['storedfile']->get_contenthash());
    }

    public function test_manager_keeps_desktop_and_mobile_files_separate(): void {
        $this->resetAfterTest(true);
        $context = \context_system::instance();
        $manager = new CommerceCatalogDigitalFileManager($context);
        $productid = 987654;

        get_file_storage()->create_file_from_string([
            'contextid' => $context->id,
            'component' => CommerceCatalogDigitalFileManager::COMPONENT,
            'filearea' => CommerceCatalogDigitalFileManager::FILEAREA,
            'itemid' => $productid,
            'filepath' => '/desktop/',
            'filename' => 'desktop.pdf',
        ], 'desktop');
        get_file_storage()->create_file_from_string([
            'contextid' => $context->id,
            'component' => CommerceCatalogDigitalFileManager::COMPONENT,
            'filearea' => CommerceCatalogDigitalFileManager::FILEAREA,
            'itemid' => $productid,
            'filepath' => '/mobile/',
            'filename' => 'mobile.pdf',
        ], 'mobile');

        $this->assertSame('desktop.pdf', $manager->get_file($productid, 'desktop')?->get_filename());
        $this->assertSame('mobile.pdf', $manager->get_file($productid, 'mobile')?->get_filename());
        $this->assertTrue($manager->has_any_file($productid));

        $manager->delete_file($productid, 'mobile');
        $this->assertNull($manager->get_file($productid, 'mobile'));
        $this->assertNotNull($manager->get_file($productid, 'desktop'));
    }
    public function test_activation_validator_accepts_native_digital_file_without_legacy_mapping(): void {
        global $DB;
        $this->resetAfterTest(true);

        $now = time();
        $productid = (int)$DB->insert_record('local_subs_commerce_product', (object) [
            'sku' => 'DIGITAL.H486.VALIDATION',
            'type' => 'digital_download',
            'status' => 'active',
            'name' => 'H4.86 Native validation',
            'description' => '',
            'metadatajson' => '{}',
            'availablefrom' => null,
            'availableuntil' => null,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);

        $DB->insert_record('local_subs_commerce_prod_price', (object) [
            'productid' => $productid,
            'currency' => 'EUR',
            'amountminor' => 1990,
            'active' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);

        get_file_storage()->create_file_from_string([
            'contextid' => \context_system::instance()->id,
            'component' => CommerceCatalogDigitalFileManager::COMPONENT,
            'filearea' => CommerceCatalogDigitalFileManager::FILEAREA,
            'itemid' => $productid,
            'filepath' => '/' . CommerceCatalogDigitalFileManager::ROLE_DESKTOP . '/',
            'filename' => 'native-validation.pdf',
        ], '%PDF-1.4 Native validation');

        $product = new CommerceProduct(
            'DIGITAL.H486.VALIDATION',
            'digital_download',
            'active',
            'H4.86 Native validation',
            '',
            [],
            $productid
        );
        $result = (new CommerceCatalogActivationValidator($DB))->validate($product);
        $codes = array_map(static fn($issue): string => $issue->code, $result->issues);

        $this->assertNotContains('missing_digital', $codes);
        $this->assertNotContains('missing_digital_file', $codes);
        $this->assertTrue($result->is_valid());
    }

}
