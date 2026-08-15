<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\mail\library\CommerceMailLibraryExporter;
use local_subscriptions\commerce\mail\library\CommerceMailLibraryRepository;

AdminSecurity::require(Capabilities::MANAGE_CONFIGURATION);
$source = required_param('source', PARAM_ALPHA);
$exporter = new CommerceMailLibraryExporter($DB, new CommerceMailLibraryRepository($DB));
if ($source === 'native') {
    $id = required_param('id', PARAM_INT);
    $payload = $exporter->native($id);
    $basename = clean_filename((string)($payload['template']['name'] ?? 'mail-template'));
} else if ($source === 'transactional') {
    $mailtype = required_param('mailtype', PARAM_ALPHANUMEXT);
    $payload = $exporter->transactional($mailtype);
    $basename = clean_filename('transactional-' . $mailtype);
} else {
    throw new moodle_exception('invalidparameter');
}
header('Content-Type: application/json; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $basename . '.campusfr-mail.json"');
echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit;
