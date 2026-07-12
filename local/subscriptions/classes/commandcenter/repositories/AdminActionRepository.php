<?php

namespace local_subscriptions\commandcenter\repositories;

defined('MOODLE_INTERNAL') || die();

use moodle_url;
use local_subscriptions\subscription_config;
use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commandcenter\CommandIcons;

final class AdminActionRepository {

    public function all(): array {
        $actions = [
            $this->action(
                CommandIcons::DASHBOARD,
                'command_action_dashboard_title',
                'command_action_dashboard_subtitle',
                subscription_config::admin_dashboard_page(),
                'dashboard accueil home crm command center ' .
                'главная дашборд панель управление crm'
            ),

            $this->action(
                CommandIcons::USERS,
                'command_action_users_title',
                'command_action_users_subtitle',
                subscription_config::admin_users_page(),
                'users utilisateurs élèves étudiants contacts clients crm ' .
                'utilisateur user utilisateurs crm ' .
                'пользователь пользователи ученики студенты клиенты контакты'
            ),

            $this->action(
                CommandIcons::DIGITAL_PRODUCTS,
                'command_action_digital_products_title',
                'command_action_digital_products_subtitle',
                subscription_config::digital_products_admin_page(),
                'digital products produits digitaux boutique fichiers ' .
                'produit numérique product produits ' .
                'товар товары продукт продукты цифровые файлы магазин'
            ),

            $this->action(
                CommandIcons::CREATE,
                'command_action_product_create_title',
                'command_action_product_create_subtitle',
                subscription_config::digital_product_edit_admin_page(),
                'create product créer produit nouveau digital ajouter ' .
                'создать продукт новый товар курс материал'
            ),

            $this->action(
                CommandIcons::DIGITAL_PURCHASES,
                'command_action_digital_purchases_title',
                'command_action_digital_purchases_subtitle',
                subscription_config::digital_purchases_admin_page(),
                'digital purchases achats ventes commandes paiements payments ' .
                'sales receipt reçu achats digitaux ' .
                'покупка покупки заказ заказы оплата платеж продажи чек квитанция'
            ),

            $this->action(
                CommandIcons::SUBSCRIPTIONS,
                'command_action_subscriptions_title',
                'command_action_subscriptions_subtitle',
                subscription_config::user_subscriptions_page(),
                'subscriptions abonnements accès access plans cours étudiants ' .
                'subscription abonnement ' .
                'доступ подписка подписки курс курсы ученики студенты'
            ),

            $this->action(
                CommandIcons::EMAIL,
                'command_action_user_email_title',
                'command_action_user_email_subtitle',
                subscription_config::admin_users_page(),
                'email mail envoyer message contact user utilisateur courrier ' .
                'письмо почта отправить сообщение контакт пользователь'
            ),

            $this->action(
                CommandIcons::NOTE,
                'command_action_user_note_title',
                'command_action_user_note_subtitle',
                subscription_config::admin_users_page(),
                'note notes commentaire crm interne annotation remark user ' .
                'заметка комментарий примечание запись crm пользователь'
            ),

            $this->action(
                CommandIcons::RESEND_EMAIL,
                'command_action_purchase_resend_email_title',
                'command_action_purchase_resend_email_subtitle',
                subscription_config::digital_purchases_admin_page(),
                'receipt reçu email achat purchase resend renvoyer facture ' .
                'commande téléchargement ' .
                'письмо повторно отправить покупка заказ чек квитанция'
            ),

            $this->action(
                CommandIcons::AUTOMATION,
                'command_action_automations_title',
                'command_action_automations_subtitle',
                subscription_config::automation_rules_admin_page(),
                'automation automations automatisation workflow workflows crm ' .
                'rules règles moteur ' .
                'автоматизация автоматизации правила workflow'
            ),

            $this->action(
                CommandIcons::AUTOMATION_HISTORY,
                'command_action_automation_history_title',
                'command_action_automation_history_subtitle',
                subscription_config::automation_history_admin_page(),
                'automation history historique automatisations logs crm audit ' .
                'журнал история автоматизации'
            ),
        ];

        if (
            AdminSecurity::can(
                Capabilities::VIEW_DASHBOARD
            )
        ) {
            $actions[] = $this->action(
                CommandIcons::HELP,
                'command_help_center_title',
                'command_help_center_subtitle',
                subscription_config::admin_help_page(),
                'help documentation docs guide guides onboarding aide ' .
                'centre aide centre d aide documentation crm ' .
                'справка документация руководство помощь'
            );
        }

        return $actions;
    }

    private function action(
        string $icon,
        string $titlekey,
        string $subtitlekey,
        string $path,
        string $keywords
    ): array {
        $url = new moodle_url($path);
        $urlstring = $url->out(false);

        return [
            'icon' => $icon,
            'title' => get_string(
                $titlekey,
                'local_subscriptions'
            ),
            'subtitle' => get_string(
                $subtitlekey,
                'local_subscriptions'
            ),
            'url' => $urlstring,
            'actionkey' => 'open_url',
            'payload' => [
                'url' => $urlstring,
            ],
            'keywords' => $keywords,
        ];
    }
}