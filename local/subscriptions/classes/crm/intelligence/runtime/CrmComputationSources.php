<?php

namespace local_subscriptions\crm\intelligence\runtime;

defined('MOODLE_INTERNAL') || die();

/**
 * Known computation sources.
 */
final class CrmComputationSources {

    public const SNAPSHOT = 'snapshot';

    public const RECOMMENDATION = 'recommendation';

    public const USER360 = 'user360';

    public const LEGACY_BUILDER = 'legacy_builder';

    public const CLI = 'cli';

    public const TASK = 'task';

    private function __construct() {
    }
}