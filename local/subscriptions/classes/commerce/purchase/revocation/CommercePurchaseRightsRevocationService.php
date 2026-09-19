<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\purchase\revocation;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\education\access\CommerceStudentAccessProfile;
use local_subscriptions\commerce\education\access\CommerceStudentCourseAccess;
use local_subscriptions\commerce\education\access\CommerceStudentCourseAccessRepository;
use local_subscriptions\commerce\education\lifecycle\CommercePedagogicalLifecycleService;
use local_subscriptions\commerce\education\lifecycle\CommercePedagogicalParticipationRepository;
use local_subscriptions\commerce\education\promotionjoin\CommercePedagogicalPromotionJoinGrant;
use local_subscriptions\commerce\persistence\CommercePersistenceSchema;

/**
 * Explicitly revokes the rights originating from one Commerce purchase.
 *
 * This service is intentionally independent from refunds. A financial refund
 * never calls it automatically. Admin UI may invoke it together with a refund,
 * or later as a standalone rights decision.
 *
 * Revocation is provenance based: only grants created by the selected purchase
 * are revoked. Rights that remain justified by another active grant, a legacy
 * subscription or a pre-existing Moodle enrolment are preserved.
 */
final class CommercePurchaseRightsRevocationService {
    private const GRANT_TABLE = 'local_subs_commerce_grant';
    private const DIGITAL_ACCESS_TABLE = 'local_subs_commerce_dig_access';
    private const FULFILLMENT_STATE_TABLE = 'local_subs_commerce_ful_state';

    private const TERMINAL_GRANT_STATUSES = [
        'revoked',
        'refunded',
        'cancelled',
        'canceled',
        'expired',
    ];

    public function __construct(
        private readonly \moodle_database $db,
        private readonly CommercePedagogicalLifecycleService $pedagogy,
        private readonly CommerceStudentCourseAccessRepository $access
    ) {
    }

    public static function create(?\moodle_database $db = null): self {
        global $DB;
        $db = $db ?? $DB;

        return new self(
            $db,
            CommercePedagogicalLifecycleService::create($db),
            CommerceStudentCourseAccessRepository::create($db)
        );
    }

    /**
     * @return array<int,array{grantreference:string,type:string,productsku:string,resourcekey:string,status:string}>
     */
    public function preview(string $purchasereference): array {
        $purchasereference = trim($purchasereference);
        if ($purchasereference === '') {
            return [];
        }

        $rows = [];
        foreach ($this->db->get_records(
            self::GRANT_TABLE,
            ['purchasereference' => $purchasereference],
            'id ASC'
        ) as $grant) {
            if (!$this->is_revocable_status((string)$grant->status)) {
                continue;
            }
            $rows[] = [
                'grantreference' => (string)$grant->grantreference,
                'type' => (string)$grant->type,
                'productsku' => (string)$grant->productsku,
                'resourcekey' => (string)$grant->resourcekey,
                'status' => (string)$grant->status,
            ];
        }

        return $rows;
    }

    public function has_revocable_rights(string $purchasereference): bool {
        return $this->preview($purchasereference) !== [];
    }

    /**
     * @return array{revoked:int,course:int,digital:int,promotionjoin:int,preservedcourse:int}
     */
    public function revoke(
        string $purchasereference,
        int $actoruserid,
        ?string $reason = null,
        ?int $now = null,
        string $participationstate = CommercePedagogicalParticipationRepository::CANCELLED
    ): array {
        $purchasereference = trim($purchasereference);
        if ($purchasereference === '') {
            throw new \coding_exception('Purchase rights revocation requires a purchase reference.');
        }

        $purchase = $this->db->get_record(
            CommercePersistenceSchema::TABLE_PURCHASE,
            ['reference' => $purchasereference],
            'id,reference,userid,customeremail,status',
            MUST_EXIST
        );

        $grants = array_values($this->db->get_records(
            self::GRANT_TABLE,
            ['purchasereference' => $purchasereference],
            'id ASC'
        ));

        $revocable = array_values(array_filter(
            $grants,
            fn(\stdClass $grant): bool => $this->is_revocable_status((string)$grant->status)
        ));

        if ($revocable === []) {
            return [
                'revoked' => 0,
                'course' => 0,
                'digital' => 0,
                'promotionjoin' => 0,
                'preservedcourse' => 0,
            ];
        }

        $now ??= time();
        $reason = trim((string)$reason);
        $participationstate = strtolower(trim($participationstate));
        if (!in_array($participationstate, [
            CommercePedagogicalParticipationRepository::CANCELLED,
            CommercePedagogicalParticipationRepository::REFUNDED,
        ], true)) {
            throw new \coding_exception('Unsupported pedagogical participation state for rights revocation.');
        }
        $coursecontexts = [];
        $joingrants = [];
        $counts = [
            'revoked' => 0,
            'course' => 0,
            'digital' => 0,
            'promotionjoin' => 0,
            'preservedcourse' => 0,
        ];

        $transaction = $this->db->start_delegated_transaction();

        foreach ($revocable as $grant) {
            $type = strtolower(trim((string)$grant->type));

            if ($type === 'digital_download') {
                $this->revoke_digital_access($grant, $now);
                $counts['digital']++;
            } elseif ($type === 'course_access') {
                $context = $this->course_context($grant);
                if ($context !== null) {
                    $key = $context['userid'] . ':' . $context['courseid'];
                    if (!isset($coursecontexts[$key])) {
                        $coursecontexts[$key] = $context + ['grantreferences' => []];
                    }
                    $coursecontexts[$key]['grantreferences'][] = (string)$grant->grantreference;
                }
                $counts['course']++;
            } elseif ($type === CommercePedagogicalPromotionJoinGrant::GRANT_TYPE) {
                $joingrants[] = $grant;
                $counts['promotionjoin']++;
            }

            $this->mark_grant_revoked($grant, $actoruserid, $reason, $now);
            $counts['revoked']++;
        }

        if ($joingrants !== []) {
            // Standalone revocation uses CANCELLED. When an administrator
            // explicitly combines refund + revoke, the caller supplies REFUNDED.
            // Provider refund synchronization never reaches this service.
            $this->pedagogy->terminate_purchase(
                $purchasereference,
                $participationstate,
                $now
            );

            foreach ($joingrants as $grant) {
                $this->detach_promotion_join_access($grant, $now);
            }
        }

        foreach ($coursecontexts as $context) {
            if (!$this->reconcile_course_access_after_revocation(
                $context['userid'],
                $context['courseid'],
                $now,
                $context['grantreferences']
            )) {
                $counts['preservedcourse']++;
            }
        }

        $transaction->allow_commit();
        return $counts;
    }

    private function is_revocable_status(string $status): bool {
        return !in_array(strtolower(trim($status)), self::TERMINAL_GRANT_STATUSES, true);
    }

    private function mark_grant_revoked(
        \stdClass $grant,
        int $actoruserid,
        string $reason,
        int $now
    ): void {
        $metadata = json_decode((string)($grant->metadatajson ?? ''), true);
        if (!is_array($metadata)) {
            $metadata = [];
        }

        $metadata['rights_revocation'] = [
            'revokedat' => $now,
            'revokedbyuserid' => $actoruserid > 0 ? $actoruserid : null,
            'reason' => $reason !== '' ? $reason : null,
            'source' => 'admin_explicit_revocation',
        ];

        $grant->status = 'revoked';
        $grant->metadatajson = json_encode(
            $metadata,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );
        $grant->timemodified = $now;
        $this->db->update_record(self::GRANT_TABLE, $grant);
    }

    private function revoke_digital_access(\stdClass $grant, int $now): void {
        $access = $this->db->get_record(
            self::DIGITAL_ACCESS_TABLE,
            ['grantreference' => (string)$grant->grantreference],
            '*',
            IGNORE_MISSING
        );

        if (!$access || strtolower((string)$access->status) === 'revoked') {
            return;
        }

        $access->status = 'revoked';
        $access->timemodified = $now;
        $this->db->update_record(self::DIGITAL_ACCESS_TABLE, $access);
    }

    /** @return array{userid:int,courseid:int}|null */
    private function course_context(\stdClass $grant): ?array {
        $userid = (int)($grant->beneficiaryuserid ?? 0);
        $courseid = 0;

        $configuration = json_decode((string)($grant->configurationjson ?? ''), true);
        if (is_array($configuration)) {
            $courseid = (int)($configuration['courseid'] ?? 0);
        }
        if ($courseid <= 0 && preg_match('/^course:(\d+)(?::|$)/', (string)$grant->resourcekey, $matches) === 1) {
            $courseid = (int)$matches[1];
        }

        if ($userid <= 0 || $courseid <= 0) {
            return null;
        }

        return ['userid' => $userid, 'courseid' => $courseid];
    }

    /**
     * @param string[] $revokedgrantreferences Course grants revoked by the current purchase decision.
     */
    private function reconcile_course_access_after_revocation(
        int $userid,
        int $courseid,
        int $now,
        array $revokedgrantreferences
    ): bool {
        $hasnativealternative = $this->has_effective_native_course_grant($userid, $courseid, $now);
        $haslegacyalternative = $this->has_effective_legacy_course_subscription($userid, $courseid, $now);
        $hasalternative = $hasnativealternative || $haslegacyalternative;

        $createdenrolid = null;
        $restorepreexistingroles = false;
        if (!$hasalternative) {
            $createdenrolid = $this->commerce_created_manual_enrolment_id($userid, $courseid);
            $restorepreexistingroles = $createdenrolid === null
                && $this->has_moodle_course_enrolment($userid, $courseid);
        }

        // Group/cohort/role side effects are provenance scoped to the grants
        // revoked by THIS purchase decision. If the purchase had upgraded a
        // pre-existing Moodle enrolment, restore the course roles that its
        // fulfillment explicitly recorded as removed.
        $this->revoke_course_side_effects(
            $userid,
            $courseid,
            $revokedgrantreferences,
            $hasalternative,
            $restorepreexistingroles
        );

        if ($hasalternative) {
            return false;
        }

        if ($createdenrolid === null) {
            // The purchase fulfilled into an enrolment that already existed.
            // Never remove a pre-existing/admin-created Moodle enrolment.
            return false;
        }

        $instance = $this->db->get_record('enrol', [
            'id' => $createdenrolid,
            'courseid' => $courseid,
            'enrol' => 'manual',
        ], '*', IGNORE_MISSING);
        if (!$instance) {
            return false;
        }

        $userenrolment = $this->db->get_record('user_enrolments', [
            'enrolid' => $createdenrolid,
            'userid' => $userid,
        ], 'id', IGNORE_MISSING);

        if ($userenrolment) {
            $manual = enrol_get_plugin('manual');
            if ($manual) {
                $manual->unenrol_user($instance, $userid);
                return true;
            }
        }

        return false;
    }

    private function has_effective_native_course_grant(int $userid, int $courseid, int $now): bool {
        $sql = 'SELECT 1
                  FROM {' . self::GRANT_TABLE . '}
                 WHERE beneficiaryuserid = :userid
                   AND type = :type
                   AND resourcekey LIKE :resourcekey
                   AND status IN (:active, :granted, :completed)
                   AND validfrom <= :now
                   AND (validuntil IS NULL OR validuntil = 0 OR validuntil >= :now2)';

        return $this->db->record_exists_sql($sql, [
            'userid' => $userid,
            'type' => 'course_access',
            'resourcekey' => 'course:' . $courseid . ':%',
            'active' => 'active',
            'granted' => 'granted',
            'completed' => 'completed',
            'now' => $now,
            'now2' => $now,
        ]);
    }

    private function has_effective_legacy_course_subscription(int $userid, int $courseid, int $now): bool {
        $sql = 'SELECT us.id, s.course_ids
                  FROM {user_subscription} us
                  JOIN {subscription_plan} p ON p.id = us.planid
                  JOIN {subscription_access_scope} s ON s.id = p.accessscopeid
                 WHERE us.userid = :userid
                   AND us.status IN (:active, :completed)
                   AND us.start_date <= :now
                   AND (us.end_date = 0 OR us.end_date >= :now2)';

        $records = $this->db->get_records_sql($sql, [
            'userid' => $userid,
            'active' => 'active',
            'completed' => 'completed',
            'now' => $now,
            'now2' => $now,
        ]);

        foreach ($records as $record) {
            $courseids = array_filter(array_map(
                'intval',
                preg_split('/[,;\s]+/', trim((string)$record->course_ids), -1, PREG_SPLIT_NO_EMPTY) ?: []
            ));
            if (in_array($courseid, $courseids, true)) {
                return true;
            }
        }

        return false;
    }

    private function commerce_created_manual_enrolment_id(int $userid, int $courseid): ?int {
        $sql = 'SELECT s.lastpayloadjson
                  FROM {' . self::FULFILLMENT_STATE_TABLE . '} s
                  JOIN {' . self::GRANT_TABLE . '} g ON g.grantreference = s.grantreference
                 WHERE g.beneficiaryuserid = :userid
                   AND g.type = :type
                   AND g.resourcekey LIKE :resourcekey
                   AND s.status = :completed
              ORDER BY s.id ASC';

        $records = $this->db->get_records_sql($sql, [
            'userid' => $userid,
            'type' => 'course_access',
            'resourcekey' => 'course:' . $courseid . ':%',
            'completed' => 'completed',
        ]);

        foreach ($records as $record) {
            $payload = json_decode((string)$record->lastpayloadjson, true);
            if (!is_array($payload)) {
                continue;
            }
            $enrolment = $payload['enrolment'] ?? null;
            if (!is_array($enrolment) || ($enrolment['status'] ?? '') !== 'created') {
                continue;
            }
            $enrolid = (int)($enrolment['enrolid'] ?? 0);
            if ($enrolid > 0) {
                return $enrolid;
            }
        }

        return null;
    }

    /**
     * @param string[] $grantreferences Grants revoked by the current purchase decision.
     */
    private function has_moodle_course_enrolment(int $userid, int $courseid): bool {
        $sql = 'SELECT 1
                  FROM {user_enrolments} ue
                  JOIN {enrol} e ON e.id = ue.enrolid
                 WHERE ue.userid = :userid
                   AND e.courseid = :courseid';

        return $this->db->record_exists_sql($sql, [
            'userid' => $userid,
            'courseid' => $courseid,
        ]);
    }

    private function revoke_course_side_effects(
        int $userid,
        int $courseid,
        array $grantreferences,
        bool $preserverole,
        bool $restoreremovedroles
    ): void {
        global $CFG;
        require_once($CFG->dirroot . '/cohort/lib.php');
        require_once($CFG->dirroot . '/group/lib.php');
        require_once($CFG->dirroot . '/lib/accesslib.php');

        $grantreferences = array_values(array_unique(array_filter(array_map('strval', $grantreferences))));
        if ($grantreferences === []) {
            return;
        }

        [$grantsql, $grantparams] = $this->db->get_in_or_equal(
            $grantreferences,
            SQL_PARAMS_NAMED,
            'revokedgrant'
        );

        $sql = 'SELECT g.grantreference, g.configurationjson, s.lastpayloadjson
                  FROM {' . self::GRANT_TABLE . '} g
             LEFT JOIN {' . self::FULFILLMENT_STATE_TABLE . '} s ON s.grantreference = g.grantreference
                 WHERE g.beneficiaryuserid = :userid
                   AND g.type = :type
                   AND g.resourcekey LIKE :resourcekey
                   AND g.grantreference ' . $grantsql . '
                   AND g.status IN (:revoked, :refunded, :cancelled, :canceled)';

        $records = $this->db->get_records_sql($sql, array_merge($grantparams, [
            'userid' => $userid,
            'type' => 'course_access',
            'resourcekey' => 'course:' . $courseid . ':%',
            'revoked' => 'revoked',
            'refunded' => 'refunded',
            'cancelled' => 'cancelled',
            'canceled' => 'canceled',
        ]));

        foreach ($records as $record) {
            $payload = json_decode((string)($record->lastpayloadjson ?? ''), true);
            if (!is_array($payload)) {
                continue;
            }

            foreach ((array)($payload['groups'] ?? []) as $group) {
                if (!is_array($group) || ($group['status'] ?? '') !== 'added') {
                    continue;
                }
                $groupid = (int)($group['groupid'] ?? 0);
                if ($groupid > 0 && !$this->active_grant_requires_group($userid, $courseid, $groupid)) {
                    if (groups_is_member($groupid, $userid)) {
                        groups_remove_member($groupid, $userid);
                    }
                }
            }

            foreach ((array)($payload['cohorts'] ?? []) as $cohort) {
                if (!is_array($cohort) || ($cohort['status'] ?? '') !== 'added') {
                    continue;
                }
                $cohortid = (int)($cohort['cohortid'] ?? 0);
                if ($cohortid > 0 && !$this->active_grant_requires_cohort($userid, $courseid, $cohortid)) {
                    if ($this->db->record_exists('cohort_members', ['cohortid' => $cohortid, 'userid' => $userid])) {
                        cohort_remove_member($cohortid, $userid);
                    }
                }
            }

            $role = $payload['role'] ?? null;
            if (!$preserverole && is_array($role) && ($role['status'] ?? '') === 'assigned') {
                $roleid = (int)($role['roleid'] ?? 0);
                if ($roleid > 0 && !$this->active_grant_requires_role($userid, $courseid, $roleid)) {
                    $context = \context_course::instance($courseid);
                    if (user_has_role_assignment($userid, $roleid, $context->id)) {
                        role_unassign($roleid, $userid, $context->id);
                    }
                }
            }

            if ($restoreremovedroles && is_array($role)) {
                $context = \context_course::instance($courseid);
                foreach ((array)($role['removed'] ?? []) as $removedshortname) {
                    $removedshortname = trim((string)$removedshortname);
                    if ($removedshortname === '') {
                        continue;
                    }
                    $restoreid = (int)$this->db->get_field(
                        'role',
                        'id',
                        ['shortname' => $removedshortname],
                        IGNORE_MISSING
                    );
                    if ($restoreid <= 0 || $this->active_grant_requires_role($userid, $courseid, $restoreid)) {
                        continue;
                    }
                    if (!user_has_role_assignment($userid, $restoreid, $context->id)) {
                        role_assign($restoreid, $userid, $context->id);
                    }
                }
            }
        }
    }

    private function active_grant_requires_role(int $userid, int $courseid, int $roleid): bool {
        $roleshortname = (string)$this->db->get_field('role', 'shortname', ['id' => $roleid], IGNORE_MISSING);
        if ($roleshortname === '') {
            return false;
        }
        foreach ($this->active_course_grant_configurations($userid, $courseid) as $configuration) {
            if (trim((string)($configuration['roleshortname'] ?? 'student')) === $roleshortname) {
                return true;
            }
        }
        return false;
    }

    private function active_grant_requires_group(int $userid, int $courseid, int $groupid): bool {
        $name = (string)$this->db->get_field('groups', 'name', ['id' => $groupid, 'courseid' => $courseid], IGNORE_MISSING);
        foreach ($this->active_course_grant_configurations($userid, $courseid) as $configuration) {
            $ids = array_map('intval', (array)($configuration['groupids'] ?? []));
            if (in_array($groupid, $ids, true)) {
                return true;
            }
            $names = array_map('strval', (array)($configuration['groupnames'] ?? []));
            if ($name !== '' && in_array($name, $names, true)) {
                return true;
            }
        }
        return false;
    }

    private function active_grant_requires_cohort(int $userid, int $courseid, int $cohortid): bool {
        $name = (string)$this->db->get_field('cohort', 'name', ['id' => $cohortid], IGNORE_MISSING);
        foreach ($this->active_course_grant_configurations($userid, $courseid) as $configuration) {
            $ids = array_map('intval', (array)($configuration['cohortids'] ?? []));
            if (in_array($cohortid, $ids, true)) {
                return true;
            }
            $names = array_map('strval', (array)($configuration['cohortnames'] ?? []));
            if ($name !== '' && in_array($name, $names, true)) {
                return true;
            }
        }
        return false;
    }

    /** @return array<int,array<string,mixed>> */
    private function active_course_grant_configurations(int $userid, int $courseid): array {
        $now = time();
        $records = $this->db->get_records_select(
            self::GRANT_TABLE,
            'beneficiaryuserid = :userid AND type = :type AND resourcekey LIKE :resourcekey '
                . 'AND status IN (:active, :granted, :completed) '
                . 'AND validfrom <= :now '
                . 'AND (validuntil IS NULL OR validuntil = 0 OR validuntil >= :now2)',
            [
                'userid' => $userid,
                'type' => 'course_access',
                'resourcekey' => 'course:' . $courseid . ':%',
                'active' => 'active',
                'granted' => 'granted',
                'completed' => 'completed',
                'now' => $now,
                'now2' => $now,
            ]
        );

        $configurations = [];
        foreach ($records as $record) {
            $configuration = json_decode((string)$record->configurationjson, true);
            if (is_array($configuration)) {
                $configurations[] = $configuration;
            }
        }
        return $configurations;
    }

    private function detach_promotion_join_access(\stdClass $grant, int $now): void {
        $configuration = json_decode((string)($grant->configurationjson ?? ''), true);
        if (!is_array($configuration)) {
            $configuration = [];
        }

        $courseid = max(0, (int)($configuration['promotion_join_course_id'] ?? 0));
        $userid = max(0, (int)($configuration['promotion_join_user_id'] ?? 0));
        $promotionid = max(0, (int)($configuration['promotion_join_promotion_id'] ?? 0));

        if ($courseid <= 0 || $promotionid <= 0) {
            if (preg_match(
                '/^promotion:(\d+):course:(\d+):product:\d+$/',
                trim((string)$grant->resourcekey),
                $matches
            ) === 1) {
                $promotionid = $promotionid > 0 ? $promotionid : (int)$matches[1];
                $courseid = $courseid > 0 ? $courseid : (int)$matches[2];
            }
        }
        $userid = $userid > 0 ? $userid : (int)($grant->beneficiaryuserid ?? 0);

        if ($courseid <= 0 || $userid <= 0 || $promotionid <= 0) {
            throw new \coding_exception('Promotion join revocation cannot resolve its canonical access context.');
        }

        $existing = $this->access->find($courseid, $userid);
        if ($existing === null || $existing->get_promotion_id() !== $promotionid) {
            return;
        }
        if (!CommerceStudentAccessProfile::has_full_course_access($existing->get_profile())) {
            throw new \coding_exception('Promotion join revocation must never downgrade a non-full course access profile.');
        }

        $this->access->save(new CommerceStudentCourseAccess(
            $existing->get_id(),
            $courseid,
            $userid,
            null,
            $existing->get_profile(),
            $existing->get_created_by(),
            null,
            $existing->get_time_created(),
            $now
        ));
    }
}
