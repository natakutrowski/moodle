<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_storefront_features_editor_l44c_test extends \advanced_testcase {
    public function test_features_content_uses_rich_editor_and_is_persisted(): void {
        global $CFG;

        $admin = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/admin/commerce/products/storefront.php'
        );
        $editor = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/storefront/admin/CommerceStorefrontPageEditor.php'
        );
        $presenter = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/storefront/page/CommerceStorefrontPagePresenter.php'
        );
        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/storefront/product_section.mustache'
        );

        $this->assertStringContainsString(
            "['hero', 'rich_text', 'image_text', 'video', 'h5p', 'cta', 'features']",
            $admin
        );
        $this->assertStringContainsString("case 'features':", $editor);
        $this->assertStringContainsString("\$section['content'] = \$content;", $editor);
        $this->assertStringContainsString(
            "'features' => [",
            $presenter
        );
        $this->assertStringContainsString(
            '$this->format_storefront_content(',
            $presenter
        );
        $this->assertStringContainsString('{{{content}}}', $template);
    }
}
