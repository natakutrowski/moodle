<?php
// CampusFR D2.22.2 — server-side allowlist for Reader client messages.
declare(strict_types=1);
namespace local_campusreader\local;
defined('MOODLE_INTERNAL') || die();

final class ui_translations {
    private const CLIENT_KEYS = [
        'vocab_voice',
        'vocab_voice_female',
        'vocab_voice_male',
        'js_word_action',
        'js_page_counter',
        'js_translate_search',
        'js_translate_demo_missing',
        'js_translate_ai_note',
        'js_translate_demo',
        'js_translate_future',
        'js_translate_to',
        'js_translate_language',
        'js_translate_one',
        'js_translate_off',
        'js_translate_failed',
        'js_translate_optional',
        'js_translate_placeholder',
        'js_add_vocab',
        'js_saving',
        'js_saved_vocab',
        'js_saved',
        'js_save_failed',
        'js_turn_previous',
        'js_turn_next',
        'js_turn_back',
        'js_turn_forward',
        'js_boot_loading',
        'js_boot_failed',
        'js_voice_ai',
        'js_voice_blocked',
        'js_voice_preparing',
        'js_voice_waiting',
        'js_voice_audio_error',
        'js_voice_error',
        'js_listen_again',
        'js_retry',
        'js_retry_aria',
        'js_voice_unconfigured',
        'js_vocab_all_letters',
        'js_vocab_letter_aria',
        'js_vocab_letters_aria',
        'js_vocab_count',
        'js_vocab_suggesting',
        'js_vocab_suggested',
        'js_vocab_kept',
        'js_vocab_audio_error',
        'js_vocab_preparing',
        'js_vocab_confirm_remove',
        'js_sync_paused',
        'js_sync_playing',
    ];

    public static function client(): array {
        $result = [];
        foreach (self::CLIENT_KEYS as $key) {
            $result[$key] = get_string('cfr_c_' . $key, 'local_campusreader');
        }
        return $result;
    }

    public static function attr(): string {
        return \s(json_encode(self::client(), JSON_UNESCAPED_UNICODE |
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR));
    }
}
