<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_storefront_h5p_sources_j82b3_test
        extends \advanced_testcase {

    public function test_h5p_block_supports_bank_and_direct_upload(): void {
        global $CFG;

        $page = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/admin/commerce/products/'
            . 'storefront.php'
        );

        foreach ([
            'section_h5p_contentid_',
            'section_h5p_file_',
            "accept' => '.h5p'",
            'commerce_storefront_h5p_bank_empty',
            '/contentbank/index.php',
        ] as $needle) {
            $this->assertStringContainsString($needle, $page);
        }
    }

    public function test_h5p_service_uses_exact_core_content_type(): void {
        global $CFG;

        $service = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/storefront/'
            . 'content/CommerceStorefrontH5pService.php'
        );

        $this->assertStringContainsString(
            "'contenttype' => 'contenttype_h5p'",
            $service
        );
        $this->assertStringContainsString(
            'has_options',
            $service
        );
    }

    public function test_uploaded_h5p_has_rendering_priority(): void {
        global $CFG;

        $presenter = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/storefront/'
            . 'page/CommerceStorefrontPagePresenter.php'
        );

        $uploadedposition = strpos(
            $presenter,
            "get_slot_url(\n            \$itemid,\n"
                . "            'h5p'"
        );
        $bankposition = strpos(
            $presenter,
            'CommerceStorefrontH5pService::create()'
        );

        $this->assertNotFalse($uploadedposition);
        $this->assertNotFalse($bankposition);
        $this->assertLessThan(
            $bankposition,
            $uploadedposition
        );
        $this->assertStringContainsString(
            "'/h5p/embed.php'",
            $presenter
        );
    }
}
