<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\support\CommerceSupportRequest;
use local_subscriptions\commerce\support\CommerceSupportRequestService;
use local_subscriptions\crm\inbox\repositories\InboxAccountRepository;
use local_subscriptions\crm\inbox\repositories\InboxContactRepository;
use local_subscriptions\crm\inbox\repositories\InboxMessageRepository;
use local_subscriptions\crm\inbox\repositories\InboxParticipantRepository;
use local_subscriptions\crm\inbox\repositories\InboxThreadRepository;

final class commerce_support_request_test extends advanced_testcase {
    public function test_request_creates_linked_inbox_thread_with_public_reference(): void {
        global $DB;
        $this->resetAfterTest(true);

        $user = $this->getDataGenerator()->create_user([
            'firstname' => 'Nata',
            'lastname' => 'CampusFR',
            'email' => 'nata@example.test',
        ]);
        $now = time();
        $DB->insert_record('local_subscriptions_inbox_account', (object)[
            'name' => 'CampusFR Support',
            'email' => 'support@campusfr.fr',
            'provider' => 'imap',
            'enabled' => 1,
            'credentialkey' => null,
            'configurationjson' => '{}',
            'syncstatejson' => null,
            'lastsyncedat' => null,
            'lasterrorat' => null,
            'lasterror' => null,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
        set_config('support_email', 'support@campusfr.fr', 'local_subscriptions');

        $service = new CommerceSupportRequestService(
            new InboxAccountRepository(),
            new InboxContactRepository(),
            new InboxThreadRepository(),
            new InboxMessageRepository(),
            new InboxParticipantRepository()
        );
        $threadid = $service->submit(new CommerceSupportRequest(
            'cmp_private_reference',
            'CFR-2026-ABC123',
            (int)$user->id,
            'Nata CampusFR',
            'nata@example.test',
            CommerceSupportRequest::CATEGORY_COURSE_ACCESS,
            'Accès impossible',
            'Je ne peux pas ouvrir mon cours.',
            'paid',
            'fulfilled',
            ['Cours A2 Full']
        ));

        $thread = $DB->get_record('local_subscriptions_inbox_thread', ['id' => $threadid], '*', MUST_EXIST);
        $message = $DB->get_record('local_subscriptions_inbox_message', ['threadid' => $threadid], '*', MUST_EXIST);
        $contact = $DB->get_record('local_subscriptions_inbox_contact', ['normalizedemail' => 'nata@example.test'], '*', MUST_EXIST);

        $this->assertStringContainsString('CFR-2026-ABC123', (string)$thread->subject);
        $this->assertStringContainsString('Cours A2 Full', (string)$message->bodytext);
        $this->assertStringNotContainsString('cmp_private_reference', (string)$message->bodytext);
        $this->assertSame((int)$user->id, (int)$contact->matcheduserid);
        $this->assertSame(1, (int)$thread->unreadcount);
        $this->assertSame(1, (int)$thread->messagecount);
        $this->assertSame(64, strlen((string)$message->provideruid));
        $this->assertCount(2, $DB->get_records('local_subscriptions_inbox_participant', ['messageid' => $message->id]));
    }

    public function test_request_rejects_unknown_category(): void {
        $this->resetAfterTest(true);
        $this->expectException(\invalid_parameter_exception::class);
        new CommerceSupportRequest(
            'cmp_reference',
            'CFR-2026-ABC123',
            null,
            'Guest',
            'guest@example.test',
            'unsupported',
            'Question',
            'Message',
            'paid',
            'fulfilled'
        );
    }
}
