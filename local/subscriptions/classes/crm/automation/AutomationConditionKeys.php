<?php

namespace local_subscriptions\crm\automation;

defined('MOODLE_INTERNAL') || die();

final class AutomationConditionKeys {

    public const USER_IS_VIP = 'user_is_vip';
    public const HAS_TAG = 'has_tag';
    public const MISSING_TAG = 'missing_tag';
    public const HAS_ACTIVE_SUBSCRIPTION = 'has_active_subscription';
    public const HAS_PURCHASED_PRODUCT = 'has_purchased_product';
    public const TOTAL_SPENT_AT_LEAST = 'total_spent_at_least';
    public const PURCHASE_COUNT_AT_LEAST = 'purchase_count_at_least';
    public const NOTE_COUNT_AT_LEAST = 'note_count_at_least';
    public const COUNTRY_IS = 'country_is';
    public const LANGUAGE_IS = 'language_is';
    public const EVENT_TAG_IS = 'event_tag_is';
    public const EVENT_NOTE_TYPE_IS = 'event_note_type_is';
}