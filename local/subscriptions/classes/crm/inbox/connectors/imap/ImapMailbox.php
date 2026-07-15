<?php

namespace local_subscriptions\crm\inbox\connectors\imap;

defined('MOODLE_INTERNAL') || die();

final class ImapMailbox {

    public function __construct(
        private readonly string $host,
        private readonly int $port,
        private readonly string $encryption,
        private readonly bool $validatecertificate
    ) {
    }

    public function server(): string {
        $flags = '/imap';

        if ($this->encryption === 'ssl') {
            $flags .= '/ssl';
        } else if ($this->encryption === 'tls') {
            $flags .= '/tls';
        } else {
            $flags .= '/notls';
        }

        if (!$this->validatecertificate) {
            $flags .= '/novalidate-cert';
        }

        return sprintf(
            '{%s:%d%s}',
            $this->host,
            $this->port,
            $flags
        );
    }

    public function folder(string $folder): string {
        return $this->server() . $folder;
    }
}