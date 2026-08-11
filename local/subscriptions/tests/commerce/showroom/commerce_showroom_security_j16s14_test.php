<?php
declare(strict_types=1);
namespace local_subscriptions\tests\commerce\showroom;
defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\cms\CommerceShowroomCmsRepository;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomPublicationService;

final class commerce_showroom_security_j16s14_test extends \advanced_testcase {
    public function test_publication_rejects_missing_required_content(): void {
        global $DB;
        $this->resetAfterTest(true);
        $repo=new CommerceShowroomCmsRepository($DB);
        $id=$repo->save([
            'showroomkey'=>'integrity-test','status'=>'draft','name'=>'Integrity',
            'template'=>'local_subscriptions/showroom/third_group_verbs',
            'slugfr'=>'integrity-test','productsjson'=>'{}','settingsjson'=>'{}',
        ],2);
        $repo->save_block($id,[
            'blocktype'=>'hero','blockkey'=>'hero','enabled'=>true,'configjson'=>'{}'
        ],2);
        $service=new CommerceShowroomPublicationService($DB,$repo);
        $service->submit_for_review($id,2);
        try {
            $service->publish($id,2);
            self::fail('Invalid content must block publication.');
        } catch (\moodle_exception $e) {
            self::assertSame('commerce_showroom_publish_integrity_failed',$e->errorcode);
        }
        self::assertSame('review',(string)$repo->get($id)->status);
    }

    public function test_publication_rejects_stale_internal_media_itemid(): void {
        global $DB;
        $this->resetAfterTest(true);
        $repo=new CommerceShowroomCmsRepository($DB);
        $id=$repo->save([
            'showroomkey'=>'media-integrity','status'=>'draft','name'=>'Media',
            'template'=>'local_subscriptions/showroom/third_group_verbs',
            'slugfr'=>'media-integrity','productsjson'=>'{}','settingsjson'=>'{}',
        ],2);
        $repo->save_block($id,[
            'blocktype'=>'hero','blockkey'=>'hero','enabled'=>true,
            'configjson'=>json_encode([
                'title'=>'OK',
                'backgroundurl'=>'/pluginfile.php/1/local_subscriptions/showroom_block_media/999/backgroundurl/x.jpg'
            ])
        ],2);
        $service=new CommerceShowroomPublicationService($DB,$repo);
        $service->submit_for_review($id,2);
        $this->expectException(\moodle_exception::class);
        $service->publish($id,2);
    }
}
