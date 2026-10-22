<?php
// CampusFR CR.7 - validation pure et calcul de differences pour les tests de droits.
namespace local_subscriptions\admin;

defined('MOODLE_INTERNAL') || die();

/**
 * A plan explicitly selects existing accounts; it never creates users or edits enrolments.
 * No role assignment is performed by this class.
 */
final class CrmRoleTestPlan {
    public const MAX_ACCOUNTS = 12;

    /**
     * @return array<int, array{id:int, username:string, roles:array<int,string>}>
     */
    public static function parse(string $json): array {
        try {
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new \InvalidArgumentException('Invalid CR7 JSON plan.', 0, $exception);
        }
        if (!is_array($data) || ($data['version'] ?? null) !== 1 ||
                !isset($data['accounts']) || !is_array($data['accounts']) ||
                !array_is_list($data['accounts']) || count($data['accounts']) < 1 ||
                count($data['accounts']) > self::MAX_ACCOUNTS) {
            throw new \InvalidArgumentException('CR7 plan must have version 1 and 1 to 12 accounts.');
        }

        $accounts = [];
        $seen = [];
        foreach ($data['accounts'] as $account) {
            if (!is_array($account) || !isset($account['id'], $account['username'], $account['roles']) ||
                    !is_int($account['id']) || $account['id'] <= 1 ||
                    !is_string($account['username']) || trim($account['username']) === '' ||
                    !is_array($account['roles']) || !array_is_list($account['roles'])) {
                throw new \InvalidArgumentException('Each account needs an ID, username, and role list.');
            }
            $userid = $account['id'];
            if (isset($seen[$userid])) {
                throw new \InvalidArgumentException('Duplicate account ID in CR7 plan.');
            }
            $seen[$userid] = true;
            $roles = self::role_set($account['roles']);
            $accounts[] = [
                'id' => $userid,
                'username' => trim($account['username']),
                'roles' => $roles,
            ];
        }
        return $accounts;
    }

    /**
     * @param array<int, string> $roles
     * @return array<int, string>
     */
    public static function role_set(array $roles): array {
        $allowed = CrmRoleTemplates::definitions();
        $normalized = [];
        foreach ($roles as $role) {
            if (!is_string($role) || !isset($allowed[$role])) {
                throw new \InvalidArgumentException('CR7 only accepts the six CampusFR CRM roles.');
            }
            if (isset($normalized[$role])) {
                throw new \InvalidArgumentException('Duplicate CRM role in CR7 plan.');
            }
            $normalized[$role] = true;
        }
        $result = array_keys($normalized);
        sort($result, SORT_STRING);
        return $result;
    }

    /**
     * @param array<int,string> $current
     * @param array<int,string> $desired
     * @return array{add:array<int,string>, remove:array<int,string>}
     */
    public static function diff(array $current, array $desired): array {
        $current = self::role_set($current);
        $desired = self::role_set($desired);
        return [
            'add' => array_values(array_diff($desired, $current)),
            'remove' => array_values(array_diff($current, $desired)),
        ];
    }
}
