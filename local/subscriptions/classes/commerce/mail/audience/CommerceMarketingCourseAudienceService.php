<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail\audience;

defined('MOODLE_INTERNAL') || die();

/**
 * Read-only audience preview for owners of a Moodle course who have not
 * purchased/joined its pedagogical accompaniment.
 *
 * IMPORTANT: This is a commercial eligibility preview, NOT a permission to
 * send marketing email. Consent/opt-out checks are a separate Mail Studio V1
 * stage. Do not pass these results directly to the mail queue.
 */
final class CommerceMarketingCourseAudienceService {
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
        ?int $now = null
    ): array {
        if ($courseid <= SITEID) {
            throw new \invalid_parameter_exception('A valid Moodle course ID is required.');
        }
        $course = $this->db->get_record('course', ['id' => $courseid], 'id,fullname,shortname', MUST_EXIST);
        $context = \context_course::instance($courseid);
        $now ??= time();
        $supportskus = $this->support_skus($courseid, $additionalsupportskus);
        // A Moodle student enrolment alone does not establish a legitimate purchase.
        // Resolve course-specific commercial references once, then check each identity.
        $purchaseproof = new CommerceMarketingCoursePurchaseProofService($this->db);
        $purchasereferences = $purchaseproof->references_for_course($courseid);

        // Candidate acquisition includes Trial/Grammar users, so that their
        // exclusions appear in the report instead of vanishing silently.
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
            if ((int)$user->fullrole !== 1) {
                $reasons[] = (int)$user->trialrole === 1 ? 'trial_only' : 'not_full_student';
            }
            if ((int)$user->fullrole === 1 && (int)$user->trialrole === 1) {
                $reasons[] = 'conflicting_trial_role';
            }
            if (!$this->has_current_enrolment($courseid, $userid, $now)) {
                $reasons[] = 'no_active_enrolment';
            }
            if ($purchasereferences === []) {
                // Fail closed on unmapped courses; never infer an A1 purchase from a role.
                $reasons[] = 'course_purchase_mapping_missing';
            } else if (!$purchaseproof->has_verified_purchase($userid, $email, $purchasereferences)) {
                $reasons[] = 'no_verified_course_purchase';
            }

            $access = $this->db->get_record('local_subs_commerce_ped_access', [
                'courseid' => $courseid, 'userid' => $userid,
            ], 'promotionid,profile', IGNORE_MISSING);
            if ($access && ($access->promotionid !== null
                    || (string)$access->profile === 'promotion_progressive')) {
                $reasons[] = 'promotion_course_access';
            }
            // Any historical participation (including refunded/cancelled) is
            // excluded conservatively until the commercial policy is approved.
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

            $eligible = $reasons === [];
            $summary[$eligible ? 'eligible' : 'excluded']++;
            foreach ($reasons as $reason) {
                $key = 'reason_' . $reason;
                $summary[$key] = ($summary[$key] ?? 0) + 1;
            }
            $rows[] = [
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
