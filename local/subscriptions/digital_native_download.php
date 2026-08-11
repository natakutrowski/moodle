<?php

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/filelib.php');

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\fulfillment\native\digital\CommerceNativeDigitalDownloadResolver;

\local_subscriptions\subscription_config::guard_public_access();

$token = required_param('token', PARAM_ALPHANUMEXT);
$version = optional_param('version', 'desktop', PARAM_ALPHA);
if (!in_array($version, ['desktop', 'mobile'], true)) {
    $version = 'desktop';
}

$resolver = new CommerceNativeDigitalDownloadResolver($DB);
$download = $resolver->resolve($token, time(), $version);
$resolver->register_download($download['access'], time());

if ($download['storedfile'] instanceof stored_file) {
    send_stored_file($download['storedfile'], 0, 0, true, ['filename' => $download['filename']]);
}

send_file($download['filepath'], $download['filename'], 0, 0, false, true, 'application/pdf');
