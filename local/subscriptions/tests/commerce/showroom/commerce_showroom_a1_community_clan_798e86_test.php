<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockConfigurationPresenter;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockEditorRegistry;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomCmsRepository;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomRuntimeBlockSet;

/** @covers \local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockConfigurationPresenter */
final class commerce_showroom_a1_community_clan_798e86_test extends \advanced_testcase {
    public function test_advanced_json_normalisation_keeps_multilingual_clan_content(): void {
        $raw = [
            'sectionbackground' => 'soft',
            'badgeicon' => 'fa-solid fa-people-group',
            'translations' => [
                'ru' => [
                    'eyebrow' => 'СВОИ ЛЮДИ РЯДОМ',
                    'title' => 'У тебя будет',
                    'titleaccent' => 'свой клан',
                    'text' => 'Русский текст',
                    'imagenote' => '',
                    'items' => 'Маленькая компания|Всего 6 человек.|fa-solid fa-users',
                    'buttonlabel' => 'Стать частью клана',
                ],
                'fr' => [
                    'eyebrow' => 'DES PERSONNES QUI AVANCENT AVEC VOUS',
                    'title' => 'Vous aurez',
                    'titleaccent' => 'votre clan',
                    'text' => 'Texte français',
                    'imagenote' => '',
                    'items' => 'Un petit groupe|Seulement 6 personnes.|fa-solid fa-users',
                    'buttonlabel' => 'Rejoindre le clan',
                ],
                'en' => [
                    'eyebrow' => 'YOUR PEOPLE ARE RIGHT HERE',
                    'title' => 'You will have',
                    'titleaccent' => 'your own clan',
                    'text' => 'English text',
                    'imagenote' => '',
                    'items' => 'A small group|Only 6 people.|fa-solid fa-users',
                    'buttonlabel' => 'Join the clan',
                ],
            ],
            'imageurl' => '',
            'buttontarget' => '#showroom-offers',
        ];

        $normalised = CommerceShowroomBlockEditorRegistry::normalise('community_clan', $raw);

        self::assertSame('fa-solid fa-people-group', $normalised['badgeicon']);
        self::assertSame('СВОИ ЛЮДИ РЯДОМ', $normalised['translations']['ru']['eyebrow']);
        self::assertSame('свой клан', $normalised['translations']['ru']['titleaccent']);
        self::assertSame('Стать частью клана', $normalised['translations']['ru']['buttonlabel']);
        self::assertSame('DES PERSONNES QUI AVANCENT AVEC VOUS', $normalised['translations']['fr']['eyebrow']);
        self::assertSame('YOUR PEOPLE ARE RIGHT HERE', $normalised['translations']['en']['eyebrow']);
        self::assertSame('#showroom-offers', $normalised['buttontarget']);
    }

    public function test_runtime_supports_badge_icon_image_placeholder_and_note(): void {
        global $DB;
        $this->resetAfterTest(true);

        $repo = new CommerceShowroomCmsRepository($DB);
        $showroomid = $repo->save([
            'showroomkey' => 'a1-e86-runtime',
            'status' => 'draft',
            'name' => 'A1 E8.6 runtime',
            'template' => 'local_subscriptions/showroom/default',
            'slugfr' => '', 'slugen' => '', 'slugru' => '',
            'titlekey' => '', 'descriptionkey' => '',
            'productsjson' => '{}', 'settingsjson' => '{}',
        ], 2);

        $config = CommerceShowroomBlockEditorRegistry::normalise('community_clan', [
            'badgeicon' => 'fa-solid fa-people-group',
            'translations' => [
                'fr' => [
                    'eyebrow' => 'CLAN',
                    'title' => 'Vous aurez',
                    'titleaccent' => 'votre clan',
                    'text' => 'Texte',
                    'imagenote' => 'Plus qu’un cours, une équipe !♡',
                    'items' => "Petit groupe|Six personnes.|fa-solid fa-users\nChat|Échangez ensemble.|fa-regular fa-comments\nDécouvertes|30 aventures.|fa-solid fa-flag\nPersonnalité|Chaque clan a son style.|fa-solid fa-trophy",
                    'buttonlabel' => 'Rejoindre le clan',
                ],
            ],
            'imageurl' => '',
            'buttontarget' => '#showroom-offers',
        ]);

        $repo->save_block($showroomid, [
            'blocktype' => 'community_clan',
            'sortorder' => 10,
            'enabled' => true,
            'configjson' => json_encode($config, JSON_THROW_ON_ERROR),
        ], 2);

        $blocks = CommerceShowroomRuntimeBlockSet::load_preview($DB, $showroomid);
        $data = (new CommerceShowroomBlockConfigurationPresenter())->apply([], $blocks);

        self::assertSame('fa-solid fa-people-group', $data['clanbadgeicon']);
        self::assertSame('Plus qu’un cours, une équipe !♡', $data['clanimagenote']);
        self::assertTrue($data['hasclanimagenote']);
        self::assertFalse($data['hasclanimage']);
        self::assertCount(4, $data['clanitems']);
        self::assertTrue($data['hasclanbutton']);
        self::assertSame('#showroom-offers', $data['clanbuttontarget']);
    }

    public function test_markup_and_css_match_e86_cumulative_contract(): void {
        global $CFG;

        $template = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/showroom/block_community_clan.mustache');
        $css = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/showroom.css');

        self::assertIsString($template);
        self::assertStringContainsString('commerce-showroom-community-clan__badge', $template);
        self::assertStringContainsString('{{clanbadgeicon}}', $template);
        self::assertStringContainsString('commerce-showroom-community-clan__placeholder', $template);
        self::assertStringContainsString('{{clanimagenote}}', $template);

        self::assertIsString($css);
        self::assertStringContainsString('CampusFR 7.98E.8.3', $css);
        self::assertStringContainsString('CampusFR 7.98E.8.4', $css);
        self::assertStringContainsString('CampusFR 7.98E.8.5', $css);
        self::assertStringContainsString('CampusFR 7.98E.8.5.1', $css);
        self::assertStringContainsString('CampusFR 7.98E.8.6.1', $css);
        self::assertStringContainsString('#showroom-community .commerce-showroom-community-clan__badge', $css);
        self::assertStringContainsString('font-family: "Caveat", cursive;', $css);
    }
}
