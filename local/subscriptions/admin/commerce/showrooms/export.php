<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\commerce\showroom\cms\CommerceShowroomCmsRepository;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomPackageService;

require_login();
require_sesskey();
require_capability('local/subscriptions:manage_showrooms', context_system::instance());

$id = required_param('id', PARAM_INT);
$service = new CommerceShowroomPackageService(new CommerceShowroomCmsRepository($DB));
$package = $service->export($id);
$filename = clean_filename((string)$package['showroom']['showroomkey'] . '.showroom.json');
header('Content-Type: application/json; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
echo json_encode($package, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
