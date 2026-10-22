<?php
namespace local_subscriptions\admin;

defined('MOODLE_INTERNAL') || die();

/** System-only CampusFR CRM role assignment; never touches course-level enrolments. */
final class CrmRoleAssignments {
    public static function resolve_role(string $shortname): \stdClass {
        global $DB;
        if (!array_key_exists($shortname, CrmRoleTemplates::definitions())) {
            throw new \InvalidArgumentException('Role is not a CampusFR CRM template');
        }
        $role = $DB->get_record('role', ['shortname' => $shortname], '*', MUST_EXIST);
        if (!str_starts_with((string)$role->description, 'CampusFR CRM system role (CR.3).')) {
            throw new \moodle_exception('invalidrole');
        }
        return $role;
    }

    public static function assigned(int $userid): array {
        global $DB;
        $context = \context_system::instance();
        $result = [];
        foreach (CrmRoleTemplates::definitions() as $shortname => $definition) {
            $role = $DB->get_record('role', ['shortname' => $shortname]);
            if ($role && str_starts_with((string)$role->description, 'CampusFR CRM system role (CR.3).') &&
                    user_has_role_assignment($userid, $role->id, $context->id)) {
                $result[$shortname] = $definition['name'];
            }
        }
        return $result;
    }

    public static function change(int $actorid, int $userid, string $shortname, string $action): void {
        global $DB, $USER;
        $context = \context_system::instance();
        // Never trust an actor ID passed in by callers: authorization uses the logged-in user.
        if ($actorid !== (int)$USER->id) {
            throw new \moodle_exception('invalidparameter');
        }
        require_capability(Capabilities::MANAGE_CRM_ROLES, $context);
        require_capability('moodle/role:assign', $context);
        if ($actorid === $userid || $userid <= 0 || is_siteadmin($userid) ||
                !in_array($action, ['add', 'remove'], true)) {
            throw new \moodle_exception('invalidparameter');
        }
        $target = $DB->get_record('user', ['id' => $userid, 'deleted' => 0], '*', MUST_EXIST);
        if (!empty($target->suspended)) {
            throw new \moodle_exception('invalidparameter');
        }
        $role = self::resolve_role($shortname);
        // Moodle's role delegation matrix applies, even to CRM-specific roles.
        $assignable = get_assignable_roles($context, ROLENAME_SHORT);
        if (!array_key_exists($role->id, $assignable) && !is_siteadmin($USER->id)) {
            throw new \required_capability_exception($context, 'moodle/role:assign', 'nopermissions', '');
        }
        if ($action === 'add') {
            if (!user_has_role_assignment($userid, $role->id, $context->id)) {
                role_assign($role->id, $userid, $context->id);
            }
        } else {
            // Remove only the system-context CRM assignment, never course assignments.
            role_unassign($role->id, $userid, $context->id);
        }
    }
}
