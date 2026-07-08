<?php

namespace local_subscriptions\crm\automation;

defined('MOODLE_INTERNAL') || die();

final class AutomationTriggerKeys {

    public const TRIAL_EXPIRED = 'trial_expired';
    public const PAYMENT_SUCCEEDED = 'payment_succeeded';
    public const PAYMENT_FAILED = 'payment_failed';
    public const DIGITAL_PURCHASE_CREATED = 'digital_purchase_created';
    public const DIGITAL_PURCHASE_PAID = 'digital_purchase_paid';
    public const NOTE_ADDED = 'note_added';
    public const TAG_ADDED = 'tag_added';
    public const TAG_REMOVED = 'tag_removed';
    public const SUBSCRIPTION_EXPIRED = 'subscription_expired';
    public const USER_SUSPENDED = 'user_suspended';
    public const USER_REACTIVATED = 'user_reactivated';
}