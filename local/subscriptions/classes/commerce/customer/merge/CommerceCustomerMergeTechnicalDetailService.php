<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\customer\merge;

defined('MOODLE_INTERNAL') || die();

use moodle_database;

/**
 * Read-only, row-level evidence used by the final merge preview.
 *
 * This service deliberately performs no merge decision and no mutation. It only exposes
 * the records that the existing merge services will consolidate so an administrator can
 * inspect the irreversible operation before confirming it.
 */
final class CommerceCustomerMergeTechnicalDetailService {
    public function __construct(private readonly moodle_database $database) {
    }

    public static function create(?moodle_database $database = null): self {
        global $DB;
        return new self($database ?? $DB);
    }

    /**
     * @return array<int,array<string,mixed>> keyed by Moodle userid.
     */
    public function build(CommerceCustomerMergePlan $plan): array {
        $out = [];
        foreach ($plan->profiles as $profile) {
            $userid = $profile->userid();
            $out[$userid] = [
                'courses' => $this->courses($userid),
                'purchases' => $this->purchases($userid),
                'subscriptions' => $this->legacy_subscriptions($userid),
                'digital' => $this->legacy_digital($userid),
                'grants' => $this->grants($userid),
                'digitalaccesses' => $this->digital_accesses($userid),
            ];
        }
        return $out;
    }

    /** @return array<int,array<string,mixed>> */
    private function courses(int $userid): array {
        if (!$this->table_exists('user_enrolments')) {
            return [];
        }
        $sql = "SELECT DISTINCT c.id, c.fullname, c.shortname,
                       ue.status AS enrolstatus, ue.timestart, ue.timeend, ue.timecreated
                  FROM {user_enrolments} ue
                  JOIN {enrol} e ON e.id = ue.enrolid
                  JOIN {course} c ON c.id = e.courseid
                 WHERE ue.userid = :userid
              ORDER BY c.fullname ASC, c.id ASC";
        $rows = $this->database->get_records_sql($sql, ['userid' => $userid]);
        $out = [];
        foreach ($rows as $row) {
            $courseid = (int)$row->id;
            $completion = $this->database->get_record(
                'course_completions',
                ['userid' => $userid, 'course' => $courseid],
                'id,timeenrolled,timestarted,timecompleted',
                IGNORE_MISSING
            );

            $activities = 0;
            if ($this->table_exists('course_modules_completion')) {
                $activities = (int)$this->database->count_records_sql(
                    "SELECT COUNT(1)
                       FROM {course_modules_completion} cmc
                       JOIN {course_modules} cm ON cm.id = cmc.coursemoduleid
                      WHERE cmc.userid = :userid
                        AND cm.course = :courseid
                        AND cmc.completionstate > 0",
                    ['userid' => $userid, 'courseid' => $courseid]
                );
            }

            $gradecount = 0;
            $gradeaverage = null;
            if ($this->table_exists('grade_grades')) {
                $graderow = $this->database->get_record_sql(
                    "SELECT COUNT(gg.id) AS gradecount,
                            AVG(CASE
                                WHEN gi.grademax > gi.grademin
                                 AND gg.finalgrade IS NOT NULL
                                THEN ((gg.finalgrade - gi.grademin) / (gi.grademax - gi.grademin)) * 100
                                ELSE NULL
                            END) AS gradeaverage
                       FROM {grade_grades} gg
                       JOIN {grade_items} gi ON gi.id = gg.itemid
                      WHERE gg.userid = :userid
                        AND gi.courseid = :courseid
                        AND gg.finalgrade IS NOT NULL",
                    ['userid' => $userid, 'courseid' => $courseid],
                    IGNORE_MISSING
                );
                if ($graderow) {
                    $gradecount = (int)$graderow->gradecount;
                    $gradeaverage = $graderow->gradeaverage === null ? null : (float)$graderow->gradeaverage;
                }
            }

            $lastaccess = 0;
            if ($this->table_exists('user_lastaccess')) {
                $lastaccess = (int)($this->database->get_field(
                    'user_lastaccess',
                    'timeaccess',
                    ['userid' => $userid, 'courseid' => $courseid],
                    IGNORE_MISSING
                ) ?: 0);
            }

            $roles = [];
            $coursecontext = \context_course::instance($courseid);
            if ($this->table_exists('role_assignments')) {
                $roles = $this->database->get_fieldset_sql(
                    "SELECT DISTINCT r.shortname
                       FROM {role_assignments} ra
                       JOIN {role} r ON r.id = ra.roleid
                      WHERE ra.userid = :userid
                        AND ra.contextid = :contextid
                   ORDER BY r.shortname ASC",
                    ['userid' => $userid, 'contextid' => $coursecontext->id]
                );
            }

            $out[] = [
                'id' => $courseid,
                'fullname' => (string)$row->fullname,
                'shortname' => (string)$row->shortname,
                'roles' => array_values(array_map('strval', $roles)),
                'enrolstatus' => (int)$row->enrolstatus,
                'enrolledat' => max((int)$row->timestart, (int)$row->timecreated),
                'timeend' => (int)$row->timeend,
                'timecompleted' => $completion ? (int)$completion->timecompleted : 0,
                'activities' => $activities,
                'gradecount' => $gradecount,
                'gradeaverage' => $gradeaverage,
                'lastaccess' => $lastaccess,
            ];
        }
        return $out;
    }

    /** @return array<int,array<string,mixed>> */
    private function purchases(int $userid): array {
        if (!$this->table_exists('local_subscriptions_commerce_purchase')) {
            return [];
        }
        $rows = $this->database->get_records(
            'local_subscriptions_commerce_purchase',
            ['userid' => $userid],
            'timecreated DESC, id DESC'
        );
        $out = [];
        foreach ($rows as $row) {
            $snapshot = json_decode((string)$row->snapshotjson, true) ?: [];
            $label = trim((string)($snapshot['offerlabel'] ?? $snapshot['offerreference'] ?? ''));
            $out[] = [
                'id' => (int)$row->id,
                'reference' => (string)$row->reference,
                'type' => (string)$row->type,
                'label' => $label,
                'status' => (string)$row->status,
                'currency' => (string)$row->currency,
                'totalminor' => (int)$row->totalminor,
                'legacyfamily' => (string)($row->legacyfamily ?? ''),
                'legacyid' => empty($row->legacyid) ? null : (int)$row->legacyid,
                'email' => (string)$row->customeremail,
                'timecreated' => (int)$row->timecreated,
            ];
        }
        return $out;
    }

    /** @return array<int,array<string,mixed>> */
    private function legacy_subscriptions(int $userid): array {
        if (!$this->table_exists('user_subscription')) {
            return [];
        }
        $sql = "SELECT us.id, us.planid, us.pricepaid, us.currency, us.status,
                       us.payment_provider, us.start_date, us.end_date, us.creation_date,
                       sp.name AS planname
                  FROM {user_subscription} us
             LEFT JOIN {subscription_plan} sp ON sp.id = us.planid
                 WHERE us.userid = :userid
              ORDER BY us.creation_date DESC, us.id DESC";
        $rows = $this->database->get_records_sql($sql, ['userid' => $userid]);
        return array_values(array_map(static fn($row): array => [
            'id' => (int)$row->id,
            'planid' => (int)$row->planid,
            'planname' => (string)($row->planname ?? ''),
            'price' => (float)$row->pricepaid,
            'currency' => (string)$row->currency,
            'status' => (string)$row->status,
            'provider' => (string)$row->payment_provider,
            'start' => (int)$row->start_date,
            'end' => (int)$row->end_date,
            'created' => (int)$row->creation_date,
        ], $rows));
    }

    /** @return array<int,array<string,mixed>> */
    private function legacy_digital(int $userid): array {
        if (!$this->table_exists('subscription_digital_payment_request')) {
            return [];
        }
        $sql = "SELECT d.id, d.productid, d.email, d.currency, d.price, d.amount_minor,
                       d.payment_provider, d.status, d.creation_date, d.payment_date,
                       d.download_token, p.name AS productname
                  FROM {subscription_digital_payment_request} d
             LEFT JOIN {subscription_digital_product} p ON p.id = d.productid
                 WHERE d.userid = :userid
              ORDER BY COALESCE(d.payment_date,d.creation_date) DESC, d.id DESC";
        $rows = $this->database->get_records_sql($sql, ['userid' => $userid]);
        return array_values(array_map(static fn($row): array => [
            'id' => (int)$row->id,
            'productid' => (int)$row->productid,
            'productname' => (string)($row->productname ?? ''),
            'email' => (string)$row->email,
            'currency' => (string)$row->currency,
            'price' => (float)$row->price,
            'amountminor' => (int)$row->amount_minor,
            'provider' => (string)$row->payment_provider,
            'status' => (string)$row->status,
            'created' => (int)$row->creation_date,
            'paidat' => (int)$row->payment_date,
            'hastoken' => trim((string)$row->download_token) !== '',
        ], $rows));
    }

    /** @return array<int,array<string,mixed>> */
    private function grants(int $userid): array {
        if (!$this->table_exists('local_subs_commerce_grant')) {
            return [];
        }
        $rows = $this->database->get_records(
            'local_subs_commerce_grant',
            ['beneficiaryuserid' => $userid],
            'timecreated DESC, id DESC'
        );
        return array_values(array_map(static fn($row): array => [
            'id' => (int)$row->id,
            'reference' => (string)$row->grantreference,
            'purchase' => (string)$row->purchasereference,
            'sku' => (string)$row->productsku,
            'type' => (string)$row->type,
            'resource' => (string)$row->resourcekey,
            'status' => (string)$row->status,
            'validfrom' => (int)$row->validfrom,
            'validuntil' => (int)$row->validuntil,
        ], $rows));
    }

    /** @return array<int,array<string,mixed>> */
    private function digital_accesses(int $userid): array {
        if (!$this->table_exists('local_subs_commerce_dig_access')) {
            return [];
        }
        $rows = $this->database->get_records(
            'local_subs_commerce_dig_access',
            ['beneficiaryuserid' => $userid],
            'timecreated DESC, id DESC'
        );
        return array_values(array_map(static fn($row): array => [
            'id' => (int)$row->id,
            'purchase' => (string)$row->purchasereference,
            'sku' => (string)$row->productsku,
            'resource' => (string)$row->resourcekey,
            'status' => (string)$row->status,
            'downloads' => (int)$row->downloadcount,
            'maxdownloads' => (int)$row->maxdownloads,
            'validfrom' => (int)$row->validfrom,
            'validuntil' => (int)$row->validuntil,
        ], $rows));
    }

    private function table_exists(string $table): bool {
        return $this->database->get_manager()->table_exists(new \xmldb_table($table));
    }
}
