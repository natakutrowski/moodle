<?php
defined('MOODLE_INTERNAL') || die();

$capabilities = [
    'block/edly_subscribe:addinstance' => [
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => [
            'manager' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
        ],
        'clonepermissionsfrom' => 'moodle/site:manageblocks'
    ],

    'block/edly_subscribe:myaddinstance' => [
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => [
            'user' => CAP_PROHIBIT, // on n'affiche pas sur /my par défaut
        ],
        'clonepermissionsfrom' => 'moodle/my:manageblocks'
    ],
];
