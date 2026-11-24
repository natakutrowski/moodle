<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\gift;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\mail\CommerceMailCustomerContentPolicy;
use local_subscriptions\commerce\mail\CommerceMailMessage;
use local_subscriptions\commerce\mail\CommerceMailRecipient;
use local_subscriptions\commerce\mail\MoodleCommerceMailTransport;
use local_subscriptions\commerce\mail\library\CommerceMailLibrary;
use local_subscriptions\commerce\mail\library\CommerceMailLibraryRepository;
use local_subscriptions\mail\MailRenderer;

/**
 * Dedicated Mail Studio template for Advent gift invitations.
 * Never persists a real invitation secret: it exists only during rendering
 * and synchronous dispatch. Deliberately does not use the JSON mail queue.
 */
final class CommerceGiftMailStudioInvitation {
    public const RUNTIME_KEY = 'gift:advent:invitation:v1';
    public const TEMPLATE_NAME = 'Invitation cadeau — Calendrier de l’Avent';

    public function __construct(private readonly \moodle_database $db) {}

    private function repository(): CommerceMailLibraryRepository {
        return new CommerceMailLibraryRepository($this->db);
    }

    public static function normalize_locale(string $locale): string {
        $locale = strtolower(trim(str_replace('-', '_', $locale)));
        $locale = explode('_', $locale)[0] ?: 'fr';
        return in_array($locale, CommerceMailLibrary::LANGUAGES, true) ? $locale : 'fr';
    }

    /** @return array<string,array<string,mixed>> */
    public static function defaults(): array {
        $copy = [
            'fr' => [
                'subject' => 'Un cadeau CampusFR t’attend 🎁',
                'preheader' => 'Découvre ton Calendrier de l’Avent et active ton cadeau.',
                'heading' => 'Un cadeau rien que pour toi ! 🎁',
                'intro' => '<p>Bonjour {{gift_recipient_name}},</p><p>Quelqu’un a pensé à toi et t’offre le <strong>Calendrier de l’Avent 2026</strong> de CampusFR.</p><p>{{gift_personal_message}}</p>',
                'outro' => '<p>Pour recevoir ton cadeau, connecte-toi à CampusFR ou crée gratuitement ton compte, puis ouvre cette page :</p><p><a href="{{gift_claim_url}}">Découvrir et activer mon cadeau</a></p><p>Ton code cadeau personnel : <strong>{{gift_code}}</strong></p><p>Ne partage pas ce code avec d’autres personnes.</p>',
                'signature' => '<p>À bientôt,<br><strong>L’équipe CampusFR</strong></p>',
            ],
            'en' => [
                'subject' => 'A CampusFR gift is waiting for you 🎁',
                'preheader' => 'Discover your Advent Calendar and claim your gift.',
                'heading' => 'A special gift for you! 🎁',
                'intro' => '<p>Hello {{gift_recipient_name}},</p><p>Someone has a surprise for you: the <strong>CampusFR Advent Calendar 2026</strong>.</p><p>{{gift_personal_message}}</p>',
                'outro' => '<p>To claim your gift, sign in to CampusFR or create your free account, then open this page:</p><p><a href="{{gift_claim_url}}">Discover and claim my gift</a></p><p>Your personal gift code: <strong>{{gift_code}}</strong></p><p>Do not share this code with anyone else.</p>',
                'signature' => '<p>See you soon,<br><strong>The CampusFR team</strong></p>',
            ],
            'ru' => [
                'subject' => 'Тебя ждёт подарок от CampusFR 🎁',
                'preheader' => 'Открой адвент-календарь и активируй свой подарок.',
                'heading' => 'Для тебя приготовили подарок! 🎁',
                'intro' => '<p>Привет, {{gift_recipient_name}}!</p><p>Тебе подарили <strong>Адвент-календарь CampusFR 2026</strong> с волшебными историями.</p><p>{{gift_personal_message}}</p>',
                'outro' => '<p>Чтобы получить подарок, войди в CampusFR или создай бесплатный аккаунт, а затем открой эту страницу:</p><p><a href="{{gift_claim_url}}">Открыть и активировать подарок</a></p><p>Твой личный код подарка: <strong>{{gift_code}}</strong></p><p>Никому не передавай этот код.</p>',
                'signature' => '<p>До встречи!<br><strong>Команда CampusFR</strong></p>',
            ],
        ];
        $result = [];
        foreach ($copy as $locale => $t) {
            $body = $t['intro'] . $t['outro'] . $t['signature'];
            $result[$locale] = [
                'subject' => $t['subject'],
                'preheader' => $t['preheader'],
                'bodyhtml' => $body,
                'document' => [
                    'mode' => 'transactional_editorial',
                    'builderversion' => CommerceMailLibrary::BUILDER_VERSION,
                    'heading' => $t['heading'],
                    'introhtml' => $t['intro'],
                    'outrohtml' => $t['outro'],
                    'signaturehtml' => $t['signature'],
                    'headerimage' => 0,
                    'bodyhtml' => $body,
                    'blocks' => [],
                ],
            ];
        }
        return $result;
    }

    /** Never overwrites a human-edited template; an explicit CLI action only. */
    public function seed(int $adminid): \stdClass {
        if (!CommerceGiftDevE2EGate::is_dev_environment() || $adminid <= 0 || !is_siteadmin($adminid)) {
            throw new \DomainException('Gift Mail Studio seeding is DEV-administrator only.');
        }
        $repo = $this->repository();
        $existing = $repo->get_by_runtime_key(self::RUNTIME_KEY);
        if ($existing) { return $existing; }
        return $repo->save([
            'name' => self::TEMPLATE_NAME,
            'category' => CommerceMailLibrary::CATEGORY_TRANSACTIONAL,
            'status' => CommerceMailLibrary::STATUS_DRAFT,
            'runtimekey' => self::RUNTIME_KEY,
            'metadata' => [
                'foundation' => 'CampusFR Gifts G4B.4', 'editor' => 'mail_builder',
                'runtime' => 'gift_invitation_ephemeral', 'sku' => 'AVENT_2026',
                'no_persistent_secret' => true,
            ],
        ], self::defaults(), $adminid);
    }

    /** @return array{templateid:int,language:string,subject:string,preheader:string,body:string} */
    private function read_template(string $locale, bool $for_dispatch): array {
        $locale = self::normalize_locale($locale);
        $repo = $this->repository();
        $template = $repo->get_by_runtime_key(self::RUNTIME_KEY);
        if (!$template || (string)$template->category !== CommerceMailLibrary::CATEGORY_TRANSACTIONAL ||
                ($for_dispatch && (string)$template->status !== CommerceMailLibrary::STATUS_ACTIVE) ||
                (string)$template->status === CommerceMailLibrary::STATUS_ARCHIVED) {
            throw new \DomainException('Advent gift invitation Mail Studio template is missing or inactive.');
        }
        $content = $repo->contents((int)$template->id)[$locale] ?? null;
        if (!$content) {
            throw new \DomainException('Gift Mail Studio translation is missing.');
        }
        $doc = json_decode((string)$content->contentjson, true);
        if (!is_array($doc) || ($doc['mode'] ?? '') !== 'transactional_editorial') {
            throw new \DomainException('Gift Mail Studio template has an invalid format.');
        }
        $body = '<h2>' . s((string)($doc['heading'] ?? '')) . '</h2>'
            . (string)($doc['introhtml'] ?? '')
            . (string)($doc['outrohtml'] ?? '')
            . (string)($doc['signaturehtml'] ?? '');
        // An editor cannot silently remove the activation code or URL.
        if (substr_count($body, '{{gift_code}}') !== 1 ||
                // Never permit a secret token inside an HTML attribute/URL.
                preg_match('~<[^>]*\{\{gift_code\}\}[^>]*>~is', $body) === 1 ||
                substr_count($body, '{{gift_claim_url}}') < 1 ||
                strpos($body, '{{gift_personal_message}}') === false ||
                trim((string)$content->subject) === '') {
            throw new \DomainException('Gift Mail Studio template is missing required invitation placeholders.');
        }
        return ['templateid' => (int)$template->id, 'language' => $locale,
            'subject' => (string)$content->subject,
            'preheader' => (string)$content->preheader, 'body' => $body];
    }

    public function assert_ready(string $locale): void {
        $this->read_template($locale, true);
    }

    public function render(CommerceMailRecipient $recipient, string $locale, string $note,
        string $code, string $claimurl, bool $sample = false): CommerceMailMessage {
        $template = $this->read_template($locale, !$sample);
        if (!$sample && !CommerceGiftDeliveryDispatchService::enabled()) {
            throw new \DomainException('Gift Mail Studio dispatch is disabled.');
        }
        if ($code === '' || strlen($code) > 300 || !filter_var($claimurl, FILTER_VALIDATE_URL) ||
                !str_starts_with($claimurl, 'https://')) {
            throw new \DomainException('Invalid gift invitation render context.');
        }
        $name = trim($recipient->get_name());
        $name = $name !== '' ? $name : match ($template['language']) {
            'ru' => 'друг', 'en' => 'friend', default => 'ami(e)',
        };
        $replace = [
            '{{gift_recipient_name}}' => s($name),
            '{{gift_personal_message}}' => $note !== '' ? nl2br(s($note)) : '',
            '{{gift_claim_url}}' => s($claimurl),
            '{{gift_code}}' => s($code),
        ];
        $subject = trim(strtr($template['subject'], [
            '{{gift_recipient_name}}' => $name,
        ]));
        $preheader = strtr($template['preheader'], ['{{gift_recipient_name}}' => $name]);
        $body = strtr($template['body'], $replace);
        if (preg_match('/\{\{gift_[a-z_]+\}\}/', $body . $subject . $preheader)) {
            throw new \DomainException('Unrecognised gift Mail Studio variable.');
        }
        $body = format_text($body, FORMAT_HTML, [
            'context' => \context_system::instance(),
            'filter' => false, 'noclean' => false, 'para' => false,
        ]);
        [$html, $text] = MailRenderer::layout($subject, $body, null, null, ['preheader' => $preheader]);
        $message = new CommerceMailMessage($recipient, $subject, $html, $text,
            ['gift_mailstudio_template_id' => $template['templateid']]);
        (new CommerceMailCustomerContentPolicy())->assert_safe($message);
        return $message;
    }

    /**
     * Mail Studio transport accepts a confirmed Moodle account or a DEV-only,
     * buyer-approved unregistered mailbox. No fabricated Moodle user is made.
     * Raw gift codes are never put into the general persistent email queue.
     */
    public function send_to_recipient(CommerceMailRecipient $recipient, string $locale,
        string $note, string $secret): string {
        $claimurl = (new \moodle_url('/local/subscriptions/gift_claim.php'))->out(false);
        $message = $this->render($recipient, $locale, $note, $secret, $claimurl);
        (new MoodleCommerceMailTransport())->send($message);
        return $message->get_subject();
    }

    /** Backwards-compatible entry point for the original G4B.2 pilot. */
    public function send(\stdClass $moodleuser, string $locale, string $note, string $secret): void {
        $recipient = new CommerceMailRecipient((string)$moodleuser->email,
            trim((string)$moodleuser->firstname . ' ' . (string)$moodleuser->lastname),
            (int)$moodleuser->id);
        $this->send_to_recipient($recipient, $locale, $note, $secret);
    }

    /** Safe sample. Never reads an invitation token, email address or user record. */
    public function sample(string $locale): CommerceMailMessage {
        $recipient = new CommerceMailRecipient('camille@example.test', 'Camille Exemple');
        return $this->render($recipient, $locale, 'Joyeuses fêtes ! ✨',
            'EXEMPLE-NE-PAS-UTILISER', 'https://example.test/local/subscriptions/gift_claim.php', true);
    }
}
