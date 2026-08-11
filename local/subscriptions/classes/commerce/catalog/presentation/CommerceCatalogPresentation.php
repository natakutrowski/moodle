<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\presentation;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use local_subscriptions\commerce\catalog\readmodel\CommerceCatalogFulfillment;
use local_subscriptions\commerce\catalog\readmodel\CommerceCatalogPrice;
use local_subscriptions\commerce\purchase\presentation\CommercePurchasePresentation;

/** Presentation vocabulary shared by the federated catalogue pages. */
final class CommerceCatalogPresentation {
    public static function label(string $dimension, string $value): string {
        $key = 'commerce_catalog_' . $dimension . '_' . strtolower($value);
        return get_string_manager()->string_exists($key, 'local_subscriptions')
            ? get_string($key, 'local_subscriptions')
            : s($value);
    }

    public static function badge(string $dimension, string $value): string {
        if ($dimension === 'type') {
            $purchasetype = match ($value) {
                'course_access' => 'subscription',
                'digital_download' => 'digital',
                default => $value,
            };
            return CommercePurchasePresentation::type_badge($purchasetype);
        }
        $classes = [
            'editorial' => ['published' => 'text-bg-success', 'draft' => 'text-bg-secondary', 'archived' => 'text-bg-dark'],
            'visibility' => ['visible' => 'text-bg-info', 'hidden' => 'text-bg-secondary', 'direct_link' => 'text-bg-warning'],
            'availability' => ['on_sale' => 'text-bg-success', 'upcoming' => 'text-bg-info', 'unavailable' => 'text-bg-secondary', 'ended' => 'text-bg-dark'],
            'technical' => ['valid' => 'text-bg-success', 'incomplete' => 'text-bg-warning', 'error' => 'text-bg-danger'],
            'origin' => ['native' => 'text-bg-primary', 'legacy_plan' => 'text-bg-secondary', 'legacy_digital' => 'text-bg-secondary'],
            'lifecycle' => ['active' => 'text-bg-success', 'inactive' => 'text-bg-secondary', 'archived' => 'text-bg-dark'],
            'type' => ['course_access' => 'text-bg-primary', 'digital_download' => 'text-bg-info', 'bundle' => 'text-bg-success', 'service' => 'text-bg-warning'],
        ];
        $class = $classes[$dimension][$value] ?? 'text-bg-secondary';
        return html_writer::span(self::label($dimension, $value), 'badge rounded-pill ' . $class);
    }

    public static function money(CommerceCatalogPrice $price): string {
        return format_float($price->get_amount_minor() / 100, 2) . ' ' . s($price->get_currency());
    }

    /** @param CommerceCatalogPrice[] $prices */
    public static function prices(array $prices): string {
        if ($prices === []) { return html_writer::span(get_string('none'), 'text-muted'); }
        $items = [];
        foreach ($prices as $price) {
            $items[] = html_writer::div(
                html_writer::tag('strong', self::money($price)) .
                ($price->get_provider() !== null ? html_writer::span(' · ' . s($price->get_provider()), 'small text-muted') : ''),
                'mb-1'
            );
        }
        return implode('', $items);
    }

    public static function fulfillment_label(CommerceCatalogFulfillment $fulfillment, ?\moodle_database $db = null): string {
        $type = $fulfillment->get_type();
        $resource = $fulfillment->get_resource_key();
        if ($type === 'course_enrolment' && preg_match('/course:(\d+)/', $resource, $match)) {
            $courseid = (int)$match[1];
            $name = $db?->get_field('course', 'fullname', ['id' => $courseid]);
            return get_string('commerce_catalog_fulfillment_course', 'local_subscriptions',
                $name ? format_string((string)$name) : $courseid);
        }
        if ($type === 'digital_download') {
            return get_string('commerce_catalog_fulfillment_download', 'local_subscriptions');
        }
        return self::label('fulfillment', $type);
    }
}
