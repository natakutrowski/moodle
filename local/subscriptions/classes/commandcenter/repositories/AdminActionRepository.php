<?php

namespace local_subscriptions\commandcenter\repositories;

defined('MOODLE_INTERNAL') || die();

use moodle_url;
use local_subscriptions\subscription_config;
use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commandcenter\CommandIcons;
use local_subscriptions\commandcenter\actions\CommandActionKeys;

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
                Capabilities::VIEW_INBOX
            )
        ) {
            $actions[] = $this->action(
                CommandIcons::INBOX,
                'command_action_inbox_title',
                'command_action_inbox_subtitle',
                subscription_config::
                    admin_inbox_page(),
                'inbox email emails support messages conversations tickets ' .
                'boite réception boîte réception assistance campusfr ' .
                'ouvrir inbox ouvrir boite réception ' .
                'почта входящие письма поддержка сообщения тикеты'
            );

            $actions[] = $this->action(
                CommandIcons::UNASSIGNED,
                'command_action_inbox_unassigned_title',
                'command_action_inbox_unassigned_subtitle',
                subscription_config::
                    admin_inbox_page(),
                'inbox unassigned non assigné non assignées sans responsable ' .
                'conversation ticket support équipe ' .
                'входящие без ответственного неназначенные диалоги',
                [
                    'assignment' => 'unassigned',
                ]
            );

            $actions[] = $this->action(
                CommandIcons::URGENT,
                'command_action_inbox_urgent_title',
                'command_action_inbox_urgent_subtitle',
                subscription_config::
                    admin_inbox_page(),
                'inbox urgent urgentes priorité haute critique support ' .
                'conversation ticket ' .
                'срочные важные приоритетные диалоги',
                [
                    'priority' => 'urgent',
                ]
            );

            $actions[] = $this->action(
                CommandIcons::DIAGNOSTICS,
                'command_action_inbox_diagnostics_title',
                'command_action_inbox_diagnostics_subtitle',
                subscription_config::
                    admin_inbox_diagnostics_page(),
                'inbox diagnostics diagnostic imap smtp connexion santé erreurs ' .
                'диагностика входящие imap smtp ошибки соединение'
            );

            if (
                AdminSecurity::can(
                    Capabilities::USE_INBOX_AI
                )
            ) {
                $actions[] = $this->action(
                    CommandIcons::AI,
                    'command_action_inbox_ai_diagnostics_title',
                    'command_action_inbox_ai_diagnostics_subtitle',
                    subscription_config::
                        admin_inbox_ai_diagnostics_page(),
                    'inbox ai ia intelligence artificielle openai diagnostics ' .
                    'diagnostic provider prompts cache modèles ' .
                    'ии openai диагностика провайдер кэш промпты'
                );
            }
        }

        if (
            AdminSecurity::can(
                Capabilities::MANAGE_INBOX
            )
        ) {
            $actions[] = $this->command_action(
                CommandIcons::INBOX_SYNC,
                'command_action_inbox_sync_title',
                'command_action_inbox_sync_subtitle',
                subscription_config::
                    admin_inbox_page(),
                'inbox sync synchroniser synchronisation recevoir emails imap ' .
                'mettre à jour actualiser boîte réception ' .
                'синхронизация входящие получить письма обновить imap',
                CommandActionKeys::INBOX_SYNC,
                [],
                'command_confirm_inbox_sync',
                'command_center_action_run'
            );
        }

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
        string $keywords,
        array $params = []
    ): array {
        $url = new moodle_url(
            $path,
            $params
        );

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

            'actionkey' =>
                CommandActionKeys::OPEN_URL,

            'payload' => [
                'url' => $urlstring,
            ],

            'keywords' => $keywords,
        ];
    }

    private function command_action(
        string $icon,
        string $titlekey,
        string $subtitlekey,
        string $path,
        string $keywords,
        string $actionkey,
        array $payload = [],
        ?string $confirmationkey = null,
        ?string $actionlabelkey = null
    ): array {
        $url = new moodle_url($path);
        $urlstring = $url->out(false);

        $action = [
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
            'actionkey' => $actionkey,
            'payload' => $payload,
            'keywords' => $keywords,
        ];

        if ($confirmationkey !== null) {
            $action['confirmation'] =
                get_string(
                    $confirmationkey,
                    'local_subscriptions'
                );
        }

        if ($actionlabelkey !== null) {
            $action['actionlabel'] =
                get_string(
                    $actionlabelkey,
                    'local_subscriptions'
                );
        }

        return $action;
    }

}