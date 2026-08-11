<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail;

defined('MOODLE_INTERNAL') || die();

/**
 * Renders and optionally transports transactional Commerce messages.
 */
final class CommerceMailDispatcher {

    public function __construct(
        private readonly CommerceMailTemplateRegistry $templates,
        private readonly CommerceMailTransport $transport
    ) {
    }

    public function preview(CommerceMailRequest $request): CommerceMailMessage {
        $message = $this->templates
            ->get($request->get_type())
            ->render($request);

        (new CommerceMailCustomerContentPolicy())->assert_safe($message);

        return $message;
    }

    public function dispatch(CommerceMailRequest $request): CommerceMailMessage {
        $message = $this->preview($request);

        if (
            strtolower($message->get_recipient()->get_email())
            !== strtolower($request->get_recipient()->get_email())
        ) {
            throw new \coding_exception(
                'A Commerce transactional mail template cannot change the request recipient.'
            );
        }

        $this->transport->send($message);

        return $message;
    }
}
