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

use block_xp\di;
use core\persistent;
use lang_string;

/**
 * OAuth token persistent.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class oauth_token extends persistent {
    /** Database table. */
    const TABLE = 'local_xp_oauth_token';

    /** Type authorisation code. */
    const TYPE_CODE = 'code';
    /** Type access token. */
    const TYPE_ACCESS = 'access';
    /** Type refresh token. */
    const TYPE_REFRESH = 'refresh';

    /** TTL for code. */
    const TTL_CODE = 300;
    /** TTL for access token. */
    const TTL_ACCESS = 3600;
    /** TTL for refresh token. */
    const TTL_REFRESH = 2592000;

    /**
     * Define properties.
     *
     * @return array
     */
    protected static function define_properties(): array {
        return [
            'type' => [
                'type' => PARAM_ALPHANUMEXT,
                'choices' => [self::TYPE_CODE, self::TYPE_ACCESS, self::TYPE_REFRESH],
            ],
            'tokenhash' => [
                'type' => PARAM_ALPHANUM,
            ],
            'familyhash' => [
                'type' => PARAM_ALPHANUM,
            ],
            'userid' => [
                'type' => PARAM_INT,
            ],
            'clientid' => [
                'type' => PARAM_RAW,
            ],
            'redirecturi' => [
                'type' => PARAM_RAW,
            ],
            'codechallenge' => [
                'type' => PARAM_RAW,
                'null' => NULL_ALLOWED,
                'default' => null,
            ],
            'expires' => [
                'type' => PARAM_INT,
            ],
            'revoked' => [
                'type' => PARAM_BOOL,
                'default' => false,
            ],
        ];
    }

    /**
     * Get the client ID.
     *
     * @return string
     */
    public function get_client_id(): string {
        return (string) $this->get('clientid');
    }

    /**
     * Is active.
     *
     * @return bool
     */
    public function is_active(): bool {
        return $this->get('expires') > di::get('clock')->time() && !$this->get('revoked');
    }

    /**
     * Check if valid challenge.
     *
     * @param string $challenge External verifier for exact match.
     * @return bool
     */
    public function is_valid_challenge(string $challenge): bool {
        $ownchallenge = $this->get('codechallenge');
        if (!$ownchallenge) {
            return false;
        }
        return hash_equals($ownchallenge, $challenge);
    }

    /**
     * Revoke the token.
     *
     * @return void
     */
    public function revoke() {
        $this->set('revoked', true);
        $this->save();
    }

    /**
     * Validate client ID.
     *
     * @param string $value
     */
    protected function validate_clientid($value) {
        if (!oauth_client::record_exists_select('clientid = ?', [$value])) {
            return new lang_string('invaliddata', 'core_error');
        }
        return true;
    }

    /**
     * Validate the code challenge.
     *
     * @param mixed $value The value.
     * @return lang_string|true
     */
    protected function validate_codechallenge($value) {
        if ($this->get('type') !== static::TYPE_CODE) {
            return true;
        }
        if (!is_string($value) || !preg_match('/^[A-Za-z0-9.~_-]{43,128}$/', $value)) {
            return new lang_string('invaliddata', 'core_error');
        }
        return true;
    }

    /**
     * Validate the token hash.
     *
     * @param string $value
     */
    protected function validate_familyhash($value) {
        if (!preg_match('/^[a-z0-9]{64}$/', $value)) {
            return new lang_string('invaliddata', 'core_error');
        }
        return true;
    }

    /**
     * Validate the token hash.
     *
     * @param string $value
     */
    protected function validate_tokenhash($value) {
        if (!preg_match('/^[a-z0-9]{64}$/', $value)) {
            return new lang_string('invaliddata', 'core_error');
        }
        return true;
    }

    /**
     * Delete obsolete records.
     *
     * @return void
     */
    public static function delete_obsolete(): void {
        $db = di::get('db');
        $recordset = $db->get_recordset_select(
            static::TABLE,
            'expires <= :now OR revoked = :revoked',
            [
                'now' => di::get('clock')->time(),
                'revoked' => 1,
            ]
        );
        foreach ($recordset as $record) {
            $persistent = new static(0, $record);
            $persistent->delete();
        }
        $recordset->close();
    }

    /**
     * Get an active token with its client.
     *
     * @param string $type Type.
     * @param string $tokenhash Token hash.
     * @return array{0: self|null, 1: oauth_client|null}
     */
    public static function get_active_token_and_client(string $type, string $tokenhash): array {
        $db = di::get('db');
        $tokenfields = static::get_sql_fields('t', 'tk_');
        $clientfields = oauth_client::get_sql_fields('c', 'oc_');
        $sql = "SELECT $tokenfields, $clientfields
                  FROM {" . static::TABLE . "} t
                  JOIN {" . oauth_client::TABLE . "} c
                    ON c.clientid = t.clientid
                 WHERE t.type = :type
                   AND t.tokenhash = :tokenhash
                   AND t.revoked = :revoked
                   AND t.expires > :now
                   AND c.isenabled = :isenabled
                   AND c.isvalid = :isvalid";
        $record = $db->get_record_sql($sql, [
            'type' => $type,
            'tokenhash' => $tokenhash,
            'revoked' => 0,
            'now' => di::get('clock')->time(),
            'isenabled' => 1,
            'isvalid' => 1,
        ]);

        if (!$record) {
            return [null, null];
        }

        return [
            new static(0, static::extract_record($record, 'tk_')),
            new oauth_client(0, oauth_client::extract_record($record, 'oc_')),
        ];
    }

    /**
     * Whether the user has usable grants.
     *
     * This does not care whether the matching clients are enabled.
     *
     * @param int $userid
     * @return bool
     */
    public static function has_usable_grants(int $userid) {
        $params = [
            'userid' => $userid,
            'expires' => di::get('clock')->time(),
            'type1' => static::TYPE_ACCESS,
            'type2' => static::TYPE_REFRESH,
        ];
        $sql = 'userid = :userid AND revoked = 0 AND expires > :expires AND type IN (:type1, :type2)';
        return static::record_exists_select($sql, $params);
    }

    /**
     * Revoke all grants for a client.
     *
     * @param string $clientid
     */
    public static function revoke_all_client_grants(string $clientid) {
        $db = di::get('db');
        $db->set_field(static::TABLE, 'revoked', 1, ['clientid' => $clientid]);
    }

    /**
     * Revoke all for a user.
     *
     * @param int $userid
     */
    public static function revoke_all_user_grants(int $userid) {
        $db = di::get('db');
        $db->set_field(static::TABLE, 'revoked', 1, ['userid' => $userid]);
    }

    /**
     * Revoke all of a user's grants.
     *
     * @param int $userid
     * @param string $familyhash
     */
    public static function revoke_user_grants(int $userid, string $familyhash) {
        $db = di::get('db');
        $db->set_field(static::TABLE, 'revoked', 1, ['userid' => $userid, 'familyhash' => $familyhash]);
    }
}
