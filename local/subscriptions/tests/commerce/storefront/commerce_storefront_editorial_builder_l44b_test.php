<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_storefront_editorial_builder_l44b_test extends \advanced_testcase {
    public function test_features_section_renders_its_rich_content_before_items(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/storefront/product_section.mustache'
        );

        $featuresstart = strpos($template, '{{#isfeatures}}');
        $featuresend = strpos($template, '{{/isfeatures}}', $featuresstart);
        $this->assertNotFalse($featuresstart);
        $this->assertNotFalse($featuresend);

        $features = substr($template, $featuresstart, $featuresend - $featuresstart);
        $contentpos = strpos(
            $features,
            '{{#content}}<div class="commerce-product-section__richtext">{{{content}}}</div>{{/content}}'
        );
        $itemspos = strpos($features, '{{#items}}');

        $this->assertNotFalse($contentpos);
        $this->assertNotFalse($itemspos);
        $this->assertLessThan($itemspos, $contentpos);
    }
}
