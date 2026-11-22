<?php
// CampusFR C2.2c.3. Manual Native course revocation, provenance-scoped, fail-closed.
declare(strict_types=1);

namespace local_subscriptions\commerce\grant;

defined('MOODLE_INTERNAL') || die();

/**
 * Revokes exactly one manually issued Native course grant.
 *
 * Intentionally does not implement bundle, cohort, group, promotion-join or
 * digital revocation. Unsupported provenance/side effects fail closed.
 * No purchase rows are created or modified, and no automated revocation is run.
 * All calls must originate from a privileged administrative entry point.
 */
final class CommerceManualCourseGrantRevocationService {
    private const GRANTS = 'local_subs_commerce_grant';
    private const FULFILLMENTS = 'local_subs_commerce_ful_state';
    private const ACTIVE = ['active', 'granted', 'completed'];
    private const PURCHASES = \local_subscriptions\commerce\persistence\CommercePersistenceSchema::TABLE_PURCHASE;

    public function __construct(private readonly \moodle_database $db) {
    }

    /**
     * Preview the only supported manual course grant. Never mutates data.
     * @return array{grantreference:string,userid:int,courseid:int,status:string,enrolmentstatus:string}
     */
    public function preview(string $grantreference): array {
        $checked = $this->inspect($grantreference);
        return [
            'grantreference' => (string)$checked['grant']->grantreference,
            'userid' => $checked['userid'],
            'courseid' => $checked['courseid'],
            'status' => (string)$checked['grant']->status,
            'enrolmentstatus' => $checked['enrolmentstatus'],
        ];
    }

    /**
     * @return array{revoked:int,unenrolled:int,preserved:int,courseid:int,userid:int}
     */
    public function revoke(string $grantreference, int $actoruserid, string $reason): array {
        if ($actoruserid <= 0 || trim($reason) === '' || strlen($reason) > 500) {
            throw new \coding_exception('Manual course grant revocation requires actor and a reason (1..500 chars).');
        }
        $actor = $this->db->get_record('user', ['id' => $actoruserid, 'deleted' => 0], 'id', IGNORE_MISSING);
        if (!$actor || !is_siteadmin($actoruserid)) {
            throw new \coding_exception('Manual course grant revocation is restricted to site administrators.');
        }
        $grantreference = trim($grantreference);
        if ($grantreference === '' || strlen($grantreference) > 128) {
            throw new \coding_exception('Invalid manual grant reference.');
        }

        $lock = \core\lock\lock_config::get_lock_factory('local_subscriptions_manual_revocation')
            ->get_lock('grant-' . hash('sha256', $grantreference), 10);
        if (!$lock) {
            throw new \RuntimeException('Manual grant revocation lock unavailable.');
        }
        try {
            $tx = $this->db->start_delegated_transaction();
            try {
                $checked = $this->inspect($grantreference);
                $grant = $checked['grant'];
                $courseid = $checked['courseid'];
                $userid = $checked['userid'];
                $counts = ['revoked' => 0, 'unenrolled' => 0, 'preserved' => 0,
                    'courseid' => $courseid, 'userid' => $userid];

                if ((string)$grant->status === 'revoked') {
                    $tx->allow_commit();
                    return $counts;
                }
                if (!in_array((string)$grant->status, self::ACTIVE, true)) {
                    throw new \coding_exception('Manual grant status cannot be safely revoked.');
                }

                // Only the selected grant changes status; every alternative is inspected AFTER that change.
                $now = time();
                $metadata = $checked['metadata'];
                $metadata['rights_revocation'] = [
                    'source' => 'admin_explicit_manual_revocation',
                    'revokedat' => $now,
                    'revokedbyuserid' => $actoruserid,
                    'reason' => trim($reason),
                ];
                $this->db->update_record(self::GRANTS, (object)[
                    'id' => (int)$grant->id,
                    'status' => 'revoked',
                    'metadatajson' => json_encode($metadata, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                    'timemodified' => $now,
                ]);
                $counts['revoked'] = 1;

                $alternative = $this->has_native_alternative($userid, $courseid, $now, (int)$grant->id)
                    || $this->has_legacy_alternative($userid, $courseid, $now);
                // A later grant may have UPDATED an enrolment CREATED by an earlier
                // Commerce grant. Only the last revocation may remove it.
                $ownedenrolid = $this->find_commerce_owned_enrolment($userid, $courseid);
                if ($alternative || $ownedenrolid === null) {
                    // Never remove another grant's Moodle access or an enrolment that predates this grant.
                    $counts['preserved'] = 1;
                } else {
                    $enrolid = $ownedenrolid;
                    $instance = $this->db->get_record('enrol', [
                        'id' => $enrolid, 'courseid' => $courseid, 'enrol' => 'manual',
                    ], '*', IGNORE_MISSING);
                    if (!$instance) {
                        throw new \coding_exception('Owned Moodle enrolment instance disappeared.');
                    }
                    $existing = $this->db->get_record('user_enrolments', [
                        'enrolid' => $enrolid, 'userid' => $userid,
                    ], 'id', IGNORE_MISSING);
                    if ($existing) {
                        $manual = enrol_get_plugin('manual');
                        if (!$manual) {
                            throw new \coding_exception('Moodle manual enrolment plugin is unavailable.');
                        }
                        $manual->unenrol_user($instance, $userid);
                        $counts['unenrolled'] = 1;
                    }
                }

                $tx->allow_commit();
                return $counts;
            } catch (\Throwable $e) {
                $tx->rollback($e);
                throw $e;
            }
        } finally {
            $lock->release();
        }
    }

    /**
     * @return array{grant:\stdClass,userid:int,courseid:int,enrolmentstatus:string,enrolid:int,metadata:array}
     */
    private function inspect(string $reference): array {
        $reference = trim($reference);
        if (!preg_match('/^ent-manual-[a-f0-9]{32}$/', $reference)) {
            throw new \coding_exception('Only exact deterministic manual Native grant references are supported.');
        }
        $grant = $this->db->get_record(self::GRANTS, ['grantreference' => $reference], '*', MUST_EXIST);
        $metadata = json_decode((string)$grant->metadatajson, true);
        $config = json_decode((string)$grant->configurationjson, true);
        if (!is_array($metadata) || !is_array($config)
                || ($metadata['source'] ?? '') !== CommerceManualProductGrantService::SOURCE
                || (int)($metadata['rootproductid'] ?? 0) <= 0
                || (string)$grant->type !== 'course_access') {
            throw new \coding_exception('Manual course grant provenance/type mismatch.');
        }
        $userid = (int)$grant->beneficiaryuserid;
        $courseid = (int)($config['courseid'] ?? 0);
        $source = (string)$grant->purchasereference;
        $expectedprefix = 'manual-u' . $userid . '-p' . (int)$metadata['rootproductid'] . '-';
        if ($userid <= 0 || $courseid <= 0 || !str_starts_with($source, $expectedprefix)
                || !preg_match('/^manual-u\d+-p\d+-[a-f0-9]{16}$/', $source)
                || (string)$grant->resourcekey !== 'course:' . $courseid . ':full'
                || (string)($config['roleshortname'] ?? 'student') !== 'student') {
            throw new \coding_exception('Manual course grant scope/provenance mismatch.');
        }
        if ($this->db->record_exists(self::PURCHASES, ['reference' => $source])) {
            throw new \coding_exception('Manual source must not be a purchase.');
        }
        if ($this->db->count_records(self::GRANTS, ['purchasereference' => $source]) !== 1) {
            // A bundle may contain multiple rights or have group/cohort side effects.
            throw new \coding_exception('Multiple source grants are unsupported: refuse partial revocation.');
        }
        foreach (['groupids','groupnames','cohortids','cohortnames'] as $key) {
            if (!empty($config[$key])) {
                throw new \coding_exception('Group/cohort side effects require a dedicated revoker.');
            }
        }
        $state = $this->db->get_record(self::FULFILLMENTS, [
            'grantreference' => $reference,
        ], '*', MUST_EXIST);
        if ((string)$state->status !== 'completed') {
            throw new \coding_exception('Only completed manual course fulfillment may be revoked.');
        }
        $payload = json_decode((string)$state->lastpayloadjson, true);
        $enrolment = is_array($payload) ? ($payload['enrolment'] ?? null) : null;
        $role = is_array($payload) ? ($payload['role'] ?? null) : null;
        if (!is_array($payload) || !is_array($enrolment) || !is_array($role)
                || (int)($payload['courseid'] ?? 0) !== $courseid
                || (int)($payload['userid'] ?? 0) !== $userid
                || (int)($enrolment['courseid'] ?? 0) !== $courseid
                || (int)($enrolment['userid'] ?? 0) !== $userid
                || !in_array((string)($enrolment['status'] ?? ''), ['created', 'updated'], true)
                || !empty($payload['groups']) || !empty($payload['cohorts'])
                || !empty($role['removed'])) {
            throw new \coding_exception('Manual course fulfillment has unsupported side effects.');
        }
        $enrolstatus = (string)$enrolment['status'];
        $enrolid = (int)($enrolment['enrolid'] ?? 0);
        if ($enrolid <= 0) {
            throw new \coding_exception('Manual fulfillment enrolment provenance missing.');
        }
        return ['grant' => $grant, 'userid' => $userid, 'courseid' => $courseid,
            'enrolmentstatus' => $enrolstatus, 'enrolid' => $enrolid, 'metadata' => $metadata];
    }

    /** Resolve ownership from completed fulfillment evidence, including a previously revoked grant. */
    private function find_commerce_owned_enrolment(int $userid, int $courseid): ?int {
        $sql = 'SELECT s.id, s.lastpayloadjson
                  FROM {local_subs_commerce_ful_state} s
                  JOIN {local_subs_commerce_grant} g ON g.grantreference = s.grantreference
                 WHERE g.beneficiaryuserid = :userid
                   AND g.type = :type
                   AND g.resourcekey LIKE :resource
                   AND s.status = :completed';
        $owned = null;
        foreach ($this->db->get_records_sql($sql, [
            'userid' => $userid, 'type' => 'course_access',
            'resource' => 'course:' . $courseid . ':%', 'completed' => 'completed',
        ]) as $record) {
            $payload = json_decode((string)$record->lastpayloadjson, true);
            if (!is_array($payload) || (int)($payload['courseid'] ?? 0) !== $courseid
                    || (int)($payload['userid'] ?? 0) !== $userid) {
                continue;
            }
            $e = $payload['enrolment'] ?? null;
            if (!is_array($e) || ($e['status'] ?? '') !== 'created') {
                continue;
            }
            $id = (int)($e['enrolid'] ?? 0);
            if ($id <= 0 || ($owned !== null && $owned !== $id)) {
                throw new \coding_exception('Ambiguous Commerce enrolment provenance: refuse unenrolment.');
            }
            $owned = $id;
        }
        return $owned;
    }

    private function has_native_alternative(int $userid, int $courseid, int $now, int $excluded): bool {
        foreach ($this->db->get_records(self::GRANTS, [
            'beneficiaryuserid' => $userid, 'type' => 'course_access',
        ]) as $other) {
            if ((int)$other->id === $excluded
                    || !in_array(strtolower((string)$other->status), self::ACTIVE, true)
                    || (int)$other->validfrom > $now
                    || ($other->validuntil !== null && (int)$other->validuntil > 0 && (int)$other->validuntil < $now)) {
                continue;
            }
            if (preg_match('/^course:(\d+):/', (string)$other->resourcekey, $matches)
                    && (int)$matches[1] === $courseid) {
                return true;
            }
        }
        return false;
    }

    private function has_legacy_alternative(int $userid, int $courseid, int $now): bool {
        $sql = 'SELECT us.id, s.course_ids
                  FROM {user_subscription} us
                  JOIN {subscription_plan} p ON p.id = us.planid
                  JOIN {subscription_access_scope} s ON s.id = p.accessscopeid
                 WHERE us.userid = :userid
                   AND us.status IN (:active,:completed)
                   AND us.start_date <= :now
                   AND (us.end_date = 0 OR us.end_date >= :now2)';
        foreach ($this->db->get_records_sql($sql, [
            'userid' => $userid, 'active' => 'active', 'completed' => 'completed',
            'now' => $now, 'now2' => $now,
        ]) as $subscription) {
            $raw = trim((string)$subscription->course_ids);
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $ids = array_map('intval', $decoded);
            } elseif (preg_match('/^\d+(?:[,;\s]+\d+)*$/', $raw)) {
                $ids = array_map('intval', preg_split('/[,;\s]+/', $raw));
            } else {
                // Ambiguous active subscription: never remove access on the basis of an unreadable scope.
                return true;
            }
            if (in_array($courseid, $ids, true)) {
                return true;
            }
        }
        return false;
    }
}
