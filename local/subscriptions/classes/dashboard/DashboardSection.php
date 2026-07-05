<?php

namespace local_subscriptions\dashboard;

defined('MOODLE_INTERNAL') || die();

use html_writer;

final class DashboardSection {

    public static function render(array $cards, string $classes = 'row'): string {
        $out = html_writer::start_div($classes);

        foreach ($cards as $cardclass) {
            if (!class_exists($cardclass)) {
                continue;
            }

            if (!is_subclass_of($cardclass, DashboardCard::class)) {
                continue;
            }

            $out .= $cardclass::render();
        }

        $out .= html_writer::end_div();

        return $out;
    }
}