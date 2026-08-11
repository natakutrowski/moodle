<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\support;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\dto\InboxMessageData;
use local_subscriptions\crm\inbox\dto\InboxParticipantData;
use local_subscriptions\crm\inbox\repositories\InboxAccountRepository;
use local_subscriptions\crm\inbox\repositories\InboxContactRepository;
use local_subscriptions\crm\inbox\repositories\InboxMessageRepository;
use local_subscriptions\crm\inbox\repositories\InboxParticipantRepository;
use local_subscriptions\crm\inbox\repositories\InboxThreadRepository;

final class CommerceSupportRequestService {
    public function __construct(
        private readonly InboxAccountRepository $accounts,
        private readonly InboxContactRepository $contacts,
        private readonly InboxThreadRepository $threads,
        private readonly InboxMessageRepository $messages,
        private readonly InboxParticipantRepository $participants
    ) {
    }

    public static function create(): self {
        return new self(
            new InboxAccountRepository(),
            new InboxContactRepository(),
            new InboxThreadRepository(),
            new InboxMessageRepository(),
            new InboxParticipantRepository()
        );
    }

    public function submit(CommerceSupportRequest $request): int {
        global $DB;

        $account = $this->resolve_account();
        if ($account === null) {
            throw new \moodle_exception('commerce_support_unavailable', 'local_subscriptions');
        }

        $transaction = $DB->start_delegated_transaction();
        $contact = $this->contacts->get_or_create($request->customeremail, $request->customername);
        if ($request->userid !== null && $request->userid > 0) {
            $this->contacts->set_manual_match($contact->id, $request->userid);
        }

        $now = time();
        $providerhash = hash('sha256', implode('|', [
            $request->orderreference,
            \core_text::strtolower($request->customeremail),
            (string)$now,
            random_string(12),
        ]));
        $providerthreadid = 'commerce-support-' . $providerhash;
        $provideruid = $providerhash;
        $subjectprefix = trim($request->publicreference) !== ''
            ? '[' . $request->publicreference . '] '
            : '';
        $subject = $subjectprefix . trim($request->subject);

        $thread = $this->threads->create(
            $account->id,
            $contact->id,
            $providerthreadid,
            $subject,
            'INBOX',
            $now,
            false
        );

        $supportreference = self::public_reference((int)$thread->id, $now);
        $bodytext = $this->build_body_text($request, $supportreference);
        $bodyhtml = nl2br(s($bodytext));

        $headers = [
            'x-campusfr-source' => 'commerce-support-form',
            'x-campusfr-support-category' => $request->category,
            'x-campusfr-support-reference' => $supportreference,
        ];
        if (trim($request->orderreference) !== '') {
            $headers['x-campusfr-order-reference'] = $request->orderreference;
        }
        if (trim($request->publicreference) !== '') {
            $headers['x-campusfr-public-reference'] = $request->publicreference;
        }

        $messagedata = new InboxMessageData(
            'INBOX',
            null,
            $provideruid,
            '<' . $providerthreadid . '@campusfr.local>',
            null,
            $providerthreadid,
            'incoming',
            'received',
            $subject,
            $bodytext,
            $bodyhtml,
            $headers,
            null,
            [],
            $now,
            null,
            false,
            [],
            [],
            hash('sha256', $subject . '|' . $bodytext)
        );
        $providerkey = InboxMessageRepository::provider_key($messagedata);
        $message = $this->messages->create($account->id, (int)$thread->id, $providerkey, $messagedata);

        $this->participants->create((int)$message->id, $contact->id, new InboxParticipantData(
            'from',
            $request->customeremail,
            \core_text::strtolower(trim($request->customeremail)),
            $request->customername
        ));
        $this->participants->create((int)$message->id, null, new InboxParticipantData(
            'to',
            $account->email,
            \core_text::strtolower(trim($account->email)),
            $account->name
        ));

        $this->threads->update_after_message(
            (int)$thread->id,
            $contact->id,
            $subject,
            'INBOX',
            $now,
            true,
            true,
            (int)$message->id
        );

        $transaction->allow_commit();
        return (int)$thread->id;
    }

    public static function public_reference(int $threadid, ?int $timestamp = null): string {
        return sprintf('SUP-%s-%05d', date('Y', $timestamp ?? time()), max(0, $threadid));
    }

    private function resolve_account(): ?object {
        $supportemail = trim((string)(get_config('local_subscriptions', 'support_email') ?: ''));
        if ($supportemail !== '') {
            $account = $this->accounts->find_by_email($supportemail);
            if ($account !== null && $account->enabled) {
                return $account;
            }
        }

        $enabled = $this->accounts->get_enabled();
        return $enabled[0] ?? null;
    }

    private function build_body_text(
        CommerceSupportRequest $request,
        string $supportreference
    ): string {
        $products = array_values(array_filter(array_map(
            static fn(mixed $value): string => trim((string)$value),
            $request->products
        )));

        $lines = [
            get_string('commerce_support_mail_technical_heading', 'local_subscriptions'),
            str_repeat('=', 34),
        ];

        $this->append_line($lines, 'commerce_support_reference', $supportreference);
        $this->append_line($lines, 'commerce_support_internal_reference', $request->publicreference);
        $this->append_line($lines, 'commerce_support_customer', $request->customername);
        $this->append_line($lines, 'commerce_support_email', $request->customeremail);
        $this->append_line(
            $lines,
            'commerce_support_category',
            get_string('commerce_support_category_' . $request->category, 'local_subscriptions')
        );
        $this->append_line(
            $lines,
            'commerce_support_payment_status',
            $this->translated_status($request->paymentstatus)
        );
        $this->append_line(
            $lines,
            'commerce_support_fulfillment_status',
            $this->translated_status($request->fulfillmentstatus)
        );
        if ($products !== []) {
            $this->append_line(
                $lines,
                'commerce_support_products',
                implode(', ', $products)
            );
        }

        $lines[] = '';
        $lines[] = get_string('commerce_support_mail_message_heading', 'local_subscriptions');
        $lines[] = str_repeat('=', 34);
        $lines[] = trim($request->message);

        return implode("\n", $lines);
    }

    /** @param string[] $lines */
    private function append_line(array &$lines, string $labelkey, string $value): void {
        $value = trim($value);
        if ($value === '') {
            return;
        }
        $lines[] = get_string($labelkey, 'local_subscriptions') . ': ' . $value;
    }

    private function translated_status(string $status): string {
        $status = strtolower(trim($status));
        if ($status === '') {
            return '';
        }
        $known = [
            'paid', 'completed', 'pending', 'failed', 'cancelled',
            'refunded', 'partial', 'processing', 'succeeded',
        ];
        if (in_array($status, $known, true)) {
            return get_string('commerce_support_status_' . $status, 'local_subscriptions');
        }
        return format_string(ucfirst(str_replace('_', ' ', $status)));
    }
}
