<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\education\xp;

defined('MOODLE_INTERNAL') || die();

/** Display-only point unit for promotion standings and CRM views. */
final class CommercePedagogicalPointsPresenter {
    public static function promotion(int $points, bool $signed = false): string {
        $number = number_format($points, 0, ',', ' ');
        if ($signed && $points > 0) {
            $number = '+' . $number;
        }
        $icon = \html_writer::empty_tag('img', [
            'src' => (new \moodle_url('/local/subscriptions/pix/pain_au_chocolat.png'))->out(false),
            'class' => 'campusfr-points-choco-icon',
            'width' => '20',
            'height' => '20',
            'alt' => get_string('commerce_points_promo_unit', 'local_subscriptions'),
            'loading' => 'lazy',
        ]);
        return \html_writer::span(s($number) . ' ' . $icon, 'campusfr-points-promo');
    }
}
