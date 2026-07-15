<?php

namespace local_subscriptions\crm\inbox\services;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\domain\InboxContact;
use local_subscriptions\crm\inbox\repositories\InboxContactRepository;

final class InboxContactService {

    public function __construct(
        private readonly InboxContactRepository $repository
    ) {
    }

    public function resolve_external_contact(
        string $email,
        ?string $displayname = null
    ): InboxContact {
        return $this->repository->get_or_create(
            $email,
            $displayname
        );
    }

    public function attach_manually(
        int $contactid,
        int $userid
    ): void {
        $this->repository->set_manual_match(
            $contactid,
            $userid
        );
    }

    public function detach(
        int $contactid
    ): void {
        $this->repository->detach(
            $contactid,
            true
        );
    }

    public function enable_automatic_matching(
        int $contactid
    ): void {
        $this->repository->unlock_matching(
            $contactid
        );
    }
}