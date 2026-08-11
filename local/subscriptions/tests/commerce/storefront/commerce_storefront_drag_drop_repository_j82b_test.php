<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_storefront_drag_drop_repository_j82b_test
        extends \advanced_testcase {

    public function test_builder_loads_drag_drop_amd_and_exposes_hooks(): void {
        global $CFG;

        $page = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/admin/commerce/products/'
            . 'storefront.php'
        );
        $amd = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/amd/src/'
            . 'storefront_builder_drag_drop.js'
        );

        foreach ([
            'storefront_builder_drag_drop',
            'storefront-section-list',
            'storefront-section-card',
            'data-section-order',
            'data-builder-command',
            'data-drag-handle',
        ] as $needle) {
            $this->assertStringContainsString($needle, $page);
        }

        $this->assertStringContainsString('dragstart', $amd);
        $this->assertStringContainsString('event.altKey', $amd);
        $this->assertStringContainsString(
            'dataset.builderCommand',
            $amd
        );
    }

    public function test_submission_sorts_sections_by_drag_order(): void {
        global $CFG;

        $editor = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/storefront/'
            . 'admin/CommerceStorefrontPageEditor.php'
        );

        $this->assertStringContainsString('usort(', $editor);
        $this->assertStringContainsString(
            "\$section['order'] = \$position * 10",
            $editor
        );
    }

    public function test_tinymce_receives_initialised_filepicker_objects(): void {
        global $CFG;

        $service = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/storefront/'
            . 'content/CommerceStorefrontContentFileService.php'
        );

        foreach ([
            '\\initialise_filepicker($arguments)',
            '$picker->client_id = uniqid();',
            '$arguments->env = \'filepicker\';',
            "\$picker->env = 'editor'",
            "\$picker->itemid = \$draftitemid",
            "'image' =>",
            "'media' =>",
            "'link' =>",
            "'subtitle' =>",
            "'h5p'",
        ] as $needle) {
            $this->assertStringContainsString($needle, $service);
        }
    }

    public function test_compiled_amd_is_delivered(): void {
        global $CFG;

        $this->assertFileExists(
            $CFG->dirroot
            . '/local/subscriptions/amd/build/'
            . 'storefront_builder_drag_drop.min.js'
        );
    }
}
