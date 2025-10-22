<?php
defined('MOODLE_INTERNAL') || die();

$capabilities = [
  'block/edly_contact_form:addinstance' => [
      'riskbitmask' => RISK_XSS,
      'captype' => 'write',
      'contextlevel' => CONTEXT_BLOCK,
      'archetypes' => ['editingteacher' => CAP_ALLOW, 'manager' => CAP_ALLOW],
      'clonepermissionsfrom' => 'moodle/site:manageblocks'
  ],
  'block/edly_contact_form:myaddinstance' => [
      'riskbitmask' => RISK_XSS,
      'captype' => 'write',
      'contextlevel' => CONTEXT_BLOCK,
      'archetypes' => ['user' => CAP_PROHIBIT],
      'clonepermissionsfrom' => 'moodle/my:manageblocks'
  ],
];
