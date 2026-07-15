<?php

namespace local_subscriptions\crm\inbox\contracts;

defined('MOODLE_INTERNAL') || die();

interface InboxCredentialStoreInterface {

    public function has(string $credentialkey): bool;

    public function get_username(
        string $credentialkey
    ): string;

    public function get_password(
        string $credentialkey
    ): string;
}