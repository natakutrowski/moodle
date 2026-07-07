<?php

namespace local_subscriptions\commandcenter\actions;

defined('MOODLE_INTERNAL') || die();

interface CommandActionInterface {

    public function key(): string;

    public function capability(): string;

    public function execute(array $payload): CommandActionResult;
}