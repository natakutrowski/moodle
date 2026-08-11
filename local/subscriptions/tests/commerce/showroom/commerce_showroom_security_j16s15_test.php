<?php
declare(strict_types=1);
namespace local_subscriptions\tests\commerce\showroom;
defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_security_j16s15_test extends \advanced_testcase {
    public function test_import_hardening_contract(): void {
        global $CFG;
        $s=file_get_contents($CFG->dirroot.'/local/subscriptions/classes/commerce/showroom/cms/CommerceShowroomPortablePackageService.php');
        foreach ([
            'Duplicate path in portable Showroom archive.',
            'Suspicious compression ratio',
            'Duplicate portable media manifest entry.',
            'contains an undeclared file.',
            'media manifest totals do not match.',
            'showroom-import-stage-',
        ] as $needle) {
            self::assertStringContainsString($needle,$s);
        }
        $stage=strpos($s,'showroom-import-stage-');
        $txn=strpos($s,'$transaction = $this->db->start_delegated_transaction();',$stage);
        self::assertNotFalse($stage);
        self::assertNotFalse($txn);
        self::assertLessThan($txn,$stage);
    }
}
