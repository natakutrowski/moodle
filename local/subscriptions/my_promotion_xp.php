<?php

declare(strict_types=1);

// Compatibility route for older Mon Campus and promotion history links.
// Authorization, private history and all rendering are handled by my_points.php.
require_once(__DIR__ . '/../../config.php');

$promotionid = optional_param('promotionid', 0, PARAM_INT);
$page = optional_param('page', 0, PARAM_INT);
$params = [];
if ($promotionid !== 0) {
    $params['promotionid'] = $promotionid;
}
if ($page !== 0) {
    $params['page'] = $page;
}
redirect(new moodle_url('/local/subscriptions/my_points.php', $params));
