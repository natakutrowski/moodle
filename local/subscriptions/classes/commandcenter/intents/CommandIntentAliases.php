<?php

namespace local_subscriptions\commandcenter\intents;

defined('MOODLE_INTERNAL') || die();

final class CommandIntentAliases {

    public const OPEN = [
        'open', 'ouvrir', 'voir', 'show', 'view',
        'открыть', 'показать', 'посмотреть'
    ];

    public const EMAIL = [
        'email', 'mail', 'message', 'courriel', 'envoyer', 'write', 'send',
        'ecrire', 'écrire',
        'письмо', 'почта', 'написать', 'отправить', 'емейл'
    ];

    public const NOTE = [
        'note', 'notes', 'commentaire', 'commentaires', 'remark', 'memo',
        'заметка', 'заметки', 'комментарий', 'комментарии', 'примечание'
    ];

    public const RESET = [
        'reset', 'password', 'resetpassword', 'réinitialiser', 'reinitialiser',
        'motdepasse', 'mdp',
        'пароль', 'сброс', 'сбросить'
    ];

    public const RESEND = [
        'resend', 'renvoyer', 'receipt', 'reçu', 'recu', 'facture',
        'повтор', 'повторно', 'отправить', 'чек', 'квитанция'
    ];

    public const CHECK = [
        'check', 'verify', 'vérifier', 'verifier', 'paiement', 'payment', 'provider',
        'проверить', 'оплата', 'платеж', 'платёж'
    ];

    public const USER = [
        'user', 'utilisateur', 'client', 'student', 'eleve', 'élève',
        'etudiant', 'étudiant',
        'пользователь', 'клиент', 'ученик', 'студент'
    ];

    public const PURCHASE = [
        'purchase', 'achat', 'sale', 'vente', 'order', 'commande',
        'покупка', 'заказ', 'продажа'
    ];

    public const PRODUCT = [
        'product', 'produit', 'item',
        'товар', 'продукт'
    ];

    public const SUBSCRIPTION = [
        'subscription', 'abonnement', 'access', 'accès', 'acces',
        'подписка', 'доступ'
    ];

    public static function contains(array $tokens, array $aliases): bool {
        $aliases = array_map(static function(string $alias): string {
            return \local_subscriptions\commandcenter\CommandContext::normalize_text($alias);
        }, $aliases);

        foreach ($tokens as $token) {
            if (in_array($token, $aliases, true)) {
                return true;
            }
        }

        return false;
    }

    public static function first_int(array $tokens): int {
        foreach ($tokens as $token) {
            if (ctype_digit($token)) {
                return (int)$token;
            }
        }

        return 0;
    }

    public static function first_matching_alias(array $tokens, array $aliases): string {
        $normalizedaliases = array_map(static function(string $alias): string {
            return \local_subscriptions\commandcenter\CommandContext::normalize_text($alias);
        }, $aliases);

        foreach ($tokens as $token) {
            if (in_array($token, $normalizedaliases, true)) {
                return $token;
            }
        }

        return '';
    }

}