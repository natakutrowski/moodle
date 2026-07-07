<?php

namespace local_subscriptions\commandcenter\intents;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commandcenter\CommandContext;

final class CommandEntityAliases {

    public const USER = 'user';
    public const PURCHASE = 'purchase';
    public const PRODUCT = 'product';
    public const SUBSCRIPTION = 'subscription';

    public static function aliases(): array {
        return [
            self::USER => [
                'user', 'users',
                'utilisateur', 'utilisateurs',
                'client', 'clients',
                'student', 'students',
                'eleve', 'eleves',
                'etudiant', 'etudiants',
                'пользователь', 'пользователи',
                'клиент', 'клиенты',
                'ученик', 'ученики',
                'студент', 'студенты',
            ],
            self::PURCHASE => [
                'purchase', 'purchases',
                'achat', 'achats',
                'vente', 'ventes',
                'sale', 'sales',
                'order', 'orders',
                'commande', 'commandes',
                'покупка', 'покупки',
                'заказ', 'заказы',
                'продажа', 'продажи',
            ],
            self::PRODUCT => [
                'product', 'products',
                'produit', 'produits',
                'item', 'items',
                'товар', 'товары',
                'продукт', 'продукты',
            ],
            self::SUBSCRIPTION => [
                'subscription', 'subscriptions',
                'abonnement', 'abonnements',
                'access', 'acces',
                'подписка', 'подписки',
                'доступ',
            ],
        ];
    }

    public static function resolve(string $token): ?string {
        $token = CommandContext::normalize_text($token);

        foreach (self::aliases() as $entity => $aliases) {
            foreach ($aliases as $alias) {
                if ($token === CommandContext::normalize_text($alias)) {
                    return $entity;
                }
            }
        }

        return null;
    }

    public static function contains(array $tokens, string $entity): bool {
        foreach ($tokens as $token) {
            if (self::resolve($token) === $entity) {
                return true;
            }
        }

        return false;
    }

    public static function first_entity(array $tokens): ?string {
        foreach ($tokens as $token) {
            $entity = self::resolve($token);

            if ($entity !== null) {
                return $entity;
            }
        }

        return null;
    }
}