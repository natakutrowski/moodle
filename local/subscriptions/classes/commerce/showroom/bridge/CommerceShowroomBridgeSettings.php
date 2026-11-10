<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\showroom\bridge;

defined('MOODLE_INTERNAL') || die();

/** Separate, version-neutral CMS settings for bridge renders; no DB migration. */
final class CommerceShowroomBridgeSettings {
    public const LANGUAGES = ['ru', 'fr', 'en'];

    /** @return array{id:string,content:array<string,array<string,mixed>>,videoUrl:string,posterUrl:string,telegramUrl:string,whatsappUrl:string} */
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
            'posterUrl' => self::validate_poster_url((string)($bridge['posterUrl'] ?? '')),
            'telegramUrl' => self::validate_contact_url('telegram', (string)($bridge['telegramUrl'] ?? '')),
            'whatsappUrl' => self::validate_contact_url('whatsapp', (string)($bridge['whatsappUrl'] ?? ''))];
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
            // Preserve the support contacts when saving the legacy Bridge editor.
            'telegramUrl' => self::validate_contact_url('telegram', (string)($oldbridge['telegramUrl'] ?? '')),
            'whatsappUrl' => self::validate_contact_url('whatsapp', (string)($oldbridge['whatsappUrl'] ?? '')),
        ];
        return json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    /**
     * Official public contact links only. Convert supported links to the trusted hosts
     * used by the Lovable Bridge: t.me and wa.me.
     *
     * WhatsApp's ?text= message and Telegram's bot ?start= are official deep links.
     * Any other query parameter, fragment or unrelated host remains forbidden.
     */
    public static function validate_contact_url(string $kind, string $url): string {
        if (!in_array($kind, ['telegram', 'whatsapp'], true)
            || preg_match('/[\x00-\x1f\x7f]/', $url)) {
            throw new \invalid_parameter_exception('Invalid Bridge contact link.');
        }
        $url = trim($url);
        if ($url === '') {
            return '';
        }
        if (strlen($url) > 512 || !filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \invalid_parameter_exception('Invalid Bridge contact link.');
        }
        $parts = parse_url($url);
        if (!is_array($parts) || strtolower((string)($parts['scheme'] ?? '')) !== 'https'
            || isset($parts['user']) || isset($parts['pass']) || isset($parts['port'])
            || isset($parts['fragment'])) {
            throw new \invalid_parameter_exception('Bridge contacts require an official HTTPS link.');
        }
        $host = strtolower((string)($parts['host'] ?? ''));
        $path = (string)($parts['path'] ?? '');
        $query = (string)($parts['query'] ?? '');

        if ($kind === 'telegram') {
            if (!in_array($host, ['t.me', 'telegram.me'], true)
                || !preg_match('~\A/(?:[A-Za-z][A-Za-z0-9_]{4,31}|\+[A-Za-z0-9_-]{8,128}|joinchat/[A-Za-z0-9_-]{8,128})/?\z~D', $path)) {
                throw new \invalid_parameter_exception('Invalid official Telegram URL.');
            }
            if ($query !== '' && !preg_match('/\A(?:start|startgroup)=[A-Za-z0-9_-]{1,64}\z/D', $query)) {
                throw new \invalid_parameter_exception('Unsupported Telegram link parameter.');
            }
            // The Bridge explicitly allows only t.me, never arbitrary external hosts.
            return 'https://t.me' . $path . ($query !== '' ? '?' . $query : '');
        }

        $phone = '';
        $text = null;
        if ($host === 'wa.me') {
            if (preg_match('~\A/([1-9][0-9]{6,14})/?\z~D', $path, $matches)) {
                $phone = $matches[1];
                $params = self::parse_contact_query($query, ['text']);
                $text = $params['text'] ?? null;
            } else if (preg_match('~\A/message/[A-Za-z0-9_-]{8,128}/?\z~D', $path) && $query === '') {
                // WhatsApp Business short links already use the trusted wa.me host.
                return 'https://wa.me' . $path;
            } else {
                throw new \invalid_parameter_exception('Invalid official WhatsApp URL.');
            }
        } else if ($host === 'api.whatsapp.com' && ($path === '/send' || $path === '/send/')) {
            $params = self::parse_contact_query($query, ['phone', 'text']);
            $phone = $params['phone'] ?? '';
            $text = $params['text'] ?? null;
            if (!preg_match('/\A[1-9][0-9]{6,14}\z/D', $phone)) {
                throw new \invalid_parameter_exception('Invalid WhatsApp international phone number.');
            }
        } else {
            throw new \invalid_parameter_exception('Invalid official WhatsApp URL.');
        }
        $result = 'https://wa.me/' . $phone;
        if ($text !== null) {
            $result .= '?text=' . rawurlencode($text);
        }
        return $result;
    }

    /**
     * Parse only explicitly allowed query fields. No duplicate keys, PHP array
     * parameters, malformed percent encodings or invalid UTF-8 values.
     *
     * @param list<string> $allowed
     * @return array<string,string>
     */
    private static function parse_contact_query(string $query, array $allowed): array {
        if ($query === '') {
            return [];
        }
        $values = [];
        foreach (explode('&', $query) as $pair) {
            if (!str_contains($pair, '=')) {
                throw new \invalid_parameter_exception('Unsupported contact link parameter.');
            }
            [$key, $raw] = explode('=', $pair, 2);
            if (!in_array($key, $allowed, true) || array_key_exists($key, $values)
                || strlen($raw) > 512 || preg_match('/%(?![A-Fa-f0-9]{2})/', $raw)) {
                throw new \invalid_parameter_exception('Unsupported contact link parameter.');
            }
            $value = rawurldecode(str_replace('+', ' ', $raw));
            if (preg_match('//u', $value) !== 1 || preg_match('/[\x00-\x1f\x7f]/', $value)) {
                throw new \invalid_parameter_exception('Invalid contact link text encoding.');
            }
            $values[$key] = $value;
        }
        return $values;
    }

    /** Update ONLY contact links, preserving all content, offers and legacy JSON keys. */
    public static function merge_contact_links(string $settingsjson, string $telegram, string $whatsapp): string {
        $settings = json_decode($settingsjson, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($settings) || !is_array($settings['designbridge'] ?? null)
            || ($settings['designbridge']['id'] ?? '') !== 'articles_alf_reimport') {
            throw new \invalid_parameter_exception('Contacts editor requires the Articles / Alf bridge.');
        }
        $settings['designbridge']['telegramUrl'] = self::validate_contact_url('telegram', $telegram);
        $settings['designbridge']['whatsappUrl'] = self::validate_contact_url('whatsapp', $whatsapp);
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
