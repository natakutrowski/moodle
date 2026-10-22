<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockConfigurationPresenter;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockEditorRegistry;

final class commerce_showroom_articles_hero_stats_layout_798c34_test extends \advanced_testcase {
    public function test_stats_editor_exposes_opt_in_grid_layout(): void {
        $schema = CommerceShowroomBlockEditorRegistry::schema('stats');
        $fields = $schema['fields'] ?? [];
        $layout = null;
        foreach ($fields as $field) {
            if (($field['name'] ?? '') === 'layout') {
                $layout = $field;
                break;
            }
        }

        self::assertIsArray($layout);
        self::assertSame('select', $layout['type']);
        self::assertArrayHasKey('bar', $layout['options']);
        self::assertArrayHasKey('grid_2x2_left', $layout['options']);
    }

    public function test_stats_presenter_defaults_to_bar_and_accepts_left_grid(): void {
        $reflection = new \ReflectionClass(CommerceShowroomBlockConfigurationPresenter::class);
        $presenter = $reflection->newInstance();
        $method = $reflection->getMethod('apply_stats');
        $method->setAccessible(true);

        $bar = $method->invoke($presenter, [], [
            'items' => "✓|A|\n★|B|\n✦|C|\n∞|D|",
            'animate' => false,
        ]);
        self::assertSame('bar', $bar['statslayout']);

        $grid = $method->invoke($presenter, [], [
            'items' => "✓|A|\n★|B|\n✦|C|\n∞|D|",
            'layout' => 'grid_2x2_left',
            'animate' => false,
        ]);
        self::assertSame('grid_2x2_left', $grid['statslayout']);
    }

    public function test_default_template_and_css_scope_grid_without_touching_expedition(): void {
        global $CFG;

        $template = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/showroom/default.mustache');
        $css = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/showroom.css');
        $verbsprofile = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/showroom/CommerceShowroomThirdGroupVerbsPresentationProfile.php'
        );

        self::assertIsString($template);
        self::assertIsString($css);
        self::assertIsString($verbsprofile);
        self::assertStringContainsString(
            'commerce-showroom-hero__stats--{{statslayout}}',
            $template
        );
        self::assertStringContainsString(
            '.commerce-showroom-hero__stats--grid_2x2_left',
            $css
        );
        self::assertMatchesRegularExpression(
            '/commerce-showroom-hero__stats--grid_2x2_left div \{[^}]*align-items:\s*center;[^}]*text-align:\s*center;/s',
            $css
        );
        self::assertStringContainsString("'statslayout' => 'bar'", $verbsprofile);

        $marker = '7.98C.3.4 — optional 2x2 Hero stats grid';
        $start = strpos($css, $marker);
        self::assertNotFalse($start);
        $section = substr($css, $start);
        self::assertStringContainsString('.commerce-showroom--generic', $section);
        self::assertStringNotContainsString('.commerce-showroom--expedition', $section);
    }
}
