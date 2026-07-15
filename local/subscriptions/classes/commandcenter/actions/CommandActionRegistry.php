<?php

namespace local_subscriptions\commandcenter\actions;

defined('MOODLE_INTERNAL') || die();

final class CommandActionRegistry {

    /**
     * @return CommandActionInterface[]
     */
    public static function all(): array {
        return [
            new OpenUrlAction(),
            new OpenUserAction(),
            new OpenProductAction(),
            new OpenPurchaseAction(),
            new OpenSubscriptionAction(),
            new InboxSyncAction(),

            new UserEmailAction(),
            new UserResetPasswordAction(),
            new UserAddNoteAction(),

            new PurchaseResendEmailAction(),
            new PurchaseRegenerateTokenAction(),
            new PurchaseExtendTokenAction(),
            new PurchaseCheckProviderAction(),
        ];
    }

    /**
     * @return array<string, CommandActionInterface>
     */
    public static function map(): array {
        $map = [];

        foreach (self::all() as $action) {
            $map[$action->key()] = $action;
        }

        return $map;
    }

    public static function get(string $key): ?CommandActionInterface {
        $map = self::map();

        return $map[$key] ?? null;
    }
}