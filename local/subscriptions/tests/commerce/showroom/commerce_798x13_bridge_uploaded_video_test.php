<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\bridge\CommerceShowroomBridgeVideoService;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomCmsRepository;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomPortablePackageService;

/** 7.98X.1.3: Moodle-managed Bridge presentation files and portable transfer. */
final class commerce_798x13_bridge_uploaded_video_test extends \advanced_testcase {
    private function fixture_webm(): string {
        return base64_decode(
            'GkXfo59ChoEBQveBAULygQRC84EIQoKEd2VibUKHgQJChYECGFOAZwEAAAAAAAHmEU2bdLpNu4t'
            . 'Tq4QVSalmU6yBoU27i1OrhBZUrmtTrIHWTbuMU6uEElTDZ1OsggEjTbuMU6uEHFO7a1OsggHQ7A'
            . 'EAAAAAAABZAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA'
            . 'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAVSalmsCrXsYMPQkBNgIxM'
            . 'YXZmNjEuNy4xMDNXQYxMYXZmNjEuNy4xMDNEiYhAj0AAAAAAABZUrmvIrgEAAAAAAAA/14EBc8W'
            . 'I4ZbbX7QkGJ6cgQAitZyDdW5kiIEAhoVWX1ZQOIOBASPjg4Q7msoA4JCwgRC6gRCagQJVsIRVuY'
            . 'EBElTDZ/tzc59jwIBnyJlFo4dFTkNPREVSRIeMTGF2ZjYxLjcuMTAzc3PWY8CLY8WI4ZbbX7QkG'
            . 'J5nyKFFo4dFTkNPREVSRIeUTGF2YzYxLjE5LjEwMSBsaWJ2cHhnyKFFo4hEVVJBVElPTkSHkzAw'
            . 'OjAwOjAxLjAwMDAwMDAwMAAfQ7Z1qOeBAKOjgQAAgBACAJ0BKhAAEAAARwiFhYiZhIgCAgAMDWA'
            . 'A/v+rUIAcU7trkbuPs4EAt4r3gQHxggGj8IED'
        );
    }

    public function test_file_storage_url_replacement_clone_and_delete(): void {
        global $DB;
        $this->resetAfterTest(true);
        $context = \context_system::instance();
        $repo = new CommerceShowroomCmsRepository($DB);
        $id = $repo->save([
            'showroomkey' => 'x13-video-original',
            'name' => 'Bridge media source',
            'status' => 'draft',
            'template' => 'local_subscriptions/showroom/bridge',
            'productsjson' => '{}',
            'settingsjson' => '{"designbridge":{"id":"articles_alf"}}',
        ], 2);
        $service = new CommerceShowroomBridgeVideoService($context);
        self::assertNull($service->get_url($id));
        $file = get_file_storage()->create_file_from_string([
            'contextid' => $context->id,
            'component' => CommerceShowroomBridgeVideoService::COMPONENT,
            'filearea' => CommerceShowroomBridgeVideoService::FILEAREA,
            'itemid' => $id,
            'filepath' => '/',
            'filename' => 'presentation.webm',
            'mimetype' => 'video/webm',
        ], $this->fixture_webm());
        self::assertSame($file->get_contenthash(), $service->get_file($id)->get_contenthash());
        self::assertStringContainsString('/showroom_bridge_video/' . $id . '/', $service->get_url($id)->out(false));
        $newid = $repo->duplicate_showroom($id, 2);
        self::assertNotSame($id, $newid);
        self::assertSame($file->get_contenthash(), $service->get_file($newid)->get_contenthash());
        $repo->delete($id);
        self::assertNull($service->get_file($id));
        self::assertNotNull($service->get_file($newid));
    }

    public function test_portable_zip_includes_uploaded_video_and_restores_it(): void {
        global $DB;
        if (!class_exists(\ZipArchive::class)) {
            $this->markTestSkipped('ZIP not available.');
        }
        $this->resetAfterTest(true);
        $context = \context_system::instance();
        $repo = new CommerceShowroomCmsRepository($DB);
        $id = $repo->save([
            'showroomkey' => 'x13-portable-original',
            'name' => 'Bridge portable',
            'status' => 'draft',
            'template' => 'local_subscriptions/showroom/bridge',
            'productsjson' => '{}',
            'settingsjson' => '{"designbridge":{"id":"articles_alf"}}',
        ], 2);
        $video = get_file_storage()->create_file_from_string([
            'contextid' => $context->id,
            'component' => CommerceShowroomBridgeVideoService::COMPONENT,
            'filearea' => CommerceShowroomBridgeVideoService::FILEAREA,
            'itemid' => $id,
            'filepath' => '/',
            'filename' => 'presentation.webm',
            'mimetype' => 'video/webm',
        ], $this->fixture_webm());
        $portable = new CommerceShowroomPortablePackageService($DB, $repo, $context);
        $preflight = $portable->preflight_export($id);
        self::assertSame(1, $preflight['mediacount']);
        self::assertSame($video->get_filesize(), $preflight['bytes']);
        $export = $portable->export_zip($id);
        self::assertSame(1, $export['mediacount']);
        $archive = new \ZipArchive();
        self::assertTrue($archive->open($export['pathname']) === true);
        $manifest = json_decode((string)$archive->getFromName('showroom.json'), true);
        self::assertSame('__campusfr_bridge_video__', $manifest['media']['files'][0]['blockkey']);
        $archive->close();
        $import = $portable->import_zip($export['pathname'], 2);
        self::assertSame(1, $import['mediacount']);
        $targetid = (int)$import['showroomid'];
        self::assertNotSame($id, $targetid);
        $restored = (new CommerceShowroomBridgeVideoService($context))->get_file($targetid);
        self::assertNotNull($restored);
        self::assertSame($video->get_contenthash(), $restored->get_contenthash());
    }
}
