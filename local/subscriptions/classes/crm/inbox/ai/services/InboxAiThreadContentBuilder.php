<?php

namespace local_subscriptions\crm\inbox\ai\services;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\ai\safety\InboxAiContentSanitizer;
use local_subscriptions\crm\inbox\repositories\InboxReadRepository;

final class InboxAiThreadContentBuilder {

    private const MAX_MESSAGES = 50;
    private const MAX_CHARACTERS = 60000;

    public function __construct(
        private readonly InboxReadRepository $repository,
        private readonly InboxAiContentSanitizer $sanitizer
    ) {
    }

    public function build(int $threadid): string {
        $thread = $this->repository->get_thread(
            $threadid
        );

        if (!$thread) {
            throw new \moodle_exception(
                'crm_inbox_thread_not_found',
                'local_subscriptions'
            );
        }

        $messages = $this->repository->get_messages(
            $threadid
        );

        if (count($messages) > self::MAX_MESSAGES) {
            $messages = array_slice(
                $messages,
                -self::MAX_MESSAGES
            );
        }

        $parts = [];

        $subject = trim(
            (string)($thread->subject ?? '')
        );

        if ($subject !== '') {
            $parts[] = 'THREAD SUBJECT: ' . $subject;
        }

        foreach ($messages as $message) {
            if (
                in_array(
                    (string)$message->status,
                    ['draft', 'failed'],
                    true
                )
            ) {
                continue;
            }

            $body = trim(
                (string)(
                    $message->bodytext
                    ?: $message->bodyhtml
                    ?: ''
                )
            );

            $body = $this->sanitizer->sanitize(
                $body
            );

            if ($body === '') {
                continue;
            }

            $date = (int)(
                $message->receivedat
                ?? $message->sentat
                ?? $message->timecreated
            );

            $direction =
                $message->direction === 'outbound'
                    ? 'SUPPORT'
                    : 'CUSTOMER';

            $parts[] = implode(PHP_EOL, [
                '---',
                'SPEAKER: ' . $direction,
                'DATE: ' . userdate(
                    $date,
                    '%Y-%m-%d %H:%M'
                ),
                'SUBJECT: ' .
                    trim((string)$message->subject),
                'MESSAGE:',
                $body,
            ]);
        }

        $content = implode(
            PHP_EOL . PHP_EOL,
            $parts
        );

        if (
            \core_text::strlen($content) >
            self::MAX_CHARACTERS
        ) {
            /*
             * On conserve la fin, qui contient généralement
             * les développements les plus récents.
             */
            $content = \core_text::substr(
                $content,
                -self::MAX_CHARACTERS
            );

            $content =
                '[Earlier messages truncated]' .
                PHP_EOL .
                $content;
        }

        return trim($content);
    }
}