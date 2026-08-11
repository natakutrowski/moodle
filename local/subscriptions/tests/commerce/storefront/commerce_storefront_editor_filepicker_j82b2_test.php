<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_storefront_editor_filepicker_j82b2_test
        extends \advanced_testcase {

    public function test_file_api_library_is_loaded_explicitly(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/storefront/'
            . 'content/CommerceStorefrontContentFileService.php'
        );

        $this->assertStringContainsString(
            "require_once(\$CFG->libdir . '/filelib.php')",
            $source
        );
        $this->assertStringContainsString(
            '\\file_save_draft_area_files(',
            $source
        );
        $this->assertStringContainsString(
            '\\file_prepare_draft_area(',
            $source
        );
    }

    public function test_picker_client_id_matches_moodle_core_contract(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/storefront/'
            . 'content/CommerceStorefrontContentFileService.php'
        );

        $this->assertStringContainsString(
            '$picker->client_id = uniqid();',
            $source
        );
        $this->assertStringNotContainsString(
            "uniqid(\n            'storefront_content_',\n"
                . "            true\n        )",
            $source
        );
        $this->assertStringNotContainsString(
            'uniqid(\'storefront_content_\', true)',
            $source
        );
    }

    public function test_picker_initialisation_mirrors_native_editor(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/storefront/'
            . 'content/CommerceStorefrontContentFileService.php'
        );

        foreach ([
            '\\initialise_filepicker($arguments)',
            "\$arguments->env = 'filepicker'",
            "\$picker->env = 'editor'",
            "\$picker->itemid = \$draftitemid",
            "'enable_filemanagement' => true",
            'FILE_CONTROLLED_LINK',
        ] as $needle) {
            $this->assertStringContainsString($needle, $source);
        }
    }
}
