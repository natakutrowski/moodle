<?php

namespace local_subscriptions\crm\automation;

defined('MOODLE_INTERNAL') || die();

final class AutomationActionKeys {

    public const SEND_EMAIL = 'send_email';
    public const CREATE_NOTE = 'create_note';
    public const ADD_TAG = 'add_tag';
    public const REMOVE_TAG = 'remove_tag';
    public const CREATE_ADMIN_NOTIFICATION = 'create_admin_notification';
    public const CREATE_TASK = 'create_task';
    public const TRIGGER_WORKFLOW = 'trigger_workflow';
}