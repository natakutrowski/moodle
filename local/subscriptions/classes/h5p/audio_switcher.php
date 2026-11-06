<?php
// CampusFR - H5P audio compatibility switches, controlled by local_subscriptions.
namespace local_subscriptions\h5p;

defined('MOODLE_INTERNAL') || die();

/**
 * Intercepts core_h5p scripts without changing installed H5P library versions.
 * OFF means no additional JS; pre-existing library scripts are not touched.
 *
 * Works only for Moodle core_h5p, not mod_hvp or H5P editor.
 */
final class audio_switcher {
    public static function alter_scripts(&$scripts, array $libraries, string $embedtype): void {
        if (!is_array($scripts) || !in_array($embedtype, ['iframe', 'div', 'external'], true)) {
            return;
        }
        if (self::enabled('h5p_audio_campusfr_enabled') && self::matches($libraries, 'H5P.Audio', 1, 5)) {
            self::append($scripts, 'audio-campusfr-1.5.37-ui3-spacing6.js', 'a1537ui3s6');
        }
        if (self::enabled('h5p_memory_campusfr_enabled') && self::matches($libraries, 'H5P.MemoryGame', 1, 3)) {
            self::append($scripts, 'memory-card-campusfr-1.3.55.js', 'm1355r1');
        }
    }

    private static function enabled(string $key): bool {
        return (string)get_config('local_subscriptions', $key) === '1';
    }

    private static function matches(array $libraries, string $name, int $major, int $minor): bool {
        $entry = $libraries[$name] ?? null;
        return is_array($entry) &&
            isset($entry['majorVersion'], $entry['minorVersion']) &&
            (int)$entry['majorVersion'] === $major &&
            (int)$entry['minorVersion'] === $minor;
    }

    private static function append(array &$scripts, string $file, string $version): void {
        global $CFG;
        $relative = '/local/subscriptions/assets/h5p/' . $file;
        if (!is_readable($CFG->dirroot . $relative)) {
            // Fail closed: missing candidate script must not break the official H5P player.
            return;
        }
        $url = new \moodle_url($relative);
        foreach ($scripts as $script) {
            if (is_object($script) && ($script->path ?? null) === $url->out(false)) {
                return; // Idempotent if renderer is called twice for the same dependency list.
            }
        }
        $scripts[] = (object)[
            'path' => $url->out(false),
            'version' => '?ver=' . $version,
        ];
    }
}
