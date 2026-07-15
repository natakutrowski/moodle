<?php

namespace local_subscriptions\commandcenter\actions;

defined('MOODLE_INTERNAL') || die();

final class CommandActionKeys {

    public const OPEN_URL = 'open_url';
    public const OPEN_USER = 'open_user';
    public const OPEN_PRODUCT = 'open_product';
    public const OPEN_PURCHASE = 'open_purchase';
    public const OPEN_SUBSCRIPTION = 'open_subscription';
    public const USER_EMAIL = 'user_email';
    public const USER_RESET_PASSWORD = 'user_reset_password';
    public const USER_ADD_NOTE = 'user_add_note';

    public const PURCHASE_RESEND_EMAIL = 'purchase_resend_email';
    public const PURCHASE_REGENERATE_TOKEN = 'purchase_regenerate_token';
    public const PURCHASE_EXTEND_TOKEN = 'purchase_extend_token';
    public const PURCHASE_CHECK_PROVIDER = 'purchase_check_provider';
    public const INBOX_SYNC = 'inbox_sync';
}