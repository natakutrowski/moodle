<?php

namespace local_subscriptions\commandcenter;

defined('MOODLE_INTERNAL') || die();

final class CommandMenuItem implements \JsonSerializable {

    private string $icon = '';
    private string $label = '';
    private string $actionKey = '';
    private array $payload = [];
    private bool $requiresConfirmation = false;
    private string $confirmMessage = '';
    private string $shortcut = '';
    private bool $danger = false;

    public static function create(): self {
        return new self();
    }

    public function icon(string $icon): self {
        $this->icon = $icon;
        return $this;
    }

    public function label(string $label): self {
        $this->label = $label;
        return $this;
    }

    public function action(string $actionkey, array $payload = []): self {
        $this->actionKey = $actionkey;
        $this->payload = $payload;
        return $this;
    }

    public function confirmation(string $message): self {
        $this->requiresConfirmation = true;
        $this->confirmMessage = $message;
        return $this;
    }

    public function shortcut(string $shortcut): self {
        $this->shortcut = $shortcut;
        return $this;
    }

    public function danger(bool $danger = true): self {
        $this->danger = $danger;
        return $this;
    }
    public function jsonSerialize(): array {
        $result = [
            'icon' => $this->icon,
            'label' => $this->label,
            'actionKey' => $this->actionKey,
            'payload' => $this->payload,
            'shortcut' => $this->shortcut,
        ];

        if ($this->requiresConfirmation) {
            $result['requiresConfirmation'] = true;
            $result['confirmMessage'] = $this->confirmMessage;
        }

        if ($this->danger) {
            $result['danger'] = true;
        }

        return $result;
    }
}