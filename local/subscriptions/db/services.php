<?php
$functions = [
    'local_subscriptions_toggle_plan' => [
        'classname'   => 'local_subscriptions\external\toggle_plan',
        'methodname'  => 'execute',
        'description' => 'Toggle plan activation status',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities'=> 'moodle/site:config'
    ],

    'local_subscriptions_save_dashboard_currency' => [
        'classname' =>
            'local_subscriptions\external\save_dashboard_currency',
        'methodname' => 'execute',
        'description' =>
            'Save the preferred currency for the CRM Dashboard.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' =>
            'local/subscriptions:view_dashboard',
    ],    

    'local_subscriptions_save_dashboard_layout' => [
        'classname' =>
            'local_subscriptions\external\save_dashboard_layout',
        'methodname' => 'execute',
        'description' =>
            'Save or reset the current CRM Dashboard layout.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' =>
            'local/subscriptions:view_dashboard',
    ],

    'local_subscriptions_save_inbox_thread_layout' => [
        'classname' =>
            'local_subscriptions\external\save_inbox_thread_layout',
        'methodname' => 'execute',
        'description' =>
            'Save or reset the current CRM Inbox thread layout.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' =>
            'local/subscriptions:view_inbox',
    ],

    'local_subscriptions_save_workspace_layout' => [
        'classname' =>
            'local_subscriptions\external\save_workspace_layout',
        'methodname' => 'execute',
        'description' =>
            'Save or reset a registered CRM Workspace layout.',
        'type' => 'write',
        'ajax' => true,
    ],

];
