<?php

namespace local_subscriptions\commandcenter;

defined('MOODLE_INTERNAL') || die();

final class CommandQuery {

    private string $raw;
    private string $text;
    private ?string $entity;
    private ?int $id;
    private bool $actionmode;

    private function __construct(string $raw, string $text, ?string $entity, ?int $id, bool $actionmode) {
        $this->raw = $raw;
        $this->text = $text;
        $this->entity = $entity;
        $this->id = $id;
        $this->actionmode = $actionmode;
    }

    public static function parse(string $query): self {
        $raw = trim($query);
        $text = $raw;
        $entity = null;
        $id = null;
        $actionmode = false;

        if (strpos($raw, '>') === 0) {
            $actionmode = true;
            $text = trim(substr($raw, 1));
        }

        if (!$actionmode && preg_match('/^#?(user|u|utilisateur|product|prod|produit|purchase|buy|achat|subscription|sub|abonnement|пользователь|юзер|продукт|товар|покупка|заказ|подписка|доступ)\s*:?\s*(\d+)$/iu', $raw, $matches)) {
            $entity = self::normalize_entity($matches[1]);
            $id = (int)$matches[2];
            $text = (string)$id;
        }

        return new self($raw, $text, $entity, $id, $actionmode);
    }

    public function raw(): string {
        return $this->raw;
    }

    public function text(): string {
        return $this->text;
    }

    public function entity(): ?string {
        return $this->entity;
    }

    public function id(): ?int {
        return $this->id;
    }

    public function is_action_mode(): bool {
        return $this->actionmode;
    }

    public function is_direct_entity(string $entity): bool {
        return $this->entity === $entity && $this->id !== null;
    }

    public function has_direct_entity(): bool {
        return $this->entity !== null && $this->id !== null;
    }

    private static function normalize_entity(string $entity): string {
        $entity = \core_text::strtolower(trim($entity));

        if (in_array($entity, ['u', 'user', 'utilisateur', 'пользователь', 'юзер'], true)) {
            return 'user';
        }

        if (in_array($entity, ['prod', 'product', 'produit', 'продукт', 'товар'], true)) {
            return 'product';
        }

        if (in_array($entity, ['buy', 'purchase', 'achat', 'покупка', 'заказ'], true)) {
            return 'purchase';
        }

        if (in_array($entity, ['sub', 'subscription', 'abonnement', 'подписка', 'доступ'], true)) {
            return 'subscription';
        }

        return $entity;
    }
}