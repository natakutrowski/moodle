<?php

namespace local_subscriptions\commandcenter\providers;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\admin\Capabilities;
use local_subscriptions\commandcenter\CommandContext;
use local_subscriptions\commandcenter\CommandContextAwareProviderInterface;
use local_subscriptions\commandcenter\CommandIcons;
use local_subscriptions\commandcenter\CommandProviderInterface;
use local_subscriptions\commandcenter\CommandQuery;
use local_subscriptions\commandcenter\CommandResult;
use local_subscriptions\commandcenter\CommandScorer;
use local_subscriptions\commandcenter\CommandTypes;
use local_subscriptions\commandcenter\actions\CommandActionKeys;
use local_subscriptions\commandcenter\repositories\InboxSearchRepository;
use local_subscriptions\crm\inbox\rendering\InboxValuePresentation;
use local_subscriptions\subscription_config;
use moodle_url;

final class InboxProvider implements
    CommandProviderInterface,
    CommandContextAwareProviderInterface {

    public function __construct(
        private readonly ?InboxSearchRepository $repository = null
    ) {
    }

    public function search_with_context(
        CommandContext $context,
        int $limit = 10
    ): array {
        return $this->search(
            $context->query(),
            $limit
        );
    }

    public function search(
        CommandQuery $query,
        int $limit = 10
    ): array {
        if (
            !Capabilities::can_view_inbox()
        ) {
            return [];
        }

        if (
            $query->has_direct_entity() &&
            !$query->is_direct_entity(
                'inbox_thread'
            ) &&
            !$query->is_direct_entity(
                'inbox_contact'
            )
        ) {
            return [];
        }

        if (
            $query->is_direct_entity(
                'inbox_thread'
            )
        ) {
            $thread =
                $this->repository()
                    ->find_thread(
                        (int)$query->id()
                    );

            return $thread
                ? [
                    $this->format_thread(
                        $thread,
                        (string)$query->id(),
                        130
                    ),
                ]
                : [];
        }

        if (
            $query->is_direct_entity(
                'inbox_contact'
            )
        ) {
            $contact =
                $this->repository()
                    ->find_contact(
                        (int)$query->id()
                    );

            return $contact
                ? [
                    $this->format_contact(
                        $contact,
                        (string)$query->id(),
                        130
                    ),
                ]
                : [];
        }

        if ($query->is_action_mode()) {
            return [];
        }

        $text = trim(
            $query->text()
        );

        if (
            \core_text::strlen($text) < 2
        ) {
            return [];
        }

        $pertype = max(
            2,
            (int)ceil($limit / 2)
        );

        $results = [];

        foreach (
            $this->repository()
                ->search_threads(
                    $text,
                    $pertype
                )
            as $thread
        ) {
            $results[] =
                $this->format_thread(
                    $thread,
                    $text
                );
        }

        foreach (
            $this->repository()
                ->search_contacts(
                    $text,
                    $pertype
                )
            as $contact
        ) {
            $results[] =
                $this->format_contact(
                    $contact,
                    $text
                );
        }

        usort(
            $results,
            static function(
                array $a,
                array $b
            ): int {
                return
                    ($b['score'] ?? 0)
                    <=>
                    ($a['score'] ?? 0);
            }
        );

        return array_slice(
            $results,
            0,
            $limit
        );
    }

    private function format_thread(
        object $thread,
        string $text,
        int $basescore = 0
    ): array {
        $subject = trim(
            (string)(
                $thread->subject
                ?? ''
            )
        );

        if ($subject === '') {
            $subject = get_string(
                'crm_inbox_no_subject',
                'local_subscriptions'
            );
        }

        $contact = trim(
            (string)(
                $thread->contactname
                ?: $thread->contactemail
                ?: ''
            )
        );

        $subtitle = [];

        if ($contact !== '') {
            $subtitle[] = $contact;
        }

        $subtitle[] = get_string(
            'command_inbox_thread_status',
            'local_subscriptions',
            InboxValuePresentation::
                status_label(
                    (string)$thread->status
                )
        );

        $subtitle[] = get_string(
            'command_inbox_thread_priority',
            'local_subscriptions',
            InboxValuePresentation::
                priority_label(
                    (string)$thread->priority
                )
        );

        if (
            !empty($thread->unreadcount)
        ) {
            $subtitle[] = get_string(
                'command_inbox_thread_unread',
                'local_subscriptions',
                (int)$thread->unreadcount
            );
        }

        $score = $basescore ?: CommandScorer::best(
            CommandScorer::id(
                $text,
                (int)$thread->id
            ),

            CommandScorer::exact_or_prefix(
                $text,
                $subject,
                120,
                105,
                75
            ),

            CommandScorer::email(
                $text,
                (string)(
                    $thread->contactemail
                    ?? ''
                )
            ),

            CommandScorer::fullname(
                $text,
                (string)(
                    $thread->contactname
                    ?? ''
                )
            )
        );

        if (
            (string)$thread->priority ===
            'urgent'
        ) {
            $score += 10;
        }

        return CommandResult::create()
            ->icon(
                CommandIcons::INBOX_THREAD
            )
            ->type(
                CommandTypes::inbox_thread()
            )
            ->group(
                'inbox_threads',
                get_string(
                    'command_center_group_inbox_threads',
                    'local_subscriptions'
                )
            )
            ->action_label(
                get_string(
                    'command_center_action_open',
                    'local_subscriptions'
                )
            )
            ->shortcut(
                'thread:' .
                (int)$thread->id
            )
            ->title($subject)
            ->subtitle(
                implode(
                    ' · ',
                    $subtitle
                )
            )
            ->url(
                (
                    new moodle_url(
                        subscription_config::
                            admin_inbox_thread_page(),
                        [
                            'id' =>
                                (int)$thread->id,
                        ]
                    )
                )->out(false)
            )
            ->action(
                CommandActionKeys::OPEN_URL,
                [
                    'url' =>
                        (
                            new moodle_url(
                                subscription_config::
                                    admin_inbox_thread_page(),
                                [
                                    'id' =>
                                        (int)$thread->id,
                                ]
                            )
                        )->out(false),
                ]
            )
            ->score($score)
            ->meta(
                'provider',
                'inbox'
            )
            ->meta(
                'entity',
                'inbox_thread'
            )
            ->meta(
                'id',
                (int)$thread->id
            )
            ->meta(
                'contactid',
                (int)(
                    $thread->contactid
                    ?? 0
                )
            )
            ->to_array();
    }

    private function format_contact(
        object $contact,
        string $text,
        int $basescore = 0
    ): array {
        $name = trim(
            (string)(
                $contact->displayname
                ?? ''
            )
        );

        $email = trim(
            (string)(
                $contact->primaryemail
                ?? ''
            )
        );

        $title = $name !== ''
            ? $name
            : $email;

        if ($title === '') {
            $title = get_string(
                'command_inbox_unknown_contact',
                'local_subscriptions'
            );
        }

        $subtitle = [];

        if (
            $email !== '' &&
            $email !== $title
        ) {
            $subtitle[] = $email;
        }

        $subtitle[] = get_string(
            'command_inbox_contact_conversations',
            'local_subscriptions',
            (int)(
                $contact->conversationcount
                ?? 0
            )
        );

        if (
            !empty($contact->unreadcount)
        ) {
            $subtitle[] = get_string(
                'command_inbox_contact_unread',
                'local_subscriptions',
                (int)$contact->unreadcount
            );
        }

        $url = new moodle_url(
            subscription_config::
                admin_inbox_page(),
            [
                'q' => $email !== ''
                    ? $email
                    : $name,
            ]
        );

        $score = $basescore ?: CommandScorer::best(
            CommandScorer::id(
                $text,
                (int)$contact->id
            ),

            CommandScorer::email(
                $text,
                $email
            ),

            CommandScorer::fullname(
                $text,
                $name
            )
        );

        return CommandResult::create()
            ->icon(
                CommandIcons::INBOX_CONTACT
            )
            ->type(
                CommandTypes::inbox_contact()
            )
            ->group(
                'inbox_contacts',
                get_string(
                    'command_center_group_inbox_contacts',
                    'local_subscriptions'
                )
            )
            ->action_label(
                get_string(
                    'command_center_action_open',
                    'local_subscriptions'
                )
            )
            ->shortcut(
                'contact:' .
                (int)$contact->id
            )
            ->title($title)
            ->subtitle(
                implode(
                    ' · ',
                    $subtitle
                )
            )
            ->url(
                $url->out(false)
            )
            ->action(
                CommandActionKeys::OPEN_URL,
                [
                    'url' =>
                        $url->out(false),
                ]
            )
            ->score($score)
            ->meta(
                'provider',
                'inbox'
            )
            ->meta(
                'entity',
                'inbox_contact'
            )
            ->meta(
                'id',
                (int)$contact->id
            )
            ->to_array();
    }

    private function repository():
        InboxSearchRepository {
        return $this->repository
            ?? new InboxSearchRepository();
    }
}