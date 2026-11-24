<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail\library;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\mail\builder\CommerceMailBuilderCtaRenderer;
use local_subscriptions\mail\MailRenderer;

/** Non-persistent preview of the CURRENT form values (including unsaved edits). */
final class CommerceMailStudioPreviewService {
    /** @param array<string,mixed> $translation @return array{subject:string,html:string,text:string} */
    public static function render(array $translation, string $language, string $category): array {
        if (!in_array($language, CommerceMailLibrary::LANGUAGES, true)) {
            throw new \invalid_parameter_exception('Invalid preview language.');
        }
        $subject = trim((string)($translation['subject'] ?? ''));
        $preheader = trim((string)($translation['preheader'] ?? ''));
        $body = (string)($translation['bodyhtml'] ?? '');
        if ($category === CommerceMailLibrary::CATEGORY_TRANSACTIONAL) {
            $document = (array)($translation['document'] ?? []);
            $heading = trim((string)($document['heading'] ?? ''));
            $body = ($heading !== '' ? '<h2>' . s($heading) . '</h2>' : '')
                . (string)($document['introhtml'] ?? '')
                . (string)($document['outrohtml'] ?? '')
                . (string)($document['signaturehtml'] ?? '');
        }
        if ($subject === '' || trim(strip_tags($body)) === '') {
            throw new \invalid_parameter_exception('Preview requires a subject and body in the selected language.');
        }
        $tokens = [
            '{{firstname}}' => 'Camille', '{{fullname}}' => 'Camille Martin',
            '{{username}}' => 'camille', '{{email}}' => 'camille@example.test',
            '{{greeting}}' => match ($language) {
                'ru' => 'Здравствуйте, Камиль!',
                'en' => 'Hello Camille!',
                default => 'Bonjour Camille !',
            },
            // Gift preview uses public demonstration values, never real tokens.
            '{{gift_recipient_name}}' => 'Camille',
            '{{gift_personal_message}}' => match ($language) {
                'ru' => 'Счастливого Рождества!', 'en' => 'Merry Christmas!',
                default => 'Joyeux Noël !',
            },
            '{{gift_claim_url}}' => 'https://example.test/local/subscriptions/gift_claim.php',
            '{{gift_code}}' => 'EXEMPLE-NE-PAS-UTILISER',
        ];
        // Other transactional variables stay visible: no invented live order or URL.
        $subject = strtr($subject, $tokens);
        $preheader = strtr($preheader, $tokens);
        $body = strtr($body, $tokens);
        $cta = new CommerceMailBuilderCtaRenderer();
        $body = $cta->render_tags($body, 'https://example.test/campusfr-preview');
        $body = format_text($body, FORMAT_HTML, [
            'context' => \context_system::instance(),
            'filter' => false, 'noclean' => false, 'para' => false,
        ]);
        [$html, $text] = MailRenderer::layout($subject, $body, null, null, [
            'preheader' => $preheader,
            'headcss' => $cta->hover_css(),
        ]);
        return ['subject' => $subject, 'html' => $html, 'text' => $text];
    }
}
