<?php

namespace local_subscriptions\crm\intelligence\scoring;

defined('MOODLE_INTERNAL') || die();

final class LeadScoreReasons {

    public const ACTIVE_CUSTOMER = 'active_customer';
    public const TRIAL_USER = 'trial_user';
    public const PAID_DIGITAL_PURCHASE = 'paid_digital_purchase';
    public const HIGH_VALUE = 'high_value';
    public const RECENT_ACTIVITY = 'recent_activity';
    public const INACTIVE = 'inactive';
    public const EXPIRED_SUBSCRIPTION = 'expired_subscription';
    public const SUSPENDED = 'suspended';
}