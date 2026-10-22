<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockConfigurationPresenter;

final class commerce_showroom_articles_heart_puzzle_slide4_798c35_test extends \advanced_testcase {
    public function test_presenter_exposes_piece_slots_for_slide_four_layout(): void {
        $reflection = new \ReflectionClass(CommerceShowroomBlockConfigurationPresenter::class);
        $presenter = $reflection->newInstance();
        $method = $reflection->getMethod('apply_heart_puzzle');
        $method->setAccessible(true);

        $data = $method->invoke($presenter, [], [
            'eyebrow' => 'LE CŒUR DU SYSTÈME',
            'title' => 'Au centre, il y a une logique.',
            'titleaccent' => 'Pas une loterie.',
            'text' => 'Cinq pièces vous aident à lire la situation avant de choisir l’article.',
            'pieces' => implode("\n", [
                '01|Identifier le nom|De quoi parle-t-on ?|exemple 1|fa-solid fa-magnifying-glass',
                '02|Repérer si c’est précis|Le contexte l’identifie ?|exemple 2|fa-solid fa-location-crosshairs',
                '03|Voir si c’est une quantité|On compte ou non ?|exemple 3|fa-solid fa-glass-water',
                '04|Décider si l’on parle en général|Une notion globale ?|exemple 4|fa-solid fa-earth-europe',
                '05|Vérifier si l’article tombe|Profession, langue, pays...|exemple 5|fa-solid fa-scissors',
            ]),
            'centerlabel' => 'La logique des articles',
            'footertitle' => 'Une fois les 5 pièces réunies,',
            'footertext' => 'vous ne choisissez plus au hasard :',
            'footeraccent' => 'vous comprenez ce que vous voulez dire.',
        ]);

        self::assertCount(5, $data['heartpuzzlepieces']);
        self::assertSame(1, $data['heartpuzzlepieces'][0]['slot']);
        self::assertSame('commerce-showroom-heart-puzzle__card--3', $data['heartpuzzlepieces'][2]['cardclass']);
        self::assertSame('commerce-showroom-heart-puzzle__arrow--5', $data['heartpuzzlepieces'][4]['arrowclass']);
        self::assertTrue($data['hasheartpuzzlefooter']);
    }

    public function test_slide_four_markup_contains_badge_arrows_and_hoverable_heart_segments(): void {
        global $CFG;

        $template = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/showroom/default.mustache');
        $css = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/showroom.css');

        self::assertStringContainsString('commerce-showroom-heart-puzzle__card-arrow', $template);
        self::assertStringContainsString('data-heart-piece="{{slot}}"', $template);
        self::assertStringContainsString('data-heart-segment="1"', $template);
        self::assertStringContainsString('.commerce-showroom-heart-puzzle__card-arrow', $css);
        self::assertStringContainsString(':has(.commerce-showroom-heart-puzzle__card--1:hover) .commerce-showroom-heart-puzzle__segment--1', $css);
        self::assertStringContainsString('.commerce-showroom-heart-puzzle__segment:hover', $css);
        self::assertStringContainsString('drop-shadow(0 0 .72rem rgba(246, 31, 130, .32))', $css);
        self::assertStringContainsString('.commerce-showroom-heart-puzzle__outline', $css);
        self::assertStringContainsString(':has(.commerce-showroom-heart-puzzle__segment--1:hover) .commerce-showroom-heart-puzzle__card--1', $css);
        self::assertStringContainsString('.commerce-showroom-heart-puzzle__badge i', $css);
    }
}
