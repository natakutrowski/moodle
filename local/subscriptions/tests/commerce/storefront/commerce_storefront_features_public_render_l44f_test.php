<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_storefront_features_public_render_l44f_test extends \advanced_testcase {
    public function test_features_rich_content_is_rendered_before_feature_cards(): void {
        global $CFG;

        $template = (string)file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/templates/storefront/product_section.mustache'
        );

        $start = strpos($template, '{{#isfeatures}}');
        $end = strpos($template, '{{/isfeatures}}', $start);

        $this->assertNotFalse($start);
        $this->assertNotFalse($end);

        $block = substr($template, $start, $end - $start);
        $content = strpos(
            $block,
            '{{#content}}<div class="commerce-product-section__richtext">{{{content}}}</div>{{/content}}'
        );
        $items = strpos($block, '{{#items}}');

        $this->assertNotFalse($content);
        $this->assertNotFalse($items);
        $this->assertLessThan($items, $content);
    }
}
