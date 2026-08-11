<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\presentation;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use local_subscriptions\commerce\presentation\CommercePresentationContext;
use local_subscriptions\commerce\presentation\CommerceVocabulary;

/** Human-readable labels and badges for Native Commerce objects in the CRM. */
final class CommerceProductPresentation {
    public static function type_label(
        string $type,
        string $context = CommercePresentationContext::CRM
    ): string {
        return CommerceVocabulary::product_type($type, $context)->label();
    }

    public static function status_label(
        string $status,
        string $context = CommercePresentationContext::CRM
    ): string {
        return CommerceVocabulary::product_status($status, $context)->label();
    }

    public static function type_badge(string $type): string {
        $class = match (strtolower(trim($type))) {
            'course_access' => 'crm-commerce-badge-course',
            'digital_download' => 'crm-commerce-badge-digital',
            'bundle' => 'crm-commerce-badge-bundle',
            'service' => 'crm-commerce-badge-service',
            default => 'crm-commerce-badge-neutral',
        };

        return html_writer::span(self::type_label($type), 'crm-commerce-type-badge ' . $class);
    }

    public static function status_badge(string $status): string {
        $class = match (strtolower(trim($status))) {
            'active' => 'crm-commerce-status-active',
            'draft' => 'crm-commerce-status-draft',
            'inactive' => 'crm-commerce-status-inactive',
            'archived' => 'crm-commerce-status-archived',
            default => 'crm-commerce-status-neutral',
        };

        return html_writer::span(self::status_label($status), 'crm-commerce-status-badge ' . $class);
    }

    public static function entitlement_label(string $type, string $resourcekey, ?\moodle_database $db = null): string {
        $type = strtolower(trim($type));
        $resourcekey = trim($resourcekey);

        // The resource key is the authoritative source for known entitlement families.
        // Historical records may expose a generic type such as "other" even though the
        // resource key still identifies a course or a digital product precisely.
        if (preg_match('/^course:(\d+):([a-z0-9_-]+)$/i', $resourcekey, $matches)) {
            $coursename = self::course_name((int) $matches[1], $db);
            return get_string('commerce_entitlement_course_named', 'local_subscriptions', (object) [
                'course' => $coursename,
                'level' => self::access_level_label($matches[2]),
            ]);
        }

        if (preg_match('/^digital-product:(\d+)$/i', $resourcekey, $matches)) {
            $productname = self::digital_product_name((int) $matches[1], $db);
            return get_string('commerce_entitlement_digital_named', 'local_subscriptions', $productname);
        }

        $typekey = 'commerce_entitlement_type_' . str_replace('-', '_', $type);
        $typelabel = get_string_manager()->string_exists($typekey, 'local_subscriptions')
            ? get_string($typekey, 'local_subscriptions')
            : get_string('commerce_entitlement_type_other', 'local_subscriptions');

        return get_string('commerce_entitlement_generic_readable', 'local_subscriptions', (object) [
            'type' => $typelabel,
            'resource' => $resourcekey,
        ]);
    }

    public static function entitlement_reference(
        string $resourcekey,
        string $context = CommercePresentationContext::DIAGNOSTIC
    ): string {
        if (!CommercePresentationContext::allows_technical_details($context)) {
            return '';
        }

        return html_writer::span(
            '(' . s(trim($resourcekey)) . ')',
            'crm-commerce-technical-reference'
        );
    }

    public static function entitlement_html(
        string $type,
        string $resourcekey,
        ?\moodle_database $db = null,
        string $context = CommercePresentationContext::CRM
    ): string {
        return html_writer::div(
            html_writer::div(self::entitlement_label($type, $resourcekey, $db), 'crm-commerce-entitlement-label') .
            self::entitlement_reference($resourcekey, $context),
            'crm-commerce-entitlement'
        );
    }

    private static function course_name(int $courseid, ?\moodle_database $db): string {
        if ($db !== null) {
            $name = $db->get_field('course', 'fullname', ['id' => $courseid]);
            if (is_string($name) && trim($name) !== '') {
                return format_string($name);
            }
        }
        return get_string('commerce_course_fallback', 'local_subscriptions', $courseid);
    }

    private static function digital_product_name(int $productid, ?\moodle_database $db): string {
        if ($db !== null) {
            $record = $db->get_record('subscription_digital_product', ['id' => $productid], 'id,name', IGNORE_MISSING);
            if ($record) {
                foreach (['name'] as $field) {
                    if (property_exists($record, $field) && trim((string) $record->{$field}) !== '') {
                        return format_string((string) $record->{$field});
                    }
                }
            }
        }
        return get_string('commerce_digital_product_fallback', 'local_subscriptions', $productid);
    }

    private static function access_level_label(string $level): string {
        $key = 'commerce_entitlement_access_' . strtolower(trim($level));
        return get_string_manager()->string_exists($key, 'local_subscriptions')
            ? get_string($key, 'local_subscriptions')
            : get_string('commerce_entitlement_access_generic', 'local_subscriptions');
    }
}
