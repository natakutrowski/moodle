<?php

namespace local_subscriptions\crm\inbox\ai\services;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\logging\InboxAdminEventLogger;

final class InboxAiPanelService {

    public function __construct(
        private readonly InboxAiRuntimeFactory $factory,
        private readonly ?InboxAdminEventLogger $events = null
    ) {
    }

    public function analyse(
        int $threadid,
        string $outputlanguage,
        ?int $actorid = null,
        bool $forcerefresh = false
    ): array {
        $message = $this->latest_customer_message(
            $threadid
        );

        $content = $message !== null
            ? $this->message_content($message)
            : '';

        $messageid = $message !== null
            ? (int)$message->id
            : null;

        $language =
            $this->factory
                ->language()
                ->detect(
                    $threadid,
                    $messageid,
                    $content,
                    $actorid,
                    $forcerefresh
                );

        $urgency =
            $this->factory
                ->urgency()
                ->classify(
                    $threadid,
                    $messageid,
                    $content,
                    [],
                    $actorid,
                    $forcerefresh
                );

        $category =
            $this->factory
                ->categorization()
                ->categorize(
                    $threadid,
                    $messageid,
                    $content,
                    [],
                    $actorid,
                    $forcerefresh
                );

        $summary =
            $this->factory
                ->summary()
                ->summarize(
                    $threadid,
                    $outputlanguage,
                    $actorid,
                    $forcerefresh
                );

        $result = [
            'type' => 'analysis',
            'generatedat' => time(),
            'language' => [
                'value' => $language->language,
                'confidence' =>
                    $language->confidence,
                'successful' =>
                    $language->successful,
                'warnings' =>
                    $language->warnings,
            ],
            'urgency' => [
                'value' => $urgency->urgency,
                'confidence' =>
                    $urgency->confidence,
                'signals' =>
                    $urgency->signals,
                'successful' =>
                    $urgency->successful,
                'warnings' =>
                    $urgency->warnings,
            ],
            'category' => [
                'value' => $category->category,
                'confidence' =>
                    $category->confidence,
                'secondary' =>
                    $category->secondarycategories,
                'signals' =>
                    $category->signals,
                'successful' =>
                    $category->successful,
                'warnings' =>
                    $category->warnings,
            ],
            'summary' => [
                'text' => $summary->summary,
                'keypoints' =>
                    $summary->keypoints,
                'pendingquestions' =>
                    $summary->pendingquestions,
                'customerrequests' =>
                    $summary->customerrequests,
                'confidence' =>
                    $summary->confidence,
                'successful' =>
                    $summary->successful,
                'warnings' =>
                    $summary->warnings,
            ],
        ];

        if ($actorid !== null) {
            $this->event_logger()
                ->ai_analysis_executed(
                    $threadid,
                    $result
                );
        }

        return $result;
    }

    public function suggest_reply(
        int $threadid,
        string $language,
        string $tone,
        ?int $actorid = null,
        bool $forcerefresh = false
    ): array {
        $reply =
            $this->factory
                ->reply()
                ->suggest(
                    $threadid,
                    $language,
                    $tone,
                    $actorid,
                    $forcerefresh
                );

        $result = [
            'type' => 'reply',
            'generatedat' => time(),
            'reply' => [
                'subject' => $reply->subject,
                'body' => $reply->body,
                'language' => $reply->language,
                'tone' => $reply->tone,
                'confidence' =>
                    $reply->confidence,
                'warnings' =>
                    $reply->warnings,
                'requiresreview' =>
                    $reply->requiresreview,
                'generated' =>
                    trim($reply->subject) !== '' ||
                    trim($reply->body) !== '',
            ],
        ];

        if ($actorid !== null) {
            $this->event_logger()
                ->ai_reply_suggested(
                    $threadid,
                    $result
                );
        }

        return $result;
    }

    private function event_logger():
        InboxAdminEventLogger {
        return $this->events
            ?? new InboxAdminEventLogger(
                $this->factory
                    ->read_repository()
            );
    }

    private function latest_customer_message(
        int $threadid
    ): ?object {
        $messages =
            $this->factory
                ->read_repository()
                ->get_messages($threadid);

        foreach (
            array_reverse($messages)
            as $message
        ) {
            if (
                (string)$message->direction !==
                'inbound'
            ) {
                continue;
            }

            if (
                in_array(
                    (string)$message->status,
                    ['draft', 'failed'],
                    true
                )
            ) {
                continue;
            }

            return $message;
        }

        return null;
    }

    private function message_content(
        object $message
    ): string {
        return trim(
            (string)(
                $message->bodytext
                ?: $message->bodyhtml
                ?: ''
            )
        );
    }
}