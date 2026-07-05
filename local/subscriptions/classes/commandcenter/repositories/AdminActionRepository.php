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
                'keywords' => 'dashboard accueil home crm command center главная дашборд панель управление crm',
            ],
            [
                'icon' => CommandIcons::USERS,
                'title' => get_string('command_action_users_title', 'local_subscriptions'),
                'subtitle' => get_string('command_action_users_subtitle', 'local_subscriptions'),
                'url' => (new moodle_url(subscription_config::admin_users_page()))->out(false),
                'keywords' => 'users utilisateurs élèves clients crm пользователи ученики клиенты студенты',
            ],
            [
                'icon' => CommandIcons::DIGITAL_PRODUCT,
                'title' => get_string('command_action_products_title', 'local_subscriptions'),
                'subtitle' => get_string('command_action_products_subtitle', 'local_subscriptions'),
                'url' => (new moodle_url(subscription_config::digital_products_admin_page()))->out(false),
                'keywords' => 'products produits digitaux digital product продукты цифровые товары курсы материалы',
            ],
            [
                'icon' => CommandIcons::CREATE,
                'title' => get_string('command_action_product_create_title', 'local_subscriptions'),
                'subtitle' => get_string('command_action_product_create_subtitle', 'local_subscriptions'),
                'url' => (new moodle_url(subscription_config::digital_product_edit_admin_page()))->out(false),
                'keywords' => 'create product créer produit nouveau digital создать продукт новый товар курс материал',
            ],
            [
                'icon' => CommandIcons::DIGITAL_PURCHASE,
                'title' => get_string('command_action_purchases_title', 'local_subscriptions'),
                'subtitle' => get_string('command_action_purchases_subtitle', 'local_subscriptions'),
                'url' => (new moodle_url(subscription_config::digital_purchases_admin_page()))->out(false),
                'keywords' => 'purchases achats commandes ventes paiements payments digital покупки заказы продажи платежи оплаты цифровые',
            ],
            [
                'icon' => CommandIcons::SUBSCRIPTION,
                'title' => get_string('command_action_subscriptions_title', 'local_subscriptions'),
                'subtitle' => get_string('command_action_subscriptions_subtitle', 'local_subscriptions'),
                'url' => (new moodle_url(subscription_config::user_subscriptions_page()))->out(false),
                'keywords' => 'subscriptions abonnements plans accès access подписки абонементы доступы доступ планы',
            ],
        ];
    }
}