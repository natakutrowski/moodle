<?php
namespace local_subscriptions\constants;
defined('MOODLE_INTERNAL') || die();

final class Operation {
    public const PURCHASE_NEW               = 'purchase_new';
    public const QUEUE_FUTURE               = 'queue_future';
    public const UPGRADE_NOW_REPLACE_CHAIN  = 'upgrade_now_replace_chain';
}
