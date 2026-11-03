<?php

declare(strict_types=1);

namespace local_subscriptions\crm\user;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\admin\Capabilities;

/**
 * CRM-scoped impersonation policy.
 *
 * The historical class/capability names mention "student", but this action is
 * available for any active, confirmed, non-admin Moodle account. Crucially,
 * no global moodle/user:loginas permission is granted to CRM Support.
 */
final class CrmStudentImpersonation {

    /** Check the currently authenticated operator, not the real user behind an impersonation. */
    private static function has_crm_rights(): bool {
        $context = \context_system::instance();
        return has_capability(Capabilities::VIEW_USERS, $context)
            && has_capability(Capabilities::LOGIN_AS_STUDENT, $context);
    }

    /** A true switch is only possible from an ordinary (not already impersonated) Moodle session. */
    public static function can_start(\stdClass $target): bool {
        global $USER;

        return !\core\session\manager::is_loggedinas()
            && self::has_crm_rights()
            && self::is_eligible_target($target, (int)$USER->id);
    }

    /**
     * An admin impersonating a CRM Support operator may preview the action.
     * This does NOT permit a second login-as, which Moodle core prohibits.
     */
    public static function can_preview(\stdClass $target): bool {
        global $DB, $USER;

        if (!\core\session\manager::is_loggedinas()
                || !is_siteadmin(\core\session\manager::get_realuser())
                || !self::has_crm_rights()
                || !self::is_eligible_target($target, (int)$USER->id)) {
            return false;
        }

        $context = \context_system::instance();
        return $DB->record_exists_sql(
            "SELECT 1 FROM {role_assignments} ra
               JOIN {role} r ON r.id = ra.roleid
              WHERE ra.userid = :userid
                AND ra.contextid = :contextid
                AND r.shortname = :role",
            [
                'userid' => (int)$USER->id,
                'contextid' => $context->id,
                'role' => 'campus_crm_support',
            ]
        );
    }

    /** Button visibility: normal action or read-only admin test of a Support account. */
    public static function can_show(\stdClass $target): bool {
        return self::can_start($target) || self::can_preview($target);
    }

    /**
     * Only basic account safeguards. An enrolment, a student role or a
     * commercial purchase is not required to inspect a user's experience.
     * Non-admin staff accounts are eligible too, as explicitly authorised
     * by the CampusFR CRM permissions policy.
     */
    public static function is_eligible_target(\stdClass $target, int $operatorid): bool {
        $targetid = (int)($target->id ?? 0);
        return $targetid > 1
            && $targetid !== $operatorid
            && empty($target->deleted)
            && empty($target->suspended)
            && !empty($target->confirmed)
            && !in_array((string)($target->auth ?? ''), ['guest', 'nologin'], true)
            && !is_siteadmin($targetid);
    }

    /** Backward-compatible alias for existing integrations and tests. */
    public static function is_student_target(\stdClass $target, int $operatorid): bool {
        return self::is_eligible_target($target, $operatorid);
    }
}
