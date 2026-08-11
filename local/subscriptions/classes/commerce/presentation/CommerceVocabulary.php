<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\presentation;

defined('MOODLE_INTERNAL') || die();

/**
 * Central Commerce vocabulary for client, CRM and diagnostic interfaces.
 */
final class CommerceVocabulary {
    private const PRODUCT_TYPE_INTENTS = [
        'course_access' => CommercePresentationLabel::INTENT_INFO,
        'digital_download' => CommercePresentationLabel::INTENT_INFO,
        'bundle' => CommercePresentationLabel::INTENT_SUCCESS,
        'service' => CommercePresentationLabel::INTENT_INFO,
    ];

    private const PRODUCT_STATUS_INTENTS = [
        'active' => CommercePresentationLabel::INTENT_SUCCESS,
        'draft' => CommercePresentationLabel::INTENT_MUTED,
        'inactive' => CommercePresentationLabel::INTENT_WARNING,
        'archived' => CommercePresentationLabel::INTENT_MUTED,
    ];

    private const PURCHASE_STATUS_INTENTS = [
        'draft' => CommercePresentationLabel::INTENT_MUTED,
        'created' => CommercePresentationLabel::INTENT_INFO,
        'prepared' => CommercePresentationLabel::INTENT_INFO,
        'payment_pending' => CommercePresentationLabel::INTENT_WARNING,
        'authorized' => CommercePresentationLabel::INTENT_INFO,
        'captured' => CommercePresentationLabel::INTENT_SUCCESS,
        'paid' => CommercePresentationLabel::INTENT_SUCCESS,
        'fulfillment_pending' => CommercePresentationLabel::INTENT_WARNING,
        'fulfilled' => CommercePresentationLabel::INTENT_SUCCESS,
        'completed' => CommercePresentationLabel::INTENT_SUCCESS,
        'active' => CommercePresentationLabel::INTENT_SUCCESS,
        'expired' => CommercePresentationLabel::INTENT_MUTED,
        'replaced' => CommercePresentationLabel::INTENT_MUTED,
        'cancelled' => CommercePresentationLabel::INTENT_MUTED,
        'failed' => CommercePresentationLabel::INTENT_DANGER,
        'refunded' => CommercePresentationLabel::INTENT_WARNING,
        'unknown' => CommercePresentationLabel::INTENT_MUTED,
    ];

    private const PAYMENT_STATUS_INTENTS = [
        'created' => CommercePresentationLabel::INTENT_INFO,
        'requires_action' => CommercePresentationLabel::INTENT_WARNING,
        'pending' => CommercePresentationLabel::INTENT_WARNING,
        'authorized' => CommercePresentationLabel::INTENT_INFO,
        'captured' => CommercePresentationLabel::INTENT_SUCCESS,
        'paid' => CommercePresentationLabel::INTENT_SUCCESS,
        'succeeded' => CommercePresentationLabel::INTENT_SUCCESS,
        'failed' => CommercePresentationLabel::INTENT_DANGER,
        'cancelled' => CommercePresentationLabel::INTENT_MUTED,
        'expired' => CommercePresentationLabel::INTENT_MUTED,
        'refunded' => CommercePresentationLabel::INTENT_WARNING,
        'partially_refunded' => CommercePresentationLabel::INTENT_WARNING,
        'unknown' => CommercePresentationLabel::INTENT_MUTED,
    ];

    private const FULFILLMENT_STATUS_INTENTS = [
        'pending' => CommercePresentationLabel::INTENT_WARNING,
        'processing' => CommercePresentationLabel::INTENT_INFO,
        'fulfilled' => CommercePresentationLabel::INTENT_SUCCESS,
        'completed' => CommercePresentationLabel::INTENT_SUCCESS,
        'failed' => CommercePresentationLabel::INTENT_DANGER,
        'cancelled' => CommercePresentationLabel::INTENT_MUTED,
        'unknown' => CommercePresentationLabel::INTENT_MUTED,
    ];

    private function __construct() {
    }

    public static function product_type(
        string $type,
        string $context = CommercePresentationContext::CRM
    ): CommercePresentationLabel {
        return self::label('product_type', $type, $context, self::PRODUCT_TYPE_INTENTS);
    }

    public static function product_status(
        string $status,
        string $context = CommercePresentationContext::CRM
    ): CommercePresentationLabel {
        return self::label('product_status', $status, $context, self::PRODUCT_STATUS_INTENTS);
    }

    public static function purchase_status(
        string $status,
        string $context = CommercePresentationContext::CRM
    ): CommercePresentationLabel {
        return self::label('purchase_status', $status, $context, self::PURCHASE_STATUS_INTENTS);
    }

    public static function payment_status(
        string $status,
        string $context = CommercePresentationContext::CRM
    ): CommercePresentationLabel {
        return self::label('payment_status', $status, $context, self::PAYMENT_STATUS_INTENTS);
    }

    public static function fulfillment_status(
        string $status,
        string $context = CommercePresentationContext::CRM
    ): CommercePresentationLabel {
        return self::label('fulfillment_status', $status, $context, self::FULFILLMENT_STATUS_INTENTS);
    }

    public static function access_type(
        string $type,
        string $context = CommercePresentationContext::CRM
    ): CommercePresentationLabel {
        return self::label('access_type', $type, $context, [
            'course' => CommercePresentationLabel::INTENT_INFO,
            'digital_product' => CommercePresentationLabel::INTENT_INFO,
            'subscription' => CommercePresentationLabel::INTENT_SUCCESS,
            'bundle' => CommercePresentationLabel::INTENT_SUCCESS,
        ]);
    }

    /**
     * @param array<string, string> $intents
     */
    private static function label(
        string $family,
        string $value,
        string $context,
        array $intents
    ): CommercePresentationLabel {
        $context = CommercePresentationContext::require_valid($context);
        $normalised = self::normalise($value);
        $known = array_key_exists($normalised, $intents);
        $keycontext = $context === CommercePresentationContext::DIAGNOSTIC
            ? CommercePresentationContext::CRM
            : $context;
        $stringkey = 'commerce_vocabulary_' . $family . '_' . $keycontext . '_' . $normalised;

        if (!$known || !get_string_manager()->string_exists($stringkey, 'local_subscriptions')) {
            $stringkey = 'commerce_vocabulary_' . $family . '_unknown';
        }

        return new CommercePresentationLabel(
            get_string($stringkey, 'local_subscriptions'),
            $intents[$normalised] ?? CommercePresentationLabel::INTENT_MUTED,
            $family . ':' . ($normalised === '' ? 'empty' : $normalised)
        );
    }

    private static function normalise(string $value): string {
        $value = strtolower(trim($value));
        return str_replace(['-', ' '], '_', $value);
    }
}
