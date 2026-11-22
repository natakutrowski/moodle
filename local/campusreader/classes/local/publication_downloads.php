<?php
// CampusFR D2.12.3 — download controls for an already authorized frozen story.
// Links are presentation only: publication_pdf.php independently rechecks Commerce and unlocks.
declare(strict_types=1);
namespace local_campusreader\local;
defined('MOODLE_INTERNAL') || die();

final class publication_downloads {
    public static function render(string $slug, int $chapter): string {
        // Match the private endpoint's URL contract; fail closed for programmer mistakes.
        if (!preg_match('/\A[a-z0-9][a-z0-9-]{0,63}\z/D', $slug) ||
                $chapter < 1 || $chapter > 100) {
            throw new \invalid_parameter_exception('Invalid published chapter download');
        }
        $download = static function(string $mode) use ($slug, $chapter): string {
            $url = new \moodle_url('/local/campusreader/publication_pdf.php', [
                'slug' => $slug, 'chapter' => $chapter, 'mode' => $mode,
            ]);
            return \s($url->out(false));
        };
        $ui = static fn(string $key): string => \s(get_string('cfr_c_' . $key, 'local_campusreader'));
        return '<section class="cfr-pub-downloads" aria-labelledby="cfr-pub-downloads-title">' .
            '<div class="cfr-pub-downloads__heading">' .
            '<span class="cfr-pub-downloads__eyebrow">' . $ui('dl_eyebrow') . '</span>' .
            '<h3 id="cfr-pub-downloads-title">' . $ui('dl_heading') . '</h3>' .
            '<p>' . $ui('dl_intro') . '</p></div>' .
            '<div class="cfr-pub-downloads__grid">' .
            '<a class="cfr-pub-downloads__action" href="' . $download('reading') . '">' .
            '<span class="cfr-pub-downloads__glyph" aria-hidden="true">📖</span>' .
            '<span class="cfr-pub-downloads__body"><strong>' . $ui('dl_reading') . '</strong>' .
            '<small>' . $ui('dl_reading_desc') . '</small></span>' .
            '<span class="cfr-pub-downloads__arrow" aria-hidden="true">↓</span></a>' .
            '<a class="cfr-pub-downloads__action" href="' . $download('booklet') . '">' .
            '<span class="cfr-pub-downloads__glyph" aria-hidden="true">✂</span>' .
            '<span class="cfr-pub-downloads__body"><strong>' . $ui('dl_booklet') . '</strong>' .
            '<small>' . $ui('dl_booklet_desc') . '</small></span>' .
            '<span class="cfr-pub-downloads__arrow" aria-hidden="true">↓</span></a>' .
            '</div>' .
            '<p class="cfr-pub-downloads__tip"><strong>' . $ui('dl_tip_label') . '</strong> ' . $ui('dl_tip') . '</p>' .
            '</section>';
    }
}
