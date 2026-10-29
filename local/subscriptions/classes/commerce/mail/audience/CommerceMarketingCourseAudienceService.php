<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail\audience;

defined('MOODLE_INTERNAL') || die();

/**
 * Read-only course audience: assigned Student, Trial, Grammar roles, plus
 * pedagogical-promotion access. Filters are exclusions, not proof of marketing consent.
 * The verified-purchase check is available as an optional filter only.
 */
final class CommerceMarketingCourseAudienceService {
    /** Defaults: all three course roles included, promotion-only participants excluded. */
    public const DEFAULT_FILTERS = [
        'exclude_student_role' => false,
        'exclude_trial_role' => false,
        'exclude_grammar_role' => false,
        'exclude_promotion_access' => true,
        'exclude_accompaniment' => false,
        'exclude_inactive_enrolment' => false,
        'require_verified_purchase' => false,
    ];

    /** @param array<string, mixed> $filters @return array<string, bool> */
    public static function normalise_filters(array $filters): array {
        $result = self::DEFAULT_FILTERS;
        foreach ($result as $key => $default) {
            if (array_key_exists($key, $filters)) {
                $result[$key] = in_array($filters[$key], [true, 1, '1'], true);
            }
        }
        return $result;
    }
    public function __construct(private readonly \moodle_database $db) {
    }

    public static function create(?\moodle_database $db = null): self {
        global $DB;
        return new self($db ?? $DB);
    }

    /**
     * @param string[] $additionalsupportskus Catalog SKUs that identify supported offers.
     * @return array{courseid:int,course:string,checkedat:int,supportskus:string[],summary:array<string,int>,rows:array<int,array<string,mixed>>}
     */
    public function preview(
        int $courseid,
        array $additionalsupportskus = [],
        ?int $now = null,
        array $filters = []
    ): array {
        $filters = self::normalise_filters($filters);
        if ($courseid <= SITEID) {
            throw new \invalid_parameter_exception('A valid Moodle course ID is required.');
        }
        $course = $this->db->get_record('course', ['id' => $courseid], 'id,fullname,shortname', MUST_EXIST);
        $context = \context_course::instance($courseid);
        $now ??= time();
        $supportskus = $this->support_skus($courseid, $additionalsupportskus);
        // The course audience is NOT limited to buyers. Purchase proof is an
        // optional filter; resolving it only when requested avoids unnecessary
        // database work across large Trial/Grammar cohorts.
        $purchaseproof = null;
        $purchasereferences = [];
        if ($filters['require_verified_purchase']) {
            $purchaseproof = new CommerceMarketingCoursePurchaseProofService($this->db);
            $purchasereferences = $purchaseproof->references_for_course($courseid);
        }

        // Include every relevant course role, even for accounts which have not
        // paid for a full enrolment. Multiple role assignments are deduplicated.
        $sql = "SELECT u.id, u.firstname, u.lastname, u.email, u.lang,
                       u.deleted, u.suspended, u.confirmed, u.emailstop,
                       MAX(CASE WHEN r.shortname = 'student' THEN 1 ELSE 0 END) AS fullrole,
                       MAX(CASE WHEN r.shortname = 'trialstudent' THEN 1 ELSE 0 END) AS trialrole,
                       MAX(CASE WHEN r.shortname = 'grammarstudent' THEN 1 ELSE 0 END) AS grammarrole
                  FROM {role_assignments} ra
                  JOIN {role} r ON r.id = ra.roleid
                  JOIN {user} u ON u.id = ra.userid
                 WHERE ra.contextid = :contextid
                   AND r.shortname IN ('student', 'trialstudent', 'grammarstudent')
              GROUP BY u.id, u.firstname, u.lastname, u.email, u.lang,
                       u.deleted, u.suspended, u.confirmed, u.emailstop
              ORDER BY u.id ASC";
        $users = $this->db->get_records_sql($sql, ['contextid' => (int)$context->id]);

        // A pedagogical promotion can exist without any of the three roles.
        // It must therefore be an independent source, not a filter applied to
        // role holders alone. Read all access rows once to avoid N+1 queries.
        $promotions = [];
        $sql = "SELECT u.id, u.firstname, u.lastname, u.email, u.lang,
                       u.deleted, u.suspended, u.confirmed, u.emailstop,
                       0 AS fullrole, 0 AS trialrole, 0 AS grammarrole
                  FROM {local_subs_commerce_ped_access} a
                  JOIN {user} u ON u.id = a.userid
                 WHERE a.courseid = :courseid
                   AND (a.promotionid IS NOT NULL OR a.profile = :profile)";
        $promotionusers = $this->db->get_records_sql($sql, [
            'courseid' => $courseid, 'profile' => 'promotion_progressive',
        ]);
        foreach ($promotionusers as $promotionuser) {
            $promotions[(int)$promotionuser->id] = true;
            if (!isset($users[(int)$promotionuser->id])) {
                $users[(int)$promotionuser->id] = $promotionuser;
            }
        }
        ksort($users, SORT_NUMERIC);

        // Never address two Moodle accounts sharing the same email as if they
        // were one confirmed commercial identity.
        $emailcounts = [];
        foreach ($users as $user) {
            $email = $this->normalise_email((string)$user->email);
            if ($email !== '') {
                $emailcounts[$email] = ($emailcounts[$email] ?? 0) + 1;
            }
        }

        $rows = [];
        $summary = ['candidates' => 0, 'eligible' => 0, 'excluded' => 0];
        foreach ($users as $user) {
            $summary['candidates']++;
            $userid = (int)$user->id;
            $email = $this->normalise_email((string)$user->email);
            $reasons = [];

            if ((int)$user->deleted !== 0 || (int)$user->suspended !== 0 || (int)$user->confirmed !== 1) {
                $reasons[] = 'inactive_account';
            }
            if ((int)$user->emailstop !== 0) {
                $reasons[] = 'email_stopped';
            }
            // A course role does not turn an administrator into a marketing customer.
            // In particular, administrators may be enrolled as students for QA.
            if (\is_siteadmin($userid)) {
                $reasons[] = 'site_administrator';
            }
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $reasons[] = 'invalid_email';
            } else if (($emailcounts[$email] ?? 0) > 1
                    || $this->email_belongs_to_other_account($email, $userid)) {
                $reasons[] = 'ambiguous_email';
            }
            $fullrole = (int)$user->fullrole === 1;
            $trialrole = (int)$user->trialrole === 1;
            $grammarrole = (int)$user->grammarrole === 1;
            $promotionaccess = isset($promotions[$userid]);
            // Exclusions apply to every matching source, including accounts
            // with multiple roles. A Trial-only account is now selectable.
            if ($filters['exclude_student_role'] && $fullrole) {
                $reasons[] = 'excluded_student_role';
            }
            if ($filters['exclude_trial_role'] && $trialrole) {
                $reasons[] = 'excluded_trial_role';
            }
            if ($filters['exclude_grammar_role'] && $grammarrole) {
                $reasons[] = 'excluded_grammar_role';
            }
            if ($filters['exclude_promotion_access'] && $promotionaccess) {
                $reasons[] = 'promotion_course_access';
            }
            if ($filters['exclude_inactive_enrolment'] &&
                    !$this->has_current_enrolment($courseid, $userid, $now)) {
                $reasons[] = 'no_active_enrolment';
            }
            if ($filters['require_verified_purchase']) {
                if ($purchasereferences === []) {
                    $reasons[] = 'course_purchase_mapping_missing';
                } else if (!$purchaseproof->has_verified_purchase($userid, $email, $purchasereferences)) {
                    $reasons[] = 'no_verified_course_purchase';
                }
            }
            // Any historical participation (including refunded/cancelled) is
            // excluded conservatively until the commercial policy is approved.
            if ($filters['exclude_accompaniment']) {
                if ($this->db->record_exists('local_subs_commerce_ped_join', [
                    'courseid' => $courseid, 'userid' => $userid,
                ])) {
                    $reasons[] = 'pedagogical_participation_history';
                }
                if ($this->has_promotion_join_grant($courseid, $userid)) {
                    $reasons[] = 'promotion_join_grant';
                }
                if ($supportskus !== [] && $this->has_support_purchase($userid, $email, $supportskus)) {
                    $reasons[] = 'support_purchase_history';
                }
            }

            $eligible = $reasons === [];
            $summary[$eligible ? 'eligible' : 'excluded']++;
            foreach ($reasons as $reason) {
                $key = 'reason_' . $reason;
                $summary[$key] = ($summary[$key] ?? 0) + 1;
            }
            $sources = [];
            if ($fullrole) { $sources[] = 'student'; }
            if ($trialrole) { $sources[] = 'trialstudent'; }
            if ($grammarrole) { $sources[] = 'grammarstudent'; }
            if ($promotionaccess) { $sources[] = 'promotion'; }
            foreach ($sources as $source) {
                $key = 'source_' . $source;
                $summary[$key] = ($summary[$key] ?? 0) + 1;
            }
            $rows[] = [
                'sources' => $sources,
                'userid' => $userid,
                'email' => $email,
                'firstname' => (string)$user->firstname,
                'lastname' => (string)$user->lastname,
                'language' => (string)$user->lang,
                'eligible' => $eligible,
                'reasons' => $reasons,
            ];
        }
        return [
            'courseid' => $courseid,
            'course' => (string)$course->shortname,
            'checkedat' => $now,
            'supportskus' => $supportskus,
            'filters' => $filters,
            'summary' => $summary,
            'rows' => $rows,
        ];
    }

    /** @param string[] $additional @return string[] */
    private function support_skus(int $courseid, array $additional): array {
        $sql = 'SELECT DISTINCT product.sku
                  FROM {local_subs_commerce_ped_offer} offer
                  JOIN {local_subs_commerce_ped_promo} promo ON promo.id = offer.promotionid
                  JOIN {local_subs_commerce_product} product ON product.id = offer.productid
                 WHERE promo.courseid = :courseid';
        $skus = $this->db->get_fieldset_sql($sql, ['courseid' => $courseid]);
        // The two A1 support products are historical catalog SKUs (including an inactive one).
        // They must remain detectable without making an operator type product references.
        if ($courseid === 13) {
            $historical = $this->db->get_fieldset_sql(
                'SELECT sku FROM {local_subs_commerce_product}
                  WHERE sku IN (:supportfr, :supportru)',
                ['supportfr' => 'A1-FR-SUPPORT', 'supportru' => 'A1-RU-SUPPORT']
            );
            $skus = array_merge($skus, $historical);
        }
        foreach ($additional as $sku) {
            if (!is_string($sku)) {
                throw new \invalid_parameter_exception('Support SKU must be a string.');
            }
            $skus[] = $sku;
        }
        $skus = array_values(array_unique(array_filter(array_map(
            static fn(string $sku): string => strtoupper(trim($sku)),
            $skus
        ))));
        sort($skus, SORT_STRING);
        return $skus;
    }

    private function normalise_email(string $email): string {
        return trim(\core_text::strtolower($email));
    }

    private function email_belongs_to_other_account(string $email, int $userid): bool {
        $sql = 'SELECT 1 FROM {user} u
                 WHERE u.id <> :userid AND u.deleted = 0 AND ' .
                 $this->db->sql_equal('u.email', ':email', false, false);
        return $this->db->record_exists_sql($sql, [
            'userid' => $userid, 'email' => $email,
        ]);
    }

    private function has_current_enrolment(int $courseid, int $userid, int $now): bool {
        $sql = 'SELECT 1 FROM {user_enrolments} ue
                  JOIN {enrol} e ON e.id = ue.enrolid
                 WHERE ue.userid = :userid AND e.courseid = :courseid
                   AND ue.status = 0 AND e.status = 0
                   AND (ue.timestart = 0 OR ue.timestart <= :nowstart)
                   AND (ue.timeend = 0 OR ue.timeend > :nowend)';
        return $this->db->record_exists_sql($sql, [
            'userid' => $userid, 'courseid' => $courseid,
            'nowstart' => $now, 'nowend' => $now,
        ]);
    }

    private function has_promotion_join_grant(int $courseid, int $userid): bool {
        $sql = 'SELECT 1 FROM {local_subs_commerce_grant}
                 WHERE beneficiaryuserid = :userid
                   AND type = :type
                   AND resourcekey LIKE :resourcepattern';
        return $this->db->record_exists_sql($sql, [
            'userid' => $userid,
            'type' => 'pedagogical_promotion_join',
            'resourcepattern' => 'promotion:%:course:' . $courseid . ':product:%',
        ]);
    }

    /** @param string[] $skus */
    private function has_support_purchase(int $userid, string $email, array $skus): bool {
        [$insql, $params] = $this->db->get_in_or_equal($skus, SQL_PARAMS_NAMED, 'supportsku');
        $params['userid'] = $userid;
        $identity = 'p.userid = :userid';
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $identity .= ' OR ' . $this->db->sql_equal('p.customeremail', ':customeremail', false, false);
            $params['customeremail'] = $email;
        }
        // Paid/completed orders and confirmed payment attempts. Both sources
        // are consulted because older Commerce orders may have different final
        // aggregate statuses. Fully refunded orders remain excluded for now.
        $sql = 'SELECT 1 FROM {local_subscriptions_commerce_purchase} p
                  JOIN {local_subscriptions_commerce_purchase_item} item ON item.purchaseid = p.id
                 WHERE (' . $identity . ')
                   AND UPPER(item.itemreference) ' . $insql . '
                   AND (
                       p.status IN (\'paid\',\'completed\',\'succeeded\',\'captured\',\'fulfilled\',\'refunded\')
                       OR EXISTS (
                          SELECT 1 FROM {local_subscriptions_commerce_payment} pay
                           WHERE pay.purchaseid = p.id
                             AND pay.status IN (\'paid\',\'completed\',\'succeeded\',\'success\',\'captured\',\'refunded\',\'partially_refunded\')
                       )
                   )';
        return $this->db->record_exists_sql($sql, $params);
    }
}
