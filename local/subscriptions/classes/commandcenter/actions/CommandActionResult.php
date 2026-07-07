<?php

namespace local_subscriptions\commandcenter\actions;

defined('MOODLE_INTERNAL') || die();

final class CommandActionResult implements \JsonSerializable {

    private bool $success;
    private string $message;
    private ?string $redirecturl;
    private array $data;
    private bool $refresh;

    private function __construct(
        bool $success,
        string $message = '',
        ?string $redirecturl = null,
        array $data = [],
        bool $refresh = false
    ) {
        $this->success = $success;
        $this->message = $message;
        $this->redirecturl = $redirecturl;
        $this->data = $data;
        $this->refresh = $refresh;
    }

    public static function success(
        string $message = '',
        ?string $redirecturl = null,
        array $data = [],
        bool $refresh = false
    ): self {
        return new self(true, $message, $redirecturl, $data, $refresh);
    }

    public static function error(string $message): self {
        return new self(false, $message);
    }

    public function jsonSerialize(): array {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'redirectUrl' => $this->redirecturl,
            'data' => $this->data,
            'refresh' => $this->refresh,
        ];
    }
}