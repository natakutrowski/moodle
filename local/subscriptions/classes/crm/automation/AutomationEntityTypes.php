<?php

namespace local_subscriptions\crm\automation;

defined('MOODLE_INTERNAL') || die();

final class AutomationEntityTypes {

    public const USER_SUBSCRIPTION = 'user_subscription';
    public const PAYMENT_REQUEST = 'payment_request';
    public const DIGITAL_PAYMENT_REQUEST = 'digital_payment_request';
    public const USER = 'user';
}