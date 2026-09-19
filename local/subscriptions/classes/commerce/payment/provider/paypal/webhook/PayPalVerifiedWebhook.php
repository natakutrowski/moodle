<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\payment\provider\paypal\webhook;

defined('MOODLE_INTERNAL') || die();

final class PayPalVerifiedWebhook {
    public function __construct(
        private readonly string $eventid,
        private readonly string $eventtype,
        private readonly array $event
    ) {
        if (trim($eventid) === '') {
            throw new \coding_exception(
                'A verified PayPal webhook requires an event id.'
            );
        }
        if (trim($eventtype) === '') {
            throw new \coding_exception(
                'A verified PayPal webhook requires an event type.'
            );
        }
    }

    public function get_event_id(): string {
        return trim($this->eventid);
    }

    public function get_event_type(): string {
        return strtoupper(trim($this->eventtype));
    }

    public function get_event(): array {
        return $this->event;
    }

    public function get_resource(): array {
        $resource = $this->event['resource'] ?? [];

        return is_array($resource)
            ? $resource
            : [];
    }
}
