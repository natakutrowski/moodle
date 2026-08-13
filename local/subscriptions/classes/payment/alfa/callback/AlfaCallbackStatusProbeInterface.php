<?php

declare(strict_types=1);

namespace local_subscriptions\payment\alfa\callback;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\payment\dto\InternalEvent;

interface AlfaCallbackStatusProbeInterface {
    public function probe(array $identity, array $headers = []): InternalEvent;
}
