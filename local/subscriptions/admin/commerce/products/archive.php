<?php

define('NO_OUTPUT_BUFFERING', true);
require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\catalog\service\CommerceCatalogFactory;

AdminSecurity::require(Capabilities::MANAGE_CONFIGURATION);
require_sesskey();
$sku = required_param('sku', PARAM_RAW_TRIMMED);
(new CommerceCatalogFactory($DB))->product_manager()->archive_product($sku);
redirect(new moodle_url('/local/subscriptions/admin/commerce/products/index.php'), get_string('commerce_product_archived', 'local_subscriptions'));
