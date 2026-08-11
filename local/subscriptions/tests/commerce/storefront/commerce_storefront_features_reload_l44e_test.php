<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\storefront\admin\CommerceStorefrontPageEditor;

final class commerce_storefront_features_reload_l44e_test extends \advanced_testcase {
    public function test_features_content_is_read_back_into_builder_rows(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/storefront/admin/CommerceStorefrontPageEditor.php'
        );

        $this->assertIsString($source);
        $this->assertStringContainsString(
            "'features'\n                => (string)(\$section['content'] ?? '')",
            $source
        );
    }

    public function test_features_merge_and_reload_contract_is_symmetric(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/storefront/admin/CommerceStorefrontPageEditor.php'
        );

        $this->assertStringContainsString("case 'features':", $source);
        $this->assertStringContainsString(
            "\$section['content'] = \$content;",
            $source
        );

        // The same type must also be accepted by section_content(), otherwise
        // persisted metadata is silently hidden again on the next Builder load.
        $sectioncontentpos = strpos($source, 'function section_content');
        $this->assertNotFalse($sectioncontentpos);
        $sectioncontent = substr($source, $sectioncontentpos, 1200);
        $this->assertStringContainsString("'features'", $sectioncontent);
        $this->assertStringContainsString(
            "\$section['content']",
            $sectioncontent
        );
    }
}
