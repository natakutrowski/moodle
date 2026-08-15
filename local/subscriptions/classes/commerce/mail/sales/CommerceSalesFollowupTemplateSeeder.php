<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail\sales;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\mail\library\CommerceMailLibrary;
use local_subscriptions\commerce\mail\library\CommerceMailLibraryRepository;

final class CommerceSalesFollowupTemplateSeeder {
    public function __construct(
        private readonly CommerceMailLibraryRepository $library
    ) {}

    public function ensure(int $userid): void {
        if ($this->library->all(
                CommerceMailLibrary::CATEGORY_SALES_FOLLOWUP,
                CommerceMailLibrary::STATUS_ACTIVE
            ) !== []) {
            return;
        }

        $this->create(
            'Paiement en attente — proposer de l’aide',
            [
                'fr' => [
                    'subject' => 'Besoin d’aide pour finaliser votre achat ?',
                    'bodyhtml' => '<p>Bonjour {{firstname}},</p>'
                        . '<p>Nous avons vu que votre commande <strong>{{order_reference}}</strong> '
                        . 'pour <strong>{{product_name}}</strong> n’est pas encore finalisée.</p>'
                        . '<p>Si vous avez rencontré un problème au moment du paiement, répondez simplement '
                        . 'à ce message : nous pouvons vous aider.</p>'
                        . '{{resume_payment}}'
                        . '<p>À bientôt ❤️<br>L’équipe CampusFR</p>',
                ],
                'en' => [
                    'subject' => 'Need help completing your purchase?',
                    'bodyhtml' => '<p>Hello {{firstname}},</p>'
                        . '<p>We noticed that order <strong>{{order_reference}}</strong> for '
                        . '<strong>{{product_name}}</strong> has not been completed yet.</p>'
                        . '<p>If something went wrong during payment, simply reply to this email and we will help.</p>'
                        . '{{resume_payment}}'
                        . '<p>See you soon ❤️<br>The CampusFR team</p>',
                ],
                'ru' => [
                    'subject' => 'Помочь завершить покупку?',
                    'bodyhtml' => '<p>Здравствуйте, {{firstname}}!</p>'
                        . '<p>Мы заметили, что заказ <strong>{{order_reference}}</strong> на '
                        . '<strong>{{product_name}}</strong> пока не завершён.</p>'
                        . '<p>Если при оплате возникла проблема, просто ответьте на это письмо — мы поможем.</p>'
                        . '{{resume_payment}}'
                        . '<p>До встречи ❤️<br>Команда CampusFR</p>',
                ],
            ],
            $userid
        );

        $this->create(
            'Paiement échoué — proposer de l’aide',
            [
                'fr' => [
                    'subject' => 'Un problème au moment du paiement ?',
                    'bodyhtml' => '<p>Bonjour {{firstname}},</p>'
                        . '<p>Le paiement de votre commande <strong>{{order_reference}}</strong> '
                        . 'pour <strong>{{product_name}}</strong> n’a pas pu être finalisé.</p>'
                        . '<p>Si vous le souhaitez, nous pouvons regarder cela avec vous et vous aider à terminer votre achat.</p>'
                        . '{{resume_payment}}'
                        . '<p>Vous pouvez aussi répondre directement à cet e-mail.</p>'
                        . '<p>À bientôt ❤️<br>L’équipe CampusFR</p>',
                ],
                'en' => [
                    'subject' => 'A problem during payment?',
                    'bodyhtml' => '<p>Hello {{firstname}},</p>'
                        . '<p>The payment for order <strong>{{order_reference}}</strong> for '
                        . '<strong>{{product_name}}</strong> could not be completed.</p>'
                        . '<p>We can take a look with you and help you complete the purchase.</p>'
                        . '{{resume_payment}}'
                        . '<p>You can also reply directly to this email.</p>'
                        . '<p>See you soon ❤️<br>The CampusFR team</p>',
                ],
                'ru' => [
                    'subject' => 'Возникла проблема с оплатой?',
                    'bodyhtml' => '<p>Здравствуйте, {{firstname}}!</p>'
                        . '<p>Оплата заказа <strong>{{order_reference}}</strong> на '
                        . '<strong>{{product_name}}</strong> не была завершена.</p>'
                        . '<p>Мы можем помочь разобраться и завершить покупку.</p>'
                        . '{{resume_payment}}'
                        . '<p>Можно просто ответить на это письмо.</p>'
                        . '<p>До встречи ❤️<br>Команда CampusFR</p>',
                ],
            ],
            $userid
        );

        $this->create(
            'Achat annulé — demander si nous pouvons aider',
            [
                'fr' => [
                    'subject' => 'Pouvons-nous vous aider avec votre commande ?',
                    'bodyhtml' => '<p>Bonjour {{firstname}},</p>'
                        . '<p>Votre commande <strong>{{order_reference}}</strong> pour '
                        . '<strong>{{product_name}}</strong> a été interrompue.</p>'
                        . '<p>Si vous avez changé d’avis, aucun souci. Si c’est un problème technique ou de paiement, '
                        . 'répondez simplement à ce message et nous vous aiderons.</p>'
                        . '{{resume_payment}}'
                        . '<p>À bientôt ❤️<br>L’équipe CampusFR</p>',
                ],
                'en' => [
                    'subject' => 'Can we help with your order?',
                    'bodyhtml' => '<p>Hello {{firstname}},</p>'
                        . '<p>Your order <strong>{{order_reference}}</strong> for '
                        . '<strong>{{product_name}}</strong> was interrupted.</p>'
                        . '<p>If you changed your mind, no problem. If you ran into a technical or payment issue, '
                        . 'reply to this email and we will help.</p>'
                        . '{{resume_payment}}'
                        . '<p>See you soon ❤️<br>The CampusFR team</p>',
                ],
                'ru' => [
                    'subject' => 'Помочь с вашим заказом?',
                    'bodyhtml' => '<p>Здравствуйте, {{firstname}}!</p>'
                        . '<p>Заказ <strong>{{order_reference}}</strong> на '
                        . '<strong>{{product_name}}</strong> был прерван.</p>'
                        . '<p>Если вы передумали — всё в порядке. Если возникла техническая проблема или проблема с оплатой, '
                        . 'ответьте на письмо, и мы поможем.</p>'
                        . '{{resume_payment}}'
                        . '<p>До встречи ❤️<br>Команда CampusFR</p>',
                ],
            ],
            $userid
        );
    }

    private function create(string $name, array $translations, int $userid): void {
        foreach ($translations as $language => &$translation) {
            $translation['preheader'] = '';
        }
        unset($translation);

        $this->library->save([
            'name' => $name,
            'category' => CommerceMailLibrary::CATEGORY_SALES_FOLLOWUP,
            'status' => CommerceMailLibrary::STATUS_ACTIVE,
            'metadata' => [
                'foundation' => 'N6.1',
                'editor' => 'mail_builder',
                'sales_followup_starter' => true,
            ],
        ], $translations, $userid);
    }
}
