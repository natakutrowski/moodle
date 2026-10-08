<?php
// CampusFR - H5P renderer customizations.

namespace theme_edly\output;

defined('MOODLE_INTERNAL') || die();

class core_h5p_renderer extends \core_h5p\output\renderer {

    public function h5p_alter_styles(&$styles, array $libraries, string $embedtype) {
        // Preserve Moodle's existing custom H5P styles.
        parent::h5p_alter_styles($styles, $libraries, $embedtype);

        // Apply CampusFR-specific cover image fixes inside the H5P player.
        $styles[] = (object) [
            'path' => (new \moodle_url(
                '/theme/edly/style/h5p-cover-fix.css'
            ))->out(false),
            'version' => '?ver=1',
        ];
    }
}