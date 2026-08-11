<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\storefront;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\storefront\page\CommerceStorefrontComposerLayout;
use local_subscriptions\commerce\storefront\page\CommerceStorefrontSectionSchema;

final class commerce_storefront_visual_page_composer_test extends advanced_testcase {
    public function test_legacy_section_receives_safe_single_column_layout(): void {
        $section = CommerceStorefrontSectionSchema::normalise([
            'id' => 'intro',
            'type' => 'rich_text',
            'content' => '<p>Introduction</p>',
        ], 0);

        $this->assertNotNull($section);
        $this->assertSame(3, CommerceStorefrontSectionSchema::VERSION);
        $this->assertSame('row-1', $section['layout']['rowid']);
        $this->assertSame(1, $section['layout']['columns']);
        $this->assertSame(1, $section['layout']['column']);
        $this->assertSame('100', $section['layout']['ratio']);
        $this->assertSame('contained', $section['layout']['width']);
    }

    public function test_two_column_layout_is_normalised_without_losing_supported_options(): void {
        $layout = CommerceStorefrontComposerLayout::normalise([
            'layout' => [
                'rowid' => 'benefits-row',
                'columns' => 2,
                'column' => 2,
                'ratio' => '40_60',
                'width' => 'wide',
                'background' => 'soft',
                'spacing' => 'large',
                'alignment' => 'center',
            ],
        ], 1);

        $this->assertSame('benefits-row', $layout['rowid']);
        $this->assertSame(2, $layout['columns']);
        $this->assertSame(2, $layout['column']);
        $this->assertSame('40_60', $layout['ratio']);
        $this->assertSame('wide', $layout['width']);
        $this->assertSame('soft', $layout['background']);
        $this->assertSame('large', $layout['spacing']);
        $this->assertSame('center', $layout['alignment']);
    }

    public function test_invalid_layout_falls_back_to_controlled_values(): void {
        $layout = CommerceStorefrontComposerLayout::normalise([
            'layout' => [
                'rowid' => '../../unsafe',
                'columns' => 3,
                'column' => 99,
                'ratio' => '60_40',
                'width' => 'viewport-breakout',
                'background' => 'javascript',
                'spacing' => 'huge',
                'alignment' => 'absolute',
            ],
        ], 4);

        $this->assertSame('row-5', $layout['rowid']);
        $this->assertSame(3, $layout['columns']);
        $this->assertSame(3, $layout['column']);
        $this->assertSame('33_33_33', $layout['ratio']);
        $this->assertSame('contained', $layout['width']);
        $this->assertSame('default', $layout['background']);
        $this->assertSame('medium', $layout['spacing']);
        $this->assertSame('stretch', $layout['alignment']);
    }
}
