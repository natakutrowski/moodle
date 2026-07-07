<?php

namespace local_subscriptions\commandcenter\repositories;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commandcenter\CommandIcons;
use local_subscriptions\subscription_config;
use moodle_url;

final class AdminActionRepository {

    public function all(): array {
        return [
            [
                'icon' => CommandIcons::DASHBOARD,
                'title' => get_string('command_action_dashboard_title', 'local_subscriptions'),
                'subtitle' => get_string('command_action_dashboard_subtitle', 'local_subscriptions'),
                'url' => (new moodle_url(subscription_config::admin_dashboard_page()))->out(false),
                'actionkey' => 'open_url',
                'payload' => [
                    'url' => (new moodle_url(subscription_config::admin_dashboard_page()))->out(false),
                ],
                'keywords' => 'dashboard accueil home crm command center главная дашборд панель управление crm',
            ],
            [
                'icon' => CommandIcons::USERS,
                'title' => get_string('command_action_users_title', 'local_subscriptions'),
                'subtitle' => get_string('command_action_users_subtitle', 'local_subscriptions'),
                'url' => (new moodle_url(subscription_config::admin_users_page()))->out(false),
                'actionkey' => 'open_url',
                'payload' => [
                    'url' => (new moodle_url(subscription_config::admin_users_page()))->out(false),
                ],
                'keywords' => 'users utilisateurs élèves clients crm пользователи ученики клиенты студенты',
            ],
            [
                'icon' => CommandIcons::DIGITAL_PRODUCT,
                'title' => get_string('command_action_products_title', 'local_subscriptions'),
                'subtitle' => get_string('command_action_products_subtitle', 'local_subscriptions'),
                'url' => (new moodle_url(subscription_config::digital_products_admin_page()))->out(false),
                'actionkey' => 'open_url',
                'payload' => [
                    'url' => (new moodle_url(subscription_config::digital_products_admin_page()))->out(false),
                ],
                'keywords' => 'products produits digitaux digital product продукты цифровые товары курсы материалы',
            ],
            [
                'icon' => CommandIcons::CREATE,
                'title' => get_string('command_action_product_create_title', 'local_subscriptions'),
                'subtitle' => get_string('command_action_product_create_subtitle', 'local_subscriptions'),
                'url' => (new moodle_url(subscription_config::digital_product_edit_admin_page()))->out(false),
                'actionkey' => 'open_url',
                'payload' => [
                    'url' => (new moodle_url(subscription_config::digital_product_edit_admin_page()))->out(false),
                ],
                'keywords' => 'create product créer produit nouveau digital создать продукт новый товар курс материал',
            ],
            [
                'icon' => CommandIcons::DIGITAL_PURCHASE,
                'title' => get_string('command_action_purchases_title', 'local_subscriptions'),
                'subtitle' => get_string('command_action_purchases_subtitle', 'local_subscriptions'),
                'url' => (new moodle_url(subscription_config::digital_purchases_admin_page()))->out(false),
                'actionkey' => 'open_url',
                'payload' => [
                    'url' => (new moodle_url(subscription_config::digital_purchases_admin_page()))->out(false),
                ],
                'keywords' => 'purchases achats commandes ventes paiements payments digital покупки заказы продажи платежи оплаты цифровые',
            ],
            [
                'icon' => CommandIcons::SUBSCRIPTION,
                'title' => get_string('command_action_subscriptions_title', 'local_subscriptions'),
                'subtitle' => get_string('command_action_subscriptions_subtitle', 'local_subscriptions'),
                'url' => (new moodle_url(subscription_config::user_subscriptions_page()))->out(false),
                'actionkey' => 'open_url',
                'payload' => [
                    'url' => (new moodle_url(subscription_config::user_subscriptions_page()))->out(false),
                ],
                'keywords' => 'subscriptions abonnements plans accès access подписки абонементы доступы доступ планы',
            ],
            [
                'icon' => CommandIcons::EMAIL,
                'title' => get_string('command_action_user_email_title', 'local_subscriptions'),
                'subtitle' => get_string('command_action_user_email_subtitle', 'local_subscriptions'),
                'url' => (new moodle_url(subscription_config::admin_users_page()))->out(false),
                'actionkey' => 'open_url',
                'payload' => [
                    'url' => (new moodle_url(subscription_config::admin_users_page()))->out(false),
                ],
                'keywords' => 'email mail envoyer message contact user utilisateur courrier envoyer_email email utilisateur письмо почта отправить сообщение контакт пользователь емейл',
            ],
            [
                'icon' => CommandIcons::NOTE,
                'title' => get_string('command_action_user_note_title', 'local_subscriptions'),
                'subtitle' => get_string('command_action_user_note_subtitle', 'local_subscriptions'),
                'url' => (new moodle_url(subscription_config::admin_users_page()))->out(false),
                'actionkey' => 'open_url',
                'payload' => [
                    'url' => (new moodle_url(subscription_config::admin_users_page()))->out(false),
                ],
                'keywords' => 'note notes commentaire commentaire crm interne annotation remark user utilisateur заметка комментарий комментарии примечание запись crm пользователь',
            ],
            [
                'icon' => CommandIcons::RESEND_EMAIL,
                'title' => get_string('command_action_purchase_resend_email_title', 'local_subscriptions'),
                'subtitle' => get_string('command_action_purchase_resend_email_subtitle', 'local_subscriptions'),
                'url' => (new moodle_url(subscription_config::digital_purchases_admin_page()))->out(false),
                'actionkey' => 'open_url',
                'payload' => [
                    'url' => (new moodle_url(subscription_config::digital_purchases_admin_page()))->out(false),
                ],
                'keywords' => 'receipt reçu email achat purchase resend renvoyer facture commande reçu télécharger письмо повторно отправить покупка заказ чек квитанция повтор',
            ],
            [
                'icon' => CommandIcons::USERS,
                'title' => get_string('command_action_users_title', 'local_subscriptions'),
                'subtitle' => get_string('command_action_users_subtitle', 'local_subscriptions'),
                'url' => (new moodle_url(subscription_config::admin_users_page()))->out(false),
                'actionkey' => 'open_url',
                'payload' => [
                    'url' => (new moodle_url(subscription_config::admin_users_page()))->out(false),
                ],
                'keywords' => 'users utilisateurs élèves étudiants contacts clients crm пользователь пользователи ученики студенты клиенты контакты',
            ],
            [
                'icon' => CommandIcons::DIGITAL_PURCHASES,
                'title' => get_string('command_action_digital_purchases_title', 'local_subscriptions'),
                'subtitle' => get_string('command_action_digital_purchases_subtitle', 'local_subscriptions'),
                'url' => (new moodle_url(subscription_config::digital_purchases_admin_page()))->out(false),
                'actionkey' => 'open_url',
                'payload' => [
                    'url' => (new moodle_url(subscription_config::digital_purchases_admin_page()))->out(false),
                ],
                'keywords' => 'digital purchases achats ventes commandes boutique paiement payment sales receipt reçu покупка покупки заказ заказы оплата платеж продажи чек квитанция',
            ],
            [
                'icon' => CommandIcons::DIGITAL_PRODUCTS,
                'title' => get_string('command_action_digital_products_title', 'local_subscriptions'),
                'subtitle' => get_string('command_action_digital_products_subtitle', 'local_subscriptions'),
                'url' => (new moodle_url(subscription_config::digital_products_admin_page()))->out(false),
                'actionkey' => 'open_url',
                'payload' => [
                    'url' => (new moodle_url(subscription_config::digital_products_admin_page()))->out(false),
                ],
                'keywords' => 'digital products produits boutique fichiers fichiers digitaux produit produit numérique товар товары продукт продукты цифровой цифровые файлы магазин',
            ],
            [
                'icon' => CommandIcons::SUBSCRIPTIONS,
                'title' => get_string('command_action_subscriptions_title', 'local_subscriptions'),
                'subtitle' => get_string('command_action_subscriptions_subtitle', 'local_subscriptions'),
                'url' => (new moodle_url(subscription_config::user_subscriptions_page()))->out(false),
                'actionkey' => 'open_url',
                'payload' => [
                    'url' => (new moodle_url(subscription_config::user_subscriptions_page()))->out(false),
                ],
                'keywords' => 'subscriptions abonnements accès access élèves étudiants plans cours subscription abonnement доступ подписка подписки курс курсы ученики студенты',
            ],
        ];
    }
}