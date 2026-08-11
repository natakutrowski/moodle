<?php

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\purchase\compatibility\CommerceLegacyPurchaseRedirector;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseReadRepository;

AdminSecurity::require(Capabilities::VIEW_PAYMENTS);
$redirector = new CommerceLegacyPurchaseRedirector(new CommercePurchaseReadRepository($DB));
redirect($redirector->list_url('digital'));
