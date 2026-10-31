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

/**
 * OAuth service for the MCP endpoint.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_xp\local\oauth;

use block_xp\di;
use block_xp\local\routing\url_resolver;
use core_text;
use curl;
use local_xp\local\model\oauth_client;
use local_xp\local\model\oauth_token;
use local_xp\local\utils\agent_utils;
use moodle_database;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/filelib.php');

/**
 * OAuth service for the MCP endpoint.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class service {
    /** @var int Authorization code lifetime. */
    protected const CODE_TTL = 300;
    /** @var int Access token lifetime. */
    protected const ACCESS_TTL = 3600;
    /** @var int Refresh token lifetime. */
    protected const REFRESH_TTL = 2592000;

    /** @var string S256 PKCE code challenge method. */
    protected const CODE_CHALLENGE_METHOD_S256 = 'S256';

    /** @var \block_xp\local\config\config */
    protected $config;
    /** @var moodle_database The database. */
    protected $db;
    /** @var url_resolver The URL resolver. */
    protected $oauthurlresolver;

    /**
     * Constructor.
     *
     * @param moodle_database $db The database.
     * @param url_resolver $oauthurlresolver The URL resolver.
     */
    public function __construct(moodle_database $db, url_resolver $oauthurlresolver) {
        $this->db = $db;
        $this->oauthurlresolver = $oauthurlresolver;
        $this->config = di::get('config');
    }

    /**
     * Get the MCP endpoint URL.
     *
     * @return moodle_url
     */
    public function get_mcp_url(): moodle_url {
        return new moodle_url('/blocks/xp/mcp.php');
    }

    /**
     * Get the issuer.
     *
     * @return string
     */
    public function get_issuer(): string {
        // Closing slash matters for relative .well-known resolution.
        return (new moodle_url('/blocks/xp/oauth.php/'))->out(false);
    }

    /**
     * Get the authorization endpoint URL.
     *
     * @return moodle_url
     */
    public function get_authorize_url(): moodle_url {
        return $this->oauthurlresolver->reverse('authorize');
    }

    /**
     * Get the token endpoint URL.
     *
     * @return moodle_url
     */
    public function get_token_url(): moodle_url {
        return $this->oauthurlresolver->reverse('token');
    }

    /**
     * Get the authorization server metadata URL.
     *
     * @return moodle_url
     */
    public function get_metadata_url(): moodle_url {
        return $this->oauthurlresolver->reverse('metadata');
    }

    /**
     * Get the protected resource metadata URL.
     *
     * @return moodle_url
     */
    public function get_resource_url(): moodle_url {
        return $this->oauthurlresolver->reverse('resource');
    }

    /**
     * Get the JWKS URL.
     *
     * @return moodle_url
     */
    public function get_jwks_url(): moodle_url {
        return $this->oauthurlresolver->reverse('jwks');
    }

    /**
     * Get the supported PKCE code challenge methods.
     *
     * @return string[]
     */
    public function get_code_challenge_methods_supported(): array {
        return [self::CODE_CHALLENGE_METHOD_S256];
    }

    /**
     * Get the client.
     *
     * @param string $clientid
     * @return oauth_client|null
     */
    public function get_client(string $clientid): ?oauth_client {
        return oauth_client::get_by_client_id($clientid);
    }

    /**
     * Whether a redirect URI is acceptable.
     *
     * @param string $uri URI.
     * @return bool
     */
    public function is_valid_redirect_uri(string $uri): bool {
        if ($uri === '' || preg_match('/[\x00-\x20{}]/', $uri)) {
            return false;
        }

        $parts = parse_url($uri);
        if (
            !is_array($parts) || empty($parts['scheme']) || empty($parts['host']) || isset($parts['fragment'])
                || isset($parts['user']) || isset($parts['pass'])
        ) {
            return false;
        }

        if (isset($parts['port']) && ((int) $parts['port'] <= 0 || (int) $parts['port'] > 65535)) {
            return false;
        }

        $scheme = $parts['scheme'];
        if ($scheme === 'https') {
            return true;
        }

        if ($scheme === 'http') {
            $host = strtolower((string) $parts['host']);
            return in_array($host, ['127.0.0.1', '[::1]', 'localhost'], true);
        }

        return false;
    }

    /**
     * Whether a user has usable tokens.
     *
     * This does not validate whether the matching clients are enabled.
     *
     * @param int $userid
     * @return bool
     */
    public function has_usable_tokens(int $userid) {
        return oauth_token::has_usable_grants($userid);
    }

    /**
     * Issue an authorization code.
     *
     * @param int $userid User ID.
     * @param string $clientid Client ID.
     * @param string $redirecturi Redirect URI.
     * @param string $challenge PKCE code challenge.
     * @return string Raw authorization code.
     */
    public function issue_code(int $userid, string $clientid, string $redirecturi, string $challenge): string {
        $code = $this->random_token();
        $now = di::get('clock')->time();

        $token = new oauth_token(0, (object) [
            'type' => oauth_token::TYPE_CODE,
            'tokenhash' => $this->hash_token($code),
            'familyhash' => $this->hash_token($this->random_token()),
            'userid' => $userid,
            'clientid' => $clientid,
            'redirecturi' => $redirecturi,
            'codechallenge' => $challenge,
            'expires' => $now + self::CODE_TTL,
        ]);
        $token->save();

        return $code;
    }

    /**
     * Exchange an authorization code.
     *
     * @param oauth_client $client The client.
     * @param string $code Code.
     * @param string $redirecturi Redirect URI.
     * @param ?string $verifier PKCE verifier.
     * @param string $resource Resource indicator.
     * @return array<string, mixed>
     */
    public function exchange_code(
        oauth_client $client,
        string $code,
        string $redirecturi,
        ?string $verifier,
        string $resource
    ): array {

        if (!$this->is_valid_resource($resource)) {
            throw new oauth_exception('invalid_resource', 400);
        }

        if (!$client->is_active() || !$client->has_redirect_url($redirecturi)) {
            throw new oauth_exception('invalid_client', 400);
        }

        $oauthtoken = $this->get_active_token_by_hash(oauth_token::TYPE_CODE, $this->hash_token($code));
        if (
            !$oauthtoken || !$oauthtoken->is_active() || $oauthtoken->get_client_id() !== $client->get_client_id()
                || $oauthtoken->get('redirecturi') !== $redirecturi
        ) {
            throw new oauth_exception('invalid_code', 400);
        }

        if ($client->is_pkce_required()) {
            if (!preg_match('/^[A-Za-z0-9._~-]{43,128}$/', $verifier)) {
                throw new oauth_exception('invalid_code_challenge', 400);
            }
            $challenge = $this->base64url(hash('sha256', $verifier, true));
            if (!$oauthtoken->is_valid_challenge($challenge)) {
                throw new oauth_exception('invalid_code_challenge', 400);
            }
        }

        return $this->issue_token_pair($oauthtoken);
    }

    /**
     * Refresh an access token.
     *
     * @param string $refreshtoken Refresh token.
     * @param string $clientid Client ID.
     * @param string $resource Resource indicator.
     * @return array<string, mixed>
     */
    public function refresh(string $refreshtoken, string $clientid, string $resource): array {
        if (!$this->is_valid_resource($resource)) {
            throw new oauth_exception('invalid_resource', 400);
        }

        $client = $this->get_client($clientid);
        if (!$client || !$client->is_active()) {
            throw new oauth_exception('invalid_client', 400);
        }

        $tokenhash = $this->hash_token($refreshtoken);
        $oauthtoken = $this->get_active_token_by_hash(oauth_token::TYPE_REFRESH, $tokenhash);
        if (!$oauthtoken || $oauthtoken->get_client_id() !== $client->get_client_id()) {
            throw new oauth_exception('invalid_token', 400);
        }

        return $this->issue_token_pair($oauthtoken);
    }

    /**
     * Revoke all tokens for a user.
     *
     * @param int $userid
     */
    public function revoke_all_user_grants(int $userid) {
        oauth_token::revoke_all_user_grants($userid);
    }

    /**
     * Revoke all tokens for a client.
     *
     * @param string $clientid
     */
    public function revoke_all_client_grants(string $clientid) {
        oauth_token::revoke_all_client_grants($clientid);
    }

    /**
     * Authenticate a bearer access token.
     *
     * @param string $token Access token.
     * @return \stdClass|null User record.
     */
    public function authenticate_bearer(string $token): ?\stdClass {
        [$token, $client] = oauth_token::get_active_token_and_client(oauth_token::TYPE_ACCESS, $this->hash_token($token));
        if (!$token || !$client) {
            return null;
        } else if (!$client->is_active()) {
            return null;
        } else if (!$token->is_active()) {
            return null;
        }

        $user = $this->db->get_record('user', ['id' => $token->get('userid'), 'deleted' => 0, 'suspended' => 0, 'confirmed' => 1]);
        if (!$user || isguestuser($user)) {
            return null;
        }
        return $user;
    }

    /**
     * Issue an access/refresh token pair.
     *
     * @param oauth_token $token The oauth token.
     * @return array<string, mixed>
     */
    protected function issue_token_pair(oauth_token $token): array {
        $now = di::get('clock')->time();
        $access = $this->random_token();
        $refresh = $this->random_token();

        $accesshash = $this->hash_token($access);
        $familyhash = $token->get('familyhash');
        $userid = (int) $token->get('userid');
        $clientid = $token->get_client_id();
        $redirecturi = $token->get('redirecturi');

        $tx = $this->db->start_delegated_transaction();

        // Revoke exchange token.
        $token->revoke();

        // Create access token.
        $at = new oauth_token(0, (object) [
            'type' => oauth_token::TYPE_ACCESS,
            'tokenhash' => $accesshash,
            'familyhash' => $familyhash,
            'userid' => $userid,
            'clientid' => $clientid,
            'redirecturi' => $redirecturi,
            'codechallenge' => null,
            'expires' => $now + self::ACCESS_TTL,
        ]);
        $at->save();

        // Create refresh token.
        $rt = new oauth_token(0, (object) [
            'type' => oauth_token::TYPE_REFRESH,
            'tokenhash' => $this->hash_token($refresh),
            'familyhash' => $familyhash,
            'userid' => $userid,
            'clientid' => $clientid,
            'redirecturi' => $redirecturi,
            'codechallenge' => null,
            'expires' => $now + self::REFRESH_TTL,
        ]);
        $rt->save();

        $tx->allow_commit();

        return [
            'access_token' => $access,
            'token_type' => 'Bearer',
            'expires_in' => self::ACCESS_TTL,
            'refresh_token' => $refresh,
        ];
    }

    /**
     * Get an active token record by token hash.
     *
     * @param string $type Type.
     * @param string $tokenhash Token hash.
     * @return ?oauth_token
     */
    protected function get_active_token_by_hash(string $type, string $tokenhash): ?oauth_token {
        [$token, $client] = oauth_token::get_active_token_and_client($type, $tokenhash);
        if (!$token) {
            return null;
        }
        return $token;
    }

    /**
     * Register a client from client ID metadata document.
     *
     * @param string $clientid Client ID metadata document URL.
     * @return oauth_client
     */
    public function register_cimd_client(string $clientid): oauth_client {
        $metadataerror = '';
        $metadata = $this->fetch_client_metadata($clientid, $metadataerror);
        $metadatafound = $metadata !== null;
        $metadata ??= [];

        $redirecturis = is_array($metadata['redirect_uris'] ?? null) ? $metadata['redirect_uris'] : [];
        $redirecturisvalid = !empty($redirecturis);
        foreach ($redirecturis as $uri) {
            if (!is_string($uri) || !$this->is_valid_redirect_uri($uri)) {
                $redirecturisvalid = false;
                break;
            }
        }

        $invalidhint = null;
        if (!$metadatafound) {
            $invalidhint = $metadataerror ?: 'metadatafetchfailed';
        } else if (($metadata['client_id'] ?? null) !== $clientid) {
            $invalidhint = 'clientidmismatch';
        } else if (!$redirecturisvalid) {
            $invalidhint = 'invalidredirecturis';
        } else if (($metadata['token_endpoint_auth_method'] ?? 'none') !== 'none') {
            $invalidhint = 'invalidauthmethod';
        }
        $isvalid = $invalidhint === null;

        $name = trim((string) ($metadata['client_name'] ?? ''));
        if (!$name) {
            $name = preg_replace('@^https?://([^/]+)/.*@i', '\\1', $clientid) ?: 'Unknown';
        }

        $rawlogourl = $metadata['logo_uri'] ?? '';
        $rawlogourl = is_string($rawlogourl) ? $rawlogourl : '';

        $client = new oauth_client(0, (object) [
            'clientid' => $clientid,
            'type' => oauth_client::TYPE_CIMD,
            'displayname' => core_text::substr($name, 0, 255),
            'rawlogourl' => $rawlogourl,
            'logofilename' => null,
            'redirecturis' => implode(',', $redirecturis),
            'isenabled' => $isvalid,
            'isvalid' => $isvalid,
            'invalidhint' => !$isvalid ? core_text::substr($invalidhint ?? '', 0, 255) : null,
            'lastmetadatacheck' => di::get('clock')->time(),
        ]);
        $client->create();

        return $client;
    }

    /**
     * Whether a client ID metadata document URL.
     *
     * @param string $url URL.
     * @return bool
     */
    public function is_trusted_cimd_url(string $url): bool {
        $parts = parse_url($url);
        if (!is_array($parts)) {
            return false;
        } else if (($parts['scheme'] ?? '') !== 'https') {
            return false;
        } else if (empty($parts['host'])) {
            return false;
        } else if (isset($parts['fragment']) || isset($parts['user']) || isset($parts['pass'])) {
            return false;
        }

        // The specs require a path, and disallow single-dot or double-dot segments.
        if (!isset($parts['path']) || $parts['path'] === '') {
            return false;
        }
        foreach (explode('/', $parts['path']) as $segment) {
            $segment = rawurldecode($segment);
            if ($segment === '.' || $segment === '..') {
                return false;
            }
        }

        // Validate against the admin setting.
        $rawdomains = $this->config->get('oauthcimddomains') ?: '';
        $alloweddomains = array_values(array_filter(array_map('trim', explode("\n", $rawdomains))));
        if ((bool) $this->config->get('enablechatgpt')) {
            $alloweddomains = array_merge($alloweddomains, agent_utils::get_chatgpt_cimd_domains());
        }
        if ((bool) $this->config->get('enableclaude')) {
            $alloweddomains = array_merge($alloweddomains, agent_utils::get_claude_cimd_domains());
        }

        // Wildcard match.
        if (in_array('*', $alloweddomains)) {
            return true;
        }

        return in_array($parts['host'], $alloweddomains, true);
    }

    /**
     * Fetch a client metadata document.
     *
     * @param string $url URL.
     * @param string|null $error Error hint.
     * @return array<string, mixed>|null
     */
    protected function fetch_client_metadata(string $url, ?string &$error = null): ?array {
        $error = null;

        $curl = new curl();
        $curl->setHeader(['Accept: application/json']);
        $response = $curl->get($url, [], [
            'CURLOPT_TIMEOUT' => 10,
            'CURLOPT_CONNECTTIMEOUT' => 5,
            'CURLOPT_FOLLOWLOCATION' => 0,
            'CURLOPT_MAXREDIRS' => 0,
            'CURLOPT_MAXFILESIZE' => 5 * 1024,
            'CURLOPT_SSL_VERIFYPEER' => 1,
            'CURLOPT_SSL_VERIFYHOST' => 2,
        ]);
        $errno = $curl->get_errno();
        if ($errno) {
            $message = trim((string) $curl->error);
            $error = 'curlerror:' . $errno . ($message !== '' ? ':' . core_text::substr($message, 0, 200) : '');
            return null;
        }

        $info = $curl->get_info();
        $httpcode = (int) ($info['http_code'] ?? 0);
        if ($httpcode !== 200) {
            $error = 'httperror:' . $httpcode;
            return null;
        }

        $metadata = json_decode($response, true);
        if (!is_array($metadata)) {
            $error = 'invalidjson';
            return null;
        }

        return $metadata;
    }

    /**
     * Whether the resource indicator targets this MCP endpoint.
     *
     * @param string $resource Resource indicator.
     * @return bool
     */
    public function is_valid_resource(string $resource): bool {
        $expected = $this->parse_resource_uri($this->get_mcp_url()->out(false));
        $actual = $this->parse_resource_uri($resource);
        if (!$expected || !$actual) {
            return false;
        }

        return $actual['scheme'] === $expected['scheme']
            && $actual['host'] === $expected['host']
            && $actual['port'] === $expected['port']
            && $actual['path'] === $expected['path']
            && $actual['query'] === $expected['query'];
    }

    /**
     * Parse a resource URI for comparison.
     *
     * @param string $uri URI.
     * @return array<string, string|int|null>|null
     */
    protected function parse_resource_uri(string $uri): ?array {
        if ($uri === '' || preg_match('/[\x00-\x20]/', $uri)) {
            return null;
        }

        $parts = parse_url($uri);
        if (
            !is_array($parts) || empty($parts['scheme']) || empty($parts['host']) || isset($parts['fragment'])
                || isset($parts['user']) || isset($parts['pass'])
        ) {
            return null;
        }

        $scheme = strtolower($parts['scheme']);
        $host = strtolower($parts['host']);
        if (!preg_match('/^[a-z][a-z0-9+.-]{1,63}$/', $scheme)) {
            return null;
        }

        return [
            'scheme' => $scheme,
            'host' => $host,
            'port' => $this->normalise_port($scheme, $parts['port'] ?? null),
            'path' => $parts['path'] ?? '',
            'query' => $parts['query'] ?? null,
        ];
    }

    /**
     * Normalise an URI port.
     *
     * @param string $scheme Scheme.
     * @param int|null $port Port.
     * @return int|null
     */
    protected function normalise_port(string $scheme, ?int $port): ?int {
        if ($port !== null) {
            return $port;
        } else if ($scheme === 'http') {
            return 80;
        } else if ($scheme === 'https') {
            return 443;
        }
        return null;
    }

    /**
     * Generate a random token.
     *
     * @return string
     */
    protected function random_token(): string {
        return $this->base64url(random_bytes(32));
    }

    /**
     * Hash a token for storage.
     *
     * @param string $token Raw token.
     * @return string
     */
    protected function hash_token(string $token): string {
        return hash('sha256', $token);
    }

    /**
     * Base64 URL encode.
     *
     * @param string $bytes Bytes.
     * @return string
     */
    protected function base64url(string $bytes): string {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }
}
