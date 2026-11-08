<?php
// Guard against reintroducing old Bridge regressions when merging Lovable CMS.
declare(strict_types=1);
namespace local_subscriptions\tests\commerce\showroom;
defined('MOODLE_INTERNAL') || die();

final class commerce_gustave_lovable_bridge_preservation_test extends \advanced_testcase {
    public function test_bridge_features_survive_cms_draft_integration(): void {
        global $CFG;
        $root = $CFG->dirroot . '/local/subscriptions/';
        $edit = (string)file_get_contents($root . 'admin/commerce/showrooms/edit.php');
        foreach (['CommerceShowroomBridgeContentEditor', 'CommerceShowroomBridgeSettings',
            'CommerceShowroomBridgeVideoService', 'bridgefields', 'bridgevideo_upload',
            'CommerceShowroomLovableDraftService::metadata'] as $fragment) {
            self::assertStringContainsString($fragment, $edit);
        }
        $publication = (string)file_get_contents($root .
            'classes/commerce/showroom/cms/CommerceShowroomPublicationService.php');
        self::assertStringContainsString('CommerceShowroomBridgePackageRegistry::TEMPLATE', $publication);
        self::assertStringContainsString('campusfr_lovable_draft', $publication);
        $repo = (string)file_get_contents($root .
            'classes/commerce/showroom/cms/CommerceShowroomCmsRepository.php');
        self::assertStringContainsString('CommerceShowroomBridgeVideoService', $repo);
        self::assertStringContainsString("'lovablecmsdraft'", $repo);
    }
}
