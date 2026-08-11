<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\domain\CommerceProduct;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomProductLinkOptions;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomRenderTemplateRegistry;

final class commerce_showroom_general_config_j16i2_test extends \advanced_testcase {
    public function test_render_templates_are_explicit_and_validated(): void {
        $options = CommerceShowroomRenderTemplateRegistry::options();

        $this->assertArrayHasKey(
            'local_subscriptions/showroom/third_group_verbs',
            $options
        );
        $this->assertSame(
            'local_subscriptions/showroom/third_group_verbs',
            CommerceShowroomRenderTemplateRegistry::normalise(
                'local_subscriptions/showroom/third_group_verbs'
            )
        );
    }

    public function test_product_link_selector_filters_by_expected_type(): void {
        global $DB;

        $this->resetAfterTest(true);

        $now = time();
        $DB->insert_record('local_subs_commerce_product', (object)[
            'sku' => 'COURSE-ONE',
            'type' => 'course_access',
            'status' => 'active',
            'name' => 'Course One',
            'description' => '',
            'metadatajson' => '{}',
            'availablefrom' => null,
            'availableuntil' => null,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
        $DB->insert_record('local_subs_commerce_product', (object)[
            'sku' => 'PDF-ONE',
            'type' => 'digital_download',
            'status' => 'active',
            'name' => 'PDF One',
            'description' => '',
            'metadatajson' => '{}',
            'availablefrom' => null,
            'availableuntil' => null,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);

        $service = new CommerceShowroomProductLinkOptions($DB);
        $options = $service->grouped_options();

        $this->assertArrayHasKey('COURSE-ONE', $options['course']);
        $this->assertArrayNotHasKey('PDF-ONE', $options['course']);
        $this->assertArrayHasKey('PDF-ONE', $options['pdf']);
        $this->assertSame(
            'COURSE-ONE',
            $service->normalise_sku('course-one', 'course_access')
        );
    }

    public function test_builder_no_longer_exposes_products_json_textarea(): void {
        global $CFG;

        $editor = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/admin/commerce/showrooms/edit.php'
        );

        $this->assertStringContainsString("'linkedcourse'", $editor);
        $this->assertStringContainsString("'linkedpdf'", $editor);
        $this->assertStringContainsString("'linkedbundle'", $editor);
        $this->assertStringNotContainsString(
            "'productsjson' => 'Produits liés (JSON)'",
            $editor
        );
        $this->assertStringContainsString(
            'CommerceShowroomRenderTemplateRegistry::options()',
            $editor
        );
    }
}
