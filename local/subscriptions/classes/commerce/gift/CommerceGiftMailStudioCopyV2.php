<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\gift;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\mail\builder\CommerceMailBuilder;
use local_subscriptions\commerce\mail\builder\CommerceMailBuilderCtaRenderer;

/**
 * Editorial v2 for the Advent gift invitation.
 * Reuses the official CampusFR Mail Studio CTA renderer and the existing
 * ephemeral gift token delivery path; it does not send or create invitations.
 */
final class CommerceGiftMailStudioCopyV2 {
    /** @return array<string,array<string,mixed>> */
    public static function translations(): array {
        $translations = CommerceGiftMailStudioInvitation::defaults();
        $cta = new CommerceMailBuilderCtaRenderer();
        $copy = [
            'fr' => [
                'subject' => 'Un cadeau CampusFR rien que pour toi 🎁',
                'preheader' => '24 histoires de l’Avent t’attendent. Découvre comment activer ton cadeau.',
                'heading' => 'Une belle surprise t’attend ! 🎁',
                'intro' => '<p>Bonjour {{gift_recipient_name}},</p>'
                    . '<p>Quelqu’un a pensé à toi : tu as reçu le <strong>Calendrier de l’Avent 2026 de CampusFR</strong> ! '
                    . '24 histoires à découvrir, une chaque jour du 1er au 24 décembre.</p>'
                    . '<p style="margin:16px 0;padding:12px 16px;border-left:3px solid #E0397C;'
                    . 'background-color:#fff5f9;color:#352650;">{{gift_personal_message}}</p>',
                'cta' => 'Découvrir mon cadeau',
                'steps' => '<p><strong>Comment récupérer ton cadeau ?</strong></p>'
                    . '<ol style="padding-left:22px;line-height:1.75;">'
                    . '<li><strong>Ouvre la page du cadeau</strong> avec le bouton rose.</li>'
                    . '<li><strong>Tu as déjà un compte CampusFR ?</strong> Connecte-toi. '
                    . '<strong>Tu n’en as pas encore ?</strong> Depuis la page de connexion, '
                    . 'choisis « Créer un compte » et confirme ton adresse e-mail si demandé.</li>'
                    . '<li>Une fois connecté(e), <strong>saisis le code cadeau ci-dessous</strong> '
                    . 'pour activer ton calendrier dans « Ma bibliothèque ».</li>'
                    . '</ol>',
                'code' => 'Ton code cadeau personnel',
                'fallback' => 'Si le bouton ne fonctionne pas, utilise ce lien :',
                'security' => 'Garde ce code pour toi : il permet de réclamer ton cadeau.',
                'signature' => '<p>De belles découvertes t’attendent ! ✨<br><strong>L’équipe CampusFR</strong></p>',
            ],
            'en' => [
                'subject' => 'A special CampusFR gift just for you 🎁',
                'preheader' => '24 Advent stories are waiting. Here’s how to claim your gift.',
                'heading' => 'A lovely surprise awaits! 🎁',
                'intro' => '<p>Hello {{gift_recipient_name}},</p>'
                    . '<p>Someone has a surprise for you: the <strong>CampusFR Advent Calendar 2026</strong>! '
                    . 'Discover 24 stories, one each day from December 1 to 24.</p>'
                    . '<p style="margin:16px 0;padding:12px 16px;border-left:3px solid #E0397C;'
                    . 'background-color:#fff5f9;color:#352650;">{{gift_personal_message}}</p>',
                'cta' => 'Discover my gift',
                'steps' => '<p><strong>How do you claim your gift?</strong></p>'
                    . '<ol style="padding-left:22px;line-height:1.75;">'
                    . '<li><strong>Open your gift page</strong> using the pink button.</li>'
                    . '<li><strong>Already have a CampusFR account?</strong> Sign in. '
                    . '<strong>New to CampusFR?</strong> On the sign-in page, '
                    . 'choose “Create an account” and verify your email if asked.</li>'
                    . '<li>Once you are signed in, <strong>enter your gift code below</strong> '
                    . 'to add the calendar to “My library”.</li>'
                    . '</ol>',
                'code' => 'Your personal gift code',
                'fallback' => 'If the button does not work, use this link:',
                'security' => 'Keep this code private: it is used to claim your gift.',
                'signature' => '<p>Wonderful discoveries await! ✨<br><strong>The CampusFR team</strong></p>',
            ],
            'ru' => [
                'subject' => 'Для тебя приготовили подарок от CampusFR 🎁',
                'preheader' => '24 истории к Рождеству ждут тебя. Узнай, как получить подарок.',
                'heading' => 'Тебя ждёт волшебный сюрприз! 🎁',
                'intro' => '<p>Привет, {{gift_recipient_name}}!</p>'
                    . '<p>Кто-то решил тебя порадовать и подарил <strong>Адвент-календарь CampusFR 2026</strong>! '
                    . 'Внутри 24 истории — по одной с 1 по 24 декабря.</p>'
                    . '<p style="margin:16px 0;padding:12px 16px;border-left:3px solid #E0397C;'
                    . 'background-color:#fff5f9;color:#352650;">{{gift_personal_message}}</p>',
                'cta' => 'Открыть мой подарок',
                'steps' => '<p><strong>Как получить подарок?</strong></p>'
                    . '<ol style="padding-left:22px;line-height:1.75;">'
                    . '<li><strong>Открой страницу подарка</strong> по розовой кнопке.</li>'
                    . '<li><strong>Уже есть аккаунт CampusFR?</strong> Войди в него. '
                    . '<strong>Впервые на CampusFR?</strong> На странице входа выбери «Создать аккаунт» '
                    . 'и подтверди электронную почту, если потребуется.</li>'
                    . '<li>После входа <strong>введи код подарка ниже</strong> — '
                    . 'календарь появится в разделе «Моя библиотека».</li>'
                    . '</ol>',
                'code' => 'Твой личный код подарка',
                'fallback' => 'Если кнопка не открывается, используй эту ссылку:',
                'security' => 'Не передавай этот код другим людям: он нужен для получения подарка.',
                'signature' => '<p>Пусть тебя ждут чудесные открытия! ✨<br><strong>Команда CampusFR</strong></p>',
            ],
        ];
        foreach ($copy as $language => $data) {
            $button = $cta->button('{{gift_claim_url}}', $data['cta'], CommerceMailBuilder::CTA_CAMPUS_PINK);
            $outro = $button . $data['steps']
                . '<p style="text-align:center;font-size:12px;color:#697184;">'
                . $data['fallback'] . ' <a href="{{gift_claim_url}}">{{gift_claim_url}}</a></p>'
                . '<div style="margin:20px 0;padding:16px;border:1px solid #e3d8eb;'
                . 'background-color:#f8f4fb;border-radius:12px;text-align:center;">'
                . '<p style="margin:0 0 8px;color:#31234d;font-size:13px;font-weight:700;">'
                . $data['code'] . '</p>'
                . '<p style="margin:0;font-size:18px;font-weight:800;letter-spacing:.03em;'
                . 'color:#2b2046;overflow-wrap:anywhere;">{{gift_code}}</p></div>'
                . '<p style="font-size:12px;color:#6b6477;">' . $data['security'] . '</p>';
            $doc = $translations[$language]['document'];
            $doc['heading'] = $data['heading'];
            $doc['introhtml'] = $data['intro'];
            $doc['outrohtml'] = $outro;
            $doc['signaturehtml'] = $data['signature'];
            $doc['bodyhtml'] = $data['intro'] . $outro . $data['signature'];
            $translations[$language]['subject'] = $data['subject'];
            $translations[$language]['preheader'] = $data['preheader'];
            $translations[$language]['bodyhtml'] = $doc['bodyhtml'];
            $translations[$language]['document'] = $doc;
        }
        return $translations;
    }
}
