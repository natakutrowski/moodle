<?php
declare(strict_types=1);
namespace local_subscriptions\tests\commerce\showroom;
defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_security_j16s17_certification_test extends \advanced_testcase {
    public function test_j16s_security_contracts_are_present(): void {
        global $CFG;
        $r=$CFG->dirroot.'/local/subscriptions/';
        $published=file_get_contents($r.'classes/commerce/showroom/cms/CommerceShowroomPublishedDefinitionResolver.php');
        $preview=file_get_contents($r.'admin/commerce/showrooms/preview.php');
        $router=file_get_contents($r.'public_router.php');
        $portable=file_get_contents($r.'classes/commerce/showroom/cms/CommerceShowroomPortablePackageService.php');
        $repo=file_get_contents($r.'classes/commerce/showroom/cms/CommerceShowroomCmsRepository.php');
        $pub=file_get_contents($r.'classes/commerce/showroom/cms/CommerceShowroomPublicationService.php');

        self::assertStringContainsString('CommerceShowroomStatus::PUBLISHED',$published);
        self::assertStringContainsString('require_login();',$preview);
        self::assertStringContainsString("require_capability('local/subscriptions:manage_showrooms'",$preview);
        self::assertStringContainsString('find_published_showroom_key($slug)',$router);
        self::assertStringContainsString('validate_exported_archive(',$portable);
        self::assertStringContainsString('showroom-import-stage-',$portable);
        self::assertStringContainsString('delete_block_media',$repo);
        self::assertStringContainsString('CommerceShowroomPublicationIntegrityValidator',$pub);
        self::assertStringContainsString('start_delegated_transaction',$pub);
    }
}
