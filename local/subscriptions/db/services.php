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
];
