<?php
require_once(__DIR__ . '/../../config.php');

use local_subscriptions\url\UrlFactory;

require_login();

redirect(UrlFactory::my_purchases());