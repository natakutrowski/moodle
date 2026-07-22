<?php

require_once(__DIR__ . '/../../config.php');

use local_subscriptions\subscription_config;

redirect(
    new moodle_url(
        subscription_config::
            admin_dashboard_page()
    )
);