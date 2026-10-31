<?php
// This file is part of Level Up XP+.
//
// Level Up XP+ is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Level Up XP+ is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Level Up XP+.  If not, see <https://www.gnu.org/licenses/>.
//
// See <https://levelup.plus>.

namespace local_xp\local\model;

use context_system;
use core\persistent;
use moodle_url;
use Override;

/**
 * OAuth client persistent.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class oauth_client extends persistent {
    /** Database table. */
    const TABLE = 'local_xp_oauth_client';

    /** Manual type. */
    const TYPE_MANUAL = 0;
    /** CIMD type. */
    const TYPE_CIMD = 1;

    /** Token auth none. */
    const TOKEN_ENDPOINT_AUTH_NONE = 'none';
    /** Token auth client secret basic. */
    const TOKEN_ENDPOINT_AUTH_CLIENT_SECRET_BASIC = 'client_secret_basic';

    /**
     * Define properties.
     *
     * @return array
     */
    protected static function define_properties(): array {
        return [
            'clientid' => [
                'type' => PARAM_RAW,
                'default' => static function () {
                    return bin2hex(random_bytes(32));
                },
            ],
            'clientsecret' => [
                'type' => PARAM_RAW,
                'null' => NULL_ALLOWED,
                'default' => null,
            ],
            'type' => [
                'type' => PARAM_INT,
                'choices' => [self::TYPE_MANUAL, self::TYPE_CIMD],
                'default' => self::TYPE_MANUAL,
            ],
            'displayname' => [
                'type' => PARAM_RAW,
            ],
            'rawlogourl' => [
                'type' => PARAM_RAW,
                'null' => NULL_ALLOWED,
                'default' => null,
            ],
            'logofilename' => [
                'type' => PARAM_RAW,
                'null' => NULL_ALLOWED,
                'default' => null,
            ],
            'redirecturis' => [
                'type' => PARAM_RAW,
                'default' => '',
            ],
            'isenabled' => [
                'type' => PARAM_BOOL,
                'default' => 0,
            ],
            'isvalid' => [
                'type' => PARAM_BOOL,
                'default' => 0,
            ],
            'invalidhint' => [
                'type' => PARAM_RAW,
                'null' => NULL_ALLOWED,
                'default' => null,
            ],
            'lastmetadatacheck' => [
                'type' => PARAM_INT,
                'default' => 0,
            ],
        ];
    }

    /**
     * Delete related OAuth tokens.
     *
     * @param bool $result Whether the client was deleted.
     * @return void
     */
    protected function after_delete($result) {
        global $DB;
        if ($result) {
            $DB->delete_records(oauth_token::TABLE, ['clientid' => $this->raw_get('clientid')]);
        }
    }

    /**
     * Prepare the client data.
     *
     * @return void
     */
    protected function before_create() {
        if ($this->get('type') === static::TYPE_CIMD) {
            if (!$this->get('rawlogourl') && strpos($this->get('clientid'), 'https://claude.ai/') === 0) {
                $this->set('rawlogourl', 'https://upload.wikimedia.org/wikipedia/commons/b/b0/Claude_AI_symbol.svg');
            }
        }

        if ($this->get('type') === static::TYPE_MANUAL && $this->get('clientsecret') === null) {
            $this->set('clientsecret', bin2hex(random_bytes(32)));
        }
    }

    /**
     * Get the client ID.
     *
     * @return string
     */
    public function get_client_id(): string {
        return $this->get('clientid');
    }

    /**
     * Get PKCE code challenge methods supported.
     *
     * @return string[]
     */
    public function get_code_challenge_methods_supported(): array {
        if ($this->is_pkce_required()) {
            return ['S256'];
        }
        return [];
    }


    /**
     * Get the logo URL.
     *
     * @return moodle_url|null
     */
    public function get_logo_url(): ?moodle_url {
        $fn = $this->get('logofilename');
        if ($fn === '.') {
            return null;
        } else if (!empty($fn)) {
            return moodle_url::make_pluginfile_url(
                context_system::instance()->id,
                'local_xp',
                'oauthclient',
                $this->get('id'),
                '/',
                $fn,
                false
            );
        }

        $iscimd = $this->get('type') === static::TYPE_CIMD;
        $logourl = (string) $this->get('rawlogourl');
        if (!$iscimd || !preg_match('@^https?://@', $logourl)) {
            return null;
        }

        $context = context_system::instance();
        $filerecord = (object) [
            'contextid' => $context->id,
            'component' => 'local_xp',
            'filearea' => 'oauthclient',
            'itemid' => $this->get('id'),
            'filepath' => '/',
        ];
        $fs = get_file_storage();

        $file = null;
        try {
            $file = $fs->create_file_from_url($filerecord, $logourl, ['timeout' => 10, 'connecttimeout' => 5]);
        } catch (\Throwable $e) {
            unset($e);
        }

        if ($file) {
            $fn = $file->get_filename();
            if (strpos($file->get_mimetype(), 'image/') !== 0) {
                try {
                    $file->delete();
                } catch (\Throwable $e) {
                    unset($e);
                }
                $fn = '.';
            }
        }

        $this->set('logofilename', $fn ?: '.');
        $this->save();
        return $this->get_logo_url();
    }

    /**
     * Get the origin from the client ID.
     *
     * @return string|null
     */
    public function get_origin(): ?string {
        if ($this->get('type') !== static::TYPE_CIMD) {
            return null;
        }
        return preg_replace('@^https?://([^/]+)/.*@i', '\\1', $this->get('clientid')) ?: 'Unknown';
    }

    /**
     * Get the redirect URLs.
     *
     * @return string[]
     */
    public function get_redirect_urls(): array {
        $urls = array_map('trim', explode(',', (string) $this->get('redirecturis')));
        return array_values(array_filter($urls, function ($url): bool {
            return preg_match('@^https?://@i', $url) === 1;
        }));
    }

    /**
     * Get the token endpoint auth method.
     *
     * @return string
     */
    public function get_token_endpoint_auth_method(): string {
        $type = $this->get('type');
        if ($type === static::TYPE_CIMD) {
            return static::TOKEN_ENDPOINT_AUTH_NONE;
        }

        if ($this->get('clientsecret') === null) {
            return static::TOKEN_ENDPOINT_AUTH_NONE;
        }

        return static::TOKEN_ENDPOINT_AUTH_CLIENT_SECRET_BASIC;
    }

    /**
     * Get the type.
     *
     * @return int
     */
    protected function get_type(): int {
        return (int) $this->raw_get('type');
    }

    /**
     * Is PKCE required.
     *
     * @return bool
     */
    public function is_pkce_required(): bool {
        return $this->get_token_endpoint_auth_method() === 'none';
    }

    /**
     * Whether has the given redirect URL.
     *
     * @param string $url
     * @return bool
     */
    public function has_redirect_url(string $url): bool {
        return in_array($url, $this->get_redirect_urls(), true);
    }

    /**
     * Whether the client is active.
     *
     * @return bool
     */
    public function is_active(): bool {
        return $this->get('isenabled') && $this->get('isvalid');
    }

    /**
     * Get a client by client ID.
     *
     * @param string $clientid Client ID.
     * @return self|null
     */
    public static function get_by_client_id(string $clientid): ?self {
        $client = static::get_record(['clientid' => $clientid]);
        return $client ?: null;
    }

    /**
     * Toggle CIMD clients by domains.
     *
     * @param array $domains
     */
    public static function toggle_cimd_isenabled_by_domains(array $domains, bool $isenabled) {
        $clients = static::get_records(['type' => static::TYPE_CIMD]);
        foreach ($clients as $client) {
            $host = parse_url($client->get_client_id(), PHP_URL_HOST);
            if (!is_string($host) || !in_array(strtolower($host), $domains, true)) {
                continue;
            }
            $client->set('isenabled', $isenabled);
            $client->save();
        }
    }
}
