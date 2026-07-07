<?php

namespace local_subscriptions\commandcenter;

defined('MOODLE_INTERNAL') || die();

interface CommandContextAwareProviderInterface {

    public function search_with_context(CommandContext $context, int $limit = 10): array;
}