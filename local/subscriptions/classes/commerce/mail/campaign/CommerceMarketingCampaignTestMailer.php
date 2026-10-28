<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail\campaign;

defined('MOODLE_INTERNAL') || die();

use context_system;
use local_subscriptions\commerce\mail\builder\CommerceMailBuilderCtaRenderer;
use local_subscriptions\commerce\mail\campaign\CommerceMarketingCampaignContentPolicy;
use local_subscriptions\commerce\mail\CommerceMailMessage;
use local_subscriptions\commerce\mail\CommerceMailCustomerContentPolicy;
use local_subscriptions\commerce\mail\CommerceMailRecipient;
use local_subscriptions\commerce\mail\MoodleCommerceMailTransport;
use local_subscriptions\mail\MailRenderer;

/** Explicit test delivery. No queue row and no campaign-recipient mutation. */
final class CommerceMarketingCampaignTestMailer {
    public function __construct(
        private readonly \moodle_database $db,
        private readonly CommerceMarketingCampaignRepository $repository
    ) {}

    public function render(int $campaignid, \stdClass $operator, string $language): CommerceMailMessage {
        if (!filter_var((string)$operator->email, FILTER_VALIDATE_EMAIL)) {
            throw new \invalid_parameter_exception('Operator email is not valid.');
        }
        if (!in_array($language, ['fr', 'ru', 'en'], true)) {
            throw new \invalid_parameter_exception('Invalid test language.');
        }
        $contents = $this->repository->contents($campaignid);
        $content = CommerceMarketingCampaignContentPolicy::select($contents, $language);
        if ($content === null) {
            throw new \invalid_parameter_exception(get_string(
                'commerce_marketing_campaign_languages_missing', 'local_subscriptions', $language . ' (1)'
            ));
        }
        $language = (string)$content->language;
        $campaign = $this->repository->get($campaignid);
        $firstname = trim((string)$operator->firstname);
        $fullname = trim($firstname . ' ' . (string)$operator->lastname);
        $tokens = [
            '{{firstname}}' => $firstname,
            '{{fullname}}' => $fullname,
            '{{username}}' => (string)$operator->username,
            '{{email}}' => (string)$operator->email,
            '{{greeting}}' => match ($language) {
                'ru' => 'Здравствуйте, ' . $firstname . '!',
                'en' => 'Hello ' . $firstname . '!',
                default => 'Bonjour ' . $firstname . ' !',
            },
        ];
        $subject = '[TEST CampusFR] ' . trim(strtr((string)$content->subject, $tokens));
        $bodyhtml = strtr((string)$content->bodyhtml, $tokens);
        $cta = new CommerceMailBuilderCtaRenderer();
        $bodyhtml = $cta->render_tags($bodyhtml, trim((string)$campaign->ctaurl));
        $bodyhtml = format_text($bodyhtml, FORMAT_HTML, [
            'context' => context_system::instance(),
            'filter' => false,
            'noclean' => false,
            'para' => false,
        ]);
        $notice = match ($language) {
            'ru' => 'Тестовое письмо: настоящие ссылки отписки добавляются только при отправке кампании.',
            'en' => 'Test email: real unsubscribe links are added only to campaign deliveries.',
            default => 'Email de test : les vrais liens de désabonnement sont ajoutés uniquement aux envois de campagne.',
        };
        $bodyhtml .= '<p style="font-size:12px;color:#777">' . s($notice) . '</p>';
        [$html, $text] = MailRenderer::layout($subject, $bodyhtml, null, null, [
            'preheader' => strtr((string)$content->preheader, $tokens),
            'headcss' => $cta->hover_css(),
        ]);
        $text .= "\n\n" . $notice;
        return new CommerceMailMessage(
            new CommerceMailRecipient((string)$operator->email, $fullname, (int)$operator->id),
            $subject, $html, $text,
            ['campaignid' => $campaignid, 'test' => true]
        );
    }

    public function send(int $campaignid, \stdClass $operator, string $language): void {
        $message = $this->render($campaignid, $operator, $language);
        (new CommerceMailCustomerContentPolicy())->assert_safe($message);
        (new MoodleCommerceMailTransport())->send($message);
    }
}
