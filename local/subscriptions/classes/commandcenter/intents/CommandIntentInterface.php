<?php

namespace local_subscriptions\commandcenter\intents;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commandcenter\CommandContext;

interface CommandIntentInterface {

    /**
     * @return CommandIntentMatch[]
     */
    public function match(CommandContext $context): array;
}