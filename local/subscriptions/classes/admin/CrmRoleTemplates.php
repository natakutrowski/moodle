<?php
namespace local_subscriptions\admin;

defined('MOODLE_INTERNAL') || die();

/** Conservative, system-level CRM role profiles; no course role changes. */
final class CrmRoleTemplates {
    public static function definitions(): array {
        return [
            'campus_crm_operations' => [
                'name' => 'CampusFR — CRM Operations',
                'capabilities' => [
                    Capabilities::VIEW_DASHBOARD, Capabilities::VIEW_USERS,
                    Capabilities::MANAGE_USERS, Capabilities::MANAGE_SUBSCRIPTIONS,
                    Capabilities::VIEW_DIGITAL, Capabilities::VIEW_PAYMENTS,
                    Capabilities::VIEW_STATISTICS, Capabilities::VIEW_INBOX,
                    Capabilities::MANAGE_INBOX, Capabilities::VIEW_WORK_ITEMS,
                    Capabilities::MANAGE_WORK_ITEMS, Capabilities::MANAGE_PURCHASE_NOTES,
                ],
            ],
            'campus_crm_support' => [
                'name' => 'CampusFR — Support',
                'capabilities' => [
                    Capabilities::VIEW_DASHBOARD, Capabilities::VIEW_USERS,
                    Capabilities::VIEW_INBOX, Capabilities::MANAGE_INBOX,
                    Capabilities::VIEW_WORK_ITEMS, Capabilities::MANAGE_WORK_ITEMS,
                ],
            ],
            'campus_crm_commerce' => [
                'name' => 'CampusFR — Commerce',
                'capabilities' => [
                    Capabilities::VIEW_DASHBOARD, Capabilities::VIEW_USERS,
                    Capabilities::VIEW_PAYMENTS, Capabilities::VIEW_STATISTICS,
                    Capabilities::VIEW_DIGITAL, Capabilities::MANAGE_PURCHASE_NOTES,
                ],
            ],
            'campus_crm_finance' => [
                'name' => 'CampusFR — Finance (lecture)',
                'capabilities' => [
                    Capabilities::VIEW_DASHBOARD, Capabilities::VIEW_PAYMENTS,
                    Capabilities::VIEW_STATISTICS,
                ],
            ],
            'campus_crm_content' => [
                'name' => 'CampusFR — Content & Marketing',
                'capabilities' => [
                    Capabilities::VIEW_DASHBOARD, Capabilities::MANAGE_SHOWROOMS,
                ],
            ],
            'campus_crm_technical' => [
                'name' => 'CampusFR — Technical CRM',
                'capabilities' => [
                    Capabilities::VIEW_DASHBOARD,
                    Capabilities::MANAGE_CONFIGURATION,
                    Capabilities::MANAGE_WORK_CONFIGURATION,
                    Capabilities::MANAGE_CRM_ADMIN_TOOLS,
                ],
            ],
        ];
    }

    /** Returns the precise allowlist; never grants a CRM role administrator privileges. */
    public static function capabilities(string $shortname): array {
        $definitions = self::definitions();
        if (!isset($definitions[$shortname])) {
            throw new \InvalidArgumentException('Unknown CampusFR CRM role: ' . $shortname);
        }
        return $definitions[$shortname]['capabilities'];
    }
}
