<?php
declare(strict_types=1);
namespace local_subscriptions\tests\commerce\showroom;
defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\cms\CommerceShowroomCmsRepository;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomPublicationService;

final class commerce_showroom_security_j16s16_test extends \advanced_testcase {
    public function test_publication_precondition_is_inside_transaction(): void {
        global $CFG;
        $s=file_get_contents($CFG->dirroot.'/local/subscriptions/classes/commerce/showroom/cms/CommerceShowroomPublicationService.php');
        self::assertStringContainsString('?\\Closure $precondition = null',$s);
        self::assertStringContainsString('$precondition();',$s);
        self::assertStringContainsString('CommerceShowroomPublicationIntegrityValidator',$s);
    }

    public function test_restore_reuses_block_id_for_same_key(): void {
        global $DB;
        $this->resetAfterTest(true);
        $repo=new CommerceShowroomCmsRepository($DB);
        $id=$repo->save([
            'showroomkey'=>'restore-safe','status'=>'draft','name'=>'Restore',
            'template'=>'local_subscriptions/showroom/third_group_verbs',
            'productsjson'=>'{}','settingsjson'=>'{}',
        ],2);
        $blockid=$repo->save_block($id,[
            'blocktype'=>'hero','blockkey'=>'hero','enabled'=>true,
            'configjson'=>'{"title":"Before"}'
        ],2);
        $service=new CommerceShowroomPublicationService($DB,$repo);
        $service->submit_for_review($id,2);
        $revs=$service->revisions($id);
        $repo->save_block($id,[
            'id'=>$blockid,'blocktype'=>'hero','blockkey'=>'hero','enabled'=>true,
            'configjson'=>'{"title":"After"}'
        ],2);
        $service->restore($id,(int)$revs[0]->id,2);
        $blocks=$repo->blocks($id);
        self::assertCount(1,$blocks);
        self::assertSame($blockid,(int)$blocks[0]->id);
        self::assertStringContainsString('Before',(string)$blocks[0]->configjson);
    }
}
