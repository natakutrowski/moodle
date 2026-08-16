<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;

AdminSecurity::require(Capabilities::VIEW_PAYMENTS);

redirect(new moodle_url(
    '/local/subscriptions/admin/commerce/offers-access/campaigns.php',
    ['kind' => 'offer']
));
