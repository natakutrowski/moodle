<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockConfigurationPresenter;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockEditorRegistry;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomCmsRepository;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomRuntimeBlockSet;

/** @covers \local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockConfigurationPresenter */
final class commerce_showroom_a1_preparation_lovable_798e11_test extends \advanced_testcase {
    public function test_lovable_a0_has_four_steps_accent_suffix_and_preserves_legacy(): void {
        global $DB;
        $this->resetAfterTest(true);
        $repository = new CommerceShowroomCmsRepository($DB);
        $showroomid = $repository->save([
            'showroomkey' => 'a1-e811-lovable-test',
            'status' => 'draft',
            'name' => 'A1 E8.11 Lovable test',
            'template' => 'local_subscriptions/showroom/default',
            'slugfr' => '', 'slugen' => '', 'slugru' => '',
            'titlekey' => '', 'descriptionkey' => '',
            'productsjson' => '{}', 'settingsjson' => '{}',
        ], 2);
        $repository->save_block($showroomid, [
            'blocktype' => 'preparation_flow',
            'sortorder' => 10,
            'enabled' => true,
            'configjson' => json_encode([
                'presentation' => 'lovable_a0',
                'translations' => [
                    'fr' => [
                        'eyebrow' => 'LES PREMIERS PAS',
                        'title' => 'Ta',
                        'titleaccent' => 'première aventure',
                        'titlesuffix' => 'commence avant le A1',
                        'text' => 'Tu peux commencer tout de suite.',
                        'steps' => "Acheter A1|fa-solid fa-cart-shopping\nAccès A0|fa-solid fa-graduation-cap\nApprendre à lire|fa-solid fa-book-open\nCommencer A1|fa-solid fa-flag",
                        'annotation' => 'Petit à petit ♡',
                    ],
                ],
            ], JSON_THROW_ON_ERROR),
        ], 2);
        $blocks = CommerceShowroomRuntimeBlockSet::load_preview($DB, $showroomid);
        $data = (new CommerceShowroomBlockConfigurationPresenter())->apply([], $blocks);
        self::assertTrue($data['preparationislovablea0']);
        self::assertTrue($data['haspreparationtitlesuffix']);
        self::assertSame('commence avant le A1', $data['preparationtitlesuffix']);
        self::assertSame('première aventure', $data['preparationtitleaccent']);
        self::assertSame('Petit à petit ♡', $data['preparationannotation']);
        self::assertCount(4, $data['preparationitems']);
        self::assertSame('fa-solid fa-flag', $data['preparationitems'][3]['icon']);
        self::assertFalse($data['preparationitems'][3]['hasarrow']);
        self::assertFalse($data['preparationitems'][0]['text'] !== '');

        $names = array_column(CommerceShowroomBlockEditorRegistry::schema('preparation_flow')['fields'], 'name');
        foreach (['presentation', 'titlesuffix', 'steps', 'annotation'] as $name) {
            self::assertContains($name, $names);
        }
        $template = file_get_contents(__DIR__ . '/../../../templates/showroom/block_preparation_flow.mustache');
        self::assertStringContainsString('{{#preparationislovablea0}}', $template);
        self::assertStringContainsString('{{^preparationislovablea0}}', $template);

        $legacyid = $repository->save([
            'showroomkey' => 'a1-e811-legacy-test',
            'status' => 'draft',
            'name' => 'A1 E8.11 legacy test',
            'template' => 'local_subscriptions/showroom/default',
            'slugfr' => '', 'slugen' => '', 'slugru' => '',
            'titlekey' => '', 'descriptionkey' => '',
            'productsjson' => '{}', 'settingsjson' => '{}',
        ], 2);
        $repository->save_block($legacyid, [
            'blocktype' => 'preparation_flow',
            'sortorder' => 10,
            'enabled' => true,
            'configjson' => json_encode([
                'title' => 'Old A0',
                'items' => "Étape un|Explication|fa-solid fa-book\nÉtape deux|Explication|fa-solid fa-flag",
            ], JSON_THROW_ON_ERROR),
        ], 2);
        $legacy = (new CommerceShowroomBlockConfigurationPresenter())->apply(
            [], CommerceShowroomRuntimeBlockSet::load_preview($DB, $legacyid)
        );
        self::assertFalse($legacy['preparationislovablea0']);
        self::assertSame('', $legacy['preparationflowvariantclass']);
        self::assertCount(2, $legacy['preparationitems']);
    }
}
