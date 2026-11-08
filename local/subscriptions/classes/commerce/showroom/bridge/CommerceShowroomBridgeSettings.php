<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\showroom\bridge;

defined('MOODLE_INTERNAL') || die();

/** Separate, version-neutral CMS settings for bridge renders; no DB migration. */
final class CommerceShowroomBridgeSettings {
    public const LANGUAGES = ['ru', 'fr', 'en'];

    /** @return array{id:string,content:array<string,array<string,mixed>>,videoUrl:string,posterUrl:string} */
    public static function from_json(string $json): array {
        $settings = json_decode($json, true);
        if (!is_array($settings)) {
            throw new \invalid_parameter_exception('Invalid Showroom settings JSON.');
        }
        $bridge = $settings['designbridge'] ?? [];
        if (!is_array($bridge)) {
            throw new \invalid_parameter_exception('Invalid designbridge configuration.');
        }
        $id = trim((string)($bridge['id'] ?? ''));
        $content = [];
        foreach (self::LANGUAGES as $language) {
            $value = $bridge['content'][$language] ?? [];
            if (!is_array($value) || array_is_list($value) && $value !== []) {
                throw new \invalid_parameter_exception('Invalid bridge content: ' . $language);
            }
            self::validate_pilot_copy($value, $language);
            $content[$language] = $value;
        }
        return ['id' => $id, 'content' => $content,
            'videoUrl' => self::validate_video_url((string)($bridge['videoUrl'] ?? '')),
            'posterUrl' => self::validate_poster_url((string)($bridge['posterUrl'] ?? ''))];
    }

    /** @param array<string,string> $jsonbylanguage */
    public static function merge_into_settings_json(
        string $settingsjson, string $designid, array $jsonbylanguage, ?string $videoUrl = null, ?string $posterUrl = null
    ): string {
        $settings = json_decode($settingsjson, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($settings)) {
            throw new \invalid_parameter_exception('Invalid showroom settings JSON.');
        }
        $designid = trim($designid);
        if ($designid !== '') {
            CommerceShowroomBridgePackageRegistry::require_id($designid);
        }
        $content = [];
        foreach (self::LANGUAGES as $language) {
            $raw = trim((string)($jsonbylanguage[$language] ?? ''));
            if (strlen($raw) > 65536) {
                throw new \invalid_parameter_exception('Bridge content too large: ' . $language);
            }
            $value = $raw === '' ? [] : json_decode($raw, true, 32, JSON_THROW_ON_ERROR);
            if (!is_array($value) || (array_is_list($value) && $value !== [])) {
                throw new \invalid_parameter_exception('Bridge content must be a JSON object: ' . $language);
            }
            self::validate_pilot_copy($value, $language);
            $content[$language] = $value;
        }
        // A null argument keeps the existing setting for old callers; the CMS
        // edit form supplies a string (possibly empty) to update/clear the URL.
        $oldbridge = $settings['designbridge'] ?? [];
        $existingvideo = is_array($oldbridge) ? (string)($oldbridge['videoUrl'] ?? '') : '';
        $existingposter = is_array($oldbridge) ? (string)($oldbridge['posterUrl'] ?? '') : '';
        $settings['designbridge'] = [
            'id' => $designid,
            'content' => $content,
            'videoUrl' => self::validate_video_url($videoUrl ?? $existingvideo),
            'posterUrl' => self::validate_poster_url($posterUrl ?? $existingposter),
        ];
        return json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    /** Safe HTTPS URL for a direct video or a recognised video hosting page. */
    public static function validate_video_url(string $url): string {
        // Reject control characters even if trim() would remove them.
        if (preg_match('/[\x00-\x1f\x7f]/', $url)) {
            throw new \invalid_parameter_exception('Invalid Bridge presentation video URL.');
        }
        $url = trim($url);
        if ($url === '') {
            return '';
        }
        if (strlen($url) > 2048) {
            throw new \invalid_parameter_exception('Invalid Bridge presentation video URL.');
        }
        $parts = parse_url($url);
        if (!filter_var($url, FILTER_VALIDATE_URL) || !is_array($parts)
            || strtolower((string)($parts['scheme'] ?? '')) !== 'https'
            || empty($parts['host']) || isset($parts['user']) || isset($parts['pass'])) {
            throw new \invalid_parameter_exception('Bridge presentation video requires a public HTTPS URL.');
        }
        return $url;
    }

    /** Optional external cover image; the trusted packaged image is the default.
     *  Never accept javascript:, data:, credentials, or arbitrary relative URLs. */
    public static function validate_poster_url(string $url): string {
        if (preg_match('/[\x00-\x1f\x7f]/', $url)) {
            throw new \invalid_parameter_exception('Invalid Bridge poster URL.');
        }
        $url = trim($url);
        if ($url === '') {
            return '';
        }
        if (strlen($url) > 2048) {
            throw new \invalid_parameter_exception('Bridge poster URL too long.');
        }
        $parts = parse_url($url);
        if (!filter_var($url, FILTER_VALIDATE_URL) || !is_array($parts)
            || strtolower((string)($parts['scheme'] ?? '')) !== 'https'
            || empty($parts['host']) || isset($parts['user']) || isset($parts['pass'])) {
            throw new \invalid_parameter_exception('Bridge poster requires a public HTTPS image URL.');
        }
        return $url;
    }

    /** @param array<string,mixed> $copy */
    private static function validate_pilot_copy(array $copy, string $language): void {
        foreach (['header', 'hero'] as $section) {
            if (!array_key_exists($section, $copy)) {
                continue;
            }
            $fields = $copy[$section];
            if (!is_array($fields) || array_is_list($fields)) {
                throw new \invalid_parameter_exception('Bridge ' . $section . ' must be a JSON object: ' . $language);
            }
            foreach ($fields as $field => $value) {
                if ($section === 'hero' && $field === 'facts') {
                    if (!is_array($value) || count($value) !== 3 || !array_is_list($value)) {
                        throw new \invalid_parameter_exception('Bridge hero facts must contain exactly three strings.');
                    }
                    foreach ($value as $text) {
                        if (!is_string($text)) {
                            throw new \invalid_parameter_exception('Invalid bridge hero fact.');
                        }
                    }
                } else if (!is_string($value)) {
                    throw new \invalid_parameter_exception('Bridge text must be a string: ' . $section . '.' . $field);
                }
            }
        }
    }
}
