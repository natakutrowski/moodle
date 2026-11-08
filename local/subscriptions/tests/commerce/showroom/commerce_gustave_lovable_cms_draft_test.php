<?php
// Tests for persistent, nonpublishable Lovable CMS drafts.
declare(strict_types=1);
namespace local_subscriptions\tests\commerce\showroom;
defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\bridge\CommerceShowroomLovableDraftService as Draft;
use local_subscriptions\commerce\showroom\bridge\CommerceShowroomBridgePreviewService as Preview;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomCmsRepository;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomPublicationService;

final class commerce_gustave_lovable_cms_draft_test extends \advanced_testcase {
    private function fixture_zip(string $id): string {
        $path = make_request_directory() . '/campusfr-test-' . bin2hex(random_bytes(4)) . '.zip';
        $zip = new \ZipArchive();
        self::assertTrue($zip->open($path, \ZipArchive::CREATE) === true);
        $prefix = 'draft/' . $id . '/';
        $zip->addFromString('NON_PUBLIABLE.txt', 'NOT FOR PUBLICATION');
        $zip->addFromString($prefix . 'manifest.json', json_encode([
            'id' => $id, 'entry' => 'dist/main.js', 'css' => 'dist/style.css',
            'content' => 'content/ru.json',
        ]));
        foreach (['ru', 'fr', 'en'] as $language) {
            $zip->addFromString($prefix . 'content/' . $language . '.json',
                json_encode(['page' => ['hero_001' => 'Привет', 'hero_002' => 'Мир']], JSON_UNESCAPED_UNICODE));
        }
        $zip->addFromString($prefix . 'dist/style.css', ':host{display:block;color:white}');
        $zip->addFromString($prefix . 'dist/main.js',
            'const api="campusfr-showroom-bridge/v1";const host="data-campusfr-bridge";'
            . 'new URL("assets/test.png",import.meta.url);');
        $zip->addFromString($prefix . 'dist/assets/test.png', "\x89PNG\r\n\x1a\n");
        $zip->close();
        return $path;
    }

    public function test_real_draft_persists_file_and_catalogues_and_blocks_publication(): void {
        global $DB;
        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user();
        $archive = $this->fixture_zip('campusfr_test_draft');
        $id = Draft::create_from_ready_archive($DB, $user, $archive, 'campusfr_test_draft');
        $repository = new CommerceShowroomCmsRepository($DB);
        $record = $repository->get($id);
        self::assertSame('draft', $record->status);
        self::assertSame('', (string)$record->slugru);
        // The CMS repository normalises both an empty JSON object ({}) and an empty
        // JSON list ([]) to [] when decoding to associative arrays. The important
        // contract is that the draft has no purchasable products, not the spelling.
        self::assertSame([], json_decode((string)$record->productsjson, true, 512, JSON_THROW_ON_ERROR));
        self::assertCount(0, $repository->blocks($id));
        $meta = Draft::metadata($record);
        self::assertSame('Привет', $meta['catalogues']['ru']['hero_001']);
        self::assertSame([], $meta['catalogues']['fr']);
        $file = Draft::require_archive($id, $meta);
        self::assertSame('compiled.zip', $file->get_filename());
        $materialised = Draft::materialise_archive($file, $meta);
        self::assertStringContainsString('fonts.googleapis.com', Preview::render_frame($materialised, $meta['designid']));
        @unlink($materialised);
        Draft::save_texts($DB, $id, 'fr', ['hero_001' => 'Bonjour', 'hero_002' => 'Monde'], (int)$user->id);
        [$record, $meta] = Draft::require_draft($DB, $id);
        self::assertSame('Bonjour', Draft::displayed_page($meta, 'fr')['page']['hero_001']);
        self::assertStringContainsString('Bonjour', Preview::render_frame(
            $archive, $meta['designid'], Draft::displayed_page($meta, 'fr')));
        $publication = new CommerceShowroomPublicationService($DB, $repository);
        $this->expectException(\invalid_parameter_exception::class);
        $publication->submit_for_review($id, (int)$user->id, 'test prohibited');
    }

    public function test_duplicate_cannot_overwrite_existing_draft(): void {
        global $DB;
        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user();
        $archive = $this->fixture_zip('campusfr_test_duplicate');
        Draft::create_from_ready_archive($DB, $user, $archive, 'campusfr_test_duplicate');
        $this->expectException(\invalid_parameter_exception::class);
        Draft::create_from_ready_archive($DB, $user, $archive, 'campusfr_test_duplicate');
    }

    public function test_no_public_checkout_or_untrusted_script_access(): void {
        global $CFG;
        $root = $CFG->dirroot . '/local/subscriptions/';
        $frame = file_get_contents($root . 'admin/commerce/showrooms/lovable_cms_frame.php');
        self::assertStringContainsString("connect-src 'none'", $frame);
        self::assertStringContainsString("form-action 'none'", $frame);
        self::assertStringContainsString("sandbox allow-scripts", $frame);
        self::assertStringContainsString('fonts.gstatic.com', $frame);
        self::assertStringNotContainsString('allow-same-origin', $frame);
        $publish = file_get_contents($root . 'classes/commerce/showroom/cms/CommerceShowroomPublicationService.php');
        self::assertStringContainsString('campusfr_lovable_draft', $publish);
        $preview = file_get_contents($root . 'admin/commerce/showrooms/preview.php');
        self::assertStringContainsString("required_param('id', PARAM_INT)", $preview);
        self::assertStringContainsString('lovable_cms_preview.php', $preview);
    }
}
