<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockConfigurationPresenter;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockEditorRegistry;

/**
 * 7.98C.4.13 — Sombre 2 remains selectable, renderable and uses #1A1630.
 *
 * @coversNothing
 */
final class commerce_showroom_articles_dark2_restore_798c413_test extends \advanced_testcase {
    public function test_dark2_is_available_and_keeps_the_revised_colour(): void {
        global $CFG;

        $schema = CommerceShowroomBlockEditorRegistry::schema('offers');
        $backgroundfield = null;
        foreach ((array)($schema['fields'] ?? []) as $field) {
            if (($field['name'] ?? '') === 'sectionbackground') {
                $backgroundfield = $field;
                break;
            }
        }

        $this->assertIsArray($backgroundfield);
        $this->assertArrayHasKey('dark2', (array)($backgroundfield['options'] ?? []));
        $this->assertSame('Sombre 2', $backgroundfield['options']['dark2']);

        $presenter = new CommerceShowroomBlockConfigurationPresenter();
        $method = new \ReflectionMethod($presenter, 'apply_layout');
        $data = $method->invoke($presenter, [], 'offers', [
            'sectionbackground' => 'dark2',
        ]);
        $this->assertStringContainsString(
            'commerce-showroom-background--dark2',
            (string)$data['offerslayoutclass']
        );

        $css = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/showroom.css');
        $this->assertIsString($css);
        $marker = '7.98C.4.13 — restore Sombre 2 with the revised #1A1630 tone.';
        $start = strpos($css, $marker);
        $this->assertNotFalse($start);
        $section = substr($css, $start);
        $this->assertStringContainsString(
            '.commerce-showroom .commerce-showroom-background--dark2',
            $section
        );
        $this->assertStringContainsString('background: #1A1630;', $section);
        $this->assertStringContainsString(
            '.commerce-showroom-support.commerce-showroom-background--dark2',
            $section
        );
    }
}
